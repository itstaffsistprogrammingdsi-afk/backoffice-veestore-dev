<?php

namespace App\Domain\Analytics\Support;

final class FinancialBreakdown
{
    /**
     * @return array<string, mixed>
     */
    public static function make(
        mixed $grossSales,
        mixed $productDiscount,
        mixed $voucherDiscount,
        mixed $netSales,
        mixed $basePrice,
        mixed $transactions = 0,
        mixed $qty = 0,
        mixed $netAfterProductDiscount = null
    ): array {
        $gross = self::amount($grossSales);
        $product = self::amount($productDiscount);
        $voucher = self::amount($voucherDiscount);
        $net = self::amount($netSales);
        $base = self::amount($basePrice);
        $transactionCount = max(0, (int) $transactions);
        $quantity = max(0, (int) $qty);

        $netAfterProductDiscount = $netAfterProductDiscount !== null
            ? self::amount($netAfterProductDiscount)
            : self::round($gross - $product);
        $expectedNet = self::round($netAfterProductDiscount - $voucher);
        $grossProfit = self::round($net - $base);
        $difference = self::round($net - $expectedNet);
        $clampedDiscount = max(0.0, $difference);
        $unexplainedDifference = min(0.0, $difference);

        return [
            'gross_sales' => $gross,
            'product_discount' => $product,
            'net_after_product_discount' => $netAfterProductDiscount,
            'voucher_discount' => $voucher,
            'net_sales' => $net,
            'base_price' => $base,
            'gross_profit' => $grossProfit,
            'gross_margin_percent' => self::percent($grossProfit, $net),
            'product_discount_percent' => self::percent($product, $gross),
            'voucher_discount_percent' => self::percent($voucher, $gross),
            'effective_discount_percent' => self::percent($product + $voucher, $gross),
            'transactions' => $transactionCount,
            'qty' => $quantity,
            'average_net_per_transaction' => $transactionCount > 0
                ? self::round($net / $transactionCount)
                : 0.0,
            'reconciliation' => [
                'expected_net' => $expectedNet,
                'actual_net' => $net,
                'difference' => $difference,
                'clamped_discount' => $clampedDiscount,
                'unexplained_difference' => $unexplainedDifference,
                'is_balanced' => abs($unexplainedDifference) < 0.01,
            ],
        ];
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $channels
     * @return array<int, array<string, mixed>>
     */
    public static function channels(iterable $channels): array
    {
        $rows = [];

        foreach ($channels as $channel) {
            $channelKey = (string) ($channel['channel'] ?? '');
            $label = match ($channelKey) {
                'store' => 'Store POS',
                'ecommerce' => 'Ecommerce',
                default => $channelKey !== '' ? ucfirst($channelKey) : 'Lainnya',
            };

            $rows[] = [
                'key' => $channelKey !== '' ? $channelKey : 'other',
                'label' => $label,
                ...self::make(
                    $channel['gross_sales'] ?? 0,
                    $channel['product_discount'] ?? 0,
                    $channel['voucher_discount'] ?? 0,
                    $channel['net_sales'] ?? 0,
                    $channel['base_price'] ?? 0,
                    $channel['transactions'] ?? 0,
                    $channel['qty'] ?? 0,
                    $channel['net_after_product_discount'] ?? null
                ),
            ];
        }

        usort($rows, fn (array $left, array $right): int => $right['net_sales'] <=> $left['net_sales']);

        return $rows;
    }

    private static function amount(mixed $value): float
    {
        return self::round(is_numeric($value) ? (float) $value : 0.0);
    }

    private static function round(float $value): float
    {
        return round($value, 2);
    }

    private static function percent(float $value, float $base): float
    {
        return abs($base) < 0.01 ? 0.0 : self::round(($value / $base) * 100);
    }
}
