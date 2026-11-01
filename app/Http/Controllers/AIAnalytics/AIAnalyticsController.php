<?php

namespace App\Http\Controllers\AIAnalytics;

use App\Domain\Analytics\Actions\ReviewStockRecommendation;
use App\Domain\Analytics\Actions\RunForecast;
use App\Domain\Analytics\Queries\AnalyticsDashboardQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\AIAnalytics\IndexAnalyticsRequest;
use App\Http\Requests\AIAnalytics\ReviewRecommendationRequest;
use App\Models\Analytics\StockRecommendation;
use Inertia\Inertia;
use Throwable;

class AIAnalyticsController extends Controller
{
    public function index(IndexAnalyticsRequest $request, AnalyticsDashboardQuery $query)
    {
        $filters = $request->validated();
        $latestRun = $query->latestCompletedRun();
        $recommendations = $query->recommendations($latestRun, $filters);
        $summary = $query->summary($latestRun);
        $forecastDaily = $query->forecastDaily($latestRun);
        $stores = $query->stores();

        return Inertia::render('Analytics/Index', [
            'latestRun' => $latestRun,
            'recommendations' => $recommendations,
            'summary' => $summary,
            'forecastDaily' => $forecastDaily,
            'filters' => $filters,
            'stores' => $stores,
            'urls' => [
                'index' => route('ai-analytics.index'),
                'run' => route('ai-analytics.run'),
                'review' => route('ai-analytics.recommendations.review', ['recommendation' => '__ID__']),
            ],
        ]);
    }

    public function run(RunForecast $action)
    {
        try {
            $result = $action->handle();

            return redirect()
                ->route('ai-analytics.index')
                ->with('success', 'Proses AI selesai. Model run: '.($result['model_run_id'] ?? '-'));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('ai-analytics.index')
                ->with('error', 'Service AI gagal menjalankan prediksi. Periksa service Python dan log aplikasi.');
        }
    }

    public function review(
        ReviewRecommendationRequest $request,
        StockRecommendation $recommendation,
        ReviewStockRecommendation $action
    ) {
        $action->handle($recommendation, $request->validated(), $request->user());

        return back()->with('success', 'Status rekomendasi AI berhasil diperbarui.');
    }
}
