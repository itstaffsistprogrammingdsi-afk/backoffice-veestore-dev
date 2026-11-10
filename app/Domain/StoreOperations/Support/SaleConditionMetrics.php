<?php

namespace App\Domain\StoreOperations\Support;

final class SaleConditionMetrics
{
    public const STATUS_GOOD = 'good';

    public const STATUS_GOOD_AFTER_REPAIR = 'good_after_repair';

    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_MIXED = 'mixed';

    public const CLEARANCE_ALL = 'all';

    public const CLEARANCE_YES = 'yes';

    public const CLEARANCE_NO = 'no';

    public static function labels(): array
    {
        return [
            self::STATUS_GOOD => 'Good',
            self::STATUS_GOOD_AFTER_REPAIR => 'Good After Repair',
        ];
    }

    public static function conditionOptions(): array
    {
        return collect(self::labels())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    public static function clearanceOptions(): array
    {
        return [
            ['value' => self::CLEARANCE_YES, 'label' => 'Penjualan Clearance'],
            ['value' => self::CLEARANCE_NO, 'label' => 'Bukan Clearance'],
        ];
    }

    public static function normalizeConditionStatus(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, [self::STATUS_GOOD, self::STATUS_GOOD_AFTER_REPAIR], true)
            ? $value
            : self::CLEARANCE_ALL;
    }

    public static function normalizeClearance(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, [self::CLEARANCE_YES, self::CLEARANCE_NO], true)
            ? $value
            : self::CLEARANCE_ALL;
    }

    public static function conditionNameSql(string $conditionAlias): string
    {
        return "COALESCE(
            {$conditionAlias}.name,
            CASE {$conditionAlias}.status::text
                WHEN 'good' THEN 'Good'
                WHEN 'good_after_repair' THEN 'Good After Repair'
                WHEN 'bad' THEN 'Bad'
                ELSE 'Tidak Tercatat'
            END
        )";
    }

    public static function clearancePercentageSql(string $statusSql, string $priceSql, string $discountSql): string
    {
        return "CASE
            WHEN {$statusSql} = 'good_after_repair' AND COALESCE({$priceSql}, 0) > 0 THEN
                ROUND(
                    LEAST(
                        GREATEST(COALESCE({$discountSql}, 0), 0) / NULLIF({$priceSql}, 0) * 100,
                        100
                    )::numeric,
                    2
                )
            ELSE 0
        END";
    }

    public static function clearanceDiscountSql(string $statusSql, string $discountSql): string
    {
        return "CASE WHEN {$statusSql} = 'good_after_repair' THEN COALESCE({$discountSql}, 0) ELSE 0 END";
    }

    public static function eligibleSaleLineSql(string $detailAlias): string
    {
        return "COALESCE({$detailAlias}.void_status, '') NOT IN ('void', 'voided', 'gimmick')";
    }

    public static function gimmickSaleLineSql(string $detailAlias): string
    {
        return "COALESCE({$detailAlias}.void_status, '') = 'gimmick'";
    }

    public static function excludedVoidStatuses(): array
    {
        return ['void', 'voided', 'gimmick'];
    }
}
