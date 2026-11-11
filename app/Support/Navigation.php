<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class Navigation
{
    private const INERTIA_PREFIXES = [
        'operations.',
        'monitoring.',
        'reports.',
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
                    'description' => 'Gudang, toko, dan e-commerce',
                    'url' => self::url('operations.index'),
                    'icon' => 'mdi-storefront-outline',
                    'groups' => self::filterGroups(self::operationsGroups($user)),
                ],
                [
                    'key' => 'reports',
                    'label' => 'Laporan',
                    'description' => 'Analitik, pemantauan, dan ekspor',
                    'url' => self::url('reports.index'),
                    'icon' => 'mdi-chart-box-outline',
                    'groups' => self::filterGroups(self::reportsGroups($user)),
                ],
            ],
        ];
    }

    private static function operationsGroups(?User $user): array
    {
        $ecommerceItems = [
            ['Pesanan', 'ecommerce.orders.index', 'mdi-cart-arrow-right', 'view ecommerce-orders'],
            ['Kendala E-commerce', 'ecommerce.rejections.index', 'mdi-alert-decagram-outline', 'view ecommerce-orders'],
            ['Pembayaran', 'ecommerce.payments.index', 'mdi-credit-card-check-outline', 'view monitoring-ecommerce-payment'],
            ['Metode Pembayaran', 'master-data.payment-methods.index', 'mdi-credit-card-outline', 'view master-data-payment-methods'],
            ['Banner', 'master-data.ecommerce-banners.index', 'mdi-image-multiple-outline', 'view master-data-ecommerce-banners'],
        ];

        if (! config('backoffice.ecommerce_rejection_handoff_enabled', false)) {
            $ecommerceItems = array_values(array_filter(
                $ecommerceItems,
                fn (array $item): bool => $item[1] !== 'ecommerce.rejections.index'
            ));
        }

        return [
            [
                'label' => 'Gudang',
                'icon' => 'mdi-warehouse',
                'items' => self::items($user, [
                    ['Gudang', 'master-data.warehouses.index', 'mdi-warehouse', 'view master-data-warehouses'],
                    ['Tata Letak Gudang', 'master-data.locations-warehouse.index', 'mdi-map-marker-path', 'view master-data-locations-warehouse'],
                    ['Operasional Stok', 'operations.stock.index', 'mdi-clipboard-list-outline', null],
                    ['Operasional Barang Masuk', 'operations.inbound.index', 'mdi-tray-arrow-down', null],
                    ['Operasional Barang Keluar', 'operations.outbound.index', 'mdi-tray-arrow-up', null],
                ]),
            ],
            [
                'label' => 'Toko',
                'icon' => 'mdi-store',
                'items' => self::items($user, [
                    ['Toko', 'master-data.stores.index', 'mdi-store', 'view master-data-stores'],
                    ['Tata Letak Toko', 'master-data.locations-store.index', 'mdi-map-marker-radius', 'view master-data-locations-store'],
                    ['Operasional Stok Toko', 'operations.store-stock.index', 'mdi-clipboard-list-outline', null],
                    ['Sample & Gimmick', 'operations.sample-gimmick.index', 'mdi-flask-outline', null],
                    ['Operasional Barang Masuk Toko', 'operations.store-inbound.index', 'mdi-tray-arrow-down', null],
                    ['Operasional Barang Keluar Toko', 'operations.store-outbound.index', 'mdi-tray-arrow-up', null],
                    ['Transfer Toko ke Toko', 'operations.store-transfers.index', 'mdi-swap-horizontal-bold', 'view branch-order-cabang'],
                    ['Persetujuan Pesanan Toko', 'operations.store-orders.index', 'mdi-clipboard-check-outline', 'view branch-order-cabang'],
                    ['Persetujuan Retur Toko', 'operations.store-returns.index', 'mdi-backup-restore', 'view branch-retur-cabang'],
                    ['Persetujuan Jadwal Toko', 'operations.store-schedules.index', 'mdi-calendar-check-outline', 'view branch-store-schedule'],
                    ['Pelanggan dan Kunjungan', 'operations.customers.index', 'mdi-account-group-outline', null],
                    ['Persetujuan Cetak Ulang Label', 'branch-operations.request-label.index', 'mdi-tag-check-outline', null],
                    ['Permintaan Voucher', 'branch-operations.request-voucher.index', 'mdi-ticket-confirmation-outline', null],
                ]),
            ],
            [
                'label' => 'Ecommerce',
                'icon' => 'mdi-cart-outline',
                'items' => self::items($user, $ecommerceItems),
            ],
            [
                'label' => 'Mitra',
                'icon' => 'mdi-handshake-outline',
                'items' => self::items($user, [
                    ['Kurir', 'master-data.couriers.index', 'mdi-truck-fast-outline', 'view master-data-couriers'],
                    ['Pemasok', 'master-data.suppliers.index', 'mdi-truck-delivery-outline', 'view master-data-suppliers'],
                    ['Mitra', 'master-data.partners.index', 'mdi-handshake-outline', 'view master-data-partners'],
                    ['Listing Brand', 'management.brand-listings.index', 'mdi-tag-plus-outline', null],
                ]),
            ],
            [
                'label' => 'Promo',
                'icon' => 'mdi-sale-outline',
                'items' => self::items($user, [
                    ['Promo', 'master-data.promos.index', 'mdi-sale-outline', 'view master-data-promos'],
                    ['Diskon', 'master-data.diskons.index', 'mdi-percent-outline', 'view master-data-discounts'],
                    ['Diskon Produk', 'master-data.products-diskons.index', 'mdi-tag-multiple-outline', 'view master-data-product-discounts'],
                    ['Voucher', 'master-data.vouchers.index', 'mdi-ticket-percent-outline', 'view master-data-vouchers'],
                    ['Pengaturan Promo', 'master-data.promo-setup.index', 'mdi-playlist-plus', 'view master-data-promos'],
                ]),
            ],
            [
                'label' => 'Loyalty',
                'icon' => 'mdi-crown-outline',
                'items' => self::items($user, [
                    ['Poin Loyalitas', 'loyalty.customer-loyalty-points.index', 'mdi-star-circle-outline', 'view loyalty-customer-loyalty-points'],
                    ['Mutasi Poin', 'loyalty.customer-loyalty-point-mutations.index', 'mdi-swap-horizontal', 'view loyalty-customer-loyalty-point-mutations'],
                    ['Tingkat Keanggotaan', 'master-data.memberships.index', 'mdi-crown-outline', 'view master-data-memberships'],
                    ['Manfaat Keanggotaan', 'master-data.membership-benefits.index', 'mdi-gift-outline', 'view master-data-membership-benefits'],
                    ['Tarif Poin', 'settings.points.edit', 'mdi-currency-usd', null],
                    ['Hadiah Loyalitas', 'settings.app.customer-loyalty-point-transaction-reward.edit', 'mdi-star-circle-outline', null],
                    ['Afiliasi Pengundang', 'settings.app.affiliate-points.inviter.edit', 'mdi-account-plus-outline', null],
                    ['Afiliasi Undangan', 'settings.app.affiliate-points.invitee.edit', 'mdi-account-multiple-plus-outline', null],
                ]),
            ],
            [
                'label' => 'Master Produk',
                'icon' => 'mdi-package-variant',
                'items' => self::items($user, [
                    ['Produk', 'master-data.products.index', 'mdi-package-variant', 'view master-data-products'],
                    ['Harga Produk', 'master-data.product-prices.index', 'mdi-currency-usd', 'view master-data-product-prices'],
                    ['Spesifikasi Produk', 'master-data.product-specifications.index', 'mdi-format-list-bulleted', 'view master-data-product-specifications'],
                    ['Kategori', 'master-data.product-categories.index', 'mdi-shape-outline', 'view master-data-product-categories'],
                    ['Grup Kategori', 'master-data.product-category-groups.index', 'mdi-layers-outline', 'view master-data-product-categories'],
                    ['Merek', 'master-data.brands.index', 'mdi-tag-outline', 'view master-data-brands'],
                    ['Satuan', 'master-data.units.index', 'mdi-ruler', 'view master-data-units'],
                    ['Kondisi Barang', 'master-data.item-conditions.index', 'mdi-clipboard-check-outline', 'view master-data-item-conditions'],
                ]),
            ],
            [
                'label' => 'Administrasi',
                'icon' => 'mdi-shield-account-outline',
                'items' => self::items($user, [
                    ['Peran Master Data & Report', 'management.roles-backoffice.index', 'mdi-shield-account-outline', null],
                    ['Hak Akses Peran', 'management.role-permissions.index', 'mdi-shield-key-outline', null],
                    ['Peran Pengguna', 'management.user-roles.index', 'mdi-account-key-outline', null],
                    ['Pengguna', 'master-data.users.index', 'mdi-account-multiple-outline', null],
                    ['Integrasi Doku & Biteship', 'settings.integrations.edit', 'mdi-connection', null],
                ]),
            ],
        ];
    }

    private static function reportsGroups(?User $user): array
    {
        return [
            [
                'label' => 'Ringkasan',
                'icon' => 'mdi-view-dashboard-outline',
                'items' => self::items($user, [
                    ['Ringkasan Usaha', 'reports.business-overview.index', 'mdi-view-dashboard-outline', null],
                    ['Pusat Komando', 'monitoring.command-center.index', 'mdi-monitor-dashboard', null],
                    ['Asisten VEE', 'ai-assistant.index', 'mdi-chat-processing-outline', 'view ai-analytics|view ai-assistant'],
                ]),
            ],
            [
                'label' => 'Rekomendasi Cabang',
                'icon' => 'mdi-map-marker-plus-outline',
                'items' => self::items($user, [
                    ['Rekomendasi Cabang Baru', 'monitoring.branch-expansion.index', 'mdi-store-plus-outline', null],
                ]),
            ],
            [
                'label' => 'Penjualan & Pembayaran',
                'icon' => 'mdi-cash-multiple',
                'items' => self::items($user, [
                    ['Omzet', 'monitoring.omset-cabang.index', 'mdi-chart-box-outline', 'view monitoring-omset-cabang'],
                    ['Sample, Gimmick & Gratis', 'monitoring.sample-gimmick-report.index', 'mdi-flask-outline', 'view monitoring-omset-kotor'],
                    ['Kontrol Margin Harga', 'reports.price-margin.index', 'mdi-scale-balance', null],
                    ['Laba Rugi', 'reports.profit-loss.index', 'mdi-chart-waterfall', null],
                    ['Piutang & Hutang', 'reports.receivable-payable.index', 'mdi-cash-clock', null],
                    ['Produk Terlaris (Fast Moving)', 'monitoring.produk-terlaris.index', 'mdi-fire', null],
                    ['Pemasukan & Pembayaran', 'monitoring.payment-income-realtime.index', 'mdi-cash-register', null],
                    ['Metode Pembayaran & Kembalian', 'monitoring.store-payment-transactions.index', 'mdi-credit-card-check-outline', null],
                    ['EDC BCA', 'monitoring.edc-bca.index', 'mdi-credit-card-chip-outline', 'view monitoring-store-payment-transactions'],
                    ['Transaksi Gantung', 'monitoring.pending-transactions.index', 'mdi-timer-alert-outline', null],
                    ['Anomali Transaksi', 'monitoring.transaction-anomalies.index', 'mdi-alert-decagram-outline', null],
                    ['Pembayaran E-commerce', 'monitoring.ecommerce-payment.index', 'mdi-receipt-text-outline', 'view monitoring-ecommerce-payment'],
                    ['SLA Pembayaran', 'monitoring.payment-sla.index', 'mdi-timer-sand-complete', null],
                    ['Shift Kasir', 'monitoring.cashier-shift-report.index', 'mdi-account-clock-outline', null],
                    ['Pembelian per Shift', 'monitoring.shift-purchases.index', 'mdi-basket-outline', 'view monitoring-cashier-shift-report'],
                    ['Void & Cancel', 'monitoring.void-cancel-customer.index', 'mdi-cancel', null],
                ]),
            ],
            [
                'label' => 'Pelanggan, Loyalty & Promo',
                'icon' => 'mdi-account-heart-outline',
                'items' => self::items($user, [
                    ['CRM', 'reports.crm.index', 'mdi-account-heart-outline', null],
                    ['Demografi Pelanggan', 'reports.crm.demographics', 'mdi-account-details-outline', null],
                    ['Preferensi Pelanggan', 'reports.crm.customer-preference.index', 'mdi-account-star-outline', null],
                    ['Riwayat Pelanggan', 'reports.history-customer.index', 'mdi-history', null],
                    ['Loyalty Poin & Voucher per Akun', 'monitoring.loyalty-accounts.index', 'mdi-account-cash-outline', null],
                    ['Promo, Poin, Referral & Voucher', 'monitoring.promo-loyalty.index', 'mdi-sale-outline', null],
                    ['Sinkronisasi Max Diskon', 'monitoring.discount-sync.index', 'mdi-sync', 'view monitoring-diskon'],
                ]),
            ],
            [
                'label' => 'Stok & Opname',
                'icon' => 'mdi-warehouse',
                'items' => self::items($user, [
                    ['Stok per Cabang', 'monitoring.stok-percabang', 'mdi-package-variant-closed', null],
                    ['Stok Warehouse', 'monitoring.stok-wms', 'mdi-warehouse', null],
                    ['Mutasi Stok WMS', 'monitoring.mutasi-stock-wms', 'mdi-swap-horizontal', null],
                    ['Stok Barang Keluar Masuk', 'monitoring.stock-movements.index', 'mdi-swap-vertical-bold', null],
                    ['Stok Opname, Selisih & Penyesuaian', 'monitoring.stock-opname-variance.index', 'mdi-clipboard-text-clock-outline', null],
                    ['Store Stok Opname', 'monitoring.store-stock-opnames.index', 'mdi-clipboard-list-outline', null],
                    ['WMS Stok Opname', 'monitoring.audit-wms-opnames.index', 'mdi-clipboard-check-outline', null],
                    ['Barang Hilang & Rusak WMS', 'monitoring.warehouse-loss-damage.index', 'mdi-package-variant-closed-remove', null],
                    ['Quality Control / Barang Cacat', 'monitoring.quality-control.index', 'mdi-shield-check-outline', null],
                    ['Stok Minimum Cabang', 'monitoring.stok-barier-cabang.index', 'mdi-arrow-collapse-down', null],
                    ['Stok Minimum Gudang', 'monitoring.stok-barier-gudang.index', 'mdi-arrow-collapse-down', null],
                    ['Email Notifikasi Barrier', 'settings.stock-barrier-alerts.edit', 'mdi-email-alert-outline', null],
                    ['Stok Produk', 'monitoring.physical-stocks.index', 'mdi-cube-outline', null],
                    ['Prioritas FIFO & FEFO', 'monitoring.fifo-fefo-priority.index', 'mdi-sort-clock-ascending-outline', null],
                    ['Product Aging', 'monitoring.product-aging.index', 'mdi-calendar-clock', null],
                    ['Slow Moving & Kedaluwarsa', 'monitoring.product-slowmoving.index', 'mdi-timer-sand', null],
                    ['Tracking Barang', 'monitoring.tracking-barang.index', 'mdi-map-marker-path', 'view monitoring-tracking-barang'],
                    ['Anomali Item', 'monitoring.anomali-item.index', 'mdi-package-variant-closed-remove', null],
                ]),
            ],
            [
                'label' => 'Pesanan & Retur',
                'icon' => 'mdi-clipboard-check-outline',
                'items' => self::items($user, [
                    ['Pesanan Cabang', 'reports.order-cabang.index', 'mdi-clipboard-list-outline', null],
                    ['Order Handled', 'monitoring.order-handled.index', 'mdi-clipboard-check-multiple-outline', null],
                    ['Fulfillment & SLA', 'monitoring.fulfillment-control.index', 'mdi-truck-check-outline', null],
                ]),
            ],
            [
                'label' => 'Audit & Akses',
                'icon' => 'mdi-shield-account-outline',
                'items' => self::items($user, [
                    ['Akses Pengguna Harian', 'monitoring.user-monitoring.index', 'mdi-account-clock-outline', null],
                    ['Audit Pengguna Gudang', 'monitoring.warehouse-user-audit.index', 'mdi-warehouse-cog', null],
                    ['Audit Center', 'monitoring.audit-center.index', 'mdi-shield-search', null],
                    ['Kesiapan Presentasi', 'management.presentation-readiness.index', 'mdi-presentation', null],
                    ['Riwayat Perubahan Master', 'monitoring.master-change-history.index', 'mdi-history', null],
                    ['Penyesuaian Harga Overnight', 'monitoring.overnight-price-adjustment.index', 'mdi-weather-night', null],
                    ['Cetak Ulang Label', 'monitoring.barcode-request-report.index', 'mdi-printer-alert', null],
                    ['Kualitas Data', 'monitoring.data-quality.index', 'mdi-database-check-outline', null],
                ]),
            ],
            [
                'label' => 'Lainnya & Ekspor',
                'icon' => 'mdi-file-chart-outline',
                'items' => self::items($user, [
                    ['Produk Pemasok', 'reports.supplier-products.index', 'mdi-truck-outline', 'view supplier-product'],
                    ['Ekspor Saya', 'report-exports.index', 'mdi-file-export-outline', null],
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

            if ($permission && $user) {
                $permissions = explode('|', $permission);

                if (! $user->canAny($permissions)) {
                    continue;
                }
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
