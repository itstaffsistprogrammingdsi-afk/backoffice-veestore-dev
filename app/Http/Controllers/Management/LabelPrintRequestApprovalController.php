<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LabelPrintRequestApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureManagementAccess();

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $rows = DB::table('store_label_print_requests as request')
            ->leftJoin('stores as store', 'store.id', '=', 'request.store_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'request.requested_by')
            ->leftJoin('users as approver', 'approver.id', '=', 'request.approved_by')
            ->leftJoin('store_label_print_request_items as request_item', 'request_item.label_print_request_id', '=', 'request.id')
            ->leftJoin('items as item', 'item.id', '=', 'request_item.item_id')
            ->leftJoin('products as product', 'product.id', '=', 'item.product_id')
            ->select([
                'request.id',
                'request.status',
                'request.reason',
                'request.approved_at',
                'request.created_at',
                'store.name as store_name',
                'requester.name as requested_by_name',
                'approver.name as approved_by_name',
                DB::raw('COUNT(DISTINCT request_item.id) as total_items'),
                DB::raw("STRING_AGG(DISTINCT COALESCE(NULLIF(product.code, ''), NULLIF(product.name, ''), CAST(request_item.item_id AS TEXT)), ', ') as item_summary"),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";

                $query->where(function ($subQuery) use ($like) {
                    $subQuery->where('store.name', 'ILIKE', $like)
                        ->orWhere('requester.name', 'ILIKE', $like)
                        ->orWhere('request.reason', 'ILIKE', $like)
                        ->orWhereRaw('CAST(request.id AS TEXT) ILIKE ?', [$like]);
                });
            })
            ->when($status, fn ($query) => $query->where('request.status', $status))
            ->when($storeId, fn ($query) => $query->where('request.store_id', $storeId))
            ->whereBetween(DB::raw('request.created_at::date'), [$from, $to])
            ->groupBy([
                'request.id',
                'request.status',
                'request.reason',
                'request.approved_at',
                'request.created_at',
                'store.name',
                'requester.name',
                'approver.name',
            ])
            ->orderByRaw("CASE request.status::text WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('request.created_at')
            ->paginate(15)
            ->withQueryString();

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Management/LabelRequests', [
            'rows' => $rows,
            'stores' => $stores,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('management.request-labels.index'),
                'approve' => route('management.request-labels.approve', ['id' => '__ID__']),
                'reject' => route('management.request-labels.reject', ['id' => '__ID__']),
            ],
        ]);
    }

    public function approve(Request $request, string $id)
    {
        return $this->decide($request, $id, 'approved');
    }

    public function reject(Request $request, string $id)
    {
        return $this->decide($request, $id, 'rejected');
    }

    private function decide(Request $request, string $id, string $status)
    {
        $this->ensureManagementAccess();

        $labelRequest = DB::table('store_label_print_requests')
            ->where('id', $id)
            ->first(['id', 'status']);

        if (! $labelRequest) {
            return $this->redirectAfterDecision($request)
                ->with('error', 'Request label tidak ditemukan.');
        }

        if ((string) $labelRequest->status !== 'pending') {
            return $this->redirectAfterDecision($request)
                ->with('error', 'Hanya request label berstatus pending yang dapat diproses.');
        }

        DB::table('store_label_print_requests')
            ->where('id', $id)
            ->where('status', 'pending')
            ->update([
                'status' => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->redirectAfterDecision($request)
            ->with('success', $status === 'approved'
                ? 'Request label berhasil disetujui.'
                : 'Request label berhasil ditolak.');
    }

    private function redirectAfterDecision(Request $request)
    {
        return redirect()->route(
            $request->boolean('from_branch')
                ? 'branch-operations.request-label.index'
                : 'management.request-labels.index'
        );
    }

    private function ensureManagementAccess(): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->can('approve management-label-requests') || $user->can('manage label-print-requests')) {
            return;
        }

        $managementRoles = ['superadmin', 'administrator', 'admin', 'management', 'manager'];
        $hasManagementRole = $user->getRoleNames()
            ->map(fn ($role) => strtolower(preg_replace('/[^a-z0-9]/', '', $role)))
            ->contains(fn ($role) => in_array($role, $managementRoles, true));

        abort_unless($hasManagementRole, 403);
    }
}
