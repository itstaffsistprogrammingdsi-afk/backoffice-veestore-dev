<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Ai\Support\AnswerStatus;
use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use Carbon\CarbonImmutable;

class ProductSalesTool extends AssistantTool
{
    public function name(): string
    {
        return 'product_sales';
    }

    public function description(): string
    {
        return 'Peringkat produk terjual dari transaksi POS dan ecommerce, per toko dan per produk. Gunakan untuk pertanyaan produk paling laris, produk tertentu paling banyak terjual di toko mana, atau penjualan produk per toko. Mendukung filter nama produk, nama toko, dan periode.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'product' => [
                    'type' => 'string',
                    'description' => 'Nama atau kode produk yang dicari. Kosongkan untuk semua produk.',
                ],
                'store' => [
                    'type' => 'string',
                    'description' => 'Nama atau kode toko. Kosongkan untuk semua toko.',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Tanggal awal format YYYY-MM-DD. Default 29 hari lalu.',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'Tanggal akhir format YYYY-MM-DD. Default hari ini.',
                ],
                'per_store' => [
                    'type' => 'integer',
                    'description' => 'Jika diisi, kembalikan N produk teratas per toko. Kosongkan untuk peringkat gabungan.',
                    'min' => 1,
                    'max' => 10,
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 20,
                    'min' => 1,
                    'max' => 50,
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $from = $this->date($arguments['date_from'] ?? null) ?? CarbonImmutable::now()->subDays(29)->toDateString();
        $to = $this->date($arguments['date_to'] ?? null) ?? CarbonImmutable::now()->toDateString();

        if (CarbonImmutable::parse($from)->greaterThan(CarbonImmutable::now()->startOfDay())) {
            return [
                'period' => ['date_from' => $from, 'date_to' => $to],
                'generated_at' => now()->toIso8601String(),
                'answer_status' => AnswerStatus::NOT_YET_OCCURRED,
                'message' => 'Periode belum terjadi; belum ada data.',
                'rows' => [],
            ];
        }

        $result = app(BusinessOverviewQuery::class)->productSalesSnapshot([
            'product' => $arguments['product'] ?? null,
            'store' => $arguments['store'] ?? null,
            'date_from' => $from,
            'date_to' => $to,
            'per_store' => $arguments['per_store'] ?? null,
            'limit' => $arguments['limit'] ?? 20,
        ]);

        $result['answer_status'] = AnswerStatus::OK;

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
