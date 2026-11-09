<?php

namespace App\Domain\Promotions\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncDiscountCaps
{
    /**
     * @param  array<int, string>  $discountIds
     * @return array{discounts: int, mappings: int}
     */
    public function handle(array $discountIds, ?float $targetMaxDiscount, string $channelScope): array
    {
        $discountIds = array_values(array_unique(array_filter($discountIds)));

        if ($discountIds === []) {
            throw ValidationException::withMessages([
                'discount_ids' => 'Pilih minimal satu diskon untuk disinkronkan.',
            ]);
        }

        if (! in_array($channelScope, ['keep', 'global', 'store', 'ecommerce'], true)) {
            throw ValidationException::withMessages([
                'channel_scope' => 'Cakupan kanal tidak valid.',
            ]);
        }

        $discounts = DB::table('discounts')
            ->whereIn('id', $discountIds)
            ->get(['id', 'name', 'type']);

        if ($discounts->count() !== count($discountIds)) {
            throw ValidationException::withMessages([
                'discount_ids' => 'Sebagian diskon tidak ditemukan.',
            ]);
        }

        $fixed = $discounts->filter(fn (object $discount): bool => $discount->type !== 'percentage');

        if ($fixed->isNotEmpty()) {
            throw ValidationException::withMessages([
                'discount_ids' => 'Sinkronisasi max diskon hanya berlaku untuk diskon bertipe persentase.',
            ]);
        }

        return DB::transaction(function () use ($discountIds, $targetMaxDiscount, $channelScope): array {
            $now = now();

            $updatedDiscounts = DB::table('discounts')
                ->whereIn('id', $discountIds)
                ->update([
                    'max_discount' => $targetMaxDiscount,
                    'updated_at' => $now,
                ]);

            $updatedMappings = 0;

            if ($channelScope !== 'keep') {
                $mapping = match ($channelScope) {
                    'global' => [
                        'applied_to' => 'global',
                        'store_id' => null,
                    ],
                    'store' => [
                        'applied_to' => 'store',
                    ],
                    'ecommerce' => [
                        'applied_to' => 'ecommerce',
                        'store_id' => null,
                    ],
                };

                $updatedMappings = DB::table('discount_products')
                    ->whereIn('discount_id', $discountIds)
                    ->update($mapping);
            }

            return [
                'discounts' => $updatedDiscounts,
                'mappings' => $updatedMappings,
            ];
        });
    }
}
