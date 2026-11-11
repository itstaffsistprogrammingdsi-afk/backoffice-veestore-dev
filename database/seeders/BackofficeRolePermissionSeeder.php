<?php

namespace Database\Seeders;

use App\Models\Backoffice\Permission;
use App\Models\Backoffice\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;

class BackofficeRolePermissionSeeder extends Seeder
{
    private const CONNECTION = 'immostore_backoffice';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $menuPermissions = $this->menuPermissions();
        $customPermissions = [
            'view master-customer',
            'create master-customer',
            'update master-customer',
            'view branch-retur-cabang',
            'view branch-store-schedule',
            'approve branch-store-schedule',
            'approve management-label-requests',
            'manage label-print-requests',
            'create label-print-requests',
            'print label-print-requests',
            'approve store-opname-variance',
            'manage store-opname',
        ];

        DB::connection(self::CONNECTION)->transaction(function () use ($menuPermissions, $customPermissions) {
            foreach (array_unique(array_merge($menuPermissions, $customPermissions)) as $name) {
                Permission::on(self::CONNECTION)->firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);
            }

            $allPermissions = Permission::on(self::CONNECTION)->get()->keyBy('name');
            $permissionsByName = collect($allPermissions->all());
            $viewPermissions = $allPermissions->keys()
                ->filter(fn (string $name) => str_starts_with($name, 'view '))
                ->values()
                ->all();

            $menu = fn (array $slugs): array => array_map(
                fn (string $slug) => "view {$slug}",
                $slugs
            );
            $edit = fn (array $resources): array => collect($resources)
                ->flatMap(fn (string $resource) => [
                    "view {$resource}",
                    "create {$resource}",
                    "update {$resource}",
                ])
                ->all();

            $roles = [
                'Marketing' => array_merge(
                    $edit([
                        'master-customer',
                        'master-data-product-categories',
                        'master-data-brands',
                        'master-data-products',
                        'master-data-product-prices',
                        'master-data-product-specifications',
                        'master-data-promos',
                        'master-data-discounts',
                        'master-data-product-discounts',
                        'master-data-vouchers',
                        'master-data-ecommerce-banners',
                        'master-data-memberships',
                        'master-data-membership-benefits',
                    ]),
                    $menu([
                        'dashboard-analytics',
                        'ai-assistant',
                        'ecommerce-orders',
                        'master-data-product-categories',
                        'master-data-brands',
                        'master-data-products',
                        'master-data-product-prices',
                        'master-data-units',
                        'master-data-item-conditions',
                        'master-data-product-specifications',
                        'master-data-customers',
                        'master-data-promos',
                        'master-data-discounts',
                        'master-data-product-discounts',
                        'master-data-vouchers',
                        'master-data-ecommerce-banners',
                        'master-data-memberships',
                        'master-data-membership-benefits',
                        'loyalty-customer-loyalty-points',
                        'loyalty-customer-loyalty-point-mutations',
                        'loyalty-customer-visits',
                        'crm-dashboard',
                        'crm-customer-profiles',
                        'crm-demographics',
                        'monitoring-omset-cabang',
                        'monitoring-omset-kotor',
                        'monitoring-produk-terlaris',
                        'monitoring.fulfillment-control',
                        'monitoring-ecommerce-order-fulfillment',
                        'monitoring-order-handled',
                        'monitoring.stok-percabang',
                        'monitoring-physical-stocks',
                        'monitoring-product-slowmoving',
                        'monitoring-product-aging',
                        'monitoring-stok-mendekati-kedaluwarsa',
                        'monitoring-data-customer',
                        'monitoring-customer-membership',
                        'monitoring-customer-loyalty-points-report',
                        'monitoring-referral-score-report',
                        'monitoring-redeem-point-report',
                        'monitoring-diskon',
                        'monitoring-promo-usage',
                        'monitoring-voucher-reject-report',
                        'monitoring-request-voucher',
                    ])
                ),
                'Warehouse' => array_merge(
                    $edit([
                        'master-data-products',
                        'master-data-product-specifications',
                        'master-data-units',
                        'master-data-item-conditions',
                        'master-data-warehouses',
                        'master-data-locations-warehouse',
                    ]),
                    $menu([
                        'dashboard-analytics',
                        'master-data-product-categories',
                        'master-data-brands',
                        'master-data-products',
                        'master-data-units',
                        'master-data-item-conditions',
                        'master-data-product-specifications',
                        'master-data-warehouses',
                        'master-data-locations-warehouse',
                        'monitoring.stok-percabang',
                        'monitoring.stok-wms',
                        'monitoring-warehouse-inbound',
                        'monitoring.warehouse-outbound',
                        'monitoring.quality-control',
                        'monitoring-mutasi-stock-wms',
                        'monitoring-tracking-barang',
                        'monitoring-anomali-item',
                        'monitoring-physical-stocks',
                        'monitoring-product-slowmoving',
                        'monitoring-product-aging',
                        'monitoring-stok-mendekati-kedaluwarsa',
                        'monitoring-stok-barier-cabang',
                        'monitoring-store-stock-opnames',
                        'monitoring-stok-barier-gudang',
                        'monitoring-audit-wms-opnames',
                        'monitoring-barcode-request-report',
                    ])
                ),
                'Store Operations' => array_merge(
                    $edit([
                        'master-data-stores',
                        'master-data-locations-store',
                    ]),
                    ['create label-print-requests', 'print label-print-requests'],
                    $menu([
                        'dashboard-analytics',
                        'master-data-stores',
                        'master-data-locations-store',
                        'branch-order-cabang',
                        'branch-retur-cabang',
                        'branch-store-schedule',
                        'branch-request-label',
                        'branch-request-voucher',
                        'monitoring-store-order-requests',
                        'monitoring-store-return-requests',
                        'monitoring-order-handled',
                    ])
                ),
                'Finance' => array_merge(
                    $edit([
                        'master-data-suppliers',
                        'master-data-payment-methods',
                    ]),
                    $menu([
                        'dashboard-analytics',
                        'ai-assistant',
                        'ecommerce-orders',
                        'master-data-product-prices',
                        'master-data-stores',
                        'master-data-warehouses',
                        'master-data-suppliers',
                        'master-data-payment-methods',
                        'monitoring-payment-income-realtime',
                        'monitoring.payment-reconciliation',
                        'monitoring-store-payment-transactions',
                        'monitoring-ecommerce-payment',
                        'monitoring-omset-cabang',
                        'monitoring-omset-kotor',
                        'monitoring-cashier-shift-report',
                        'monitoring-void-cancel-customer',
                        'monitoring-ecommerce-order-fulfillment',
                        'monitoring-store-order-requests',
                        'monitoring-store-return-requests',
                        'monitoring-order-handled',
                        'monitoring-voucher-reject-report',
                    ])
                ),
                'HRD' => array_merge($menu([
                    'dashboard-analytics',
                    'monitoring-cashier-shift-report',
                    'monitoring.audit-center',
                    'monitoring-user-monitoring',
                    'monitoring-employee-shift-attendance',
                ]), ['view branch-store-schedule']),
                'Management' => array_merge(
                    $viewPermissions,
                    $menuPermissions,
                    ['approve management-label-requests', 'manage label-print-requests', 'create label-print-requests', 'print label-print-requests', 'approve store-opname-variance', 'manage store-opname', 'approve branch-store-schedule']
                ),
            ];

            foreach ($roles as $name => $permissionNames) {
                $role = Role::on(self::CONNECTION)->firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);

                $role->syncPermissions(
                    $permissionsByName->only(array_unique($permissionNames))->values()
                );
            }

            foreach (['Superadmin', 'super-admin'] as $name) {
                $role = Role::on(self::CONNECTION)->firstOrCreate([
                    'name' => $name,
                    'guard_name' => 'web',
                ]);

                $role->syncPermissions($allPermissions->values());
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function menuPermissions(): array
    {
        $payload = json_decode(File::get(resource_path('menu/verticalMenu.json')), true);
        $permissions = [];

        $collect = function (array $items) use (&$collect, &$permissions): void {
            foreach ($items as $item) {
                if (! empty($item['slug'])) {
                    $permissions[] = 'view '.$item['slug'];
                }

                if (! empty($item['submenu']) && is_array($item['submenu'])) {
                    $collect($item['submenu']);
                }
            }
        };

        $collect($payload['menu'] ?? []);

        return array_values(array_unique($permissions));
    }
}
