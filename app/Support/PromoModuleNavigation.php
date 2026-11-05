<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class PromoModuleNavigation
{
    private const CURRENT_BY_ROUTE = [
        'master-data.promo-setup.index' => 'setup',
        'master-data.promos.index' => 'promos',
        'master-data.diskons.index' => 'discounts',
        'master-data.products-diskons.index' => 'product-discounts',
        'master-data.vouchers.index' => 'vouchers',
        'master-data.vouchers.codes' => 'vouchers',
        'master-data.vouchers.print-codes' => 'vouchers',
    ];

    private const ITEMS = [
        'promos' => [
            'label' => 'Promos',
            'icon' => 'mdi-sale-outline',
            'route' => 'master-data.promos.index',
            'permission' => 'view master-data-promos',
        ],
        'discounts' => [
            'label' => 'Discounts',
            'icon' => 'mdi-percent-outline',
            'route' => 'master-data.diskons.index',
            'permission' => 'view master-data-discounts',
        ],
        'product-discounts' => [
            'label' => 'Product Discounts',
            'icon' => 'mdi-tag-multiple-outline',
            'route' => 'master-data.products-diskons.index',
            'permission' => 'view master-data-product-discounts',
        ],
        'vouchers' => [
            'label' => 'Vouchers',
            'icon' => 'mdi-ticket-percent-outline',
            'route' => 'master-data.vouchers.index',
            'permission' => 'view master-data-vouchers',
        ],
        'setup' => [
            'label' => 'Promo Setup',
            'icon' => 'mdi-playlist-plus',
            'route' => 'master-data.promo-setup.index',
            'permission' => 'view master-data-promos',
        ],
    ];

    public static function forRequest(Request $request): ?array
    {
        $routeName = $request->route()?->getName();
        $current = self::CURRENT_BY_ROUTE[$routeName] ?? null;
        $user = $request->user();

        if ($current === null || $user === null) {
            return null;
        }

        $items = [];

        foreach (self::ITEMS as $key => $item) {
            if (! Route::has($item['route']) || ! $user->can($item['permission'])) {
                continue;
            }

            $items[] = [
                'key' => $key,
                'label' => __($item['label']),
                'icon' => $item['icon'],
                'url' => route($item['route']),
            ];
        }

        if ($items === []) {
            return null;
        }

        return [
            'current' => $current,
            'items' => $items,
        ];
    }
}
