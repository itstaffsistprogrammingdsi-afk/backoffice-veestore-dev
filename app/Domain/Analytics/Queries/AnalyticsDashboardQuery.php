<?php

namespace App\Domain\Analytics\Queries;

use App\Models\Analytics\ModelRun;
use App\Models\Analytics\SalesForecast;
use App\Models\Analytics\StockRecommendation;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsDashboardQuery
{
    public function latestCompletedRun(): ?ModelRun
    {
        return ModelRun::query()
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
    }

    public function recommendations(?ModelRun $run, array $filters): LengthAwarePaginator
    {
        return $this->recommendationQuery($run, $filters)
            ->paginate(20)
            ->withQueryString();
    }

    public function topRecommendations(?ModelRun $run, array $filters, int $limit = 10): Collection
    {
        return $this->recommendationQuery($run, $filters)
            ->limit(max(1, min($limit, 25)))
            ->get();
    }

    public function summary(?ModelRun $run): object
    {
        if (! $run) {
            return (object) [
                'total' => 0,
                'critical' => 0,
                'high' => 0,
                'recommended_units' => 0,
            ];
        }

        return StockRecommendation::query()
            ->where('model_run_id', $run->id)
            ->selectRaw("COUNT(*)::int AS total, COUNT(*) FILTER (WHERE risk_level='critical')::int AS critical, COUNT(*) FILTER (WHERE risk_level='high')::int AS high, COALESCE(SUM(recommended_qty),0)::int AS recommended_units")
            ->first();
    }

    public function forecastDaily(?ModelRun $run): Collection
    {
        if (! $run) {
            return collect();
        }

        return SalesForecast::query()
            ->where('model_run_id', $run->id)
            ->select('forecast_date')
            ->selectRaw('SUM(predicted_qty)::numeric AS predicted_qty, SUM(predicted_revenue)::numeric AS predicted_revenue')
            ->groupBy('forecast_date')
            ->orderBy('forecast_date')
            ->get();
    }

    public function stores(): Collection
    {
        return Store::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function recommendationQuery(?ModelRun $run, array $filters): Builder
    {
        return StockRecommendation::query()
            ->with(['store:id,name', 'product:id,code,name', 'reviewer:id,name'])
            ->when($run, fn ($query) => $query->where('model_run_id', $run->id))
            ->when(! $run, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($filters['store_id'] ?? null, fn ($query, $value) => $query->where('store_id', $value))
            ->when($filters['risk_level'] ?? null, fn ($query, $value) => $query->where('risk_level', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->orderByDesc('recommended_qty');
    }
}
