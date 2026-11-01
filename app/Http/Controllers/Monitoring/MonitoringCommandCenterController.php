<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MonitoringCommandCenterController extends Controller
{
    public function index()
    {
        $monitoringGroups = $this->monitoringGroups();

        return view('content.monitoring.command-center.index', compact('monitoringGroups'));
    }

    /**
     * Membaca direktori fitur dari sumber menu yang sama dengan sidebar, agar
     * halaman utama Monitoring selalu menampilkan menu yang aktual.
     */
    private function monitoringGroups(): array
    {
        $menuFile = resource_path('menu/verticalMenu.json');

        if (!File::exists($menuFile)) {
            return [];
        }

        $menuPayload = json_decode(File::get($menuFile), true);
        $monitoring = collect($menuPayload['menu'] ?? [])
            ->first(fn (array $item) => ($item['slug'] ?? null) === 'monitoring');

        if (!is_array($monitoring)) {
            return [];
        }

        $groupIcons = [
            'monitoring-sales-payment' => 'mdi-chart-line',
            'monitoring-orders' => 'mdi-package-variant-closed',
            'monitoring-inventory' => 'mdi-warehouse',
            'monitoring-customer-marketing' => 'mdi-account-heart-outline',
            'monitoring-audit-data' => 'mdi-shield-account-outline',
            'monitoring-supplier' => 'mdi-truck-outline',
        ];

        $groupDescriptions = [
            'monitoring-sales-payment' => 'Pemasukan, pembayaran, omzet, dan transaksi kasir.',
            'monitoring-orders' => 'Pesanan ecommerce, store, retur, serta pemenuhan SLA.',
            'monitoring-inventory' => 'Stok, warehouse, pergerakan barang, dan kontrol kualitas.',
            'monitoring-customer-marketing' => 'Customer, loyalty, promo, voucher, dan permintaan barcode.',
            'monitoring-audit-data' => 'Audit akses, aktivitas pengguna, absensi, dan kualitas data.',
            'monitoring-supplier' => 'Kinerja supplier dan daftar produk supplier.',
        ];

        return collect($monitoring['submenu'] ?? [])
            ->map(function (array $group) use ($groupIcons, $groupDescriptions) {
                $items = collect($group['submenu'] ?? [])
                    ->filter(fn (array $item) => !empty($item['url']))
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
                    'items' => $items,
                ];
            })
            ->filter(fn (array $group) => !empty($group['items']))
            ->values()
            ->all();
    }

    public function warehouseOutbound(Request $request)
    {
        $query = DB::table('warehouse_outbounds as wo')
            ->leftJoin('warehouses as w', 'w.id', '=', 'wo.warehouse_id')
            ->leftJoin('couriers as c', 'c.id', '=', 'wo.courier_id')
            ->leftJoin('users as u', 'u.id', '=', 'wo.user_id')
            ->select([
                'wo.*', 'w.name as warehouse_name', 'c.name as courier_name', 'u.name as user_name',
                DB::raw('(SELECT COUNT(*) FROM warehouse_outbound_details wod WHERE wod.warehouse_outbound_id = wo.id) AS scanned_items'),
            ]);

        $this->applySearch($query, $request, ['wo.invoice_number', 'wo.delivery_order_number', 'wo.order_number', 'wo.shipping_number']);
        if ($request->filled('status')) {
            $request->string('status')->toString() === 'released'
                ? $query->whereNotNull('wo.released_at')
                : $query->whereNull('wo.released_at');
        }

        $rows = $query->orderByDesc('wo.created_at')->paginate(20)->withQueryString();
        $summary = [
            'total' => $this->countTable('warehouse_outbounds'),
            'unreleased' => $this->countWhere('warehouse_outbounds', 'released_at IS NULL'),
            'without_awb' => $this->countWhere('warehouse_outbounds', "released_at IS NOT NULL AND NULLIF(shipping_number, '') IS NULL"),
            'today' => $this->countWhere('warehouse_outbounds', 'created_at::date = CURRENT_DATE'),
        ];

        return view('content.monitoring.operations.warehouse-outbound', compact('rows', 'summary'));
    }

    public function qualityControl(Request $request)
    {
        $warehouseRows = DB::table('warehouse_qc as qc')
            ->leftJoin('warehouses as w', 'w.id', '=', 'qc.warehouse_id')
            ->leftJoin('items as i', 'i.id', '=', 'qc.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
            ->leftJoin('users as u', 'u.id', '=', 'qc.performed_by')
            ->select('qc.*', 'w.name as location_name', 'p.name as product_name', 'p.code as product_code', 'ic.name as condition_name', 'u.name as performer_name')
            ->orderByDesc('qc.performed_at')
            ->limit(100)
            ->get()
            ->map(fn ($row) => tap($row, fn ($item) => $item->source = 'Warehouse'));

        $storeRows = DB::table('store_qc as qc')
            ->leftJoin('stores as s', 's.id', '=', 'qc.store_id')
            ->leftJoin('items as i', 'i.id', '=', 'qc.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'qc.item_condition_id')
            ->leftJoin('users as u', 'u.id', '=', 'qc.performed_by')
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

        $rows = $warehouseRows->concat($storeRows)
            ->when($request->filled('q'), function ($items) use ($request) {
                $needle = mb_strtolower($request->string('q')->toString());
                return $items->filter(fn ($row) => str_contains(mb_strtolower(implode(' ', [
                    $row->product_name, $row->product_code, $row->location_name, $row->condition_name,
                ])), $needle));
            })
            ->sortByDesc(fn ($row) => $row->performed_at ?? $row->created_at)
            ->take(100)
            ->values();

        $summary = [
            'warehouse' => $this->countTable('warehouse_qc'),
            'store' => $this->countTable('store_qc'),
            'rejected' => $this->countWhere('warehouse_qc', "status::text IN ('rejected','reject','failed','bad')"),
            'today' => $this->countWhere('warehouse_qc', 'COALESCE(performed_at, created_at)::date = CURRENT_DATE')
                + $this->countWhere('store_qc', 'COALESCE(performed_at, created_at)::date = CURRENT_DATE'),
        ];

        return view('content.monitoring.operations.quality-control', compact('rows', 'summary'));
    }

    public function paymentReconciliation(Request $request)
    {
        $query = DB::table('ecommerce_orders as eo')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->select([
                'eo.id', 'eo.order_number', 'eo.status as order_status', 'eo.grand_total',
                'eo.payment_method_name', 'eo.created_at', 'c.name as customer_name',
                'p.transaction_number', 'p.status as payment_status', 'p.paid_amount',
                'p.total_amount', 'p.completed_at',
                DB::raw("CASE
                    WHEN eo.payment_id IS NULL THEN 'Tanpa pembayaran'
                    WHEN p.id IS NULL THEN 'Referensi pembayaran hilang'
                    WHEN ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01 THEN 'Nominal berbeda'
                    WHEN p.status::text NOT IN ('paid','completed','success') THEN 'Pembayaran belum selesai'
                    ELSE 'Sesuai'
                END AS reconciliation_status"),
            ]);

        $this->applySearch($query, $request, ['eo.order_number', 'p.transaction_number', 'c.name']);
        if ($request->filled('exception_only')) {
            $query->where(function ($q) {
                $q->whereNull('eo.payment_id')
                    ->orWhereNull('p.id')
                    ->orWhereRaw('ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01')
                    ->orWhereRaw("p.status::text NOT IN ('paid','completed','success')");
            });
        }

        $rows = $query->orderByDesc('eo.created_at')->paginate(20)->withQueryString();
        $summary = [
            'orders' => $this->countTable('ecommerce_orders'),
            'without_payment' => $this->countWhere('ecommerce_orders', 'payment_id IS NULL'),
            'pending_payment' => $this->countWhere('payments', "status::text NOT IN ('paid','completed','success') AND deleted_at IS NULL"),
            'voided' => $this->countWhere('payments', "void_status IS NOT NULL AND void_status NOT IN ('none','')"),
        ];

        return view('content.monitoring.operations.payment-reconciliation', compact('rows', 'summary'));
    }

    public function fulfillmentControl(Request $request)
    {
        $ecommerce = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('stores as s', 's.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'eo.handled_by_warehouse_id')
            ->select('eo.*', 'c.name as customer_name', 's.name as store_name', 'w.name as warehouse_name')
            ->orderByDesc('eo.created_at')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'pending' => $this->countWhere('ecommerce_orders', "status::text IN ('pending','unpaid','processing')"),
            'without_handler' => $this->countWhere('ecommerce_orders', 'handled_by_store_id IS NULL AND handled_by_warehouse_id IS NULL'),
            'without_awb' => $this->countWhere('ecommerce_orders', "status::text IN ('processed','shipped') AND NULLIF(shipping_number, '') IS NULL"),
            'over_24h' => $this->countWhere('ecommerce_orders', "status::text IN ('pending','processing','paid') AND created_at < NOW() - INTERVAL '24 hours'"),
        ];

        return view('content.monitoring.operations.fulfillment-control', compact('ecommerce', 'summary'));
    }

    public function auditCenter(Request $request)
    {
        $logs = Schema::hasTable('business_audit_logs')
            ? DB::table('business_audit_logs')
                ->when($request->filled('q'), function ($query) use ($request) {
                    $like = '%' . $request->string('q')->toString() . '%';
                    $query->where(fn ($q) => $q->where('user_name', 'ILIKE', $like)
                        ->orWhere('module', 'ILIKE', $like)
                        ->orWhere('route_name', 'ILIKE', $like));
                })
                ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')->toString()))
                ->orderByDesc('occurred_at')
                ->paginate(20)
                ->withQueryString()
            : null;

        $fraudAlerts = Schema::hasTable('fraud_alerts')
            ? DB::table('fraud_alerts as fa')
                ->leftJoin('stores as s', 's.id', '=', 'fa.store_id')
                ->leftJoin('users as u', 'u.id', '=', 'fa.cashier_user_id')
                ->select('fa.*', 's.name as store_name', 'u.name as cashier_name')
                ->orderByDesc('fa.created_at')->limit(10)->get()
            : collect();

        $summary = [
            'changes_today' => $this->countWhere('business_audit_logs', 'occurred_at::date = CURRENT_DATE'),
            'failed_changes' => $this->countWhere('business_audit_logs', 'response_status >= 400'),
            'open_fraud' => $this->countWhere('fraud_alerts', "status::text NOT IN ('resolved','closed','dismissed')"),
            'failed_login' => $this->countWhere('session_logs', "status::text IN ('failed','rejected') AND logged_at >= NOW() - INTERVAL '24 hours'"),
        ];

        return view('content.monitoring.audit-center.index', compact('logs', 'fraudAlerts', 'summary'));
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

        return view('content.monitoring.data-quality.index', compact('checks'));
    }

    private function systemHealth(): array
    {
        $sources = [
            ['name' => 'POS / Store', 'table' => 'payments', 'column' => 'created_at'],
            ['name' => 'Ecommerce', 'table' => 'ecommerce_orders', 'column' => 'created_at'],
            ['name' => 'Warehouse', 'table' => 'warehouse_qc', 'column' => 'created_at'],
            ['name' => 'Aktivitas User', 'table' => 'session_logs', 'column' => 'logged_at'],
        ];

        return collect($sources)->map(function ($source) {
            $last = Schema::hasTable($source['table'])
                ? DB::table($source['table'])->max($source['column'])
                : null;
            $age = $last ? now()->diffInHours(\Carbon\Carbon::parse($last)) : null;

            return $source + [
                'last_activity' => $last,
                'status' => $age === null ? 'Belum ada data' : ($age <= 24 ? 'Aktif' : ($age <= 72 ? 'Perlu dicek' : 'Tidak aktif')),
                'tone' => $age === null ? 'secondary' : ($age <= 24 ? 'success' : ($age <= 72 ? 'warning' : 'danger')),
            ];
        })->all();
    }

    private function pushAlert($alerts, int $count, string $severity, string $title, string $description, string $url): void
    {
        if ($count > 0) {
            $alerts->push(compact('count', 'severity', 'title', 'description', 'url'));
        }
    }

    private function countTable(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }

    private function countWhere(string $table, string $condition): int
    {
        return Schema::hasTable($table) ? DB::table($table)->whereRaw($condition)->count() : 0;
    }

    private function applySearch($query, Request $request, array $columns): void
    {
        if (!$request->filled('q')) {
            return;
        }

        $like = '%' . $request->string('q')->toString() . '%';
        $query->where(function ($sub) use ($columns, $like) {
            foreach ($columns as $index => $column) {
                $index === 0 ? $sub->where($column, 'ILIKE', $like) : $sub->orWhere($column, 'ILIKE', $like);
            }
        });
    }

    private function qualityCheck(string $title, string $table, string $condition, string $severity, string $url): ?array
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        return compact('title', 'severity', 'url') + [
            'count' => DB::table($table)->whereRaw($condition)->count(),
            'total' => DB::table($table)->count(),
        ];
    }
}
