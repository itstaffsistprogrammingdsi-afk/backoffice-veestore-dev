<?php

namespace App\Providers;

use App\Ai\Providers\NineRouterProvider;
use App\Ai\Support\ToolCallLogger;
use App\Services\BranchOperationNotificationService;
use App\Support\ExcelReportFormatter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\View;
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

        View::composer([
            'layouts.sections.navbar.navbar',
            'layouts.sections.menu.verticalMenu',
        ], function ($view) {
            // Keep memoization inside this request, including with long-lived workers.
            $summary = request()->attributes->get('operational_notification_summary');

            if ($summary === null) {
                try {
                    $summary = app(BranchOperationNotificationService::class)->summary();
                } catch (\Throwable $exception) {
                    report($exception);
                    $summary = [
                        'notifications' => [],
                        'total_count' => 0,
                        'menu_counts' => [],
                        'branch_operations_count' => 0,
                    ];
                }
                request()->attributes->set('operational_notification_summary', $summary);
            }

            $view->with([
                'notifications' => $summary['notifications'],
                'notificationCount' => $summary['total_count'],
                'menuNotificationCounts' => $summary['menu_counts'],
                'branchOperationsNotificationCount' => $summary['branch_operations_count'],
            ]);
        });
    }
}
