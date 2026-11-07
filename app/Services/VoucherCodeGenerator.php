<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Support\Str;
use RuntimeException;

class VoucherCodeGenerator
{
    public function generateMasterCode(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'VCR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));

            if (! Voucher::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode voucher otomatis. Silakan coba lagi.');
    }

    public function generateUniqueCode(string $voucherCode): string
    {
        $prefix = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $voucherCode) ?? '');
        $prefix = Str::substr($prefix !== '' ? $prefix : 'VOUCHER', 0, 12);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = $prefix.'-'.Str::upper(Str::random(8));

            if (! VoucherCode::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode voucher unik. Silakan coba lagi.');
    }

    public function generateCodes(Voucher $voucher, int $quantity): array
    {
        $codes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $codes[] = VoucherCode::create([
                'voucher_id' => $voucher->getKey(),
                'code' => $this->generateUniqueCode((string) $voucher->code),
                'is_active' => true,
                'expires_at' => $voucher->valid_until,
            ]);
        }

        return $codes;
    }
}
