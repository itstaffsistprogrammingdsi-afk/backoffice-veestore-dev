<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\WarehouseOperations\Queries\StoreTransferReconciliationQuery;
use App\Http\Controllers\Controller;
use App\Support\Navigation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class MonitoringCommandCenterController extends Controller
{
    private const AWAITING_SHIPMENT_STATUSES = ['pending', 'paid', 'processed', 'processing', 'packing', 'on_hold'];

    public function index(StoreTransferReconciliationQuery $storeTransfers)
    {
        $monitoringGroups = $this->monitoringGroups();

        return Inertia::render('Monitoring/Index', [
            'monitoringGroups' => $monitoringGroups,
            'reportGroups' => $this->reportGroups(),
            'overviewUrl' => route('reports.business-overview.index'),
            'storeTransferReconciliation' => $storeTransfers->build(null, null, 'unresolved', 5),
            'storeTransferReportUrl' => route('monitoring.warehouse-outbound.index', [
                'reconciliation_status' => 'not_recorded',
            ]),
        ]);
    }

    /**
     * Membaca direktori fitur dari sumber menu yang sama dengan sidebar, agar
     * halaman utama Monitoring selalu menampilkan menu yang aktual.
     */
    private function monitoringGroups(): array
    {
        $menuFile = resource_path('menu/verticalMenu.json');

        if (! File::exists($menuFile)) {
            return [];
        }

        $menuPayload = json_decode(File::get($menuFile), true);
        $monitoring = collect($menuPayload['menu'] ?? [])
            ->first(fn (array $item) => ($item['slug'] ?? null) === 'monitoring');

        if (! is_array($monitoring)) {
            return [];
        }

        $groupIcons = [
            'monitoring-sales-payment' => 'mdi-chart-line',
            'monitoring-branch-expansion' => 'mdi-store-plus-outline',
            'monitoring-orders' => 'mdi-package-variant-closed',
            'monitoring-inventory' => 'mdi-warehouse',
            'monitoring-customer-marketing' => 'mdi-account-heart-outline',
            'monitoring-audit-data' => 'mdi-shield-account-outline',
            'monitoring-supplier' => 'mdi-truck-outline',
        ];

        $groupDescriptions = [
            'monitoring-sales-payment' => 'Pemasukan, pembayaran, transaksi gantung, anomali, omzet, dan transaksi kasir.',
            'monitoring-branch-expansion' => 'Prioritas wilayah pembukaan cabang dari demografi dan penjualan.',
            'monitoring-orders' => 'Pesanan ecommerce, store, retur, serta pemenuhan SLA.',
            'monitoring-inventory' => 'Stok, warehouse, pergerakan barang, dan kontrol kualitas.',
            'monitoring-customer-marketing' => 'Customer, loyalty, voucher per akun, promo, referral, dan afiliasi.',
            'monitoring-audit-data' => 'Audit akses, aktivitas pengguna WMS, absensi, cetak ulang label, dan kualitas data.',
            'monitoring-supplier' => 'Kinerja supplier dan daftar produk supplier.',
        ];

        return collect($monitoring['submenu'] ?? [])
            ->map(function (array $group) use ($groupIcons, $groupDescriptions) {
                $items = collect($group['submenu'] ?? [])
                    ->filter(fn (array $item) => ! empty($item['url']))
                    ->map(fn (array $item) => [
                        'name' => $item['name'] ?? 'Menu monitoring',
                        'url' => $item['url'],
                    ])
                    ->values()
                    ->all();

                return [
                    'slug' => $group['slug'] ?? '',
                    'name' => $group['name'] ?? 'Monitoring',
                    'icon' => $groupIcons[$group['slug'] ?? ''] ?? 'mdi-monitor-eye-outline',
                    'description' => $groupDescriptions[$group['slug'] ?? ''] ?? 'Akses fitur monitoring pada kategori ini.',
                    'source' => 'monitoring',
                    'items' => $items,
                ];
            })
            ->flatMap(function (array $group) {
                if ($group['slug'] !== 'monitoring-sales-payment') {
                    return [$group];
                }

                $paymentItems = collect($group['items'])
                    ->filter(fn (array $item) => preg_match('/payment-income|payment-reconciliation|store-payment|ecommerce-payment|pending-transactions|transaction-anomalies/', $item['url']) === 1)
                    ->values()
                    ->all();

                $performanceItems = collect($group['items'])
                    ->reject(fn (array $item) => in_array($item, $paymentItems, true))
                    ->values()
                    ->all();

                return [
                    [
                        ...$group,
                        'name' => 'Pembayaran & Pemasukan',
                        'icon' => 'mdi-credit-card-check-outline',
                        'description' => 'Pemasukan realtime, rekonsiliasi, transaksi gantung, anomali, dan pembayaran store/ecommerce.',
                        'items' => $paymentItems,
                    ],
                    [
                        ...$group,
                        'slug' => 'monitoring-sales-performance',
                        'name' => 'Omzet & Kasir',
                        'icon' => 'mdi-chart-line',
                        'description' => 'Omzet cabang, omset kotor & bersih, produk terlaris, shift kasir, dan void.',
                        'items' => $performanceItems,
                    ],
                ];
            })
            ->filter(fn (array $group) => ! empty($group['items']))
            ->values()
            ->all();
    }

    /**
     * Reports di sidebar memakai registry route yang sama dengan navigasi
     * aplikasi. Command Center ikut menampilkan registry ini agar report
     * yang tidak berada di verticalMenu.json tetap mudah ditemukan.
     */
    private function reportGroups(): array
    {
        $reportsArea = collect(Navigation::forUser(auth()->user())['areas'] ?? [])
            ->firstWhere('key', 'reports');

        if (! is_array($reportsArea)) {
            return [];
        }

        return collect($reportsArea['groups'] ?? [])
            ->map(function (array $group) {
                $items = collect($group['items'] ?? [])
                    ->reject(fn (array $item) => ($item['route'] ?? null) === 'monitoring.command-center.index')
                    ->map(fn (array $item) => [
                        'name' => $item['label'] ?? 'Laporan',
                        'url' => $item['url'] ?? null,
                    ])
                    ->filter(fn (array $item) => ! empty($item['url']))
                    ->values()
                    ->all();

                return [
                    'slug' => 'reports-'.str($group['label'] ?? 'reports')->slug(),
                    'name' => 'Laporan: '.($group['label'] ?? 'Lainnya'),
                    'icon' => $group['icon'] ?? 'mdi-file-chart-outline',
                    'description' => 'Laporan yang tersedia untuk area ini.',
                    'source' => 'reports',
                    'items' => $items,
                ];
            })
            ->filter(fn (array $group) => $group['items'] !== [])
            ->values()
            ->all();
    }

    public function qualityControl(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'source' => trim((string) $request->query('source', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'condition_id' => trim((string) $request->query('condition_id', '')),
            'status' => trim((string) $request->query('status', '')),
            'date_from' => $this->normalizeDate($request->query('date_from')),
            'date_to' => $this->normalizeDate($request->query('date_to')),
        ];

        $warehouseRows = collect();

        if ($filters['source'] !== 'store') {
            $warehouseRows = DB::table('warehouse_qc as qc')
                ->leftJoin('warehouses as w', 'w.id', '=', 'qc.warehouse_id')
                ->leftJoin('items as i', 'i.id', '=', 'qc.item_id')
                ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
                ->leftJoin('users as u', 'u.id', '=', 'qc.performed_by')
                ->when($filters['warehouse_id'] !== '', fn ($q) => $q->where('qc.warehouse_id', $filters['warehouse_id']))
                ->when($filters['condition_id'] !== '', fn ($q) => $q->where('qc.item_condition_id', $filters['condition_id']))
                ->when($filters['status'] !== '', fn ($q) => $q->whereRaw('qc.status::text = ?', [$filters['status']]))
                ->when($filters['date_from'], fn ($q) => $q->whereRaw('COALESCE(qc.performed_at, qc.created_at)::date >= ?', [$filters['date_from']]))
                ->when($filters['date_to'], fn ($q) => $q->whereRaw('COALESCE(qc.performed_at, qc.created_at)::date <= ?', [$filters['date_to']]))
                ->when($filters['q'] !== '', function ($q) use ($filters) {
                    $like = '%'.$filters['q'].'%';

                    $q->where(function ($search) use ($like) {
                        $search->where('p.name', 'ILIKE', $like)
                            ->orWhere('p.code', 'ILIKE', $like)
                            ->orWhere('w.name', 'ILIKE', $like)
                            ->orWhere('ic.name', 'ILIKE', $like);
                    });
                })
                ->select('qc.*', 'w.name as location_name', 'p.name as product_name', 'p.code as product_code', 'ic.name as condition_name', 'u.name as performer_name')
                ->orderByDesc('qc.performed_at')
                ->limit(100)
                ->get()
                ->map(fn ($row) => tap($row, fn ($item) => $item->source = 'Warehouse'));
        }

        $storeRows = collect();

        if ($filters['source'] !== 'warehouse' && $filters['status'] === '') {
            $storeRows = DB::table('store_qc as qc')
                ->leftJoin('stores as s', 's.id', '=', 'qc.store_id')
                ->leftJoin('items as i', 'i.id', '=', 'qc.item_id')
                ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
                ->leftJoin('users as u', 'u.id', '=', 'qc.performed_by')
                ->when($filters['store_id'] !== '', fn ($q) => $q->where('qc.store_id', $filters['store_id']))
                ->when($filters['condition_id'] !== '', fn ($q) => $q->where('qc.item_condition_id', $filters['condition_id']))
                ->when($filters['date_from'], fn ($q) => $q->whereRaw('COALESCE(qc.performed_at, qc.created_at)::date >= ?', [$filters['date_from']]))
                ->when($filters['date_to'], fn ($q) => $q->whereRaw('COALESCE(qc.performed_at, qc.created_at)::date <= ?', [$filters['date_to']]))
                ->when($filters['q'] !== '', function ($q) use ($filters) {
                    $like = '%'.$filters['q'].'%';

                    $q->where(function ($search) use ($like) {
                        $search->where('p.name', 'ILIKE', $like)
                            ->orWhere('p.code', 'ILIKE', $like)
                            ->orWhere('s.name', 'ILIKE', $like)
                            ->orWhere('ic.name', 'ILIKE', $like);
                    });
                })
                ->select([
                    'qc.id', 'qc.qc_type', 'qc.performed_at', 'qc.created_at',
                    DB::raw('NULL::text as status'), DB::raw('NULL::text as note'),
                    's.name as location_name', 'p.name as product_name', 'p.code as product_code',
                    'ic.name as condition_name', 'u.name as performer_name',
                ])
                ->orderByDesc('qc.performed_at')
                ->limit(100)
                ->get()
                ->map(fn ($row) => tap($row, fn ($item) => $item->source = 'Store'));
        }

        $rows = $warehouseRows->concat($storeRows)
            ->sortByDesc(fn ($row) => $row->performed_at ?? $row->created_at)
            ->take(100)
            ->values();

        $summary = [
            'warehouse' => $rows->where('source', 'Warehouse')->count(),
            'store' => $rows->where('source', 'Store')->count(),
            'rejected' => $rows->filter(fn ($row) => in_array(mb_strtolower((string) ($row->status ?? '')), ['rejected', 'reject', 'failed', 'bad'], true))->count(),
            'today' => $rows->filter(function ($row) {
                $date = $row->performed_at ?? $row->created_at;

                return $date && Carbon::parse($date)->isToday();
            })->count(),
        ];

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $conditions = DB::table('item_conditions')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('warehouse_qc')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return Inertia::render('Monitoring/Report', [
            'title' => 'Operations Quality Control',
            'description' => 'Telusuri hasil QC warehouse dan store beserta kondisinya.',
            'data' => compact(
                'rows',
                'summary',
                'filters',
                'warehouses',
                'stores',
                'conditions',
                'statuses'
            ) + [
                'sources' => [
                    ['value' => 'warehouse', 'label' => 'Warehouse'],
                    ['value' => 'store', 'label' => 'Store'],
                ],
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function paymentReconciliation(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'order_status' => trim((string) $request->query('order_status', '')),
            'payment_status' => trim((string) $request->query('payment_status', '')),
            'reconciliation_status' => trim((string) $request->query('reconciliation_status', '')),
            'exception_only' => $request->boolean('exception_only'),
            'start_date' => $this->normalizeDate($request->query('start_date')),
            'end_date' => $this->normalizeDate($request->query('end_date')),
            'per_page' => $this->normalizePerPage($request->query('per_page'), 20),
        ];

        $baseQuery = DB::table('ecommerce_orders as eo')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->select([
                'eo.id', 'eo.order_number', 'eo.status as order_status', 'eo.grand_total',
                'eo.payment_id', 'eo.payment_method_name', 'eo.created_at', 'c.name as customer_name',
                'p.id as payment_record_id', 'p.transaction_number', 'p.status as payment_status',
                'p.paid_amount', 'p.total_amount', 'p.completed_at',
                DB::raw("CASE
                    WHEN eo.payment_id IS NULL THEN 'Tanpa pembayaran'
                    WHEN p.id IS NULL THEN 'Referensi pembayaran hilang'
                    WHEN ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01 THEN 'Nominal berbeda'
                    WHEN p.status::text NOT IN ('paid','completed','success') THEN 'Pembayaran belum selesai'
                    ELSE 'Sesuai'
                END AS reconciliation_status"),
            ]);

        $this->applySearch($baseQuery, $request, ['eo.order_number', 'p.transaction_number', 'c.name']);

        if ($filters['order_status'] !== '') {
            $baseQuery->whereRaw('eo.status::text = ?', [$filters['order_status']]);
        }

        if ($filters['payment_status'] !== '') {
            $baseQuery->whereRaw('p.status::text = ?', [$filters['payment_status']]);
        }

        if ($filters['start_date']) {
            $baseQuery->whereRaw('eo.created_at::date >= ?', [$filters['start_date']]);
        }

        if ($filters['end_date']) {
            $baseQuery->whereRaw('eo.created_at::date <= ?', [$filters['end_date']]);
        }

        if ($filters['exception_only']) {
            $baseQuery->where(function ($q) {
                $q->whereNull('eo.payment_id')
                    ->orWhereNull('p.id')
                    ->orWhereRaw('ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01')
                    ->orWhereRaw("p.status::text NOT IN ('paid','completed','success')");
            });
        }

        $summaryQuery = DB::query()->fromSub(clone $baseQuery, 'reconciliation');

        if ($filters['reconciliation_status'] !== '') {
            $summaryQuery->where('reconciliation_status', $filters['reconciliation_status']);
        }

        $rowsQuery = DB::query()->fromSub(clone $baseQuery, 'reconciliation');

        if ($filters['reconciliation_status'] !== '') {
            $rowsQuery->where('reconciliation_status', $filters['reconciliation_status']);
        }

        $rows = $rowsQuery
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $summary = [
            'orders' => (clone $summaryQuery)->count(),
            'exceptions' => (clone $summaryQuery)->where('reconciliation_status', '!=', 'Sesuai')->count(),
            'without_payment' => (clone $summaryQuery)->whereNull('payment_id')->count(),
            'pending_payment' => (clone $summaryQuery)
                ->whereNotNull('payment_record_id')
                ->whereRaw("COALESCE(payment_status::text, '') NOT IN ('paid','completed','success')")
                ->count(),
        ];

        $orderStatuses = DB::table('ecommerce_orders')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        $paymentStatuses = DB::table('payments')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return Inertia::render('Monitoring/Report', [
            'title' => 'Operations Payment Reconciliation',
            'description' => 'Cocokkan nilai order dengan pembayaran dan temukan selisih.',
            'data' => compact(
                'rows',
                'summary',
                'filters',
                'orderStatuses',
                'paymentStatuses'
            ) + [
                'reconciliationStatuses' => [
                    'Sesuai',
                    'Tanpa pembayaran',
                    'Referensi pembayaran hilang',
                    'Nominal berbeda',
                    'Pembayaran belum selesai',
                ],
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function fulfillmentControl(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'handled_type' => trim((string) $request->query('handled_type', '')),
            'store_id' => trim((string) $request->query('store_id', '')),
            'warehouse_id' => trim((string) $request->query('warehouse_id', '')),
            'start_date' => $this->normalizeDate($request->query('start_date')),
            'end_date' => $this->normalizeDate($request->query('end_date')),
            'per_page' => $this->normalizePerPage($request->query('per_page'), 20),
        ];

        $awaitingShipmentStatuses = "'".implode("','", self::AWAITING_SHIPMENT_STATUSES)."'";

        $baseQuery = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('stores as s', 's.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'eo.handled_by_warehouse_id')
            ->select(
                'eo.*',
                'c.name as customer_name',
                's.name as store_name',
                'w.name as warehouse_name',
                DB::raw("
                    CASE
                        WHEN LOWER(eo.status::text) IN ({$awaitingShipmentStatuses})
                            AND eo.created_at < NOW() - INTERVAL '24 hours'
                        THEN 'Lewat 24 Jam'
                        ELSE NULL
                    END AS sla_status
                ")
            );

        $this->applySearch($baseQuery, $request, ['eo.order_number', 'c.name', 'eo.shipping_number']);

        if ($filters['status'] !== '') {
            $baseQuery->whereRaw('eo.status::text = ?', [$filters['status']]);
        }

        if ($filters['handled_type'] === 'store') {
            $baseQuery->whereNotNull('eo.handled_by_store_id');
        } elseif ($filters['handled_type'] === 'warehouse') {
            $baseQuery->whereNotNull('eo.handled_by_warehouse_id');
        } elseif ($filters['handled_type'] === 'unhandled') {
            $baseQuery->whereNull('eo.handled_by_store_id')->whereNull('eo.handled_by_warehouse_id');
        }

        if ($filters['store_id'] !== '') {
            $baseQuery->where('eo.handled_by_store_id', $filters['store_id']);
        }

        if ($filters['warehouse_id'] !== '') {
            $baseQuery->where('eo.handled_by_warehouse_id', $filters['warehouse_id']);
        }

        if ($filters['start_date']) {
            $baseQuery->whereRaw('eo.created_at::date >= ?', [$filters['start_date']]);
        }

        if ($filters['end_date']) {
            $baseQuery->whereRaw('eo.created_at::date <= ?', [$filters['end_date']]);
        }

        $summaryQuery = DB::query()->fromSub(clone $baseQuery, 'fulfillment');

        $summary = [
            'orders' => (clone $summaryQuery)->count(),
            'pending' => (clone $summaryQuery)->whereRaw("status::text IN ('pending','unpaid','processing')")->count(),
            'without_handler' => (clone $summaryQuery)
                ->whereNull('handled_by_store_id')
                ->whereNull('handled_by_warehouse_id')
                ->count(),
            'without_awb' => (clone $summaryQuery)
                ->whereRaw("status::text IN ('processed','shipped')")
                ->where(function ($q) {
                    $q->whereNull('shipping_number')->orWhere('shipping_number', '');
                })
                ->count(),
            'over_24h' => (clone $summaryQuery)
                ->whereRaw("LOWER(status::text) IN ({$awaitingShipmentStatuses})")
                ->where('created_at', '<', now()->subDay())
                ->count(),
        ];

        $ecommerce = $baseQuery
            ->orderByDesc('eo.created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $orderStatuses = DB::table('ecommerce_orders')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return Inertia::render('Monitoring/Report', [
            'title' => 'Operations Fulfillment Control',
            'description' => 'Pantau status pemenuhan, penanggung jawab, dan SLA pesanan.',
            'data' => compact(
                'ecommerce',
                'summary',
                'filters',
                'stores',
                'warehouses',
                'orderStatuses'
            ) + [
                'handledTypes' => ['store', 'warehouse', 'unhandled'],
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function auditCenter(Request $request)
    {
        $backoffice = DB::connection('immostore_backoffice');
        $hasAuditLogs = Schema::connection('immostore_backoffice')->hasTable('business_audit_logs');
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'action' => trim((string) $request->query('action', '')),
            'from' => $this->normalizeDate($request->query('from')),
            'to' => $this->normalizeDate($request->query('to')),
            'per_page' => $this->normalizePerPage($request->query('per_page'), 20),
        ];

        $logs = $hasAuditLogs
            ? $backoffice->table('business_audit_logs')
                ->when($filters['q'] !== '', function ($query) use ($filters) {
                    $like = '%'.$filters['q'].'%';
                    $query->where(fn ($q) => $q->where('user_name', 'ILIKE', $like)
                        ->orWhere('module', 'ILIKE', $like)
                        ->orWhere('route_name', 'ILIKE', $like));
                })
                ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
                ->when($filters['from'], fn ($q) => $q->where('occurred_at', '>=', Carbon::parse($filters['from'])->startOfDay()))
                ->when($filters['to'], fn ($q) => $q->where('occurred_at', '<', Carbon::parse($filters['to'])->addDay()->startOfDay()))
                ->orderByDesc('occurred_at')
                ->paginate($filters['per_page'])
                ->withQueryString()
            : null;

        $fraudAlerts = Schema::hasTable('fraud_alerts')
            ? DB::table('fraud_alerts as fa')
                ->leftJoin('stores as s', 's.id', '=', 'fa.store_id')
                ->leftJoin('users as u', 'u.id', '=', 'fa.cashier_user_id')
                ->select('fa.*', 's.name as store_name', 'u.name as cashier_name')
                ->orderByDesc('fa.created_at')->limit(10)->get()
            : collect();

        $actions = $hasAuditLogs
            ? $backoffice->table('business_audit_logs')
                ->selectRaw('DISTINCT action')
                ->whereNotNull('action')
                ->orderBy('action')
                ->pluck('action')
            : collect();

        $summary = [
            'changes_today' => $hasAuditLogs
                ? $backoffice->table('business_audit_logs')
                    ->where('occurred_at', '>=', now()->startOfDay())
                    ->where('occurred_at', '<', now()->addDay()->startOfDay())
                    ->count()
                : 0,
            'failed_changes' => $hasAuditLogs
                ? $backoffice->table('business_audit_logs')->where('response_status', '>=', 400)->count()
                : 0,
            'open_fraud' => $this->countWhere('fraud_alerts', "status::text NOT IN ('resolved','closed','dismissed')"),
            'failed_login' => $this->countWhere('session_logs', "status::text IN ('failed','rejected') AND logged_at >= NOW() - INTERVAL '24 hours'"),
        ];

        return Inertia::render('Monitoring/Report', [
            'title' => 'Audit Center',
            'description' => 'Telusuri aktivitas perubahan data dan peringatan fraud.',
            'data' => compact('logs', 'fraudAlerts', 'summary', 'filters', 'actions'),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function dataQuality()
    {
        $checks = collect([
            $this->qualityCheck('Customer tanpa wilayah lengkap', 'customers', '(province_code IS NULL OR regency_code IS NULL OR district_code IS NULL OR village_code IS NULL)', 'warning', route('master-data.customers.index')),
            $this->qualityCheck('Customer tanpa email', 'customers', "email IS NULL OR email = ''", 'info', route('master-data.customers.index')),
            $this->qualityCheck('Produk tanpa harga', 'products', 'NOT EXISTS (SELECT 1 FROM product_prices pp WHERE pp.product_id = products.id)', 'danger', url('master-data/products')),
            $this->qualityCheck('Produk tanpa gambar', 'products', 'NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = products.id)', 'warning', url('master-data/products')),
            $this->qualityCheck('Store tanpa koordinat', 'stores', 'geom IS NULL', 'warning', url('master-data/stores')),
            $this->qualityCheck('Warehouse tanpa koordinat', 'warehouses', 'geom IS NULL', 'warning', url('master-data/warehouses')),
            $this->qualityCheck('Item tanpa lokasi', 'items', 'current_location_id IS NULL', 'danger', route('monitoring.anomali-item.index')),
            $this->qualityCheck('Order tanpa penanggung jawab', 'ecommerce_orders', 'handled_by_store_id IS NULL AND handled_by_warehouse_id IS NULL', 'danger', route('monitoring.fulfillment-control.index')),
        ])->filter();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Data Quality',
            'description' => 'Laporan monitoring.',
            'data' => compact('checks'),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePerPage(mixed $value, int $default = 20): int
    {
        $perPage = (int) ($value ?: $default);

        return in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : $default;
    }

    private function countWhere(string $table, string $condition): int
    {
        return Schema::hasTable($table) ? DB::table($table)->whereRaw($condition)->count() : 0;
    }

    private function applySearch($query, Request $request, array $columns): void
    {
        if (! $request->filled('q')) {
            return;
        }

        $like = '%'.$request->string('q')->toString().'%';
        $query->where(function ($sub) use ($columns, $like) {
            foreach ($columns as $index => $column) {
                $index === 0 ? $sub->where($column, 'ILIKE', $like) : $sub->orWhere($column, 'ILIKE', $like);
            }
        });
    }

    private function qualityCheck(string $title, string $table, string $condition, string $severity, string $url): ?array
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        return compact('title', 'severity', 'url') + [
            'count' => DB::table($table)->whereRaw($condition)->count(),
            'total' => DB::table($table)->count(),
        ];
    }
}
