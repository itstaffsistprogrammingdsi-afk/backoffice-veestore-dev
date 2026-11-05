<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class LoyaltyModuleNavigation
{
    private const CURRENT_BY_ROUTE = [
        'loyalty.customer-loyalty-points.index' => 'points',
        'loyalty.customer-loyalty-point-mutations.index' => 'mutations',
        'master-data.memberships.index' => 'tiers',
        'master-data.membership-benefits.index' => 'benefits',
        'settings.points.edit' => 'point-rate',
        'settings.app.customer-loyalty-point-transaction-reward.edit' => 'loyalty-reward',
        'settings.app.affiliate-points.inviter.edit' => 'affiliate-inviter',
        'settings.app.affiliate-points.invitee.edit' => 'affiliate-invitee',
    ];

    private const ITEMS = [
        'points' => ['label' => 'Poin Loyalitas', 'icon' => 'mdi-star-circle-outline', 'route' => 'loyalty.customer-loyalty-points.index', 'permission' => 'view loyalty-customer-loyalty-points'],
        'mutations' => ['label' => 'Mutasi Poin', 'icon' => 'mdi-swap-horizontal', 'route' => 'loyalty.customer-loyalty-point-mutations.index', 'permission' => 'view loyalty-customer-loyalty-point-mutations'],
        'tiers' => ['label' => 'Tingkat Keanggotaan', 'icon' => 'mdi-crown-outline', 'route' => 'master-data.memberships.index', 'permission' => 'view master-data-memberships'],
        'benefits' => ['label' => 'Manfaat Keanggotaan', 'icon' => 'mdi-gift-outline', 'route' => 'master-data.membership-benefits.index', 'permission' => 'view master-data-membership-benefits'],
        'point-rate' => ['label' => 'Tarif Poin', 'icon' => 'mdi-currency-usd', 'route' => 'settings.points.edit', 'permission' => null],
        'loyalty-reward' => ['label' => 'Hadiah Loyalitas', 'icon' => 'mdi-star-circle-outline', 'route' => 'settings.app.customer-loyalty-point-transaction-reward.edit', 'permission' => null],
        'affiliate-inviter' => ['label' => 'Afiliasi Pengundang', 'icon' => 'mdi-account-plus-outline', 'route' => 'settings.app.affiliate-points.inviter.edit', 'permission' => null],
        'affiliate-invitee' => ['label' => 'Afiliasi Undangan', 'icon' => 'mdi-account-multiple-plus-outline', 'route' => 'settings.app.affiliate-points.invitee.edit', 'permission' => null],
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
            if (! Route::has($item['route'])) {
                continue;
            }

            if ($item['permission'] !== null && ! $user->can($item['permission'])) {
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
