<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcommerceOrder;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\EcommerceOrderStockAlertService;
use App\Services\EcommerceRejectionHandoffService;
use App\Services\StockReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EcommerceOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LIST ORDER
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        EcommerceOrderStockAlertService $stockAlertService,
        EcommerceRejectionHandoffService $rejectionHandoff
    ) {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                'pending',
                'paid',
                'processed',
                'packing',
                'shipped',
                'received',
                'cancelled',
            ])],
            'handler_type' => ['nullable', Rule::in(['store', 'warehouse'])],
            'store_id' => ['nullable', 'uuid'],
            'warehouse_id' => ['nullable', 'uuid'],
            'payment_method_code' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $status = $validated['status'] ?? null;
        $handlerType = $validated['handler_type'] ?? null;
        $storeId = $validated['store_id'] ?? null;
        $warehouseId = $validated['warehouse_id'] ?? null;
        $paymentMethodCode = trim((string) ($validated['payment_method_code'] ?? ''));
        $paymentMethodCode = $paymentMethodCode !== '' ? $paymentMethodCode : null;
        $stockIssue = $request->boolean('stock_issue');
        $from = $validated['from'] ?? ($stockIssue ? null : now()->toDateString());
        $to = $validated['to'] ?? ($stockIssue ? null : now()->toDateString());
        $perPage = 20;

        $orders = EcommerceOrder::with([
            'store:id,name',
            'warehouse:id,name',
            'details.product:id,name',
            'payment:id,status,total_amount,paid_amount,transaction_number,completed_at',
        ])
            ->when($q !== '', function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('order_number', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(id AS TEXT)'), 'ILIKE', $like)
                        ->orWhereHas('details.product', function ($productQuery) use ($like) {
                            $productQuery->where('name', 'ILIKE', $like);
                        });
                });
            })
            ->when($status, function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->when($handlerType === 'store', function ($builder) use ($storeId) {
                $builder->whereNotNull('handled_by_store_id');

                if ($storeId) {
                    $builder->where('handled_by_store_id', $storeId);
                }
            })
            ->when($handlerType === 'warehouse', function ($builder) use ($warehouseId) {
                $builder->whereNotNull('handled_by_warehouse_id');

                if ($warehouseId) {
                    $builder->where('handled_by_warehouse_id', $warehouseId);
                }
            })
            ->when(! $handlerType && $storeId, function ($builder) use ($storeId) {
                $builder->where('handled_by_store_id', $storeId);
            })
            ->when(! $handlerType && $warehouseId, function ($builder) use ($warehouseId) {
                $builder->where('handled_by_warehouse_id', $warehouseId);
            })
            ->when($paymentMethodCode, fn ($builder) => $builder->where('payment_method_code', $paymentMethodCode))
            ->when($stockIssue, fn ($builder) => $stockAlertService->apply($builder))
            ->when($from && $to, fn ($builder) => $builder
                ->whereBetween(DB::raw('created_at::date'), [$from, $to]))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $stockIssueOrderIds = $stockAlertService->issueOrderIds(
            $orders->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all()
        );

        $orderIds = $orders->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $rejectionHandoffEnabled = (bool) config(
            'backoffice.ecommerce_rejection_handoff_enabled',
            false
        );
        $rejectionCases = [];
        $rejectionHandoffError = null;

        if ($rejectionHandoffEnabled) {
            try {
                $rejectionCases = $rejectionHandoff->summaryByOrder($orderIds);
            } catch (\Throwable $exception) {
                report($exception);
                $rejectionHandoffError = 'Data kendala toko sedang tidak dapat dibaca. Status kendala mungkin belum tampil sampai koneksi database POS pulih.';
            }
        }

        $stores = Store::select('id', 'name')
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::select('id', 'name')
            ->orderBy('name')
            ->get();

        $statuses = collect([
            'pending',
            'paid',
            'processed',
            'packing',
            'shipped',
            'received',
            'cancelled',
        ]);

        $paymentMethods = EcommerceOrder::query()
            ->select('payment_method_code', 'payment_method_name')
            ->whereNotNull('payment_method_code')
            ->where('payment_method_code', '<>', '')
            ->distinct()
            ->orderBy('payment_method_name')
            ->get();

        return Inertia::render('Ecommerce/Orders', [
            'orders' => $orders,
            'stores' => $stores,
            'warehouses' => $warehouses,
            'statuses' => $statuses,
            'editableStatuses' => ['processed', 'packing', 'shipped', 'received', 'cancelled'],
            'paymentMethods' => $paymentMethods,
            'stockIssueOrderIds' => $stockIssueOrderIds,
            'rejectionCases' => $rejectionCases,
            'rejectionHandoffEnabled' => $rejectionHandoffEnabled,
            'rejectionHandoffError' => $rejectionHandoffError,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'handler_type' => $handlerType,
                'store_id' => $storeId,
                'warehouse_id' => $warehouseId,
                'payment_method_code' => $paymentMethodCode,
                'stock_issue' => $stockIssue,
                'from' => $from,
                'to' => $to,
            ],
            'urls' => [
                'index' => route('ecommerce.orders.index'),
                'update' => route('ecommerce.orders.update', ['id' => '__ID__']),
                'rejections' => route('ecommerce.rejections.index'),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE ORDER
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        $id,
        StockReservationService $reservations,
        EcommerceRejectionHandoffService $rejectionHandoff
    ) {
        $order = EcommerceOrder::findOrFail($id);

        if (strtolower((string) $order->status) !== 'paid') {
            return redirect()
                ->route('ecommerce.orders.index')
                ->with('error', 'Pesanan hanya dapat diubah jika statusnya sudah dibayar.');
        }

        $isCancellation = $request->input('status') === 'cancelled';

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                'processed',
                'packing',
                'shipped',
                'received',
                'cancelled',
            ])],
            'handler_type' => [
                'nullable',
                Rule::in(['store', 'warehouse']),
                Rule::requiredIf(! $isCancellation),
            ],
            'store_id' => [
                Rule::requiredIf(! $isCancellation && $request->input('handler_type') === 'store'),
                'nullable',
                Rule::exists('stores', 'id')->whereNull('deleted_at'),
            ],
            'warehouse_id' => [
                Rule::requiredIf(! $isCancellation && $request->input('handler_type') === 'warehouse'),
                'nullable',
                Rule::exists('warehouses', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $handlerType = $validated['handler_type'] ?? null;
        $locationId = null;
        $locationName = null;

        if (! $isCancellation) {
            $locationId = $handlerType === 'store'
                ? $validated['store_id']
                : $validated['warehouse_id'];
            $locationName = $handlerType === 'store'
                ? Store::whereKey($locationId)->value('name')
                : Warehouse::whereKey($locationId)->value('name');

            if (! $locationName) {
                throw ValidationException::withMessages([
                    'handler_type' => 'Lokasi penanganan tidak ditemukan atau sudah diarsipkan.',
                ]);
            }

            $requirements = $this->orderRequirements($order);
            $stocks = $this->stockMap(
                $handlerType,
                $locationId,
                $requirements->keys()->all(),
                $reservations,
                (string) $order->id
            );
            $shortages = $requirements
                ->filter(fn (int $qty, string $productId) => ($stocks[$productId] ?? 0) < $qty);

            if ($shortages->isNotEmpty()) {
                $productNames = DB::table('products')
                    ->whereIn('id', $shortages->keys())
                    ->pluck('name', 'id');

                $messages = $shortages->map(function (int $needed, string $productId) use ($stocks, $productNames) {
                    $name = $productNames[$productId] ?? 'Produk';

                    return "{$name}: perlu {$needed}, tersedia ".($stocks[$productId] ?? 0);
                })->values()->join('; ');

                throw ValidationException::withMessages([
                    'handler_type' => "Stok {$locationName} tidak mencukupi. {$messages}.",
                ]);
            }
        }

        DB::transaction(function () use (
            $order,
            $validated,
            $isCancellation,
            $handlerType,
            $reservations
        ): void {
            $locked = EcommerceOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan tidak ditemukan.',
                ]);
            }

            if (strtolower((string) $locked->status) !== 'paid') {
                throw ValidationException::withMessages([
                    'order' => 'Status pesanan sudah berubah. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            $locked->status = $validated['status'];

            if ($isCancellation) {
                $locked->handled_by_store_id = null;
                $locked->handled_by_warehouse_id = null;
            } elseif ($handlerType === 'store') {
                $locked->handled_by_store_id = $validated['store_id'];
                $locked->handled_by_warehouse_id = null;
            } else {
                $locked->handled_by_warehouse_id = $validated['warehouse_id'];
                $locked->handled_by_store_id = null;
            }

            $locked->save();

            // Setelah customer menerima pesanan, hold stok tidak boleh lagi
            // tampil sebagai "Dipesan" pada monitoring.
            if ($validated['status'] === 'received') {
                $reservations->completeEcommerceOrder((string) $locked->id);
            }
        });

        if ((bool) config('backoffice.ecommerce_rejection_handoff_enabled', false)) {
            $note = $isCancellation
                ? 'Diselesaikan dari pembaruan order Backoffice. Status: cancelled.'
                : 'Diselesaikan dari pembaruan order Backoffice. Status: '
                    .$validated['status']
                    .'; penanganan: '.$locationName.'.';

            try {
                $rejectionHandoff->resolveForOrderUpdate(
                    (string) $order->id,
                    (string) $request->user()->getAuthIdentifier(),
                    $note,
                    $isCancellation
                );
            } catch (\Throwable $exception) {
                report($exception);

                return redirect()
                    ->route('ecommerce.orders.index')
                    ->with('error', 'Order berhasil diperbarui, tetapi status kendala toko belum tersinkron. Buka halaman Kendala E-commerce untuk menyelesaikannya.');
            }
        }

        return redirect()
            ->route('ecommerce.orders.index')
            ->with(
                'success',
                $isCancellation
                    ? 'Pesanan berhasil dibatalkan.'
                    : "Pesanan berhasil diperbarui dan ditangani oleh {$locationName}."
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ORDER DETAIL API
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $order = EcommerceOrder::with([
            'store',
            'warehouse',
            'details.product',
        ])->findOrFail($id);

        return response()->json($order);
    }

    /*
    |--------------------------------------------------------------------------
    | STOCK API (DYNAMIC)
    |--------------------------------------------------------------------------
    */
    public function stock(Request $request, $id, StockReservationService $reservations)
    {
        $order = EcommerceOrder::with([
            'details.product:id,name',
        ])->findOrFail($id);

        $requirements = $this->orderRequirements($order);
        $productIds = $requirements->keys()->all();
        $productNames = $order->details
            ->mapWithKeys(fn ($detail) => [$detail->product_id => optional($detail->product)->name ?: '-']);

        $storeStocks = $this->allLocationStockMaps('store', $productIds, $reservations, (string) $order->id);
        $warehouseStocks = $this->allLocationStockMaps('warehouse', $productIds, $reservations, (string) $order->id);

        $stores = Store::query()->select('id', 'name')->orderBy('name')->get()
            ->map(fn (Store $store) => $this->locationAvailability(
                'store',
                $store->id,
                $store->name,
                $requirements,
                $storeStocks[$store->id] ?? []
            ));

        $warehouses = Warehouse::query()->select('id', 'name')->orderBy('name')->get()
            ->map(fn (Warehouse $warehouse) => $this->locationAvailability(
                'warehouse',
                $warehouse->id,
                $warehouse->name,
                $requirements,
                $warehouseStocks[$warehouse->id] ?? []
            ));

        $selectedType = $request->input('handler_type', 'store');
        $selectedId = $selectedType === 'warehouse'
            ? $request->input('warehouse_id')
            : $request->input('store_id');
        $selectedMap = $selectedType === 'warehouse'
            ? ($warehouseStocks[$selectedId] ?? [])
            : ($storeStocks[$selectedId] ?? []);

        return response()->json([
            'products' => $requirements->map(function (int $qty, string $productId) use ($productNames, $selectedMap) {
                return [
                    'product_id' => $productId,
                    'product_name' => $productNames[$productId] ?? '-',
                    'qty' => $qty,
                    'selected_stock' => (int) ($selectedMap[$productId] ?? 0),
                ];
            })->values(),
            'stores' => $stores,
            'warehouses' => $warehouses,
        ]);
    }

    private function orderRequirements(EcommerceOrder $order)
    {
        if (! $order->relationLoaded('details')) {
            $order->load('details:id,ecommerce_order_id,product_id,quantity');
        }

        return $order->details
            ->groupBy('product_id')
            ->map(fn ($details) => (int) $details->sum('quantity'));
    }

    private function stockMap(
        string $type,
        string $locationId,
        array $productIds,
        StockReservationService $reservations,
        ?string $excludeOrderId = null
    ): array {
        if ($locationId === '' || $productIds === []) {
            return [];
        }

        $physical = $this->physicalStockQuery($type, $productIds)
            ->where($this->locationColumn($type), $locationId)
            ->groupBy('items.product_id')
            ->selectRaw('items.product_id, COUNT(DISTINCT items.id)::int AS stock')
            ->pluck('stock', 'product_id')
            ->map(fn ($stock) => (int) $stock)
            ->all();

        $reserved = $reservations->reservedQuantities(
            $type,
            $locationId,
            $productIds,
            $excludeOrderId !== null ? 'ecommerce_order' : null,
            $excludeOrderId
        );

        foreach ($physical as $productId => $stock) {
            $physical[$productId] = max(0, $stock - ($reserved[$productId] ?? 0));
        }

        return $physical;
    }

    private function allLocationStockMaps(
        string $type,
        array $productIds,
        StockReservationService $reservations,
        ?string $excludeOrderId = null
    ): array {
        if ($productIds === []) {
            return [];
        }

        $physical = $this->physicalStockQuery($type, $productIds)
            ->groupBy($this->locationColumn($type), 'items.product_id')
            ->selectRaw(
                $this->locationColumn($type).' AS location_id, items.product_id, COUNT(DISTINCT items.id)::int AS stock'
            )
            ->get()
            ->groupBy('location_id')
            ->map(fn ($rows) => $rows->pluck('stock', 'product_id')
                ->map(fn ($stock) => (int) $stock)
                ->all())
            ->all();

        $reserved = $reservations->reservedQuantitiesByLocation(
            $type,
            $productIds,
            $excludeOrderId !== null ? 'ecommerce_order' : null,
            $excludeOrderId
        );

        foreach ($physical as $locationId => $products) {
            foreach ($products as $productId => $stock) {
                $physical[$locationId][$productId] = max(
                    0,
                    $stock - ($reserved[$locationId][$productId] ?? 0)
                );
            }
        }

        return $physical;
    }

    private function physicalStockQuery(string $type, array $productIds)
    {
        $linkTable = $type === 'store' ? 'store_items' : 'warehouse_items';
        $statuses = $type === 'store'
            ? ['store_stock', 'store_display', 'display_store']
            : ['warehouse_stock'];

        return DB::table('items')
            ->join($linkTable, "{$linkTable}.item_id", '=', 'items.id')
            ->whereIn('items.product_id', $productIds)
            ->whereIn(DB::raw('items.status::text'), $statuses);
    }

    private function locationColumn(string $type): string
    {
        return $type === 'store' ? 'store_items.store_id' : 'warehouse_items.warehouse_id';
    }

    private function locationAvailability(
        string $type,
        string $id,
        string $name,
        $requirements,
        array $stocks
    ): array {
        $shortages = $requirements
            ->filter(fn (int $qty, string $productId) => ($stocks[$productId] ?? 0) < $qty);

        return [
            'type' => $type,
            'id' => $id,
            'name' => $name,
            'total_stock' => array_sum($stocks),
            'can_fulfill' => $shortages->isEmpty(),
            'fulfilled_products' => $requirements->count() - $shortages->count(),
            'product_count' => $requirements->count(),
        ];
    }
}
