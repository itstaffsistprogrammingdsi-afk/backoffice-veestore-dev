<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Analytics\Queries\CustomerDemographicsQuery;
use App\Exports\CrmDemographicsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CustomerDemographicsOperationsController extends Controller
{
    public function __invoke(Request $request, CustomerDemographicsQuery $query)
    {
        $data = $query->build($request->query());

        return Inertia::render('Operations/CustomerDemographics', $data + [
            'urls' => [
                'index' => route('operations.customers.demographics'),
                'export' => route('operations.customers.demographics.export'),
                'customers' => route('operations.customers.index'),
                'manage_customers' => route('master-data.customers.index'),
                'customer_profile' => route('reports.crm.customer-profile', ['customerId' => '__ID__']),
                'regions' => [
                    'regencies' => route('reports.crm.demographics.regencies'),
                    'districts' => route('reports.crm.demographics.districts'),
                    'villages' => route('reports.crm.demographics.villages'),
                ],
            ],
        ]);
    }

    public function export(Request $request, CustomerDemographicsQuery $query)
    {
        return Excel::download(
            new CrmDemographicsExport($query->exportRows($request->query())),
            'customer-demografi-operasional-'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
