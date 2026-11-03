<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class EcommerceOrderFulfillmentController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Monitoring/Report', [
            'title' => 'Ecommerce Order Fulfillment',
            'description' => 'Laporan monitoring.',
            'data' => $this->report($request),
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.ecommerce-order-fulfillment.export')],
        ]);
    }

    public function report(Request $request): array
    {
        $filters = $this->filters($request);

        $query = $this->baseQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $query, 'x')
            ->selectRaw('
                COUNT(DISTINCT x.order_id)::int as total_order,
                COUNT(*)::int as total_product_row,
                COALESCE(SUM(x.ordered_qty), 0)::int as total_ordered_qty,
                COALESCE(SUM(x.issued_qty), 0)::int as total_issued_qty,
                COALESCE(SUM(x.remaining_qty), 0)::int as total_remaining_qty
            ')
            ->first();

        $rows = $query
            ->orderByDesc('order_created_at')
            ->orderBy('order_number')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $storesQuery = DB::table('stores')
            ->select('id', 'name');

        $this->applyDeletedAtFilter($storesQuery, 'stores');

        $stores = $storesQuery
            ->orderBy('name')
            ->get();

        $warehousesQuery = DB::table('warehouses')
            ->select('id', 'name');

        $this->applyDeletedAtFilter($warehousesQuery, 'warehouses');

        $warehouses = $warehousesQuery
            ->orderBy('name')
            ->get();

        $orderStatuses = DB::table('ecommerce_orders')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        return [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'orderStatuses' => $orderStatuses,
        ];
    }

    public function export(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Export default hari ini
        |--------------------------------------------------------------------------
        | Jika user tidak mengirim start_date / end_date, export juga otomatis
        | memakai tanggal hari ini agar sama dengan data yang tampil di halaman.
        */
        $filters = $this->filters($request);

        $rows = $this->baseQuery($filters)
            ->orderByDesc('order_created_at')
            ->orderBy('order_number')
            ->get();

        $filename = 'ecommerce-order-fulfillment-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new class($rows) implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
            {
                private int $no = 0;

                public function __construct(private Collection $rows) {}

                public function collection()
                {
                    return $this->rows;
                }

                public function headings(): array
                {
                    return [
                        'No',
                        'Tanggal Order',
                        'Jam Order',
                        'Order Number',
                        'Customer',
                        'Email',
                        'No. WA',
                        'Order Status',
                        'Handled Type',
                        'Handled By',
                        'Store Keluar',
                        'Kode Produk',
                        'Nama Produk',
                        'Qty Pesanan',
                        'Qty Keluar',
                        'Sisa / Belum Keluar',
                        'RFID Tag Keluar',
                    ];
                }

                public function map($row): array
                {
                    $this->no++;

                    return [
                        $this->no,
                        $row->order_date ?? '-',
                        $row->order_time ?? '-',
                        $row->order_number ?? '-',
                        $row->customer_name ?? '-',
                        $row->customer_email ?? '-',
                        $row->wa_number ?? '-',
                        $row->order_status ?? '-',
                        $row->handled_type ?? '-',
                        $row->handled_by_name ?? '-',
                        $row->outbound_store_names ?? '-',
                        $row->product_code ?? '-',
                        $row->product_name ?? '-',
                        (int) ($row->ordered_qty ?? 0),
                        (int) ($row->issued_qty ?? 0),
                        (int) ($row->remaining_qty ?? 0),
                        $row->rfid_tags ?? '-',
                    ];
                }
            },
            $filename
        );
    }

    private function filters(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Default data hari ini
        |--------------------------------------------------------------------------
        | Sebelumnya start_date dan end_date bisa kosong, sehingga data yang tampil
        | bisa semua data. Sekarang default otomatis hari ini.
        */
        $today = now()->toDateString();

        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $startDate = $this->normalizeDate($request->query('start_date')) ?: $today;
        $endDate = $this->normalizeDate($request->query('end_date')) ?: $today;

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [
            'order_number' => trim((string) $request->query('order_number')),
            'customer_name' => trim((string) $request->query('customer_name')),
            'wa_number' => trim((string) $request->query('wa_number')),
            'order_status' => $this->normalizeNullableString($request->query('order_status')),
            'handled_type' => $this->normalizeHandledType($request->query('handled_type')),
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'warehouse_id' => $this->normalizeNullableString($request->query('warehouse_id')),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'per_page' => $perPage,
            'today' => $today,
        ];
    }

    private function baseQuery(array $filters)
    {
        $orderNumber = $filters['order_number'] ?? '';
        $customerName = $filters['customer_name'] ?? '';
        $waNumber = $filters['wa_number'] ?? '';
        $orderStatus = $filters['order_status'] ?? null;
        $handledType = $filters['handled_type'] ?? null;
        $storeId = $filters['store_id'] ?? null;
        $warehouseId = $filters['warehouse_id'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $handledTypeSql = "
            CASE
                WHEN eo.handled_by_store_id IS NOT NULL THEN 'Store'
                WHEN eo.handled_by_warehouse_id IS NOT NULL THEN 'Warehouse'
                ELSE '-'
            END
        ";

        $handledByNameSql = "
            CASE
                WHEN eo.handled_by_store_id IS NOT NULL THEN COALESCE(hs.name, '-')
                WHEN eo.handled_by_warehouse_id IS NOT NULL THEN COALESCE(hw.name, '-')
                ELSE '-'
            END
        ";

        $rfidColumn = $this->rfidTagColumnExpression();

        return DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('stores as hs', 'hs.id', '=', 'eo.handled_by_store_id')
            ->leftJoin('warehouses as hw', 'hw.id', '=', 'eo.handled_by_warehouse_id')
            ->leftJoin('ecommerce_order_details as eod', 'eod.ecommerce_order_id', '=', 'eo.id')
            ->leftJoin('products as op', 'op.id', '=', 'eod.product_id')
            ->leftJoin('store_outbounds as so', 'so.ecommerce_order_id', '=', 'eo.id')
            ->leftJoin('stores as os', 'os.id', '=', 'so.store_id')
            ->leftJoin('store_outbound_details as sod', 'sod.store_outbound_id', '=', 'so.id')
            ->leftJoin('items as i', function ($join) {
                $join->on('i.id', '=', 'sod.item_id')
                    ->on('i.product_id', '=', 'eod.product_id');
            })
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'i.rfid_tag_id')
            ->select([
                'eo.id as order_id',
                'eo.order_number',
                'eo.status as order_status',
                'eo.created_at as order_created_at',
                DB::raw('DATE(eo.created_at) as order_date'),
                DB::raw("TO_CHAR(eo.created_at, 'HH24:MI:SS') as order_time"),

                DB::raw("COALESCE(c.name, '-') as customer_name"),
                DB::raw("COALESCE(c.email, '-') as customer_email"),
                DB::raw("COALESCE(c.wa_number, '-') as wa_number"),

                DB::raw("{$handledTypeSql} as handled_type"),
                DB::raw("{$handledByNameSql} as handled_by_name"),

                'eod.id as ecommerce_order_detail_id',
                'eod.product_id',
                DB::raw("COALESCE(op.code, '-') as product_code"),
                DB::raw("COALESCE(op.name, '-') as product_name"),

                DB::raw('COALESCE(MAX(eod.quantity), 0)::int as ordered_qty'),
                DB::raw('COUNT(DISTINCT i.id)::int as issued_qty'),
                DB::raw('
                    GREATEST(
                        COALESCE(MAX(eod.quantity), 0)::int - COUNT(DISTINCT i.id)::int,
                        0
                    ) as remaining_qty
                '),

                DB::raw("COALESCE(STRING_AGG(DISTINCT NULLIF({$rfidColumn}, ''), ', '), '-') as rfid_tags"),
                DB::raw("COALESCE(STRING_AGG(DISTINCT os.name, ', '), '-') as outbound_store_names"),
            ])
            ->whereNotNull('eod.id')

            /*
            |--------------------------------------------------------------------------
            | Filter default hari ini
            |--------------------------------------------------------------------------
            | Semua data yang muncul mengikuti tanggal order eo.created_at.
            */
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('eo.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('eo.created_at', '<=', $endDate);
            })

            ->when($orderNumber !== '', function ($q) use ($orderNumber) {
                $q->where('eo.order_number', 'ILIKE', "%{$orderNumber}%");
            })
            ->when($customerName !== '', function ($q) use ($customerName) {
                $q->where('c.name', 'ILIKE', "%{$customerName}%");
            })
            ->when($waNumber !== '', function ($q) use ($waNumber) {
                $q->where('c.wa_number', 'ILIKE', "%{$waNumber}%");
            })
            ->when($orderStatus, function ($q) use ($orderStatus) {
                $q->where('eo.status', $orderStatus);
            })
            ->when($handledType === 'store', function ($q) {
                $q->whereNotNull('eo.handled_by_store_id');
            })
            ->when($handledType === 'warehouse', function ($q) {
                $q->whereNotNull('eo.handled_by_warehouse_id');
            })
            ->when($storeId, function ($q) use ($storeId) {
                $q->where(function ($sub) use ($storeId) {
                    $sub->where('eo.handled_by_store_id', $storeId)
                        ->orWhere('so.store_id', $storeId);
                });
            })
            ->when($warehouseId, function ($q) use ($warehouseId) {
                $q->where('eo.handled_by_warehouse_id', $warehouseId);
            })
            ->groupBy([
                'eo.id',
                'eo.order_number',
                'eo.status',
                'eo.created_at',
                'c.name',
                'c.email',
                'c.wa_number',
                'hs.name',
                'hw.name',
                'eo.handled_by_store_id',
                'eo.handled_by_warehouse_id',
                'eod.id',
                'eod.product_id',
                'op.code',
                'op.name',
            ]);
    }

    private function rfidTagColumnExpression(): string
    {
        if (Schema::hasColumn('rfid_tags', 'code')) {
            return 'rt.code::text';
        }

        if (Schema::hasColumn('rfid_tags', 'name')) {
            return 'rt.name::text';
        }

        if (Schema::hasColumn('rfid_tags', 'tag')) {
            return 'rt.tag::text';
        }

        if (Schema::hasColumn('rfid_tags', 'tag_number')) {
            return 'rt.tag_number::text';
        }

        if (Schema::hasColumn('rfid_tags', 'serial_number')) {
            return 'rt.serial_number::text';
        }

        if (Schema::hasColumn('rfid_tags', 'rfid')) {
            return 'rt.rfid::text';
        }

        return 'rt.id::text';
    }

    private function normalizeDate($date): ?string
    {
        if ($date === null) {
            return null;
        }

        $date = trim((string) $date);

        if ($date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
            [$day, $month, $year] = explode('/', $date);

            return "{$year}-{$month}-{$day}";
        }

        return null;
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeHandledType($value): ?string
    {
        $value = $this->normalizeNullableString($value);

        if (! $value) {
            return null;
        }

        $value = strtolower($value);

        return in_array($value, ['store', 'warehouse'], true) ? $value : null;
    }

    private function applyDeletedAtFilter($query, string $table): void
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
    }
}
