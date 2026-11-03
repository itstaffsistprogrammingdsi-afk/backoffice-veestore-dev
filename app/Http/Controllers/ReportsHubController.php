<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsHubController extends Controller
{
    public function __invoke(Request $request, BusinessOverviewQuery $overview)
    {
        $data = $overview->build($request->query());

        return Inertia::render('Reports/Index', [
            'summary' => $data['sales']['summary'],
            'orders' => $data['orders']['summary'],
            'inventory' => $data['inventory']['summary'],
            'urls' => [
                'overview' => route('reports.business-overview.index'),
                'dashboard' => route('dashboard-analytics'),
                'assistant' => route('ai-assistant.index'),
                'monitoring' => route('monitoring.command-center.index'),
                'crm' => route('reports.crm.index'),
                'demographics' => route('reports.crm.demographics'),
                'preference' => route('reports.crm.customer-preference.index'),
                'exports' => route('report-exports.index'),
            ],
        ]);
    }
}
