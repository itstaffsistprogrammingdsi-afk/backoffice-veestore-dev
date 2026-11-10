<?php

namespace App\Http\Controllers\Monitoring;

use App\Domain\StoreOperations\Queries\StoreSampleGimmickReportQuery;
use App\Domain\StoreOperations\Queries\StoreSampleGimmickRequestQuery;
use App\Domain\StoreOperations\Support\SaleConditionMetrics;
use App\Exports\SampleGimmickReportExport;
use App\Http\Controllers\Controller;
use App\Support\Concerns\NormalizesDate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SampleGimmickReportController extends Controller
{
    use NormalizesDate;

    public function __construct()
    {
        $this->middleware('permission:view monitoring-omset-kotor')
            ->only(['index', 'export']);
    }

    public function index(
        Request $request,
        StoreSampleGimmickReportQuery $reportQuery,
        StoreSampleGimmickRequestQuery $requestQuery
    ) {
        $filters = $this->filters($request);
        $data = $reportQuery->build($filters);
        $requestData = $requestQuery->build(
            $filters['q'],
            $filters['store_id'] ?: null,
            null,
            '',
            $filters['per_page']
        );

        $summary = [
            ...$data['summary'],
            'total_pending_requests' => (int) ($requestData['requestSummary']['pending'] ?? 0),
            'total_approved_requests' => (int) ($requestData['requestSummary']['approved'] ?? 0),
        ];

        return Inertia::render('Monitoring/Report', [
            'title' => 'Sample, Gimmick & Gratis',
            'description' => 'Laporan terpisah untuk sample, gimmick, dan gratis. Nilai barang pada laporan ini tidak masuk omset kotor, omset bersih, maupun total penjualan toko. Gunakan tab data untuk melihat gimmick dari nota, stok sample, stok gimmick, dan pengajuan.',
            'data' => [
                ...$data,
                'requests' => $requestData['requests'],
                'summary' => $summary,
                'conditionStatus' => $filters['condition_status'] === 'all' ? '' : $filters['condition_status'],
                'conditionStatusOptions' => SaleConditionMetrics::conditionOptions(),
                'dateFrom' => $filters['date_from'],
                'dateTo' => $filters['date_to'],
                'q' => $filters['q'],
                'storeId' => $filters['store_id'] ?: '',
                'perPage' => $filters['per_page'],
                'omsetTab' => 'sample_gimmick',
                'omsetUrls' => [
                    'cabang' => route('monitoring.omset-cabang.index'),
                    'kotor' => route('monitoring.omset-kotor.index'),
                    'sample_gimmick' => route('monitoring.sample-gimmick-report.index'),
                ],
                'lastUpdate' => now()->format('d-m-Y H:i:s'),
            ],
            'filters' => request()->query(),
            'urls' => [
                'index' => url()->current(),
                'export' => route('monitoring.sample-gimmick-report.export'),
            ],
        ]);
    }

    public function export(
        Request $request,
        StoreSampleGimmickReportQuery $reportQuery,
        StoreSampleGimmickRequestQuery $requestQuery
    ) {
        $filters = $this->filters($request);
        $data = $reportQuery->exportData($filters);
        $requestData = $requestQuery->build(
            $filters['q'],
            $filters['store_id'] ?: null,
            null,
            '',
            $filters['per_page']
        );

        return Excel::download(
            new SampleGimmickReportExport(
                $data['gimmickReceiptRows'],
                $data['sampleRows'],
                $data['gimmickStockRows'],
                $requestData['requests']->getCollection()
            ),
            'sample-gimmick-gratis-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $today = now()->toDateString();
        $dateFrom = $this->normalizeDate($request->query('date_from')) ?? now()->startOfMonth()->toDateString();
        $dateTo = $this->normalizeDate($request->query('date_to')) ?? $today;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'store_id' => $this->normalizeNullableString($request->query('store_id')),
            'q' => $this->normalizeNullableString($request->query('q')) ?? '',
            'condition_status' => SaleConditionMetrics::normalizeConditionStatus($request->query('condition_status')),
            'per_page' => $perPage,
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
