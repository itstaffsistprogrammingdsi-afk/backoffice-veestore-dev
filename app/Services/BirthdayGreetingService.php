<?php

namespace App\Services;

use App\Mail\BirthdayGreetingMail;
use App\Models\Customer;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BirthdayGreetingService
{
    private const MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    public function template(): array
    {
        return [
            'subject' => 'Selamat Ulang Tahun, {nama}!',
            'message' => "Halo {nama},\n\nSelamat ulang tahun dari VeeStore! Terima kasih sudah menjadi bagian dari perjalanan kami.\n\nSemoga tahun ini membawa banyak hal baik untuk Anda dan keluarga.\n\nSalam hangat,\nTim VeeStore",
            'placeholders' => ['{nama}', '{email}', '{bulan}'],
        ];
    }

    /**
     * Vouchers that can be attached to a birthday greeting.
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableVouchers(): array
    {
        $claimed = $this->claimedCodeCounts();

        return Voucher::query()
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now()->startOfDay());
            })
            ->where(function (Builder $query) {
                $query->where('distribution_type', 'unique_code')
                    ->orWhereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->withCount(['voucherCodes as open_codes_count' => function (Builder $query) {
                $query->where('is_active', true)
                    ->where(function (Builder $query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay());
                    })
                    ->whereDoesntHave('usages');
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Voucher $voucher) use ($claimed) {
                $availableCodes = null;

                if ($voucher->distribution_type === 'unique_code') {
                    $availableCodes = max(
                        0,
                        (int) $voucher->open_codes_count - (int) ($claimed[$voucher->id] ?? 0)
                    );
                }

                return [
                    'id' => $voucher->id,
                    'name' => $voucher->name,
                    'code' => $voucher->code,
                    'type' => $voucher->type,
                    'value' => (float) $voucher->value,
                    'distribution_type' => $voucher->distribution_type,
                    'valid_until' => $voucher->valid_until?->toDateString(),
                    'available_codes_count' => $availableCodes,
                ];
            })
            ->values()
            ->all();
    }

    public function send(array $customerIds, string $subject, string $message, ?string $voucherId = null): array
    {
        $uniqueIds = array_values(array_unique($customerIds));

        $customers = Customer::query()
            ->whereIn('id', $uniqueIds)
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->get();

        $voucher = $voucherId ? $this->resolveVoucher($voucherId) : null;

        $result = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => count($uniqueIds) - $customers->count(),
            'voucher_attached' => 0,
            'voucher_skipped' => 0,
        ];

        if ($voucher && $voucher->distribution_type === 'unique_code') {
            $availableCodes = $this->availableCodeQuery($voucher)->count();

            if ($availableCodes < $customers->count()) {
                throw ValidationException::withMessages([
                    'voucher_id' => "Kode unik voucher tidak mencukupi. Tersedia {$availableCodes} kode untuk {$customers->count()} penerima. Tambah batas pemakaian voucher terlebih dahulu.",
                ]);
            }
        }

        foreach ($customers as $customer) {
            $voucherCode = null;

            if ($voucher && $voucher->distribution_type === 'unique_code') {
                $voucherCode = $this->claimVoucherCode($voucher, $customer);
            }

            $voucherPayload = $voucher ? $this->voucherPayload($voucher, $voucherCode) : null;

            try {
                Mail::to($customer->email)->send(new BirthdayGreetingMail(
                    mailSubject: $this->personalize($subject, $customer),
                    mailBody: $this->personalize($message, $customer),
                    voucher: $voucherPayload,
                    voucherImagePath: $voucher?->voucher_image_path,
                ));

                $result['sent']++;

                if ($voucherPayload) {
                    $result['voucher_attached']++;

                    if ($voucherCode) {
                        $this->markVoucherCodeSent($voucherCode);
                    }
                } elseif ($voucher) {
                    $result['voucher_skipped']++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $result['failed']++;

                if ($voucherCode) {
                    $this->releaseVoucherCode($voucherCode);
                }
            }
        }

        return $result;
    }

    public function personalize(string $text, Customer $customer): string
    {
        return str_replace(
            ['{nama}', '{email}', '{bulan}'],
            [
                $customer->name ?: 'Pelanggan',
                $customer->email ?: '',
                $this->monthLabel($customer->date_of_birth?->month ?? (int) now()->month),
            ],
            $text
        );
    }

    public function summaryMessage(array $result): string
    {
        $parts = [];

        if ($result['sent'] > 0) {
            $parts[] = "{$result['sent']} email terkirim";
        }

        if ($result['failed'] > 0) {
            $parts[] = "{$result['failed']} email gagal dikirim";
        }

        if ($result['skipped'] > 0) {
            $parts[] = "{$result['skipped']} pelanggan tanpa email dilewati";
        }

        if (($result['voucher_attached'] ?? 0) > 0) {
            $parts[] = "{$result['voucher_attached']} voucher terlampir";
        }

        if (($result['voucher_skipped'] ?? 0) > 0) {
            $parts[] = "{$result['voucher_skipped']} penerima tanpa voucher";
        }

        return $parts === []
            ? 'Tidak ada email yang dikirim.'
            : 'Sapaan ulang tahun: '.implode(', ', $parts).'.';
    }

    private function resolveVoucher(string $voucherId): Voucher
    {
        $voucher = Voucher::query()->find($voucherId);

        if (! $voucher) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher yang dipilih tidak ditemukan.',
            ]);
        }

        if (! $voucher->is_active) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher yang dipilih sedang tidak aktif.',
            ]);
        }

        if ($voucher->valid_from && $voucher->valid_from->isFuture()) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher belum memasuki masa berlaku.',
            ]);
        }

        if ($voucher->valid_until && $voucher->valid_until->copy()->endOfDay()->isPast()) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Voucher sudah berakhir masa berlakunya.',
            ]);
        }

        if ($voucher->distribution_type === 'public'
            && $voucher->usage_limit !== null
            && $voucher->usage_count >= $voucher->usage_limit) {
            throw ValidationException::withMessages([
                'voucher_id' => 'Batas pemakaian voucher sudah tercapai.',
            ]);
        }

        return $voucher;
    }

    private function availableCodeQuery(Voucher $voucher): Builder
    {
        $claimedIds = $this->claimedVoucherCodeIds($voucher->id);

        return VoucherCode::query()
            ->where('voucher_id', $voucher->id)
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay());
            })
            ->whereDoesntHave('usages')
            ->when($claimedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $claimedIds));
    }

    private function claimVoucherCode(Voucher $voucher, Customer $customer): ?VoucherCode
    {
        $candidates = $this->availableCodeQuery($voucher)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(25)
            ->get();

        foreach ($candidates as $candidate) {
            $claimed = DB::connection('immostore_backoffice')
                ->table('birthday_voucher_deliveries')
                ->insertOrIgnore([
                    'id' => (string) Str::uuid(),
                    'voucher_id' => $voucher->getKey(),
                    'voucher_code_id' => $candidate->getKey(),
                    'customer_id' => $customer->getKey(),
                    'email' => $customer->email,
                    'sent_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed > 0) {
                return $candidate;
            }
        }

        return null;
    }

    private function markVoucherCodeSent(VoucherCode $voucherCode): void
    {
        DB::connection('immostore_backoffice')
            ->table('birthday_voucher_deliveries')
            ->where('voucher_code_id', $voucherCode->getKey())
            ->update([
                'sent_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function releaseVoucherCode(VoucherCode $voucherCode): void
    {
        DB::connection('immostore_backoffice')
            ->table('birthday_voucher_deliveries')
            ->where('voucher_code_id', $voucherCode->getKey())
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    private function claimedVoucherCodeIds(string $voucherId): array
    {
        return DB::connection('immostore_backoffice')
            ->table('birthday_voucher_deliveries')
            ->where('voucher_id', $voucherId)
            ->whereNotNull('voucher_code_id')
            ->pluck('voucher_code_id')
            ->all();
    }

    /**
     * Count claimed codes that are still valid and unused, because those are
     * the only deliveries that reduce the available code pool.
     *
     * @return array<string, int>
     */
    private function claimedCodeCounts(): array
    {
        $rows = DB::connection('immostore_backoffice')
            ->table('birthday_voucher_deliveries')
            ->whereNotNull('voucher_code_id')
            ->get(['voucher_id', 'voucher_code_id']);

        if ($rows->isEmpty()) {
            return [];
        }

        $openCodeIds = VoucherCode::query()
            ->whereIn('id', $rows->pluck('voucher_code_id')->unique()->all())
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->startOfDay());
            })
            ->whereDoesntHave('usages')
            ->pluck('id')
            ->flip();

        $counts = [];

        foreach ($rows as $row) {
            if (! $openCodeIds->has($row->voucher_code_id)) {
                continue;
            }

            $counts[$row->voucher_id] = ($counts[$row->voucher_id] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function voucherPayload(Voucher $voucher, ?VoucherCode $voucherCode): array
    {
        $value = (float) $voucher->value;
        $maxDiscount = $voucher->max_discount !== null ? (float) $voucher->max_discount : null;
        $minPurchase = $voucher->min_purchase !== null ? (float) $voucher->min_purchase : null;

        $valueLabel = $voucher->type === 'percentage'
            ? 'Diskon '.$this->formatNumber($value).'%'
                .($maxDiscount !== null && $maxDiscount > 0 ? ' (maks. '.$this->formatCurrency($maxDiscount).')' : '')
            : 'Potongan '.$this->formatCurrency($value);

        return [
            'name' => $voucher->name,
            'description' => $voucher->description,
            'code' => $voucherCode?->code ?? $voucher->code,
            'type' => $voucher->type,
            'value' => $value,
            'value_label' => $valueLabel,
            'max_discount' => $maxDiscount,
            'min_purchase' => $minPurchase,
            'min_purchase_label' => $minPurchase !== null && $minPurchase > 0
                ? 'Min. belanja '.$this->formatCurrency($minPurchase)
                : null,
            'valid_from_label' => $voucher->valid_from?->translatedFormat('d F Y'),
            'valid_until' => $voucher->valid_until?->toDateString(),
            'valid_until_label' => $voucher->valid_until?->translatedFormat('d F Y'),
            'is_unique_code' => $voucher->distribution_type === 'unique_code',
        ];
    }

    private function formatCurrency(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function formatNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function monthLabel(int $month): string
    {
        return self::MONTHS[max(0, min(11, $month - 1))];
    }
}
