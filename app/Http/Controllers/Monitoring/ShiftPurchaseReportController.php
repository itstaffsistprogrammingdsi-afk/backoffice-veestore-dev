<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\StoreOperations\Queries\ShiftPurchaseQuery;
use App\Exports\ShiftPurchaseReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ShiftPurchaseReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view monitoring-cashier-shift-report')
            ->only(['index', 'detail', 'export']);
    }

    public function index(Request $request, ShiftPurchaseQuery $query)
    {
        $filters = $this->filters($request);

        return Inertia::render('Monitoring/ShiftPurchases', [
            ...$query->build($filters, $filters['per_page']),
            'urls' => [
                'index' => route('monitoring.shift-purchases.index'),
                'detail' => route('monitoring.shift-purchases.detail'),
                'export' => route('monitoring.shift-purchases.export'),
                'shift_report' => route('monitoring.cashier-shift-report.index'),
            ],
        ]);
    }

    public function detail(Request $request, ShiftPurchaseQuery $query): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'store_id' => ['required', 'uuid', 'exists:stores,id'],
            'shift_id' => ['nullable', 'uuid', 'exists:store_attendance_shifts,id'],
            'cashier_id' => ['nullable', 'uuid', 'exists:users,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $filters = [
            'q' => trim((string) ($data['q'] ?? '')),
            'store_id' => (string) $data['store_id'],
            'shift_id' => $data['shift_id'] ?? null,
            'cashier_id' => $data['cashier_id'] ?? null,
            'from' => (string) $data['date'],
            'to' => (string) $data['date'],
        ];

        return response()->json($query->detail($filters));
    }

    public function export(Request $request, ShiftPurchaseQuery $query)
    {
        $filters = $this->filters($request);
        $data = $query->exportData($filters);

        $filename = 'laporan-pembelian-per-shift-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(
            new ShiftPurchaseReportExport(
                collect($data['rows']),
                collect($data['products']),
                collect($data['transactions']),
                $data['summary'],
                $filters
            ),
            $filename
        );
    }

    private function filters(Request $request): array
    {
        $today = now()->toDateString();

        $from = ShiftPurchaseQuery::normalizeDate($request->query('from')) ?: $today;
        $to = ShiftPurchaseQuery::normalizeDate($request->query('to')) ?: $today;

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'store_id' => $this->nullableString($request->query('store_id')),
            'shift_id' => $this->nullableString($request->query('shift_id')),
            'cashier_id' => $this->nullableString($request->query('cashier_id')),
            'from' => $from,
            'to' => $to,
            'per_page' => $perPage,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
