<?php

namespace App\Ai\Tools;

use App\Domain\Analytics\Queries\AnalyticsDashboardQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class LatestForecastSummary implements Tool
{
    public function __construct(private readonly AnalyticsDashboardQuery $analytics) {}

    public function description(): Stringable|string
    {
        return 'Ambil ringkasan model forecast terakhir yang selesai, termasuk periode training, metrik model, total prediksi unit, dan total prediksi omzet untuk horizon aktif.';
    }

    public function handle(Request $request): Stringable|string
    {
        $run = $this->analytics->latestCompletedRun();

        if (! $run) {
            return 'Belum ada model forecast yang selesai.';
        }

        $summary = $this->analytics->summary($run);
        $forecast = $this->analytics->forecastDaily($run);

        $payload = [
            'model_run_id' => $run->id,
            'model' => $run->model_name,
            'model_version' => $run->model_version,
            'status' => $run->status,
            'training_from' => optional($run->training_from)->toDateString(),
            'training_to' => optional($run->training_to)->toDateString(),
            'completed_at' => optional($run->completed_at)->toIso8601String(),
            'metrics' => $run->metrics,
            'recommendations' => [
                'total' => (int) ($summary->total ?? 0),
                'critical' => (int) ($summary->critical ?? 0),
                'high' => (int) ($summary->high ?? 0),
                'recommended_units' => (int) ($summary->recommended_units ?? 0),
            ],
            'forecast' => [
                'days' => $forecast->count(),
                'predicted_qty' => round($forecast->sum(fn ($row) => (float) $row->predicted_qty), 2),
                'predicted_revenue' => round($forecast->sum(fn ($row) => (float) $row->predicted_revenue), 2),
            ],
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
