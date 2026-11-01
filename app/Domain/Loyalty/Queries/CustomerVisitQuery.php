<?php

namespace App\Domain\Loyalty\Queries;

use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerVisitQuery
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $wa = trim((string) ($filters['wa'] ?? ''));
        $storeId = $filters['store_id'] ?? 'all';
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if (! $dateFrom && ! $dateTo) {
            $dateFrom = Carbon::today()->toDateString();
            $dateTo = Carbon::today()->toDateString();
        }

        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $visitTimestamp = DB::raw('COALESCE(v.check_in_at, v.created_at)');

        return DB::table('store_customer_visits as v')
            ->join('customers as c', 'c.id', '=', 'v.customer_id')
            ->join('stores as s', 's.id', '=', 'v.store_id')
            ->when($wa !== '', fn ($query) => $query->where('c.wa_number', 'ILIKE', "%{$wa}%"))
            ->when($storeId && $storeId !== 'all', fn ($query) => $query->where('v.store_id', $storeId))
            ->when($start, fn ($query) => $query->whereRaw('COALESCE(v.check_in_at, v.created_at) >= ?', [$start]))
            ->when($end, fn ($query) => $query->whereRaw('COALESCE(v.check_in_at, v.created_at) <= ?', [$end]))
            ->select([
                'v.id',
                'v.visit_code',
                'v.check_in_at',
                'v.check_out_at',
                'v.status',
                'v.created_at',
                DB::raw('COALESCE(v.check_in_at, v.created_at) AS visit_at'),
                'c.id as customer_id',
                'c.name as customer_name',
                'c.wa_number',
                'c.email',
                's.id as store_id',
                's.name as store_name',
                's.code as store_code',
            ])
            ->orderByDesc($visitTimestamp)
            ->paginate($perPage)
            ->appends([
                'wa' => $wa,
                'store_id' => $storeId,
                'date_from' => $start?->toDateString(),
                'date_to' => $end?->toDateString(),
            ]);
    }

    public function stores(): Collection
    {
        return Store::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
