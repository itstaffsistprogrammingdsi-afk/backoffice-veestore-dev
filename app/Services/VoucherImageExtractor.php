<?php

namespace App\Services;

use App\Ai\Agents\VoucherImageReader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Files\Image;
use Throwable;

class VoucherImageExtractor
{
    private const CONTEXTS = [
        'ecommerce_checkout',
        'ecommerce_shipping',
        'store_checkout',
        'store_customer_return',
    ];

    private const MONTHS = [
        'januari' => 'January',
        'februari' => 'February',
        'maret' => 'March',
        'april' => 'April',
        'mei' => 'May',
        'juni' => 'June',
        'juli' => 'July',
        'agustus' => 'August',
        'september' => 'September',
        'oktober' => 'October',
        'november' => 'November',
        'desember' => 'December',
    ];

    /**
     * Read a voucher image and return normalized voucher form values.
     *
     * @return array<string, mixed>
     */
    public function extract(UploadedFile $image): array
    {
        if (! config('voucher.ocr.enabled', true)) {
            throw ValidationException::withMessages([
                'voucher_image' => 'Fitur baca otomatis voucher sedang dinonaktifkan.',
            ]);
        }

        try {
            $response = VoucherImageReader::make()->prompt(
                'Baca gambar voucher ini dan isi seluruh data voucher yang terlihat. '
                .'Gunakan nilai kosong untuk data yang tidak ada. '
                .'Konteks pemakaian yang diizinkan: '.implode(', ', self::CONTEXTS).'.',
                [Image::fromUpload($image)],
                provider: config('voucher.ocr.provider'),
                model: config('voucher.ocr.model') ?: null,
                timeout: (int) config('voucher.ocr.timeout', 90),
            );

            $raw = $response->structured ?? [];
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'voucher_image' => 'Gambar voucher gagal dibaca. Pastikan gambar jelas dan tidak terpotong, atau isi data secara manual.',
            ]);
        }

        $data = $this->normalize($raw);

        if (! $this->hasMeaningfulData($data)) {
            throw ValidationException::withMessages([
                'voucher_image' => 'Tidak ada data voucher yang terbaca dari gambar. Coba unggah gambar yang lebih jelas atau isi data secara manual.',
            ]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalize(array $raw): array
    {
        $valueText = trim((string) ($raw['value'] ?? ''));
        $type = $this->normalizeType($raw['type'] ?? null, $valueText);
        $description = $this->stringOrNull($raw['description'] ?? null);
        $name = $this->stringOrNull($raw['name'] ?? null);

        if ($name === null && $description !== null) {
            $name = Str::limit($description, 100, '');
        }

        $distributionType = $this->normalizeDistributionType($raw['distribution_type'] ?? null);

        return [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'value' => $this->toNumber($raw['value'] ?? null),
            'max_discount' => $this->toNumber($raw['max_discount'] ?? null),
            'min_purchase' => $this->toNumber($raw['min_purchase'] ?? null),
            'valid_from' => $this->toDate($raw['valid_from'] ?? null),
            'valid_until' => $this->toDate($raw['valid_until'] ?? null),
            'usage_limit' => $this->toInteger($raw['usage_limit'] ?? null),
            'distribution_type' => $distributionType,
            'contexts' => $this->normalizeContexts($raw['contexts'] ?? []),
            'code' => $this->stringOrNull($raw['code'] ?? null, uppercase: true),
            'confidence' => $this->toInteger($raw['confidence'] ?? null),
        ];
    }

    private function normalizeType(mixed $type, string $valueText): ?string
    {
        $type = Str::lower(trim((string) $type));

        if (in_array($type, ['fixed', 'percentage'], true)) {
            return $type;
        }

        if (str_contains($valueText, '%') || Str::contains(Str::lower($valueText), ['persen', 'percent'])) {
            return 'percentage';
        }

        return $valueText !== '' ? 'fixed' : null;
    }

    private function normalizeDistributionType(mixed $distributionType): ?string
    {
        $value = Str::lower(str_replace(['-', ' '], '_', trim((string) $distributionType)));

        if (in_array($value, ['public', 'unique_code'], true)) {
            return $value;
        }

        if (Str::contains($value, ['unik', 'unique'])) {
            return 'unique_code';
        }

        return $value !== '' ? 'public' : null;
    }

    /**
     * @return array<int, string>
     */
    private function normalizeContexts(mixed $contexts): array
    {
        if (is_string($contexts)) {
            $contexts = preg_split('/\s*,\s*/', $contexts, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (! is_array($contexts)) {
            return [];
        }

        return collect($contexts)
            ->filter(fn (mixed $context) => is_string($context))
            ->map(fn (string $context) => Str::lower(str_replace([' ', '-'], '_', trim($context))))
            ->intersect(self::CONTEXTS)
            ->unique()
            ->values()
            ->all();
    }

    private function stringOrNull(mixed $value, bool $uppercase = false): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $uppercase ? Str::upper($value) : $value;
    }

    private function toNumber(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[^\d,.\-]/u', '', $text) ?? '';

        if ($text === '' || $text === '-') {
            return null;
        }

        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($lastComma !== false) {
            $decimals = strlen($text) - $lastComma - 1;

            if (substr_count($text, ',') > 1 || $decimals === 3) {
                $text = str_replace(',', '', $text);
            } else {
                $text = str_replace(',', '.', $text);
            }
        } elseif ($lastDot !== false) {
            $decimals = strlen($text) - $lastDot - 1;

            if (substr_count($text, '.') > 1 || $decimals === 3) {
                $text = str_replace('.', '', $text);
            }
        }

        if (! is_numeric($text)) {
            return null;
        }

        return (float) $text;
    }

    private function toInteger(mixed $value): ?int
    {
        $number = $this->toNumber($value);

        return $number === null ? null : (int) round($number);
    }

    private function toDate(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = Str::lower($text);
        $text = str_replace(array_keys(self::MONTHS), array_values(self::MONTHS), $text);

        try {
            return Carbon::parse($text)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasMeaningfulData(array $data): bool
    {
        return filled($data['name'] ?? null)
            || filled($data['value'] ?? null)
            || filled($data['code'] ?? null)
            || filled($data['description'] ?? null);
    }
}
