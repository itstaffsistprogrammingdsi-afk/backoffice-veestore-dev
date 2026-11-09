<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('stock-barriers:monitor')
            ->everyMinute()
            ->withoutOverlapping(5);

        $schedule->command('backoffice:notify-store-orders')
            ->everyFiveMinutes()
            ->withoutOverlapping(5);

        $schedule->command('backoffice:reserve-store-orders')
            ->everyFiveMinutes()
            ->withoutOverlapping(5);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
