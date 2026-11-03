<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class MasterDataModuleNavigation
{
    private const CURRENT_BY_ROUTE = [
        'master-data.products.index' => 'products',
        'master-data.product-prices.index' => 'product-prices',
        'master-data.product-specifications.index' => 'product-specifications',
        'master-data.product-categories.index' => 'categories',
        'master-data.product-category-groups.index' => 'category-groups',
        'master-data.brands.index' => 'brands',
        'master-data.units.index' => 'units',
        'master-data.suppliers.index' => 'suppliers',
        'master-data.item-conditions.index' => 'item-conditions',
    ];

    private const ITEMS = [
        'products' => ['label' => 'Products', 'icon' => 'mdi-package-variant', 'route' => 'master-data.products.index', 'permission' => 'view master-data-products'],
        'product-prices' => ['label' => 'Product Prices', 'icon' => 'mdi-currency-usd', 'route' => 'master-data.product-prices.index', 'permission' => 'view master-data-product-prices'],
        'product-specifications' => ['label' => 'Product Specifications', 'icon' => 'mdi-format-list-bulleted', 'route' => 'master-data.product-specifications.index', 'permission' => 'view master-data-product-specifications'],
        'categories' => ['label' => 'Categories', 'icon' => 'mdi-shape-outline', 'route' => 'master-data.product-categories.index', 'permission' => 'view master-data-product-categories'],
        'category-groups' => ['label' => 'Category Groups', 'icon' => 'mdi-layers-outline', 'route' => 'master-data.product-category-groups.index', 'permission' => 'view master-data-product-categories'],
        'brands' => ['label' => 'Brands', 'icon' => 'mdi-tag-outline', 'route' => 'master-data.brands.index', 'permission' => 'view master-data-brands'],
        'units' => ['label' => 'Units', 'icon' => 'mdi-ruler', 'route' => 'master-data.units.index', 'permission' => 'view master-data-units'],
        'suppliers' => ['label' => 'Suppliers', 'icon' => 'mdi-truck-delivery-outline', 'route' => 'master-data.suppliers.index', 'permission' => 'view master-data-suppliers'],
        'item-conditions' => ['label' => 'Item Conditions', 'icon' => 'mdi-clipboard-check-outline', 'route' => 'master-data.item-conditions.index', 'permission' => 'view master-data-item-conditions'],
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
