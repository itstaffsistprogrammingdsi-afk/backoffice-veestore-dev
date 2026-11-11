<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BranchOperationNotificationService
{
    public function __construct(
        private readonly EcommerceOrderStockAlertService $ecommerceStockAlerts
    ) {}

    public function summary(): array
    {
        $definitions = [
            'orders' => [
                'table' => 'store_orders',
                'statuses' => ['requested'],
                'menu_slug' => 'branch-order-cabang',
                'title' => 'Order Store Menunggu Approval',
                'subtitle' => 'order baru perlu ditinjau',
                'icon' => 'mdi-cart-arrow-down',
                'severity' => 'warning',
                'url' => route('operations.store-orders.index'),
            ],
            'returns' => [
                'table' => 'store_returns',
                'statuses' => ['requested', 'pending_approval'],
                'menu_slug' => 'branch-retur-cabang',
                'title' => 'Retur Store Menunggu Approval',
                'subtitle' => 'retur perlu ditinjau',
                'icon' => 'mdi-backup-restore',
                'severity' => 'danger',
                'url' => route('operations.store-returns.index'),
            ],
            'label' => [
                'table' => 'store_label_print_requests',
                'statuses' => ['pending'],
                'menu_slug' => 'management-request-labels',
                'title' => 'Request Label Baru',
                'subtitle' => 'request label menunggu keputusan',
                'icon' => 'mdi-tag-text-outline',
                'severity' => 'info',
                'url' => route('management.request-labels.index', ['status' => 'pending']),
            ],
            'voucher' => [
                'table' => 'store_voucher_requests',
                'statuses' => ['pending'],
                'menu_slug' => 'branch-request-voucher',
                'title' => 'Request Voucher Baru',
                'subtitle' => 'request voucher menunggu approval',
                'icon' => 'mdi-ticket-percent-outline',
                'severity' => 'primary',
                'url' => route('branch-operations.request-voucher.index', ['status' => 'pending']),
            ],
        ];

        $menuCounts = [];
        $notifications = [];
        $readFingerprints = $this->readFingerprints();
        $snapshot = $this->sharedSnapshot($definitions);

        foreach ($definitions as $key => $definition) {
            [$count, $fingerprint] = $snapshot['branches'][$key] ?? [0, null];

            $isRead = ($readFingerprints['branch-'.$key] ?? null) === $fingerprint;
            $menuCounts[$definition['menu_slug']] = $isRead ? 0 : $count;

            if ($count === 0 || $isRead) {
                continue;
            }

            $notifications[] = [
                'key' => 'branch-'.$key,
                'fingerprint' => $fingerprint,
                'count' => $count,
                'menu_slug' => $definition['menu_slug'],
                'title' => $definition['title'],
                'subtitle' => number_format($count, 0, ',', '.')
                    .' '.$definition['subtitle'],
                'icon' => $definition['icon'],
                'severity' => $definition['severity'],
                'url' => $definition['url'],
                'open_url' => route('notifications.open', [
                    'key' => 'branch-'.$key,
                    'redirect' => $definition['url'],
                ]),
                'time' => 'Diperbarui '.$snapshot['time'],
            ];
        }

        $branchOperationsCount = array_sum(array_intersect_key($menuCounts, array_flip([
            'branch-order-cabang',
            'branch-retur-cabang',
            'branch-request-voucher',
        ])));
        $menuCounts['management'] = $menuCounts['management-request-labels'] ?? 0;
        [$ecommerceStockIssueCount, $ecommerceFingerprint] = $snapshot['ecommerce'];
        $ecommerceIsRead = ($readFingerprints['ecommerce-handler-stock'] ?? null) === $ecommerceFingerprint;
        $menuCounts['ecommerce-orders'] = $ecommerceIsRead ? 0 : $ecommerceStockIssueCount;

        if ($ecommerceStockIssueCount > 0 && ! $ecommerceIsRead) {
            $notifications[] = [
                'key' => 'ecommerce-handler-stock',
                'fingerprint' => $ecommerceFingerprint,
                'count' => $ecommerceStockIssueCount,
                'menu_slug' => 'ecommerce-orders',
                'title' => 'Handler Ecommerce Kekurangan Stok',
                'subtitle' => number_format($ecommerceStockIssueCount, 0, ',', '.')
                    .' order perlu dipindahkan ke Store/Warehouse lain',
                'icon' => 'mdi-package-variant-remove',
                'severity' => 'danger',
                'url' => route('ecommerce.orders.index', ['stock_issue' => 1]),
                'open_url' => route('notifications.open', [
                    'key' => 'ecommerce-handler-stock',
                    'redirect' => route('ecommerce.orders.index', ['stock_issue' => 1]),
                ]),
                'time' => 'Diperbarui '.$snapshot['time'],
            ];
        }

        $rejectionHandoffEnabled = (bool) config(
            'backoffice.ecommerce_rejection_handoff_enabled',
            false
        );
        [$rejectionCount, $rejectionFingerprint] = $snapshot['rejections'] ?? [0, null];
        $rejectionIsRead = ($readFingerprints['ecommerce-rejection'] ?? null) === $rejectionFingerprint;

        if ($rejectionHandoffEnabled && $rejectionCount > 0 && ! $rejectionIsRead) {
            $notifications[] = [
                'key' => 'ecommerce-rejection',
                'fingerprint' => $rejectionFingerprint,
                'count' => $rejectionCount,
                'menu_slug' => 'ecommerce-rejections',
                'title' => 'Kendala E-commerce Menunggu Keputusan',
                'subtitle' => number_format($rejectionCount, 0, ',', '.')
                    .' pengajuan kendala dari toko',
                'icon' => 'mdi-alert-decagram-outline',
                'severity' => 'danger',
                'url' => route('ecommerce.rejections.index'),
                'open_url' => route('notifications.open', [
                    'key' => 'ecommerce-rejection',
                    'redirect' => route('ecommerce.rejections.index'),
                ]),
                'time' => 'Diperbarui '.$snapshot['time'],
            ];
        }

        foreach ($this->stockBarrierSnapshots() as $scope => [$count, $fingerprint]) {
            $key = 'stock-barrier-'.$scope;
            $isRead = ($readFingerprints[$key] ?? null) === $fingerprint;

            if ($count === 0 || $isRead) {
                continue;
            }

            $isStore = $scope === 'store';
            $url = $isStore
                ? route('operations.store-stock.index')
                : route('operations.stock.index');
            $title = $isStore
                ? 'Stok Store Menyentuh Barrier'
                : 'Stok Warehouse Menyentuh Barrier';

            $notifications[] = [
                'key' => $key,
                'fingerprint' => $fingerprint,
                'count' => $count,
                'menu_slug' => $isStore ? 'operations-store-stock' : 'operations-stock',
                'title' => $title,
                'subtitle' => number_format($count, 0, ',', '.').' produk perlu ditinjau',
                'icon' => 'mdi-package-variant-closed-alert',
                'severity' => 'danger',
                'url' => $url,
                'open_url' => route('notifications.open', [
                    'key' => $key,
                    'redirect' => $url,
                ]),
                'time' => 'Stok tersedia sudah mencapai ambang barrier',
            ];
        }

        return [
            // Navbar badge represents notification items/categories, while each
            // item retains its own underlying order/request count.
            'total_count' => count($notifications),
            'category_count' => count($notifications),
            'menu_counts' => $menuCounts,
            'branch_operations_count' => $branchOperationsCount,
            'notifications' => $notifications,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    public function markRead(string $notificationKey, ?string $userId = null): void
    {
        $userId ??= auth()->id();
        $connection = 'immostore_backoffice';

        if (! $userId || ! Schema::connection($connection)->hasTable('notification_reads')) {
            return;
        }

        $summary = $this->summary();
        $notification = collect($summary['notifications'])
            ->firstWhere('key', $notificationKey);

        if (! $notification) {
            return;
        }

        $now = now();
        DB::connection($connection)->table('notification_reads')->upsert([
            [
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'notification_key' => $notificationKey,
                'fingerprint' => $notification['fingerprint'],
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id', 'notification_key'], ['fingerprint', 'read_at', 'updated_at']);
    }

    private function statusSnapshot(string $table, array $statuses): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'status')) {
            return [0, null];
        }

        $ids = DB::table($table)
            ->whereIn(DB::raw('LOWER((status)::text)'), $statuses)
            ->when(
                Schema::hasColumn($table, 'deleted_at'),
                fn ($query) => $query->whereNull('deleted_at')
            )
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return [count($ids), $this->fingerprint($ids)];
    }

    private function sharedSnapshot(array $definitions): array
    {
        // Cache only business data. URLs and user read state are assembled per request.
        $key = 'notifications:operational:v2:'.hash('sha256', json_encode([
            config('database.default'),
            config('database.connections.'.config('database.default').'.database'),
            config('backoffice.ecommerce_rejection_handoff_enabled', false),
            collect($definitions)->map(fn (array $definition) => [
                $definition['table'],
                $definition['statuses'],
            ])->all(),
        ]));
        $cached = Cache::get($key);
        if ($cached && $cached['fresh_until'] > time()) {
            return $cached;
        }

        $lock = Cache::lock($key.':refresh', 120);
        if (! $lock->get()) {
            // Serve the previous snapshot during refresh instead of repeating expensive queries.
            if ($cached) {
                return $cached;
            }
            throw new \RuntimeException('Ringkasan notifikasi sedang disiapkan.');
        }

        try {
            $latest = Cache::get($key);
            if ($latest && $latest['fresh_until'] > time()) {
                return $latest;
            }
            $branches = [];
            foreach ($definitions as $name => $definition) {
                $branches[$name] = $this->statusSnapshot($definition['table'], $definition['statuses']);
            }
            $ids = $this->ecommerceStockAlerts->allIssueOrderIds();
            $result = [
                'branches' => $branches,
                'ecommerce' => [count($ids), $this->fingerprint($ids)],
                'rejections' => $this->rejectionSnapshot(),
                'time' => now()->format('H:i:s'),
                'fresh_until' => time() + 30,
            ];
            Cache::put($key, $result, 300);

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function readFingerprints(): array
    {
        $userId = auth()->id();
        $connection = 'immostore_backoffice';

        if (! $userId || ! Schema::connection($connection)->hasTable('notification_reads')) {
            return [];
        }

        return DB::connection($connection)->table('notification_reads')
            ->where('user_id', $userId)
            ->pluck('fingerprint', 'notification_key')
            ->all();
    }

    /** @return array<string, array{0: int, 1: ?string}> */
    private function stockBarrierSnapshots(): array
    {
        $connection = 'immostore_backoffice';

        if (! Schema::connection($connection)->hasTable('stock_barrier_alert_states')) {
            return [];
        }

        $snapshots = DB::connection($connection)->table('stock_barrier_alert_states')
            ->where('is_active', true)
            ->select('scope_type')
            ->selectRaw('COUNT(*)::int AS alert_count, MAX(activated_at) AS latest_activation')
            ->groupBy('scope_type')
            ->get()
            ->keyBy('scope_type');

        return collect(['store', 'warehouse'])
            ->mapWithKeys(function (string $scope) use ($snapshots): array {
                $snapshot = $snapshots->get($scope);
                $count = (int) ($snapshot->alert_count ?? 0);
                $activation = $snapshot->latest_activation ?? null;
                $fingerprint = $count > 0
                    ? hash('sha256', $count.'|'.$activation)
                    : null;

                return [$scope => [$count, $fingerprint]];
            })
            ->all();
    }

    /** @return array{0: int, 1: ?string} */
    private function rejectionSnapshot(): array
    {
        if (! config('backoffice.ecommerce_rejection_handoff_enabled', false)) {
            return [0, null];
        }

        try {
            $ids = DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('status', 'pending_backoffice_review')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        } catch (\Throwable $exception) {
            report($exception);

            return [0, null];
        }

        return [count($ids), $this->fingerprint($ids)];
    }

    private function fingerprint(array $ids): ?string
    {
        sort($ids, SORT_STRING);

        return $ids === [] ? null : hash('sha256', implode('|', $ids));
    }
}
