<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Queries\AnalyticsDashboardQuery;
use Inertia\Inertia;

class ReportsHubController extends Controller
{
    public function __invoke(AnalyticsDashboardQuery $analytics)
    {
        $latestRun = $analytics->latestCompletedRun();

        return Inertia::render('Reports/Index', [
            'latestRun' => $latestRun,
            'summary' => $analytics->summary($latestRun),
            'urls' => [
                'dashboard' => route('dashboard-analytics'),
                'ai' => route('ai-analytics.index'),
                'assistant' => route('ai-assistant.index'),
                'monitoring' => route('monitoring.command-center.index'),
                'crm' => route('reports.crm.index'),
                'exports' => route('report-exports.index'),
            ],
        ]);
    }
}
