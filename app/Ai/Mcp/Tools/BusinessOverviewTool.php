<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Ai\Support\AnswerStatus;
use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use Carbon\CarbonImmutable;

class BusinessOverviewTool extends AssistantTool
{
    public function name(): string
    {
        return 'business_overview';
    }

    public function description(): string
    {
        return 'Ringkasan bisnis VeeStore: penjualan POS dan ecommerce, transaksi, pembeli, tren, status pesanan, retur, dan stok. Pilih focus paling sempit yang menjawab pertanyaan; gunakan all hanya untuk ringkasan lintas domain.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Tanggal awal format YYYY-MM-DD. Default 29 hari lalu.',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'Tanggal akhir format YYYY-MM-DD. Default hari ini.',
                ],
                'focus' => [
                    'type' => 'string',
                    'enum' => ['all', 'sales', 'orders', 'inventory'],
                    'default' => 'all',
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $focus = $arguments['focus'] ?? 'all';
        $focus = in_array($focus, ['all', 'sales', 'orders', 'inventory'], true) ? $focus : 'all';

        $from = $this->date($arguments['date_from'] ?? null) ?? CarbonImmutable::now()->subDays(29)->toDateString();
        $to = $this->date($arguments['date_to'] ?? null) ?? CarbonImmutable::now()->toDateString();

        $query = app(BusinessOverviewQuery::class);
        $filters = [
            'date_from' => $from,
            'date_to' => $to,
        ];

        $result = [
            'period' => ['date_from' => $from, 'date_to' => $to],
            'generated_at' => now()->toIso8601String(),
            'answer_status' => AnswerStatus::OK,
        ];

        if (CarbonImmutable::parse($from)->greaterThan(CarbonImmutable::now()->startOfDay())) {
            $result['answer_status'] = AnswerStatus::NOT_YET_OCCURRED;
            $result['message'] = 'Periode belum terjadi; belum ada data.';
            $result['sales'] = [];
            $result['orders'] = [];
            $result['inventory'] = [];

            return $result;
        }

        if ($focus === 'all') {
            $data = $query->build($filters);
            $result['generated_at'] = $data['generated_at'];
            $result['sales'] = $data['sales'];
            $result['orders'] = $data['orders'];
            $result['inventory'] = $data['inventory'];
        } elseif ($focus === 'sales') {
            $result['sales'] = $query->salesSnapshot($filters);
        } elseif ($focus === 'orders') {
            $result['orders'] = $query->ordersSnapshot($filters);
        } else {
            $result['inventory'] = $query->inventoryOverview($filters);
        }

        return $result;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
