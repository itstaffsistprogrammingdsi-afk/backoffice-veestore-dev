<?php

namespace App\Domain\Payments\Support;

final class EdcMetadata
{
    /**
     * @return array{
     *     terminal_id: string|null,
     *     approval_code: string|null,
     *     batch_number: string|null,
     *     card_number: string|null,
     *     card_type: string|null,
     *     reference_number: string|null,
     *     acquirer: string|null,
     *     issuer: string|null,
     *     merchant_id: string|null
     * }
     */
    public static function parse(?string $payload): array
    {
        $empty = [
            'terminal_id' => null,
            'approval_code' => null,
            'batch_number' => null,
            'card_number' => null,
            'card_type' => null,
            'reference_number' => null,
            'acquirer' => null,
            'issuer' => null,
            'merchant_id' => null,
        ];

        if ($payload === null || trim($payload) === '') {
            return $empty;
        }

        $data = json_decode($payload, true);

        if (! is_array($data)) {
            return $empty;
        }

        $terminal = self::first($data, [
            'terminal_id',
            'tid',
            'edc.terminal_id',
            'order.terminal_id',
            'additional_info.terminal_id',
        ]);

        $approval = self::first($data, [
            'approval_code',
            'approvalCode',
            'edc.approval_code',
            'emoney_payment.approval_code',
            'transaction.approval_code',
        ]);

        $batch = self::first($data, [
            'batch_number',
            'batch_no',
            'batch',
            'edc.batch_number',
            'transaction.batch_number',
        ]);

        $card = self::first($data, [
            'card_number',
            'masked_pan',
            'masked_card',
            'pan',
            'edc.card_number',
            'card.pan',
        ]);

        $cardType = self::first($data, [
            'card_type',
            'edc.card_type',
            'card.type',
            'issuer.name',
        ]);

        $reference = self::first($data, [
            'reference_number',
            'reference_no',
            'reference',
            'edc.reference_number',
            'virtual_account_payment.reference_number',
            'transaction.reference_number',
        ]);

        $acquirer = self::first($data, [
            'acquirer.name',
            'acquirer.id',
            'edc.acquirer',
        ]);

        $issuer = self::first($data, [
            'issuer.name',
            'issuer.id',
        ]);

        $merchant = self::first($data, [
            'merchant_id',
            'mid',
            'edc.merchant_id',
            'merchant.id',
        ]);

        return [
            'terminal_id' => self::clean($terminal),
            'approval_code' => self::clean($approval),
            'batch_number' => self::clean($batch),
            'card_number' => self::maskCard(self::clean($card)),
            'card_type' => self::clean($cardType),
            'reference_number' => self::clean($reference),
            'acquirer' => self::clean($acquirer),
            'issuer' => self::clean($issuer),
            'merchant_id' => self::clean($merchant),
        ];
    }

    public static function mdrRate(string $methodName): float
    {
        $debit = str_contains(strtolower($methodName), 'debit');

        $rate = $debit
            ? (float) config('backoffice.edc_bca.debit_mdr_percent', 1.0)
            : (float) config('backoffice.edc_bca.credit_mdr_percent', 2.0);

        return max(0, min(100, $rate));
    }

    public static function estimateMdr(float $amount, string $methodName): float
    {
        return round($amount * self::mdrRate($methodName) / 100, 2);
    }

    public static function estimateSettlement(float $amount, string $methodName): float
    {
        return round($amount - self::estimateMdr($amount, $methodName), 2);
    }

    private static function first(array $data, array $paths): mixed
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private static function clean(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function maskCard(?string $card): ?string
    {
        if ($card === null) {
            return null;
        }

        if (str_contains($card, '*')) {
            return $card;
        }

        $digits = preg_replace('/\D+/', '', $card) ?? '';

        if ($digits === '') {
            return $card;
        }

        $lastFour = substr($digits, -4);

        return str_repeat('*', max(0, strlen($digits) - 4)).$lastFour;
    }
}
