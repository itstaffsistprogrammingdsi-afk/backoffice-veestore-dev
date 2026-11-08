<?php

namespace App\Console\Commands;

use App\Models\StoreOrder;
use App\Services\StoreOrderNotifier;
use Illuminate\Console\Command;

class NotifyRequestedStoreOrders extends Command
{
    protected $signature = 'backoffice:notify-store-orders {--limit=50}';

    protected $description = 'Kirim email notifikasi untuk PO toko yang masih menunggu persetujuan.';

    public function handle(StoreOrderNotifier $notifier): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $orders = StoreOrder::query()
            ->with(['store', 'warehouse'])
            ->whereRaw('LOWER(status::text) = ?', ['requested'])
            ->where('requested_at', '>=', now()->subDays(30))
            ->orderBy('requested_at')
            ->limit($limit)
            ->get();

        $sent = 0;

        foreach ($orders as $order) {
            if ($notifier->requested($order)) {
                $sent++;
            }
        }

        $this->info("Notifikasi PO diperiksa: {$orders->count()}, terkirim: {$sent}.");

        return self::SUCCESS;
    }
}
