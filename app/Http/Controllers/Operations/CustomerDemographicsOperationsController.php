<?php

namespace App\Http\Controllers\Operations;

use App\Domain\Analytics\Queries\CustomerDemographicsQuery;
use App\Exports\CrmDemographicsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BirthdayGreetingRequest;
use App\Services\BirthdayGreetingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CustomerDemographicsOperationsController extends Controller
{
    public function __invoke(
        Request $request,
        CustomerDemographicsQuery $query,
        BirthdayGreetingService $greeting
    ) {
        $data = $query->build($request->query());

        return Inertia::render('Operations/CustomerDemographics', $data + [
            'birthdayGreeting' => [
                'template' => $greeting->template(),
                'vouchers' => $greeting->availableVouchers(),
            ],
            'urls' => [
                'index' => route('operations.customers.demographics'),
                'export' => route('operations.customers.demographics.export'),
                'customers' => route('operations.customers.index'),
                'manage_customers' => route('master-data.customers.index'),
                'customer_profile' => route('reports.crm.customer-profile', ['customerId' => '__ID__']),
                'birthday_greeting' => route('operations.customers.demographics.birthday-greeting'),
                'regions' => [
                    'regencies' => route('reports.crm.demographics.regencies'),
                    'districts' => route('reports.crm.demographics.districts'),
                    'villages' => route('reports.crm.demographics.villages'),
                ],
            ],
        ]);
    }

    public function sendBirthdayGreeting(BirthdayGreetingRequest $request, BirthdayGreetingService $greeting)
    {
        $result = $greeting->send(
            $request->validated('customer_ids'),
            $request->validated('subject'),
            $request->validated('message'),
            $request->validated('voucher_id'),
        );

        return back()->with(
            $result['sent'] > 0 ? 'success' : 'error',
            $greeting->summaryMessage($result)
        );
    }

    public function export(Request $request, CustomerDemographicsQuery $query)
    {
        return Excel::download(
            new CrmDemographicsExport($query->exportRows($request->query())),
            'customer-demografi-operasional-'.now()->format('Ymd_His').'.xlsx'
        );
    }
}
