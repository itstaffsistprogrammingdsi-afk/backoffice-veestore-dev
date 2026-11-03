<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LoyaltyModuleNavigation
{
    private const CURRENT_BY_ROUTE = [
        'loyalty.customer-loyalty-points.index' => 'points',
        'loyalty.customer-loyalty-point-mutations.index' => 'mutations',
        'loyalty.customer-visits.index' => 'visits',
        'master-data.memberships.index' => 'tiers',
        'master-data.membership-benefits.index' => 'benefits',
    ];

    private const ITEMS = [
        'points' => ['label' => 'Loyalty Points', 'icon' => 'mdi-star-circle-outline', 'route' => 'loyalty.customer-loyalty-points.index', 'permission' => 'view loyalty-customer-loyalty-points'],
        'mutations' => ['label' => 'Point Mutations', 'icon' => 'mdi-swap-horizontal', 'route' => 'loyalty.customer-loyalty-point-mutations.index', 'permission' => 'view loyalty-customer-loyalty-point-mutations'],
        'visits' => ['label' => 'Customer Visits', 'icon' => 'mdi-map-marker-check-outline', 'route' => 'loyalty.customer-visits.index', 'permission' => 'view loyalty-customer-visits'],
        'tiers' => ['label' => 'Membership Tiers', 'icon' => 'mdi-crown-outline', 'route' => 'master-data.memberships.index', 'permission' => 'view master-data-memberships'],
        'benefits' => ['label' => 'Membership Benefits', 'icon' => 'mdi-gift-outline', 'route' => 'master-data.membership-benefits.index', 'permission' => 'view master-data-membership-benefits'],
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
                'label' => $item['label'],
                'icon' => $item['icon'],
                'url' => route($item['route']),
            ];
        }

        return $items === [] ? null : ['current' => $current, 'items' => $items];
    }
}
