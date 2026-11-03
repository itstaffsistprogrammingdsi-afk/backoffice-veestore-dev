<?php

namespace App\Console\Commands;

use App\Services\StockBarrierAlertMonitorService;
use Illuminate\Console\Command;
use Throwable;

class MonitorStockBarriers extends Command
{
    protected $signature = 'stock-barriers:monitor';

    protected $description = 'Periksa barrier stok store dan warehouse lalu kirim alert baru.';

    public function handle(StockBarrierAlertMonitorService $monitor): int
    {
        try {
            $result = $monitor->run();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Pemantauan stok barrier gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        if (! $result['locked']) {
            $this->comment('Pemantauan stok barrier sudah berjalan di proses lain.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Stok barrier aktif: %d produk; alert baru: %d; email gagal: %d.',
            $result['active_count'],
            $result['new_count'],
            $result['email_failures']
        ));

        return self::SUCCESS;
    }
}
