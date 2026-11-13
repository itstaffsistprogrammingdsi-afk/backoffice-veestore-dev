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
            'filters' => $data['filters'],
            'periodOptions' => [
                ['value' => 'today', 'label' => 'Hari ini'],
                ['value' => 'week', 'label' => 'Minggu ini'],
                ['value' => 'month', 'label' => 'Bulan ini'],
                ['value' => 'last_month', 'label' => 'Bulan lalu'],
                ['value' => 'year', 'label' => 'Tahun ini'],
                ['value' => 'last_year', 'label' => 'Tahun lalu'],
            ],
            'summary' => $data['sales']['summary'],
            'orders' => $data['orders']['summary'],
            'inventory' => $data['inventory']['summary'],
            'urls' => [
                'hub' => route('reports.index'),
                'overview' => route('reports.business-overview.index'),
                'assistant' => route('ai-assistant.index'),
                'monitoring' => route('monitoring.command-center.index'),
                'crm' => route('reports.crm.index'),
                'demographics' => route('reports.crm.demographics'),
                'preference' => route('reports.crm.customer-preference.index'),
                'exports' => route('report-exports.index'),
                'branch_expansion' => route('monitoring.branch-expansion.index'),
                'loyalty_accounts' => route('monitoring.loyalty-accounts.index'),
                'pending_transactions' => route('monitoring.pending-transactions.index'),
                'anomalies' => route('monitoring.transaction-anomalies.index'),
                'warehouse_audit' => route('monitoring.warehouse-user-audit.index'),
                'stock_opname' => route('monitoring.stock-opname-variance.index'),
                'stock_movements' => route('monitoring.stock-movements.index'),
                'promo_acquisition' => route('monitoring.promo-loyalty.index'),
                'user_access' => route('monitoring.user-monitoring.index'),
            ],
        ]);
    }
}
