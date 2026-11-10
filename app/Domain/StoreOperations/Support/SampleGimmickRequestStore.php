<?php

namespace App\Domain\StoreOperations\Support;

final class SampleGimmickRequestStore
{
    public const REQUEST_KEY_PREFIX = 'store_sample_gimmick_request:';

    public const SAMPLE_MARKER_PREFIX = 'store_sample_gimmick:';

    public const SAMPLE_RESERVATION_PREFIX = 'store_sample_gimmick_reserved_item:';

    public const GIMMICK_RESERVATION_PREFIX = 'store_sample_gimmick_reserved_cart_detail:';

    public static function requestKey(string $requestId): string
    {
        return self::REQUEST_KEY_PREFIX.$requestId;
    }

    public static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public static function reservationKeys(array $request): array
    {
        $type = $request['type'] ?? null;
        $items = is_array($request['items'] ?? null) ? $request['items'] : [];
        $keys = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($type === 'sample' && ! empty($item['store_item_id'])) {
                $keys[] = self::SAMPLE_RESERVATION_PREFIX.$item['store_item_id'];
            }

            if ($type === 'gimmick' && ! empty($item['cart_detail_id'])) {
                $keys[] = self::GIMMICK_RESERVATION_PREFIX.$item['cart_detail_id'];
            }
        }

        return array_values(array_unique($keys));
    }
}
