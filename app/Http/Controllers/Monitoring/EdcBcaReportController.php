<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\Payments\Queries\EdcBcaQuery;
use App\Exports\EdcBcaReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class EdcBcaReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-store-payment-transactions')
            ->only(['index', 'export']);
    }

    public function index(Request $request, EdcBcaQuery $query)
    {
        $filters = $this->filters($request);

        return Inertia::render('Monitoring/EdcBca', [
            ...$query->build(
                $filters['q'],
                $filters['store_id'],
                $filters['method_id'],
                $filters['status'],
                $filters['from'],
                $filters['to'],
                $filters['per_page']
            ),
            'urls' => [
                'index' => route('monitoring.edc-bca.index'),
                'export' => route('monitoring.edc-bca.export'),
                'payment_methods' => route('master-data.payment-methods.index'),
                'payments_page' => route('monitoring.store-payment-transactions.index'),
            ],
        ]);
    }

    public function export(Request $request, EdcBcaQuery $query)
    {
        $filters = $this->filters($request);

        $rows = $query->exportRows(
            $filters['q'],
            $filters['store_id'],
            $filters['method_id'],
            $filters['status'],
            $filters['from'],
            $filters['to']
        );

        $summary = $query->build(
            $filters['q'],
            $filters['store_id'],
            $filters['method_id'],
            $filters['status'],
            $filters['from'],
            $filters['to'],
            1
        );

        $filename = 'edc-bca-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new EdcBcaReportExport(
                $rows,
                (array) $summary['summary'],
                $summary['byMethod']->map(fn ($row) => (array) $row)->all(),
                $summary['mdr']
            ),
            $filename
        );
    }

    private function filters(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = trim((string) $request->query('store_id', ''));
        $methodId = trim((string) $request->query('method_id', ''));
        $status = trim((string) $request->query('status', ''));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $from = $from !== '' ? $from : now()->startOfMonth()->toDateString();
        $to = $to !== '' ? $to : now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) ($request->query('per_page', 15) ?: 15);

        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        return [
            'q' => $q,
            'store_id' => $storeId !== '' ? $storeId : null,
            'method_id' => $methodId !== '' ? $methodId : null,
            'status' => $status !== '' ? $status : null,
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
        ];
    }
}
