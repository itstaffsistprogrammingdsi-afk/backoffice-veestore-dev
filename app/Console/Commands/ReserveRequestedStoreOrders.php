<?php

namespace App\Console\Commands;

use App\Models\StoreOrder;
use App\Services\StoreOrderReservationService;
use Illuminate\Console\Command;

class ReserveRequestedStoreOrders extends Command
{
    protected $signature = 'backoffice:reserve-store-orders {--limit=100}';

    protected $description = 'Reservasi stok untuk order toko yang masih menunggu approval.';

    public function handle(StoreOrderReservationService $reservations): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orders = StoreOrder::query()
            ->with('details')
            ->whereRaw('LOWER(status::text) = ?', ['requested'])
            ->whereNotNull('warehouse_id')
            ->where('requested_at', '>=', now()->subDays(30))
            ->orderBy('requested_at')
            ->limit($limit)
            ->get();

        $reserved = 0;

        foreach ($orders as $order) {
            $reserved += $reservations->reserve($order);
        }

        $this->info("Order diperiksa: {$orders->count()}, qty direservasi: {$reserved}.");

        return self::SUCCESS;
    }
}
