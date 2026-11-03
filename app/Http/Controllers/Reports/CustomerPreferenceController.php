<?php

namespace App\Http\Controllers\Reports;

use App\Domain\Analytics\Queries\CustomerProductPreferenceQuery;
use App\Exports\CustomerPreferenceExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CustomerPreferenceController extends Controller
{
    public function index(Request $request, CustomerProductPreferenceQuery $query)
    {
        return Inertia::render('Reports/CustomerPreference', $query->build($request->query()) + [
            'urls' => [
                'index' => route('reports.crm.customer-preference.index'),
                'export' => route('reports.crm.customer-preference.export'),
                'demographics' => route('reports.crm.demographics'),
                'crm' => route('reports.crm.index'),
                'customer_profiles' => route('reports.crm.customer-profiles'),
            ],
        ]);
    }

    public function export(Request $request, CustomerProductPreferenceQuery $query)
    {
        $meta = $query->exportMeta($request->query());

        return Excel::download(
            new CustomerPreferenceExport(
                $query->exportRows($request->query()),
                $meta['filters'],
                $meta['dimension_label'],
                $meta['metric_label'],
                $meta['channel_label'],
                $meta['store_name'],
            ),
            'customer-preference-'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
