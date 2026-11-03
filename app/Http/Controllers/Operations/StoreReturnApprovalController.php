<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Actions\ApproveStoreReturn;
use App\Domain\StoreOperations\Actions\RejectStoreReturn;
use App\Domain\StoreOperations\Queries\StoreReturnApprovalQuery;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoreReturnApprovalController extends Controller
{
    private const FILTER_STATUSES = [
        'pending',
        'all',
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

    public function __construct()
    {
        $this->middleware('permission:view branch-retur-cabang');
    }

    public function index(Request $request, StoreReturnApprovalQuery $query): Response
    {
        $q = trim((string) $request->query('q', ''));

        $status = $request->has('status')
            ? strtolower(trim((string) $request->query('status', '')))
            : 'pending';

        if (! in_array($status, self::FILTER_STATUSES, true)) {
            $status = 'pending';
        }

        if (in_array($status, ['requested', 'pending_approval'], true)) {
            $status = 'pending';
        }

        $storeId = trim((string) $request->query('store_id', ''));
        $storeId = $storeId !== '' ? $storeId : null;

        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return Inertia::render('Operations/StoreReturnApproval', [
            ...$query->build($q, $status, $storeId, $from, $to, $perPage),
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage,
            ],
            'urls' => [
                'index' => route('operations.store-returns.index'),
                'show' => route('operations.store-returns.show', ['return' => '__ID__']),
                'approve' => route('operations.store-returns.approve', ['return' => '__ID__']),
                'reject' => route('operations.store-returns.reject', ['return' => '__ID__']),
                'store_outbound' => route('operations.store-outbound.index'),
                'store_inbound' => route('operations.store-inbound.index'),
            ],
        ]);
    }

    public function show(string $returnId, StoreReturnApprovalQuery $query): JsonResponse
    {
        $detail = $query->detail($returnId);

        if (! $detail) {
            return response()->json([
                'message' => 'Data retur tidak ditemukan.',
            ], 404);
        }

        return response()->json($detail);
    }

    public function approve(
        Request $request,
        string $returnId,
        ApproveStoreReturn $approveStoreReturn
    ): RedirectResponse {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.qty' => ['required', 'integer', 'min:0'],
            'warehouse_id' => [
                'nullable',
                'uuid',
                Rule::exists('warehouses', 'id')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $approveStoreReturn->handle(
            $returnId,
            $data['items'],
            isset($data['warehouse_id']) ? (string) $data['warehouse_id'] : null,
            $data['note'] ?? null,
            Auth::id()
        );

        $number = $result['return_number'] ?? $returnId;

        if ($result['status'] === 'approved') {
            $message = "Retur {$number} disetujui penuh. {$result['approved_now']} barang siap diproses gudang.";
        } else {
            $message = "Retur {$number} disetujui sebagian. {$result['approved_now']} barang disetujui, {$result['remaining']} barang masih menunggu approval.";
        }

        return back()->with('success', $message);
    }

    public function reject(
        Request $request,
        string $returnId,
        RejectStoreReturn $rejectStoreReturn
    ): RedirectResponse {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $rejectStoreReturn->handle($returnId, $data['note'] ?? null);

        $number = $result['return_number'] ?? $returnId;

        return back()->with('success', "Retur {$number} ditolak. Seluruh barang pada retur ditandai rejected.");
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
