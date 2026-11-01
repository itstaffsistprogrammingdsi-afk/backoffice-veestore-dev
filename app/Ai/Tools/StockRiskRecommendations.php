<?php

namespace App\Ai\Tools;

use App\Domain\Analytics\Queries\AnalyticsDashboardQuery;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class StockRiskRecommendations implements Tool
{
    public function __construct(private readonly AnalyticsDashboardQuery $analytics) {}

    public function description(): Stringable|string
    {
        return 'Ambil daftar rekomendasi restock berstatus recommended dari model forecast terakhir. Bisa difilter berdasarkan tingkat risiko critical, high, medium, atau low.';
    }

    public function handle(Request $request): Stringable|string
    {
        $run = $this->analytics->latestCompletedRun();

        if (! $run) {
            return 'Belum ada model forecast yang selesai.';
        }

        $filters = ['status' => 'recommended'];
        $riskLevel = $request['risk_level'] ?? null;

        if (in_array($riskLevel, ['critical', 'high', 'medium', 'low'], true)) {
            $filters['risk_level'] = $riskLevel;
        }

        $limit = (int) ($request['limit'] ?? 10);
        $rows = $this->analytics->topRecommendations($run, $filters, $limit);

        if ($rows->isEmpty()) {
            return 'Tidak ada rekomendasi restock yang cocok dengan filter tersebut.';
        }

        $payload = $rows->map(fn ($row) => [
            'store' => $row->store?->name,
            'product_code' => $row->product?->code,
            'product_name' => $row->product?->name,
            'current_stock' => (int) $row->current_stock,
            'reserved_stock' => (int) $row->reserved_stock,
            'forecast_qty_7d' => round((float) $row->forecast_qty_7d, 2),
            'safety_stock' => (int) $row->safety_stock,
            'recommended_qty' => (int) $row->recommended_qty,
            'risk_level' => $row->risk_level,
            'status' => $row->status,
        ])->all();

        return json_encode([
            'model_run_id' => $run->id,
            'filter' => $filters,
            'count' => count($payload),
            'recommendations' => $payload,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'risk_level' => $schema->string()->enum(['critical', 'high', 'medium', 'low']),
            'limit' => $schema->integer()->min(1)->max(25),
        ];
    }
}
