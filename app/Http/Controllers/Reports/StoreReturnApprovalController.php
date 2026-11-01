<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

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
        $to   = $request->query('to')   ?: now()->toDateString();

        // Ada/tidaknya kolom warehouse_id pada store_returns
        $hasWarehouseId = Schema::hasColumn('store_returns', 'warehouse_id');

        $query = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->when($hasWarehouseId, fn($q2) => $q2->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id'))
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

        $returns    = $query->paginate(10)->withQueryString();
        $stores     = DB::table('stores')->orderBy('name')->get(['id', 'name']);
        $warehouses = DB::table('warehouses')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']);

        return view('content.reports.retur-cabang.index', compact(
            'returns',
            'stores',
            'warehouses',
            'q',
            'status',
            'from',
            'to'
        ));
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
            ->when($hasWarehouseId, fn($q) => $q->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id'))
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
            'header'  => $header,
            'details' => $details,
        ]);
    }

    /**
     * Approve:
     * - Validasi warehouse_id (wajib) dan simpan ke header.
     * - OPSIONAL: menerima approved_qty[product_id] dari modal.
     * - Untuk tiap product_id, update N baris detail (yang belum approved) menjadi 'approved'.
     * - Update header ke 'approved'.
     */
    public function approve(Request $request, string $returnId)
    {
        $row = DB::table('store_returns')->where('id', $returnId)->first();
        if (!$row) {
            return back()->withErrors(['retur' => 'Data retur tidak ditemukan.']);
        }

        $nowStatus = strtolower((string) $row->status);
        if (!in_array($nowStatus, ['pending_approval', 'requested'], true)) {
            return back()->withErrors(['status' => 'Retur bukan status pending_approval/requested.']);
        }

        // Wajib pilih warehouse & valid (hanya warehouse aktif, samakan dengan master data)
        $data = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->whereNull('deleted_at')],
            // Jika nanti aktifkan input qty approved per produk:
            // 'approved_qty'   => ['array'],
            // 'approved_qty.*' => ['nullable','integer','min:0'],
        ]);

        /** @var array<string,int|string|null> $approvedMap */
        $approvedMap = (array) $request->input('approved_qty', []); // opsional

        DB::transaction(function () use ($returnId, $data, $approvedMap) {
            // OPSIONAL: setujui N item per product_id bila dikirim dari modal
            foreach ($approvedMap as $productId => $qty) {
                $productId = (string) $productId;
                $qty       = (int) $qty;

                if ($qty <= 0 || $productId === '') {
                    continue;
                }

                // Ambil ID detail yang BELUM approved untuk produk tsb
                $detailIds = DB::table('store_return_details as d')
                    ->join('items as i', 'i.id', '=', 'd.item_id')
                    ->where('d.store_return_id', $returnId)
                    ->where('i.product_id', $productId)
                    ->where(function ($q) {
                        $q->whereNull('d.status')->orWhere('d.status', '!=', 'approved');
                    })
                    ->orderBy('d.created_at')
                    ->limit($qty)
                    ->pluck('d.id')
                    ->all();

                if (!empty($detailIds)) {
                    DB::table('store_return_details')
                        ->whereIn('id', $detailIds)
                        ->update([
                            'status'     => 'approved',
                            'updated_at' => now(),
                        ]);
                }
            }

            // Update header: status approved + simpan warehouse_id
            DB::table('store_returns')
                ->where('id', $returnId)
                ->update([
                    'status'       => 'approved',
                    'warehouse_id' => $data['warehouse_id'], // SIMPAN WAREHOUSE KE HEADER
                    'approved_at'  => now(),
                    'approved_by'  => Auth::id(),
                    'updated_at'   => now(),
                ]);
        });

        return back()->with('success', 'Retur telah di-approve.');
    }

    public function reject(string $returnId)
    {
        $row = DB::table('store_returns')->where('id', $returnId)->first();
        if (!$row) {
            return back()->withErrors(['retur' => 'Data retur tidak ditemukan.']);
        }

        $nowStatus = strtolower((string) $row->status);
        if (!in_array($nowStatus, ['pending_approval', 'requested', 'draft'], true)) {
            return back()->withErrors(['status' => 'Retur tidak bisa direject pada status saat ini.']);
        }

        DB::transaction(function () use ($returnId) {
            DB::table('store_returns')
                ->where('id', $returnId)
                ->update([
                    'status'      => 'rejected',
                    'approved_at' => null,
                    'approved_by' => null,
                    'rejected_at' => now(),
                ]);
        });

        return back()->with('success', 'Retur ditolak.');
    }
}