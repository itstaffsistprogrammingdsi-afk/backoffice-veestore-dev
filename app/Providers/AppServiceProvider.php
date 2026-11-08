<?php

namespace App\Providers;

use App\Ai\Providers\NineRouterProvider;
use App\Ai\Support\ToolCallLogger;
use App\Support\ExcelReportFormatter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Ai;
use Maatwebsite\Excel\Events\BeforeWriting;
use Maatwebsite\Excel\Writer;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ToolCallLogger::class);
    }

    public function boot(): void
    {
        Writer::listen(BeforeWriting::class, [ExcelReportFormatter::class, 'handle']);

        Ai::extend('nine_router', fn ($app, array $config) => new NineRouterProvider(
            $config,
            $app->make(Dispatcher::class)
        ));

    }
}
