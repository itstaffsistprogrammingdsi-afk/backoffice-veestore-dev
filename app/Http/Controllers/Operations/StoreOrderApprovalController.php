<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Actions\ApproveStoreOrder;
use App\Domain\StoreOperations\Support\WarehouseStockAvailability;
use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StoreOrderApprovalController extends Controller
{
    private const STATUSES = [
        'draft',
        'requested',
        'approved',
        'partial_approved',
        'rejected',
        'processing',
        'packing',
        'shipped',
        'received',
        'partial_received',
        'cancelled',
    ];

    private const STATUS_LABELS = [
        'draft' => 'Draft',
        'requested' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'partial_approved' => 'Disetujui sebagian',
        'rejected' => 'Ditolak',
        'processing' => 'Diproses',
        'packing' => 'Packing',
        'shipped' => 'Dikirim',
        'received' => 'Diterima',
        'partial_received' => 'Diterima sebagian',
        'cancelled' => 'Dibatalkan',
    ];

    public function __construct()
    {
        $this->middleware('permission:view branch-order-cabang');
    }

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->has('status')
            ? trim((string) $request->query('status', ''))
            : 'requested';

        if ($status === 'all') {
            $status = '';
        } elseif ($status !== '' && ! in_array($status, self::STATUSES, true)) {
            $status = 'requested';
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

        $ordersQuery = StoreOrder::query()
            ->with([
                'store:id,name,code',
                'warehouse:id,name,code',
                'approver:id,name',
            ])
            ->withCount('details')
            ->withSum('details', 'requested_qty')
            ->withSum('details', 'approved_qty')
            ->withSum('details', 'received_qty')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('shipping_number', 'ILIKE', "%{$q}%")
                        ->orWhereHas('store', function ($storeQuery) use ($q): void {
                            $storeQuery->where('name', 'ILIKE', "%{$q}%")
                                ->orWhere('code', 'ILIKE', "%{$q}%");
                        })
                        ->orWhereHas('details.product', function ($productQuery) use ($q): void {
                            $productQuery->where('name', 'ILIKE', "%{$q}%")
                                ->orWhere('code', 'ILIKE', "%{$q}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(requested_at, created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(requested_at, created_at)::date <= ?', [$to]));

        $ordersQuery = $status === 'requested'
            ? $ordersQuery->orderByRaw('COALESCE(requested_at, created_at) ASC')
            : $ordersQuery->orderByDesc('created_at');

        $orders = $ordersQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (StoreOrder $order): array => [
                'id' => (string) $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => self::STATUS_LABELS[$order->status] ?? $order->status,
                'date' => optional($order->date)->toDateString(),
                'requested_at' => optional($order->requested_at)->toIso8601String(),
                'approved_at' => optional($order->approved_at)->toIso8601String(),
                'shipping_number' => $order->shipping_number,
                'store' => $order->store ? [
                    'id' => (string) $order->store->id,
                    'name' => $order->store->name,
                    'code' => $order->store->code,
                ] : null,
                'warehouse' => $order->warehouse ? [
                    'id' => (string) $order->warehouse->id,
                    'name' => $order->warehouse->name,
                    'code' => $order->warehouse->code,
                ] : null,
                'approver' => $order->approver?->name,
                'details_count' => (int) ($order->details_count ?? 0),
                'requested_qty' => (int) ($order->details_sum_requested_qty ?? 0),
                'approved_qty' => (int) ($order->details_sum_approved_qty ?? 0),
                'received_qty' => (int) ($order->details_sum_received_qty ?? 0),
            ]);

        return Inertia::render('Operations/StoreOrderApproval', [
            'orders' => $orders,
            'stats' => $this->stats(),
            'stores' => DB::table('stores')
                ->whereNull('deleted_at')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'statusOptions' => collect(self::STATUSES)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => self::STATUS_LABELS[$value] ?? $value,
                ])
                ->values()
                ->all(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'store_id' => $storeId,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage,
            ],
            'urls' => [
                'index' => route('operations.store-orders.index'),
                'show' => route('operations.store-orders.show', ['order' => '__ID__']),
                'approve' => route('operations.store-orders.approve', ['order' => '__ID__']),
                'reject' => route('operations.store-orders.reject', ['order' => '__ID__']),
                'stock' => route('operations.store-stock.index'),
                'inbound' => route('operations.store-inbound.index'),
            ],
        ]);
    }

    public function show(
        StoreOrder $order,
        WarehouseStockAvailability $availability
    ): JsonResponse {
        $order->load([
            'store:id,name,code',
            'warehouse:id,name,code',
            'approver:id,name',
            'details.product:id,name,code,unit_id',
            'details.product.unit:id,name',
        ]);

        $warehouses = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $productIds = $order->details
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $stock = $availability->forProducts($warehouses->pluck('id')->all(), $productIds);

        $isEditable = strtolower((string) $order->status) === 'requested';
        $creatorName = $order->created_by
            ? DB::table('users')->where('id', $order->created_by)->value('name')
            : null;

        return response()->json([
            'order' => [
                'id' => (string) $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => self::STATUS_LABELS[$order->status] ?? $order->status,
                'date' => optional($order->date)->toDateString(),
                'requested_at' => optional($order->requested_at)->toIso8601String(),
                'approved_at' => optional($order->approved_at)->toIso8601String(),
                'shipping_number' => $order->shipping_number,
                'created_by' => $creatorName,
                'approved_by' => $order->approver?->name,
                'store' => $order->store ? [
                    'id' => (string) $order->store->id,
                    'name' => $order->store->name,
                    'code' => $order->store->code,
                ] : null,
                'warehouse' => $order->warehouse ? [
                    'id' => (string) $order->warehouse->id,
                    'name' => $order->warehouse->name,
                    'code' => $order->warehouse->code,
                ] : null,
            ],
            'details' => $order->details->map(function ($detail) use ($isEditable, $order): array {
                $allocations = [];

                if (! $isEditable && $order->warehouse_id && (int) ($detail->approved_qty ?? 0) > 0) {
                    $allocations[] = [
                        'warehouse_id' => (string) $order->warehouse_id,
                        'qty' => (int) $detail->approved_qty,
                    ];
                }

                return [
                    'id' => (string) $detail->id,
                    'product_id' => (string) $detail->product_id,
                    'product_code' => $detail->product?->code,
                    'product_name' => $detail->product?->name ?? (string) $detail->product_id,
                    'unit_name' => $detail->product?->unit?->name,
                    'product_price' => (float) ($detail->product_price ?? 0),
                    'requested_qty' => (int) ($detail->requested_qty ?? 0),
                    'approved_qty' => (int) ($detail->approved_qty ?? 0),
                    'received_qty' => (int) ($detail->received_qty ?? 0),
                    'note' => $detail->note,
                    'allocations' => $allocations,
                ];
            })->values(),
            'warehouses' => $warehouses,
            'stock' => $stock,
            'is_editable' => $isEditable,
        ]);
    }

    public function approve(
        Request $request,
        StoreOrder $order,
        ApproveStoreOrder $approveStoreOrder
    ): RedirectResponse {
        $data = $request->validate([
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.detail_id' => ['required', 'uuid'],
            'allocations.*.warehouse_id' => [
                'required',
                'uuid',
                Rule::exists('warehouses', 'id')->whereNull('deleted_at'),
            ],
            'allocations.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $result = $approveStoreOrder->handle($order, $data['allocations'], Auth::id());

        $total = number_format($result['total_allocated'], 0, ',', '.');
        $orderCount = count($result['order_ids']);
        $message = $orderCount > 1
            ? "Order disetujui dan dipecah menjadi {$orderCount} order gudang. Total {$total} pcs dialokasikan."
            : "Order disetujui. Total {$total} pcs dialokasikan ke gudang terpilih.";

        return back()->with('success', $message);
    }

    public function reject(StoreOrder $order): RedirectResponse
    {
        if (strtolower((string) $order->status) !== 'requested') {
            return back()->withErrors([
                'allocations' => 'Hanya order berstatus menunggu approval yang dapat ditolak.',
            ]);
        }

        DB::transaction(function () use ($order): void {
            $order->forceFill([
                'status' => 'rejected',
                'approved_at' => null,
                'approved_by' => null,
            ])->save();

            if (Schema::hasTable('stock_reservations')) {
                DB::table('stock_reservations')
                    ->where('reference_type', 'store_order')
                    ->where('reference_id', $order->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'released',
                        'released_at' => now(),
                        'release_reason' => 'Store order rejected',
                        'updated_at' => now(),
                    ]);
            }
        });

        return back()->with('success', 'Order ditolak. Stok yang sempat dipesan sudah dilepaskan.');
    }

    private function stats(): array
    {
        $today = now()->toDateString();

        return [
            'pending' => StoreOrder::query()->where('status', 'requested')->count(),
            'pending_qty' => (int) DB::table('store_order_details as sod')
                ->join('store_orders as so', 'so.id', '=', 'sod.store_order_id')
                ->where('so.status', 'requested')
                ->sum('sod.requested_qty'),
            'approved_today' => StoreOrder::query()
                ->where('status', 'approved')
                ->whereDate('approved_at', $today)
                ->count(),
            'partial_today' => StoreOrder::query()
                ->where('status', 'partial_approved')
                ->whereDate('approved_at', $today)
                ->count(),
            'rejected_today' => StoreOrder::query()
                ->where('status', 'rejected')
                ->whereDate('updated_at', $today)
                ->count(),
        ];
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
