<?php

namespace App\Ai\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class AssistantReferenceRegistry
{
    public function __construct(private readonly AssistantRouteRegistry $routes) {}

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<array{label: string, url: string}>
     */
    public function forTool(string $tool, array $arguments, ?User $user = null): array
    {
        $links = [];

        foreach ($this->candidates($tool, $arguments) as $candidate) {
            $link = $this->resolve($candidate['label'], $candidate['routes'], $user);

            if ($link !== null) {
                $links[$link['url']] = $link;
            }
        }

        return array_values($links);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return list<array{label: string, routes: list<string>}>
     */
    private function candidates(string $tool, array $arguments): array
    {
        return match ($tool) {
            'business_overview' => match ((string) ($arguments['focus'] ?? 'all')) {
                'sales' => [
                    ['label' => 'Ringkasan Usaha', 'routes' => ['reports.business-overview.index']],
                    ['label' => 'Omzet', 'routes' => ['monitoring.omset-cabang.index']],
                ],
                'orders' => [
                    ['label' => 'Pesanan Ecommerce', 'routes' => ['ecommerce.orders.index']],
                    ['label' => 'Order Toko', 'routes' => ['operations.store-orders.index']],
                ],
                'inventory' => [
                    ['label' => 'Stok per Cabang', 'routes' => ['monitoring.stok-percabang']],
                    ['label' => 'Stok Warehouse', 'routes' => ['monitoring.stok-wms']],
                ],
                default => [
                    ['label' => 'Ringkasan Usaha', 'routes' => ['reports.business-overview.index']],
                ],
            },
            'product_sales' => [
                ['label' => 'Produk Terlaris', 'routes' => ['monitoring.produk-terlaris.index']],
                ['label' => 'Omzet', 'routes' => ['monitoring.omset-cabang.index']],
            ],
            'stock_health' => match ((string) ($arguments['scope'] ?? 'all')) {
                'warehouse' => [
                    ['label' => 'Stok Warehouse', 'routes' => ['monitoring.stok-wms']],
                ],
                'store' => [
                    ['label' => 'Stok per Cabang', 'routes' => ['monitoring.stok-percabang']],
                ],
                default => [
                    ['label' => 'Stok per Cabang', 'routes' => ['monitoring.stok-percabang']],
                    ['label' => 'Stok Warehouse', 'routes' => ['monitoring.stok-wms']],
                ],
            },
            'operations_pipeline' => match ((string) ($arguments['focus'] ?? 'all')) {
                'orders' => [
                    ['label' => 'Pesanan Ecommerce', 'routes' => ['ecommerce.orders.index']],
                    ['label' => 'Order Toko', 'routes' => ['operations.store-orders.index']],
                ],
                'returns' => [
                    ['label' => 'Retur Toko', 'routes' => ['operations.store-returns.index']],
                    ['label' => 'Pesanan Cabang', 'routes' => ['reports.order-cabang.index']],
                ],
                'inbound_outbound' => [
                    ['label' => 'Barang Masuk', 'routes' => ['operations.inbound.index', 'operations.store-inbound.index']],
                    ['label' => 'Barang Keluar', 'routes' => ['operations.outbound.index', 'operations.store-outbound.index']],
                ],
                'opname' => [
                    ['label' => 'Stok Opname Toko', 'routes' => ['monitoring.store-stock-opnames.index']],
                    ['label' => 'Stok Opname WMS', 'routes' => ['monitoring.audit-wms-opnames.index']],
                ],
                default => [
                    ['label' => 'Pesanan Ecommerce', 'routes' => ['ecommerce.orders.index']],
                    ['label' => 'Order Toko', 'routes' => ['operations.store-orders.index']],
                    ['label' => 'Retur Toko', 'routes' => ['operations.store-returns.index']],
                ],
            },
            'promo_loyalty' => match ((string) ($arguments['focus'] ?? 'all')) {
                'promo' => [
                    ['label' => 'Promo', 'routes' => ['master-data.promos.index']],
                    ['label' => 'Promo & Loyalty', 'routes' => ['monitoring.promo-loyalty.index']],
                ],
                'voucher' => [
                    ['label' => 'Voucher', 'routes' => ['master-data.vouchers.index']],
                    ['label' => 'Promo & Loyalty', 'routes' => ['monitoring.promo-loyalty.index']],
                ],
                'loyalty' => [
                    ['label' => 'Loyalty per Akun', 'routes' => ['monitoring.loyalty-accounts.index']],
                    ['label' => 'Poin Loyalitas', 'routes' => ['loyalty.customer-loyalty-points.index']],
                ],
                'membership' => [
                    ['label' => 'Tingkat Keanggotaan', 'routes' => ['master-data.memberships.index']],
                ],
                default => [
                    ['label' => 'Promo & Loyalty', 'routes' => ['monitoring.promo-loyalty.index']],
                    ['label' => 'Voucher', 'routes' => ['master-data.vouchers.index']],
                ],
            },
            'customer_insight' => [
                ['label' => 'CRM Pelanggan', 'routes' => ['reports.crm.index']],
                ['label' => 'Demografi Pelanggan', 'routes' => ['reports.crm.demographics']],
                ['label' => 'Preferensi Pelanggan', 'routes' => ['reports.crm.customer-preference.index']],
            ],
            'master_data' => $this->masterData((string) ($arguments['entity'] ?? '')),
            'query_data' => match ((string) ($arguments['connection'] ?? 'operational')) {
                'backoffice' => [
                    ['label' => 'Pengguna & Peran', 'routes' => ['management.user-roles.index']],
                    ['label' => 'Audit Center', 'routes' => ['monitoring.audit-center.index']],
                ],
                'pos' => [
                    ['label' => 'Kendala Ecommerce', 'routes' => ['ecommerce.rejections.index', 'ecommerce.orders.index']],
                ],
                default => [
                    ['label' => 'Ringkasan Usaha', 'routes' => ['reports.business-overview.index']],
                ],
            },
            default => [],
        };
    }

    /**
     * @return list<array{label: string, routes: list<string>}>
     */
    private function masterData(string $entity): array
    {
        $routes = match ($entity) {
            'products' => ['master-data.products.index'],
            'product_prices' => ['master-data.product-prices.index'],
            'customers' => ['master-data.customers.index', 'operations.customers.index', 'reports.crm.index'],
            'stores' => ['master-data.stores.index'],
            'warehouses' => ['master-data.warehouses.index'],
            'suppliers' => ['master-data.suppliers.index'],
            'couriers' => ['master-data.couriers.index'],
            'partners' => ['master-data.partners.index'],
            'categories' => ['master-data.product-categories.index'],
            'brands' => ['master-data.brands.index'],
            'units' => ['master-data.units.index'],
            'payment_methods' => ['master-data.payment-methods.index'],
            'vouchers' => ['master-data.vouchers.index'],
            'memberships' => ['master-data.memberships.index'],
            'users' => ['master-data.users.index'],
            default => [],
        };

        if ($routes === []) {
            return [];
        }

        return [[
            'label' => match ($entity) {
                'customers' => 'Master Pelanggan',
                'users' => 'Master Pengguna',
                'product_prices' => 'Harga Produk',
                default => 'Master Data',
            },
            'routes' => $routes,
        ]];
    }

    /**
     * @param  list<string>  $routes
     * @return array{label: string, url: string}|null
     */
    private function resolve(string $label, array $routes, ?User $user): ?array
    {
        foreach ($routes as $route) {
            if (! Route::has($route)) {
                continue;
            }

            $url = route($route);

            if ($this->routes->isAllowed($url, $user)) {
                return ['label' => $label, 'url' => $url];
            }
        }

        return null;
    }
}
