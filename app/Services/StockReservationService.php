<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockReservationService
{
    /**
     * Total reservasi yang masih menahan stok. Untuk order cabang, qty yang
     * telah diterima tidak lagi dihitung sebagai "Dipesan" meski penerimaan
     * dilakukan sebagian.
     */
    public function remainingByLocation(string $handlerType)
    {
        if (! Schema::hasTable('stock_reservations')) {
            return DB::query()
                ->selectRaw('NULL::uuid as location_id, NULL::uuid as product_id, 0::int as qty_reserved')
                ->whereRaw('1 = 0');
        }

        return DB::table('stock_reservations as sr')
            ->leftJoinSub(
                DB::table('store_order_details')
                    ->select('store_order_id', 'product_id')
                    ->selectRaw('SUM(COALESCE(received_qty, 0))::int as received_qty')
                    ->groupBy('store_order_id', 'product_id'),
                'sod',
                function ($join) {
                    $join->on('sod.store_order_id', '=', 'sr.reference_id')
                        ->on('sod.product_id', '=', 'sr.product_id')
                        ->where('sr.reference_type', '=', 'store_order');
                }
            )
            ->leftJoin('ecommerce_orders as eo', function ($join) {
                $join->on('eo.id', '=', 'sr.reference_id')
                    ->where('sr.reference_type', '=', 'ecommerce_order');
            })
            ->select('sr.handler_id as location_id', 'sr.product_id')
            ->selectRaw("SUM(CASE
                WHEN sr.reference_type = 'store_order'
                    THEN GREATEST(sr.quantity - COALESCE(sod.received_qty, 0), 0)
                WHEN sr.reference_type = 'ecommerce_order'
                    AND LOWER(COALESCE(eo.status::text, '')) IN ('received', 'cancelled')
                    THEN 0
                ELSE sr.quantity
            END)::int as qty_reserved")
            ->where('sr.handler_type', $handlerType)
            ->where('sr.status', 'active')
            ->groupBy('sr.handler_id', 'sr.product_id');
    }

    /** @return Collection<int, object> */
    public function details(string $handlerType, string $handlerId, string $productId): Collection
    {
        if (! Schema::hasTable('stock_reservations')) {
            return collect();
        }

        return DB::table('stock_reservations as sr')
            ->leftJoin('store_orders as so', function ($join) {
                $join->on('so.id', '=', 'sr.reference_id')
                    ->where('sr.reference_type', '=', 'store_order');
            })
            ->leftJoinSub(
                DB::table('store_order_details')
                    ->select('store_order_id', 'product_id')
                    ->selectRaw('SUM(COALESCE(received_qty, 0))::int as received_qty')
                    ->groupBy('store_order_id', 'product_id'),
                'sod',
                function ($join) {
                    $join->on('sod.store_order_id', '=', 'so.id')
                        ->on('sod.product_id', '=', 'sr.product_id');
                }
            )
            ->leftJoin('stores as request_store', 'request_store.id', '=', 'so.store_id')
            ->leftJoin('users as requester', 'requester.id', '=', 'so.created_by')
            ->leftJoin('ecommerce_orders as eo', function ($join) {
                $join->on('eo.id', '=', 'sr.reference_id')
                    ->where('sr.reference_type', '=', 'ecommerce_order');
            })
            ->leftJoin('customers as customer', 'customer.id', '=', 'eo.customer_id')
            ->where('sr.handler_type', $handlerType)
            ->where('sr.handler_id', $handlerId)
            ->where('sr.product_id', $productId)
            ->where('sr.status', 'active')
            ->orderBy('sr.reserved_at')
            ->selectRaw("sr.id, sr.reference_type, sr.reference_id, sr.status, sr.quantity as reserved_quantity,
                sr.reserved_at, sr.expires_at,
                CASE
                    WHEN sr.reference_type = 'store_order'
                        THEN GREATEST(sr.quantity - COALESCE(sod.received_qty, 0), 0)
                    WHEN sr.reference_type = 'ecommerce_order'
                        AND LOWER(COALESCE(eo.status::text, '')) IN ('received', 'cancelled') THEN 0
                    ELSE sr.quantity
                END::int as remaining_quantity,
                COALESCE(so.order_number, eo.order_number, sr.reference_id::text) as order_number,
                CASE WHEN sr.reference_type = 'store_order' THEN 'Store'
                     WHEN sr.reference_type = 'ecommerce_order' THEN 'Customer'
                     ELSE sr.reference_type END as order_type,
                CASE WHEN sr.reference_type = 'store_order' THEN COALESCE(request_store.name, '-')
                     WHEN sr.reference_type = 'ecommerce_order' THEN COALESCE(customer.name, 'Customer')
                     ELSE '-' END as ordered_for,
                CASE WHEN sr.reference_type = 'store_order' THEN COALESCE(requester.name, '-')
                     WHEN sr.reference_type = 'ecommerce_order' THEN COALESCE(customer.name, 'Customer')
                     ELSE '-' END as ordered_by,
                CASE WHEN sr.reference_type = 'store_order' THEN COALESCE(requester.name, request_store.name, '-')
                     WHEN sr.reference_type = 'ecommerce_order' THEN COALESCE(customer.name, 'Customer')
                     ELSE 'Sistem' END as reserved_by_name,
                CASE WHEN sr.reference_type = 'store_order' THEN 'Pemesan Store'
                     WHEN sr.reference_type = 'ecommerce_order' THEN 'Customer'
                     ELSE 'Sistem' END as reserved_by_role,
                CASE WHEN sr.reference_type = 'store_order' THEN COALESCE(request_store.name, '-')
                     WHEN sr.reference_type = 'ecommerce_order' THEN COALESCE(customer.name, 'Customer')
                     ELSE '-' END as reserved_for_name")
            ->get()
            ->map(function ($row) {
                $row->reserved_quantity = (int) $row->reserved_quantity;
                $row->remaining_quantity = (int) $row->remaining_quantity;
                $row->is_expired = $row->expires_at !== null && Carbon::parse($row->expires_at)->isPast();

                return $row;
            })
            ->filter(fn ($row) => $row->remaining_quantity > 0)
            ->values();
    }

    public function completeStoreOrder(string $orderId): void
    {
        if (! Schema::hasTable('stock_reservations')) {
            return;
        }

        $receivedByProduct = DB::table('store_order_details')
            ->where('store_order_id', $orderId)
            ->select('product_id')
            ->selectRaw('SUM(COALESCE(received_qty, 0))::int as received_qty')
            ->groupBy('product_id')
            ->pluck('received_qty', 'product_id');

        DB::table('stock_reservations')
            ->where('reference_type', 'store_order')
            ->where('reference_id', $orderId)
            ->where('status', 'active')
            ->orderBy('reserved_at')
            ->lockForUpdate()
            ->get()
            ->each(function ($reservation) use ($receivedByProduct) {
                if ((int) ($receivedByProduct[$reservation->product_id] ?? 0) < (int) $reservation->quantity) {
                    return;
                }

                DB::table('stock_reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'status' => 'consumed',
                        'consumed_at' => now(),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function completeEcommerceOrder(string $orderId): void
    {
        if (! Schema::hasTable('stock_reservations')) {
            return;
        }

        DB::table('stock_reservations')
            ->where('reference_type', 'ecommerce_order')
            ->where('reference_id', $orderId)
            ->where('status', 'active')
            ->update([
                'status' => 'consumed',
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
