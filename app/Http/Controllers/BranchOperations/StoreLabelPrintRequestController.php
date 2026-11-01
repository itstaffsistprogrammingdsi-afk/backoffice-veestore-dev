<?php

namespace App\Http\Controllers\BranchOperations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StoreLabelPrintRequestController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $requestedBy = $request->query('requested_by');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();
        $perPage = 10;

        $rows = DB::table('store_label_print_requests as slpr')
            ->leftJoin('stores as s', 's.id', '=', 'slpr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'slpr.requested_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'slpr.approved_by')
            ->leftJoin('store_label_print_request_items as slpri', 'slpri.label_print_request_id', '=', 'slpr.id')
            ->leftJoin('store_label_print_histories as slph', 'slph.label_print_request_id', '=', 'slpr.id')
            ->select([
                'slpr.id',
                'slpr.store_id',
                'slpr.requested_by',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_by',
                'slpr.approved_at',
                'slpr.created_at',
                'slpr.updated_at',

                's.name as store_name',
                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name',

                DB::raw('COUNT(DISTINCT slpri.id) as total_items'),
                DB::raw('COUNT(DISTINCT slph.id) as total_printed'),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('req_user.name', 'ILIKE', $like)
                        ->orWhere('app_user.name', 'ILIKE', $like)
                        ->orWhere('slpr.reason', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(slpr.id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where('slpr.status', $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('slpr.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('slpr.requested_by', $requestedBy);
            })
            ->whereBetween(DB::raw('slpr.created_at::date'), [$from, $to])
            ->groupBy([
                'slpr.id',
                'slpr.store_id',
                'slpr.requested_by',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_by',
                'slpr.approved_at',
                'slpr.created_at',
                'slpr.updated_at',
                's.name',
                'req_user.name',
                'app_user.name',
            ])
            ->orderByDesc('slpr.created_at')
            ->paginate($perPage)
            ->withQueryString();

        $requestIds = $rows->getCollection()->pluck('id')->all();

        $detailItems = collect();
        if (! empty($requestIds)) {
            $detailItems = DB::table('store_label_print_request_items as slpri')
                ->join('items as i', 'i.id', '=', 'slpri.item_id')
                ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
                ->leftJoin('store_label_print_histories as slph', function ($join) {
                    $join->on('slph.label_print_request_id', '=', 'slpri.label_print_request_id')
                        ->on('slph.item_id', '=', 'slpri.item_id');
                })
                ->leftJoin('users as print_user', 'print_user.id', '=', 'slph.printed_by')
                ->whereIn('slpri.label_print_request_id', $requestIds)
                ->select([
                    'slpri.id',
                    'slpri.label_print_request_id',
                    'slpri.item_id',
                    'p.code as product_code',
                    'p.name as product_name',
                    'i.status as item_status',
                    'slph.print_index',
                    'slph.printed_at',
                    'print_user.name as printed_by_name',
                ])
                ->orderBy('slpri.created_at')
                ->get()
                ->groupBy('label_print_request_id');
        }

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('store_label_print_requests')
            ->select(DB::raw('status::text as status'))
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $canDecide = $this->canDecideLabelRequest();

        return Inertia::render('BranchOperations/LabelPrintRequests', [
            'rows' => $rows,
            'detailItems' => $detailItems,
            'stores' => $stores,
            'users' => $users,
            'statuses' => $statuses,
            'canDecide' => $canDecide,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'requested_by' => $requestedBy,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('branch-operations.request-label.index'),
                'approve' => route('management.request-labels.approve', ['id' => '__ID__']),
                'reject' => route('management.request-labels.reject', ['id' => '__ID__']),
            ],
        ]);
    }

    private function canDecideLabelRequest(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->can('approve management-label-requests') || $user->can('manage label-print-requests')) {
            return true;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];

        return $user->getRoleNames()
            ->map(fn ($role) => strtolower(preg_replace('/[^a-z0-9]/', '', $role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));
    }
}
