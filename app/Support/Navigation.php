<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    private const INERTIA_PREFIXES = [
        'operations.',
        'monitoring.',
        'dashboard-analytics',
        'reports.',
        'ai-analytics.',
        'ai-assistant.',
        'settings.',
        'loyalty.',
        'management.',
        'report-exports.',
        'branch-operations.',
        'ecommerce.',
        'master-data.',
    ];

    public static function forUser(?User $user): array
    {
        return [
            'areas' => [
                [
                    'key' => 'operations',
                    'label' => 'Operasional',
                    'description' => 'Warehouse, store, dan ecommerce',
                    'url' => self::url('operations.index'),
                    'icon' => 'mdi-storefront-outline',
                    'groups' => self::filterGroups(self::operationsGroups($user)),
                ],
                [
                    'key' => 'reports',
                    'label' => 'Laporan',
                    'description' => 'Analitik, monitoring, dan export',
                    'url' => self::url('reports.index'),
                    'icon' => 'mdi-chart-box-outline',
                    'groups' => self::filterGroups(self::reportsGroups($user)),
                ],
            ],
        ];
    }

    private static function operationsGroups(?User $user): array
    {
        return [
            [
                'label' => 'Gudang',
                'icon' => 'mdi-warehouse',
                'items' => self::items($user, [
                    ['Warehouses', 'master-data.warehouses.index', 'mdi-warehouse', 'view master-data-warehouses'],
                    ['Warehouse Layout', 'master-data.locations-warehouse.index', 'mdi-map-marker-path', 'view master-data-locations-warehouse'],
                    ['Stock Operations', 'operations.stock.index', 'mdi-clipboard-list-outline', null],
                    ['Inbound Operations', 'operations.inbound.index', 'mdi-tray-arrow-down', null],
                    ['Outbound Operations', 'operations.outbound.index', 'mdi-tray-arrow-up', null],
                ]),
            ],
            [
                'label' => 'Toko',
                'icon' => 'mdi-store',
                'items' => self::items($user, [
                    ['Stores', 'master-data.stores.index', 'mdi-store', 'view master-data-stores'],
                    ['Store Layout', 'master-data.locations-store.index', 'mdi-map-marker-radius', 'view master-data-locations-store'],
                    ['Store Stock Operations', 'operations.store-stock.index', 'mdi-clipboard-list-outline', null],
                    ['Store Inbound Operations', 'operations.store-inbound.index', 'mdi-tray-arrow-down', null],
                    ['Store Outbound Operations', 'operations.store-outbound.index', 'mdi-tray-arrow-up', null],
                    ['Customers & Visits', 'operations.customers.index', 'mdi-account-group-outline', null],
                    ['Label Requests', 'branch-operations.request-label.index', 'mdi-tag-multiple-outline', null],
                    ['Voucher Requests', 'branch-operations.request-voucher.index', 'mdi-ticket-confirmation-outline', null],
                ]),
            ],
            [
                'label' => 'Ecommerce',
                'icon' => 'mdi-cart-outline',
                'items' => self::items($user, [
                    ['Orders', 'ecommerce.orders.index', 'mdi-cart-arrow-right', null],
                    ['Banners', 'master-data.ecommerce-banners.index', 'mdi-image-multiple-outline', 'view master-data-ecommerce-banners'],
                    ['Promos', 'master-data.promos.index', 'mdi-sale-outline', 'view master-data-promos'],
                    ['Vouchers', 'master-data.vouchers.index', 'mdi-ticket-percent-outline', 'view master-data-vouchers'],
                    ['Discounts', 'master-data.diskons.index', 'mdi-percent-outline', 'view master-data-discounts'],
                ]),
            ],
            [
                'label' => 'Master Produk',
                'icon' => 'mdi-package-variant',
                'items' => self::items($user, [
                    ['Products', 'master-data.products.index', 'mdi-package-variant', 'view master-data-products'],
                    ['Product Prices', 'master-data.product-prices.index', 'mdi-currency-usd', 'view master-data-product-prices'],
                    ['Categories', 'master-data.product-categories.index', 'mdi-shape-outline', 'view master-data-product-categories'],
                    ['Brands', 'master-data.brands.index', 'mdi-tag-outline', 'view master-data-brands'],
                    ['Units', 'master-data.units.index', 'mdi-ruler', 'view master-data-units'],
                    ['Suppliers', 'master-data.suppliers.index', 'mdi-truck-delivery-outline', 'view master-data-suppliers'],
                    ['Item Conditions', 'master-data.item-conditions.index', 'mdi-clipboard-check-outline', 'view master-data-item-conditions'],
                ]),
            ],
            [
                'label' => 'Administrasi',
                'icon' => 'mdi-shield-account-outline',
                'items' => self::items($user, [
                    ['Backoffice Roles', 'management.roles-backoffice.index', 'mdi-shield-account-outline', null],
                    ['Role Permissions', 'management.role-permissions.index', 'mdi-shield-key-outline', null],
                    ['User Roles', 'management.user-roles.index', 'mdi-account-key-outline', null],
                    ['Users', 'master-data.users.index', 'mdi-account-multiple-outline', null],
                    ['Settings', 'settings.points.edit', 'mdi-cog-outline', null],
                ]),
            ],
        ];
    }

    private static function reportsGroups(?User $user): array
    {
        return [
            [
                'label' => 'Analitik',
                'icon' => 'mdi-chart-line',
                'items' => self::items($user, [
                    ['Dashboard Analitik', 'dashboard-analytics', 'mdi-view-dashboard-outline', null],
                    ['AI Analytics', 'ai-analytics.index', 'mdi-robot-outline', null],
                    ['AI Assistant', 'ai-assistant.index', 'mdi-chat-processing-outline', 'view ai-analytics'],
                ]),
            ],
            [
                'label' => 'Monitoring',
                'icon' => 'mdi-monitor-dashboard',
                'items' => self::items($user, [
                    ['Command Center', 'monitoring.command-center.index', 'mdi-monitor-dashboard', null],
                    ['Omset Kotor', 'monitoring.omset-kotor.index', 'mdi-chart-areaspline', 'view monitoring-omset-kotor'],
                    ['Omset Cabang', 'monitoring.omset-cabang.index', 'mdi-chart-bar', 'view monitoring-omset-cabang'],
                    ['Stok Percabang', 'monitoring.stok-percabang', 'mdi-package-variant-closed', null],
                    ['Tracking Barang', 'monitoring.tracking-barang.index', 'mdi-map-marker-path', 'view monitoring-tracking-barang'],
                    ['Slow Moving', 'monitoring.product-slowmoving.index', 'mdi-timer-sand', 'view monitoring-product-slowmoving'],
                    ['Product Aging', 'monitoring.product-aging.index', 'mdi-calendar-clock', null],
                    ['Audit Opname WMS', 'monitoring.audit-wms-opnames.index', 'mdi-clipboard-text-clock-outline', null],
                ]),
            ],
            [
                'label' => 'Laporan',
                'icon' => 'mdi-file-chart-outline',
                'items' => self::items($user, [
                    ['CRM', 'reports.crm.index', 'mdi-account-heart-outline', null],
                    ['History Customer', 'reports.history-customer.index', 'mdi-history', null],
                    ['Order Cabang', 'reports.order-cabang.index', 'mdi-clipboard-list-outline', null],
                    ['Retur Cabang', 'reports.retur-cabang.index', 'mdi-keyboard-return', null],
                    ['Voucher', 'reports.voucher.index', 'mdi-ticket-percent-outline', null],
                    ['Supplier Products', 'reports.supplier-products.index', 'mdi-truck-outline', 'view supplier-product'],
                ]),
            ],
            [
                'label' => 'Export',
                'icon' => 'mdi-file-export-outline',
                'items' => self::items($user, [
                    ['Export Saya', 'report-exports.index', 'mdi-file-export-outline', null],
                ]),
            ],
        ];
    }

    private static function items(?User $user, array $items): array
    {
        $result = [];

        foreach ($items as [$label, $routeName, $icon, $permission]) {
            if (! Route::has($routeName)) {
                continue;
            }

            if ($permission && $user && ! $user->can($permission)) {
                continue;
            }

            $result[] = [
                'label' => $label,
                'url' => self::url($routeName),
                'icon' => $icon,
                'route' => $routeName,
                'inertia' => self::isInertia($routeName),
            ];
        }

        return $result;
    }

    private static function filterGroups(array $groups): array
    {
        return array_values(array_filter($groups, fn (array $group) => $group['items'] !== []));
    }

    private static function url(string $routeName): ?string
    {
        return Route::has($routeName) ? route($routeName) : null;
    }

    private static function isInertia(string $routeName): bool
    {
        foreach (self::INERTIA_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
