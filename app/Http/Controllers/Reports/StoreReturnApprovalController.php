<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class StoreReturnApprovalController extends Controller
{
    /** Status yang tersedia (samakan dengan enum di DB). */
    private array $allowedStatuses = [
        'draft',
        'requested',
        'pending_approval',
        'partial_approved',
        'approved',
        'rejected',
        'packing',
        'shipped',
        'received',
        'close',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // Default ke "requested" hanya jika param "status" tidak dikirim
        $status = $request->has('status')
            ? (string) $request->query('status')   // '' atau 'all' = semua
            : 'requested';

        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        // Ada/tidaknya kolom warehouse_id pada store_returns
        $hasWarehouseId = Schema::hasColumn('store_returns', 'warehouse_id');

        $query = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->when($hasWarehouseId, fn ($q2) => $q2->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id'))
            ->leftJoin('users as ua', 'ua.id', '=', 'r.approved_by')
            ->select([
                DB::raw('r.id'),
                DB::raw('r.return_number'),
                DB::raw('r.status'),
                DB::raw('r.created_at::date as date'),
                DB::raw('r.store_id'),
                DB::raw('s.name as store_name'),
                DB::raw('r.approved_at'),
                DB::raw('ua.name as approved_by_name'),
            ]);

        // Selalu hadirkan kolom gudang agar view aman
        if ($hasWarehouseId) {
            $query->addSelect([
                DB::raw('r.warehouse_id'),
                DB::raw('w.name as warehouse_name'),
            ]);
        } else {
            // fallback jika kolom tidak ada
            $query->addSelect([
                DB::raw('NULL::uuid  as warehouse_id'),
                DB::raw('NULL::text  as warehouse_name'),
            ]);
        }

        // Pencarian (Postgres: ILIKE)
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like, $hasWarehouseId) {
                $w->where('r.return_number', 'ILIKE', $like)
                    ->orWhere('s.name', 'ILIKE', $like);
                if ($hasWarehouseId) {
                    $w->orWhere('w.name', 'ILIKE', $like);
                }
            });
        }

        // Filter status ('' atau 'all' = tidak difilter)
        if ($status !== '' && strtolower($status) !== 'all') {
            $query->where('r.status', $status);
        }

        // Range tanggal by created_at::date
        $query->whereBetween(DB::raw('r.created_at::date'), [$from, $to])
            ->orderByDesc('r.created_at');

        $returns = $query->paginate(10)->withQueryString();
        $stores = DB::table('stores')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Reports/Returns', [
            'returns' => $returns,
            'stores' => $stores,
            'statuses' => $this->allowedStatuses,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('reports.retur-cabang.index'),
                'show' => route('reports.retur-cabang.show', ['return' => '__ID__']),
                'approval' => route('operations.store-returns.index'),
            ],
        ]);
    }

    /**
     * Detail header + detail item (AGREGASI PER PRODUK).
     * Skema: store_return_details (d) -> items (i) -> products (p)
     */
    public function show(string $returnId)
    {
        $hasWarehouseId = Schema::hasColumn('store_returns', 'warehouse_id');

        $headerQuery = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'r.approved_by')
            ->when($hasWarehouseId, fn ($q) => $q->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id'))
            ->where('r.id', $returnId)
            ->select([
                DB::raw('r.id'),
                DB::raw('r.return_number'),
                DB::raw('r.status'),
                DB::raw('r.created_at'),
                DB::raw('r.approved_at'),
                DB::raw('r.rejected_at'),
                DB::raw('r.store_id'),
                DB::raw('s.name as store_name'),
                DB::raw('ua.name as approved_by_name'),
                DB::raw('r.note_store'),
                DB::raw('r.note_warehouse'),
                DB::raw('r.shipped_at'),
                DB::raw('r.received_at'),
            ]);

        // Pastikan field warehouse_* selalu ada
        if ($hasWarehouseId) {
            $headerQuery->addSelect([
                DB::raw('r.warehouse_id'),
                DB::raw('w.name as warehouse_name'),
            ]);
        } else {
            $headerQuery->addSelect([
                DB::raw('NULL::uuid as warehouse_id'),
                DB::raw('NULL::text as warehouse_name'),
            ]);
        }

        $header = $headerQuery->first();

        // ==== Detail: agregasi per product_id ====
        // qty_requested = jumlah baris detail per produk,
        // qty_approved  = jumlah baris detail berstatus 'approved' per produk.
        if (Schema::hasTable('store_return_details') && Schema::hasTable('items')) {
            $details = DB::table('store_return_details as d')
                ->join('items as i', 'i.id', '=', 'd.item_id')
                ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                ->where('d.store_return_id', $returnId)
                ->groupBy('i.product_id', 'p.name')
                ->selectRaw("
                    i.product_id,
                    COALESCE(p.name, '-') as product_name,
                    COUNT(*)::int as qty_requested,
                    SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END)::int as qty_approved
                ")
                ->orderBy('p.name')
                ->get();
        } else {
            $details = collect();
        }

        return response()->json([
            'header' => $header,
            'details' => $details,
        ]);
    }
}
