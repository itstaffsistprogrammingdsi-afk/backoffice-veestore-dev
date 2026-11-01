<?php

namespace App\Http\Controllers\BranchOperations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreVoucherRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        $perPage = 10;

        $rows = DB::table('store_voucher_requests as svr')
            ->leftJoin('stores as s', 's.id', '=', 'svr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'svr.created_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'svr.approved_by')
            ->leftJoin('store_voucher_request_details as svrd', 'svrd.store_voucher_request_id', '=', 'svr.id')
            ->leftJoin('vouchers as v', 'v.store_voucher_request_id', '=', 'svr.id')
            ->select([
                'svr.id',
                'svr.request_number',
                'svr.store_id',
                'svr.request_date',
                'svr.status',
                'svr.approved_by',
                'svr.approved_at',
                'svr.notes',
                'svr.customer_id',
                'svr.store_visit_payment_id',
                'svr.created_by',
                'svr.created_at',
                'svr.updated_at',

                's.name as store_name',
                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name',

                'v.id as voucher_id',
                'v.code as voucher_code',
                'v.name as voucher_name',
                'v.description as voucher_description',
                'v.type as voucher_type',
                'v.value as voucher_value',
                'v.max_discount as voucher_max_discount',
                'v.min_purchase as voucher_min_purchase',
                'v.valid_from',
                'v.valid_until',
                'v.usage_limit',
                'v.usage_count',
                'v.is_active as voucher_is_active',
                'v.context as voucher_context',

                DB::raw('COUNT(DISTINCT svrd.id) as total_items'),
                DB::raw("
                    CASE svr.status::text
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'rejected' THEN 'Rejected'
                        ELSE COALESCE(svr.status::text, '-')
                    END as status_label
                "),
            ])
            ->when($q !== '', function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('svr.request_number', 'ILIKE', $like)
                        ->orWhere('s.name', 'ILIKE', $like)
                        ->orWhere('req_user.name', 'ILIKE', $like)
                        ->orWhere('v.code', 'ILIKE', $like)
                        ->orWhere('v.name', 'ILIKE', $like)
                        ->orWhere('svr.notes', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(svr.id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($builder) use ($status) {
                $builder->where('svr.status', $status);
            })
            ->when($storeId, function ($builder) use ($storeId) {
                $builder->where('svr.store_id', $storeId);
            })
            ->whereBetween(DB::raw('svr.request_date::date'), [$from, $to])
            ->groupBy([
                'svr.id',
                'svr.request_number',
                'svr.store_id',
                'svr.request_date',
                'svr.status',
                'svr.approved_by',
                'svr.approved_at',
                'svr.notes',
                'svr.customer_id',
                'svr.store_visit_payment_id',
                'svr.created_by',
                'svr.created_at',
                'svr.updated_at',

                's.name',
                'req_user.name',
                'app_user.name',

                'v.id',
                'v.code',
                'v.name',
                'v.description',
                'v.type',
                'v.value',
                'v.max_discount',
                'v.min_purchase',
                'v.valid_from',
                'v.valid_until',
                'v.usage_limit',
                'v.usage_count',
                'v.is_active',
                'v.context',
            ])
            ->orderByDesc('svr.request_date')
            ->paginate($perPage)
            ->withQueryString();

        $requestIds = $rows->getCollection()->pluck('id')->filter()->values()->all();

        $detailItems = collect();

        if (!empty($requestIds)) {
            $detailItems = DB::table('store_voucher_request_details as svrd')
                ->leftJoin('products as p', 'p.id', '=', 'svrd.product_id')
                ->leftJoin('items as i', 'i.id', '=', 'svrd.item_id')
                ->whereIn('svrd.store_voucher_request_id', $requestIds)
                ->select([
                    'svrd.id',
                    'svrd.store_voucher_request_id',
                    'svrd.product_id',
                    'svrd.item_id',
                    'svrd.created_at',
                    'p.code as product_code',
                    'p.name as product_name',
                    'i.status as item_status',
                ])
                ->orderBy('svrd.created_at')
                ->get()
                ->groupBy('store_voucher_request_id');
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = collect(['pending', 'approved', 'rejected']);

        return view('content.branch-operations.request-voucher.index', compact(
            'rows',
            'detailItems',
            'q',
            'status',
            'storeId',
            'from',
            'to',
            'stores',
            'statuses'
        ));
    }

    public function approve(Request $request, string $id)
    {
        $requestRow = DB::table('store_voucher_requests')
            ->where('id', $id)
            ->first();

        if (!$requestRow) {
            return redirect()
                ->route('branch-operations.request-voucher.index')
                ->with('error', 'Data request voucher tidak ditemukan.');
        }

        if ((string) $requestRow->status !== 'pending') {
            return redirect()
                ->route('branch-operations.request-voucher.index')
                ->with('error', 'Hanya request dengan status pending yang dapat di-approve.');
        }

        DB::table('store_voucher_requests')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('branch-operations.request-voucher.index')
            ->with('success', 'Request voucher berhasil di-approve tanpa membuat voucher baru.');
    }

    public function reject(Request $request, string $id)
    {
        $requestRow = DB::table('store_voucher_requests')
            ->where('id', $id)
            ->first();

        if (!$requestRow) {
            return redirect()
                ->route('branch-operations.request-voucher.index')
                ->with('error', 'Data request voucher tidak ditemukan.');
        }

        if ((string) $requestRow->status !== 'pending') {
            return redirect()
                ->route('branch-operations.request-voucher.index')
                ->with('error', 'Hanya request dengan status pending yang dapat di-reject.');
        }

        DB::table('store_voucher_requests')
            ->where('id', $id)
            ->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('branch-operations.request-voucher.index')
            ->with('success', 'Request voucher berhasil di-reject.');
    }
}