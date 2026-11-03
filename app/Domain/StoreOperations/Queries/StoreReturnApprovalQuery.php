<?php

namespace App\Domain\StoreOperations\Queries;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreReturnApprovalQuery
{
    private const STATUSES = [
        'draft',
        'requested',
        'pending_approval',
        'partial_approved',
        'approved',
        'rejected',
        'packing',
        'packing_completed',
        'shipped',
        'received',
        'close',
    ];

    private const STATUS_LABELS = [
        'draft' => 'Draft',
        'requested' => 'Menunggu approval',
        'pending_approval' => 'Menunggu approval',
        'partial_approved' => 'Disetujui sebagian',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'packing' => 'Packing',
        'packing_completed' => 'Packing selesai',
        'shipped' => 'Dikirim',
        'received' => 'Diterima gudang',
        'close' => 'Selesai',
    ];

    public function build(
        string $q,
        string $status,
        ?string $storeId,
        ?string $from,
        ?string $to,
        int $perPage
    ): array {
        $detailAggregate = DB::table('store_return_details as d')
            ->select('d.store_return_id')
            ->selectRaw("
                COUNT(*)::int as total_items,
                SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END)::int as approved_items,
                SUM(CASE WHEN d.status = 'rejected' THEN 1 ELSE 0 END)::int as rejected_items,
                SUM(CASE WHEN d.status IS NULL OR d.status = 'in_review' THEN 1 ELSE 0 END)::int as remaining_items
            ")
            ->groupBy('d.store_return_id');

        $query = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->leftJoin('users as approver', 'approver.id', '=', 'r.approved_by')
            ->leftJoinSub($detailAggregate, 'detail_agg', 'detail_agg.store_return_id', '=', 'r.id')
            ->select([
                'r.id',
                'r.return_number',
                'r.status',
                'r.store_id',
                'r.warehouse_id',
                'r.note_store',
                'r.created_at',
                'r.updated_at',
                'r.approved_at',
                'r.rejected_at',
                's.name as store_name',
                's.code as store_code',
                'w.name as warehouse_name',
                'w.code as warehouse_code',
                'approver.name as approved_by_name',
                DB::raw('COALESCE(detail_agg.total_items, 0)::int as total_items'),
                DB::raw('COALESCE(detail_agg.approved_items, 0)::int as approved_items'),
                DB::raw('COALESCE(detail_agg.remaining_items, 0)::int as remaining_items'),
            ])
            ->when($q !== '', function ($inner) use ($q): void {
                $like = "%{$q}%";

                $inner->where(function ($search) use ($like): void {
                    $search->where('r.return_number', 'ILIKE', $like)
                        ->orWhere('r.note_store', 'ILIKE', $like)
                        ->orWhere('s.name', 'ILIKE', $like)
                        ->orWhere('s.code', 'ILIKE', $like)
                        ->orWhere('w.name', 'ILIKE', $like)
                        ->orWhere('w.code', 'ILIKE', $like)
                        ->orWhereExists(function ($detailQuery) use ($like): void {
                            $detailQuery->selectRaw('1')
                                ->from('store_return_details as sd')
                                ->join('items as si', 'si.id', '=', 'sd.item_id')
                                ->join('products as sp', 'sp.id', '=', 'si.product_id')
                                ->whereColumn('sd.store_return_id', 'r.id')
                                ->where(function ($productQuery) use ($like): void {
                                    $productQuery->where('sp.name', 'ILIKE', $like)
                                        ->orWhere('sp.code', 'ILIKE', $like);
                                });
                        });
                });
            })
            ->when($status === 'pending', fn ($inner) => $inner->whereIn('r.status', ['requested', 'pending_approval']))
            ->when($status !== '' && $status !== 'all' && $status !== 'pending', fn ($inner) => $inner->where('r.status', $status))
            ->when($storeId, fn ($inner) => $inner->where('r.store_id', $storeId))
            ->when($from, fn ($inner) => $inner->whereRaw('r.created_at::date >= ?', [$from]))
            ->when($to, fn ($inner) => $inner->whereRaw('r.created_at::date <= ?', [$to]));

        if ($status === 'pending') {
            $query->orderBy('r.created_at');
        } else {
            $query->orderByDesc('r.created_at');
        }

        $rows = $query->paginate($perPage)->withQueryString();

        $rows->getCollection()->transform(function (object $row): object {
            $row->status_label = self::STATUS_LABELS[$row->status] ?? $row->status;
            $row->total_items = (int) $row->total_items;
            $row->approved_items = (int) $row->approved_items;
            $row->remaining_items = (int) $row->remaining_items;

            return $row;
        });

        return [
            'rows' => $rows,
            'stats' => $this->stats(),
            'statusOptions' => [
                ['value' => 'pending', 'label' => 'Menunggu approval'],
                ['value' => 'all', 'label' => 'Semua status'],
                ['value' => 'partial_approved', 'label' => 'Disetujui sebagian'],
                ['value' => 'approved', 'label' => 'Disetujui'],
                ['value' => 'rejected', 'label' => 'Ditolak'],
                ['value' => 'packing', 'label' => 'Packing'],
                ['value' => 'packing_completed', 'label' => 'Packing selesai'],
                ['value' => 'shipped', 'label' => 'Dikirim'],
                ['value' => 'received', 'label' => 'Diterima gudang'],
                ['value' => 'close', 'label' => 'Selesai'],
                ['value' => 'draft', 'label' => 'Draft'],
            ],
        ];
    }

    public function detail(string $returnId): ?array
    {
        $header = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'r.created_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'r.approved_by')
            ->where('r.id', $returnId)
            ->select([
                'r.id',
                'r.return_number',
                'r.status',
                'r.store_id',
                'r.warehouse_id',
                'r.note_store',
                'r.note_warehouse',
                'r.created_at',
                'r.updated_at',
                'r.approved_at',
                'r.rejected_at',
                'r.shipped_at',
                'r.received_at',
                's.name as store_name',
                's.code as store_code',
                'w.name as warehouse_name',
                'w.code as warehouse_code',
                'creator.name as created_by_name',
                'approver.name as approved_by_name',
            ])
            ->first();

        if (! $header) {
            return null;
        }

        $details = DB::table('store_return_details as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
            ->where('d.store_return_id', $returnId)
            ->groupBy('i.product_id', 'p.name', 'p.code', 'u.name')
            ->selectRaw("
                i.product_id,
                COALESCE(p.name, '-') as product_name,
                COALESCE(p.code, '-') as product_code,
                COALESCE(u.name, 'pcs') as unit_name,
                COUNT(*)::int as qty_requested,
                SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END)::int as qty_approved,
                SUM(CASE WHEN d.status = 'rejected' THEN 1 ELSE 0 END)::int as qty_rejected,
                SUM(CASE WHEN d.status IS NULL OR d.status = 'in_review' THEN 1 ELSE 0 END)::int as qty_remaining
            ")
            ->orderBy('p.name')
            ->get();

        $conditions = DB::table('store_return_details as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('item_conditions as ic', 'ic.id', '=', 'i.current_condition_id')
            ->where('d.store_return_id', $returnId)
            ->groupBy('i.product_id', 'ic.name')
            ->select([
                'i.product_id',
                DB::raw("COALESCE(ic.name, 'Tanpa kondisi') as condition_name"),
                DB::raw('COUNT(*)::int as qty'),
            ])
            ->orderBy('ic.name')
            ->get()
            ->groupBy('product_id');

        $details = $details->map(function (object $detail) use ($conditions): array {
            $detailConditions = collect($conditions->get($detail->product_id, []))
                ->map(fn (object $condition): array => [
                    'name' => $condition->condition_name,
                    'qty' => (int) $condition->qty,
                ])
                ->values()
                ->all();

            return [
                'product_id' => (string) $detail->product_id,
                'product_name' => $detail->product_name,
                'product_code' => $detail->product_code,
                'unit_name' => $detail->unit_name,
                'qty_requested' => (int) $detail->qty_requested,
                'qty_approved' => (int) $detail->qty_approved,
                'qty_rejected' => (int) $detail->qty_rejected,
                'qty_remaining' => (int) $detail->qty_remaining,
                'conditions' => $detailConditions,
            ];
        })->values();

        $totalRequested = (int) $details->sum('qty_requested');
        $totalApproved = (int) $details->sum('qty_approved');
        $totalRemaining = (int) $details->sum('qty_remaining');
        $status = strtolower((string) $header->status);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'header' => [
                'id' => (string) $header->id,
                'return_number' => $header->return_number,
                'status' => $header->status,
                'status_label' => self::STATUS_LABELS[$status] ?? $header->status,
                'created_at' => $this->toIsoString($header->created_at),
                'approved_at' => $this->toIsoString($header->approved_at),
                'rejected_at' => $this->toIsoString($header->rejected_at),
                'shipped_at' => $this->toIsoString($header->shipped_at),
                'received_at' => $this->toIsoString($header->received_at),
                'note_store' => $header->note_store,
                'note_warehouse' => $header->note_warehouse,
                'created_by' => $header->created_by_name,
                'approved_by' => $header->approved_by_name,
                'store' => [
                    'id' => (string) $header->store_id,
                    'name' => $header->store_name ?? '-',
                    'code' => $header->store_code,
                ],
                'warehouse' => $header->warehouse_id ? [
                    'id' => (string) $header->warehouse_id,
                    'name' => $header->warehouse_name ?? '-',
                    'code' => $header->warehouse_code,
                ] : null,
                'total_requested' => $totalRequested,
                'total_approved' => $totalApproved,
                'total_remaining' => $totalRemaining,
            ],
            'details' => $details,
            'warehouses' => $warehouses,
            'is_editable' => in_array($status, ['requested', 'pending_approval', 'partial_approved'], true),
            'can_reject' => in_array($status, ['draft', 'requested', 'pending_approval'], true) && $totalApproved === 0,
            'can_change_warehouse' => ! $header->warehouse_id || $totalApproved === 0,
        ];
    }

    private function stats(): array
    {
        $today = now()->toDateString();

        $pending = DB::table('store_returns')
            ->whereIn('status', ['requested', 'pending_approval'])
            ->pluck('id');

        $pendingItems = $pending->isEmpty()
            ? 0
            : (int) DB::table('store_return_details')
                ->whereIn('store_return_id', $pending->all())
                ->where(function ($query): void {
                    $query->whereNull('status')->orWhere('status', 'in_review');
                })
                ->count();

        return [
            'pending' => $pending->count(),
            'pending_items' => $pendingItems,
            'approved_today' => DB::table('store_returns')
                ->where('status', 'approved')
                ->whereDate('approved_at', $today)
                ->count(),
            'partial_today' => DB::table('store_returns')
                ->where('status', 'partial_approved')
                ->whereDate('approved_at', $today)
                ->count(),
            'rejected_today' => DB::table('store_returns')
                ->where('status', 'rejected')
                ->whereDate('rejected_at', $today)
                ->count(),
        ];
    }

    private function toIsoString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (\Throwable) {
            return null;
        }
    }
}
