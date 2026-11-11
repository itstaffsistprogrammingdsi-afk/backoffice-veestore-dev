<?php

namespace App\Http\Controllers\Operations;

use App\Domain\StoreOperations\Actions\CreateStoreTransfer;
use App\Domain\StoreOperations\Actions\ReceiveStoreTransfer;
use App\Domain\StoreOperations\Actions\ShipStoreTransfer;
use App\Domain\StoreOperations\Queries\StoreTransferQuery;
use App\Domain\StoreOperations\Support\StoreTransferStock;
use App\Domain\StoreOperations\Support\WarehouseStockAvailability;
use App\Http\Controllers\Controller;
use App\Models\StoreOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StoreTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view branch-order-cabang');
    }

    public function index(Request $request, StoreTransferQuery $query): Response
    {
        $q = trim((string) $request->query('q', ''));
        $sourceStoreId = $this->nullableString($request->query('source_store_id'));
        $destinationStoreId = $this->nullableString($request->query('destination_store_id'));
        $status = $this->nullableString($request->query('status'));

        if ($status !== null && ! array_key_exists($status, StoreTransferQuery::STATUS_LABELS)) {
            $status = null;
        }

        $from = $this->normalizeDate($request->query('from'));
        $to = $this->normalizeDate($request->query('to'));

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return Inertia::render('Operations/StoreTransfers', [
            ...$query->build($q, $sourceStoreId, $destinationStoreId, $status, $from, $to, $perPage),
            'urls' => [
                'index' => route('operations.store-transfers.index'),
                'store' => route('operations.store-transfers.store'),
                'show' => route('operations.store-transfers.show', ['transfer' => '__ID__']),
                'stock' => route('operations.store-transfers.stock'),
                'approve' => route('operations.store-transfers.approve', ['transfer' => '__ID__']),
                'ship' => route('operations.store-transfers.ship', ['transfer' => '__ID__']),
                'receive' => route('operations.store-transfers.receive', ['transfer' => '__ID__']),
                'reject' => route('operations.store-transfers.reject', ['transfer' => '__ID__']),
                'cancel' => route('operations.store-transfers.cancel', ['transfer' => '__ID__']),
                'stock_page' => route('operations.store-stock.index'),
                'orders_page' => route('operations.store-orders.index'),
            ],
        ]);
    }

    public function stock(
        Request $request,
        StoreTransferStock $stock,
        WarehouseStockAvailability $warehouseAvailability
    ): JsonResponse {
        $data = $request->validate([
            'source_store_id' => [
                'required',
                'uuid',
                Rule::exists('stores', 'id')->whereNull('deleted_at'),
            ],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $products = $stock->products(
            (string) $data['source_store_id'],
            trim((string) ($data['q'] ?? ''))
        );

        $warehouseIds = DB::table('warehouses')
            ->whereNull('deleted_at')
            ->pluck('id')
            ->all();

        $warehouseTotals = $warehouseAvailability->availableTotals(
            $warehouseIds,
            array_map(fn (array $product): string => $product['product_id'], $products)
        );

        foreach ($products as &$product) {
            $warehouseAvailable = (int) ($warehouseTotals[$product['product_id']] ?? 0);
            $product['warehouse_available_qty'] = $warehouseAvailable;
            $product['can_transfer'] = $warehouseAvailable === 0;
        }

        unset($product);

        return response()->json([
            'products' => $products,
            'warehouse_rule' => 'Transfer antar toko hanya tersedia jika stok gudang untuk produk sudah habis.',
        ]);
    }

    public function store(Request $request, CreateStoreTransfer $create): RedirectResponse
    {
        $data = $request->validate([
            'source_store_id' => [
                'required',
                'uuid',
                Rule::exists('stores', 'id')->whereNull('deleted_at'),
            ],
            'destination_store_id' => [
                'required',
                'uuid',
                Rule::exists('stores', 'id')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ], [
            'source_store_id.required' => 'Toko asal wajib dipilih.',
            'destination_store_id.required' => 'Toko tujuan wajib dipilih.',
            'items.required' => 'Pilih minimal satu produk untuk ditransfer.',
            'items.*.qty.min' => 'Qty transfer minimal 1 pcs.',
        ]);

        $order = $create->handle(
            (string) $data['source_store_id'],
            (string) $data['destination_store_id'],
            $data['items'],
            Auth::id(),
            $this->nullableString($data['note'] ?? null)
        );

        return redirect()
            ->route('operations.store-transfers.index')
            ->with('success', 'Transfer '.$order->order_number.' berhasil dibuat dan menunggu persetujuan.');
    }

    public function show(StoreOrder $transfer, StoreTransferQuery $query): JsonResponse
    {
        $this->ensureTransfer($transfer);

        $detail = $query->detail((string) $transfer->id);

        abort_unless($detail !== null, 404);

        return response()->json($detail);
    }

    public function approve(
        StoreOrder $transfer,
        WarehouseStockAvailability $warehouseAvailability
    ): RedirectResponse {
        $this->ensureTransfer($transfer);

        DB::transaction(function () use ($transfer, $warehouseAvailability): void {
            $locked = StoreOrder::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if (strtolower((string) $locked->status) !== 'requested') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer yang menunggu persetujuan yang dapat disetujui.',
                ]);
            }

            $locked->load('details');
            $productIds = $locked->details
                ->pluck('product_id')
                ->map(fn ($productId): string => (string) $productId)
                ->unique()
                ->values()
                ->all();

            $warehouseIds = DB::table('warehouses')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            $warehouseTotals = $warehouseAvailability->availableTotals($warehouseIds, $productIds);
            $restockedProductId = collect($warehouseTotals)
                ->filter(fn (int $qty): bool => $qty > 0)
                ->keys()
                ->first();

            if ($restockedProductId !== null) {
                $productName = DB::table('products')
                    ->where('id', $restockedProductId)
                    ->value('name') ?? (string) $restockedProductId;

                throw ValidationException::withMessages([
                    'status' => "Stok gudang untuk '{$productName}' sudah tersedia kembali. Batalkan transfer ini dan buat permintaan order ke gudang.",
                ]);
            }

            $locked->details()->update([
                'approved_qty' => DB::raw('requested_qty'),
                'updated_at' => now(),
            ]);

            $locked->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ])->save();
        });

        return back()->with('success', 'Transfer disetujui. Lanjutkan pengiriman dari toko asal.');
    }

    public function ship(StoreOrder $transfer, ShipStoreTransfer $ship): RedirectResponse
    {
        $this->ensureTransfer($transfer);

        $ship->handle($transfer, Auth::id());

        return back()->with('success', 'Transfer dikirim. Barang tercatat dalam perjalanan menuju toko tujuan.');
    }

    public function receive(Request $request, StoreOrder $transfer, ReceiveStoreTransfer $receive): RedirectResponse
    {
        $this->ensureTransfer($transfer);

        $data = $request->validate([
            'received_qty' => ['nullable', 'array'],
            'received_qty.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $receive->handle($transfer, $data['received_qty'] ?? [], Auth::id());

        $message = $result['status'] === 'received'
            ? "Transfer selesai diterima. {$result['received']} pcs berpindah ke toko tujuan."
            : "Penerimaan sebagian disimpan. {$result['received']} pcs berpindah ke toko tujuan.";

        return back()->with('success', $message);
    }

    public function reject(StoreOrder $transfer): RedirectResponse
    {
        $this->ensureTransfer($transfer);

        DB::transaction(function () use ($transfer): void {
            $locked = StoreOrder::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if (strtolower((string) $locked->status) !== 'requested') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer yang menunggu persetujuan yang dapat ditolak.',
                ]);
            }

            $locked->forceFill(['status' => 'rejected'])->save();
            $this->clearScanRows((string) $locked->shipping_number);
        });

        return back()->with('success', 'Transfer ditolak.');
    }

    public function cancel(StoreOrder $transfer): RedirectResponse
    {
        $this->ensureTransfer($transfer);

        DB::transaction(function () use ($transfer): void {
            $locked = StoreOrder::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if (strtolower((string) $locked->status) !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya transfer yang sudah disetujui dan belum dikirim yang dapat dibatalkan.',
                ]);
            }

            $locked->forceFill(['status' => 'cancelled'])->save();
            $this->clearScanRows((string) $locked->shipping_number);
        });

        return back()->with('success', 'Transfer dibatalkan.');
    }

    private function ensureTransfer(StoreOrder $transfer): void
    {
        abort_unless(str_starts_with((string) $transfer->order_number, StoreTransferQuery::ORDER_PREFIX), 404);
    }

    private function clearScanRows(?string $shippingNumber): void
    {
        if (! $shippingNumber) {
            return;
        }

        $outboundId = DB::table('store_outbounds')
            ->where('shipping_number', $shippingNumber)
            ->where('outbound_type', 'transfer')
            ->value('id');

        if (! $outboundId) {
            return;
        }

        DB::table('store_outbound_details')
            ->where('store_outbound_id', $outboundId)
            ->delete();
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
