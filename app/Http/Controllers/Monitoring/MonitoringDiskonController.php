<?php

namespace App\Http\Controllers\Monitoring;

use App\Exports\MonitoringDiskonExport;
use App\Http\Controllers\Controller;
use App\Support\Concerns\NormalizesDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MonitoringDiskonController extends Controller
{
    use NormalizesDate;

    public function index(Request $request)
    {
        $filters = $this->getFilters($request);

        $query = $this->buildRowsQuery($filters);

        $summary = $this->getSummary($filters);

        $rows = $query
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Diskon',
            'description' => 'Laporan monitoring.',
            'data' => [
                'rows' => $rows,
                'summary' => $summary,

                'name' => $filters['name'],
                'type' => $filters['type'],
                'isActive' => $filters['is_active'],
                'periodStatus' => $filters['period_status'],
                'startDate' => $filters['start_date'],
                'endDate' => $filters['end_date'],
                'perPage' => $filters['per_page'],
                'lastUpdate' => now()->format('d-m-Y H:i:s'),

                'types' => [
                    'percentage' => 'Percentage',
                    'fixed' => 'Fixed',
                ],
            ],
            'filters' => request()->query(),
            'urls' => ['index' => url()->current(), 'export' => route('monitoring.diskon.export')],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->getFilters($request);

        $request->merge([
            'name' => $filters['name'],
            'type' => $filters['type'],
            'is_active' => $filters['is_active'],
            'period_status' => $filters['period_status'],
            'start_date' => $filters['start_date'],
            'end_date' => $filters['end_date'],
        ]);

        $filename = 'monitoring-diskon-'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new MonitoringDiskonExport($request), $filename);
    }

    private function getFilters(Request $request): array
    {
        $perPage = (int) ($request->query('per_page', 10) ?: 10);

        if (! in_array($perPage, [10, 25, 50, 100, 200], true)) {
            $perPage = 10;
        }

        return [
            'name' => trim((string) $request->query('name', '')),
            'type' => $this->normalizeNullableString($request->query('type')),
            'is_active' => $this->normalizeNullableString($request->query('is_active')),
            'period_status' => $this->normalizeNullableString($request->query('period_status')),
            'start_date' => $this->normalizeDate($request->query('start_date')),
            'end_date' => $this->normalizeDate($request->query('end_date')),
            'per_page' => $perPage,
        ];
    }

    private function buildRowsQuery(array $filters)
    {
        $query = DB::table('discounts as d')
            ->leftJoin('promos as p', 'p.id', '=', 'd.promo_id')
            ->select([
                'd.id',
                'd.name',
                'd.description',
                'd.type',
                'd.value',
                'd.max_discount',
                'd.min_purchase',
                'd.valid_from',
                'd.valid_until',
                'd.usage_limit',
                'd.usage_count',
                'd.is_active',
                'd.priority',
                'd.combinable',
                'd.created_at',
                DB::raw("COALESCE(p.name, '-') as promo_name"),
                DB::raw("
                    CASE
                        WHEN d.valid_from IS NULL AND d.valid_until IS NULL THEN 'no_period'
                        WHEN d.valid_from IS NOT NULL AND CURRENT_TIMESTAMP < d.valid_from THEN 'not_started'
                        WHEN d.valid_until IS NOT NULL AND CURRENT_TIMESTAMP > d.valid_until THEN 'expired'
                        WHEN d.valid_from IS NOT NULL
                             AND d.valid_until IS NOT NULL
                             AND CURRENT_TIMESTAMP BETWEEN d.valid_from AND d.valid_until THEN 'active'
                        WHEN d.valid_from IS NULL
                             AND d.valid_until IS NOT NULL
                             AND CURRENT_TIMESTAMP <= d.valid_until THEN 'active'
                        WHEN d.valid_from IS NOT NULL
                             AND d.valid_until IS NULL
                             AND CURRENT_TIMESTAMP >= d.valid_from THEN 'active'
                        ELSE 'unknown'
                    END as period_status_label
                "),
            ]);

        $this->applyDeletedAtFilter($query, 'discounts', 'd');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('d.created_at')
            ->orderBy('d.name');
    }

    private function getSummary(array $filters)
    {
        $query = DB::table('discounts as d')
            ->leftJoin('promos as p', 'p.id', '=', 'd.promo_id');

        $this->applyDeletedAtFilter($query, 'discounts', 'd');

        /*
        |--------------------------------------------------------------------------
        | Summary mengikuti filter utama
        |--------------------------------------------------------------------------
        | Summary tetap mengikuti filter nama, type, status aktif, dan tanggal.
        | Jika period_status dipilih, summary juga ikut terfilter.
        */
        $this->applyFilters($query, $filters);

        return $query
            ->selectRaw('
                COUNT(d.id)::int as total_discount,

                COUNT(
                    CASE
                        WHEN COALESCE(d.is_active, false) = true
                             AND (
                                (
                                    d.valid_from IS NOT NULL
                                    AND d.valid_until IS NOT NULL
                                    AND CURRENT_TIMESTAMP BETWEEN d.valid_from AND d.valid_until
                                )
                                OR (
                                    d.valid_from IS NULL
                                    AND d.valid_until IS NOT NULL
                                    AND CURRENT_TIMESTAMP <= d.valid_until
                                )
                                OR (
                                    d.valid_from IS NOT NULL
                                    AND d.valid_until IS NULL
                                    AND CURRENT_TIMESTAMP >= d.valid_from
                                )
                                OR (
                                    d.valid_from IS NULL
                                    AND d.valid_until IS NULL
                                )
                             )
                        THEN 1
                    END
                )::int as total_active_discount,

                COUNT(
                    CASE
                        WHEN d.valid_until IS NOT NULL
                             AND CURRENT_TIMESTAMP > d.valid_until
                        THEN 1
                    END
                )::int as total_expired_discount,

                COUNT(
                    CASE
                        WHEN d.valid_from IS NOT NULL
                             AND CURRENT_TIMESTAMP < d.valid_from
                        THEN 1
                    END
                )::int as total_not_started_discount,

                COUNT(
                    CASE
                        WHEN d.valid_from IS NULL
                             AND d.valid_until IS NULL
                        THEN 1
                    END
                )::int as total_no_period_discount,

                COUNT(
                    CASE
                        WHEN COALESCE(d.is_active, false) = false
                        THEN 1
                    END
                )::int as total_inactive_discount,

                COALESCE(SUM(COALESCE(d.usage_count, 0)), 0)::int as total_usage_count
            ')
            ->first();
    }

    private function applyFilters($query, array $filters): void
    {
        $name = $filters['name'] ?? '';
        $type = $filters['type'] ?? null;
        $isActive = $filters['is_active'] ?? null;
        $periodStatus = $filters['period_status'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if ($name !== '') {
            $query->where('d.name', 'ILIKE', "%{$name}%");
        }

        if ($type) {
            $query->where('d.type', $type);
        }

        if ($isActive !== null && $isActive !== '') {
            $query->where('d.is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false);
        }

        if ($startDate) {
            $query->whereDate('d.valid_from', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('d.valid_until', '<=', $endDate);
        }

        if ($periodStatus) {
            if ($periodStatus === 'active') {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('d.valid_from')
                            ->whereNotNull('d.valid_until')
                            ->whereRaw('CURRENT_TIMESTAMP BETWEEN d.valid_from AND d.valid_until');
                    })
                        ->orWhere(function ($sub) {
                            $sub->whereNull('d.valid_from')
                                ->whereNotNull('d.valid_until')
                                ->whereRaw('CURRENT_TIMESTAMP <= d.valid_until');
                        })
                        ->orWhere(function ($sub) {
                            $sub->whereNotNull('d.valid_from')
                                ->whereNull('d.valid_until')
                                ->whereRaw('CURRENT_TIMESTAMP >= d.valid_from');
                        });
                });
            }

            if ($periodStatus === 'not_started') {
                $query->whereNotNull('d.valid_from')
                    ->whereRaw('CURRENT_TIMESTAMP < d.valid_from');
            }

            if ($periodStatus === 'expired') {
                $query->whereNotNull('d.valid_until')
                    ->whereRaw('CURRENT_TIMESTAMP > d.valid_until');
            }

            if ($periodStatus === 'no_period') {
                $query->whereNull('d.valid_from')
                    ->whereNull('d.valid_until');
            }
        }
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function applyDeletedAtFilter($query, string $table, ?string $alias = null): void
    {
        if (! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        $column = $alias ? "{$alias}.deleted_at" : 'deleted_at';

        $query->whereNull($column);
    }
}
