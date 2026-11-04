<?php

namespace App\Domain\Ecommerce\Support;

final class DokuPaymentChannelResolver
{
    /** @var array<string, string> */
    private const CHANNEL_LABELS = [
        'QRIS_DOKU' => 'QRIS',
        'EMONEY_DANA' => 'DANA',
        'EMONEY_SHOPEEPAY' => 'ShopeePay',
        'EMONEY_GOPAY' => 'GoPay',
        'EMONEY_OVO' => 'OVO',
        'EMONEY_LINKAJA' => 'LinkAja',
        'EMONEY_SAKUKU' => 'Sakuku',
        'VIRTUAL_ACCOUNT_BCA' => 'BCA VA',
        'VIRTUAL_ACCOUNT_BNI' => 'BNI VA',
        'VIRTUAL_ACCOUNT_BRI' => 'BRI VA',
        'VIRTUAL_ACCOUNT_MANDIRI' => 'Mandiri VA',
        'VIRTUAL_ACCOUNT_PERMATA' => 'Permata VA',
        'VIRTUAL_ACCOUNT_CIMB' => 'CIMB VA',
        'CREDIT_CARD' => 'Kartu Kredit',
        'ONLINE_DEBIT' => 'Online Debit',
    ];

    public static function resolve(
        ?string $methodCode,
        ?string $methodName,
        ?string $detailMethodName,
        ?string $detailPayload
    ): ?string {
        $methodName = self::clean($methodName);
        $detailMethodName = self::clean($detailMethodName);

        if (! self::isDoku($methodCode, $methodName)) {
            return $detailMethodName ?? $methodName;
        }

        $channelLabel = self::labelFromPayload($detailPayload);

        if ($channelLabel !== null) {
            return 'DOKU - '.$channelLabel;
        }

        if ($detailMethodName !== null && preg_match('/^doku\b/i', $detailMethodName) === 1) {
            return $detailMethodName;
        }

        return $methodName;
    }

    private static function isDoku(?string $methodCode, ?string $methodName): bool
    {
        return str_contains(strtolower((string) $methodCode), 'doku')
            || str_contains(strtolower((string) $methodName), 'doku');
    }

    private static function labelFromPayload(?string $detailPayload): ?string
    {
        if ($detailPayload === null || trim($detailPayload) === '') {
            return null;
        }

        $detail = json_decode($detailPayload, true);

        if (! is_array($detail)) {
            return null;
        }

        $channel = is_array($detail['channel'] ?? null) ? $detail['channel'] : [];
        $acquirer = is_array($detail['acquirer'] ?? null) ? $detail['acquirer'] : [];
        $service = is_array($detail['service'] ?? null) ? $detail['service'] : [];

        $channelId = strtoupper(trim((string) ($channel['id'] ?? '')));
        $serviceId = strtoupper(trim((string) ($service['id'] ?? '')));
        $channelName = trim((string) ($channel['name'] ?? ''));
        $acquirerName = trim((string) ($acquirer['name'] ?? ($acquirer['id'] ?? '')));

        if ($channelId === '' && $serviceId === '' && $channelName === '' && $acquirerName === '') {
            return null;
        }

        if (isset(self::CHANNEL_LABELS[$channelId])) {
            return self::CHANNEL_LABELS[$channelId];
        }

        if ($serviceId === 'QRIS' || str_contains($channelId, 'QRIS') || stripos($channelName, 'qris') !== false) {
            return 'QRIS';
        }

        $channelSuffix = preg_replace(
            '/^(EMONEY|VIRTUAL_ACCOUNT|CREDIT_CARD|ONLINE_DEBIT|DIRECT_DEBIT|PEER_TO_PEER)_/',
            '',
            $channelId
        );

        if (is_string($channelSuffix) && $channelSuffix !== '' && $channelSuffix !== $channelId) {
            $label = self::humanize($channelSuffix);

            if ($label !== '') {
                return $label;
            }
        }

        if ($channelName !== '') {
            return self::humanize($channelName);
        }

        if ($acquirerName !== '') {
            return self::humanize($acquirerName);
        }

        if ($serviceId !== '') {
            return self::humanize($serviceId);
        }

        return null;
    }

    private static function humanize(string $value): string
    {
        $words = preg_split('/[\s_-]+/', trim($value)) ?: [];
        $words = array_filter($words, fn (string $word): bool => $word !== '');

        return implode(' ', array_map(function (string $word): string {
            $upper = strtoupper($word);

            if (in_array($upper, ['BCA', 'BNI', 'BRI', 'BTN', 'CIMB', 'DANA', 'OVO', 'QRIS', 'VA', 'CC'], true)) {
                return $upper;
            }

            return ucfirst(strtolower($word));
        }, $words));
    }

    private static function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
