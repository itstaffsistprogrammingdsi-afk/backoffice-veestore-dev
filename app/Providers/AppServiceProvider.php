<?php

namespace App\Providers;

use App\Services\BranchOperationNotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
