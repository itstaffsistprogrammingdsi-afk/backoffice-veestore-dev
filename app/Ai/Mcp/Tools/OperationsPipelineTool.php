<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperationsPipelineTool extends AssistantTool
{
    public function name(): string
    {
        return 'operations_pipeline';
    }

    public function description(): string
    {
        return 'Pantau alur operasional VeeStore: status pesanan, retur, inbound/outbound, dan opname. Pilih focus paling sempit yang menjawab pertanyaan; gunakan all hanya untuk ringkasan lintas proses.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Default 29 hari lalu.'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Default hari ini.'],
                'focus' => [
                    'type' => 'string',
                    'enum' => ['all', 'orders', 'returns', 'inbound_outbound', 'opname'],
                    'default' => 'all',
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $from = $this->date($arguments['date_from'] ?? null) ?? CarbonImmutable::now()->subDays(29)->toDateString();
        $to = $this->date($arguments['date_to'] ?? null) ?? CarbonImmutable::now()->toDateString();
        $focus = $arguments['focus'] ?? 'all';
        $focus = in_array($focus, ['all', 'orders', 'returns', 'inbound_outbound', 'opname'], true) ? $focus : 'all';

        $result = ['period' => ['date_from' => $from, 'date_to' => $to]];

        if ($focus === 'all' || $focus === 'orders') {
            $result['orders'] = app(BusinessOverviewQuery::class)->ordersSnapshot([
                'date_from' => $from,
                'date_to' => $to,
            ]);
        }

        if ($focus === 'all' || $focus === 'returns') {
            $result['returns'] = $this->returnSummary($from, $to);
        }

        if ($focus === 'all' || $focus === 'inbound_outbound') {
            $result['inbound_outbound'] = [
                'warehouse_inbound' => $this->movement('warehouse_inbounds', 'received_date', 'quantity_item', $from, $to),
                'warehouse_outbound' => $this->movement('warehouse_outbounds', 'shipment_date', 'quantity_item', $from, $to),
                'store_inbound' => $this->movement('store_inbounds', 'received_date', 'quantity_item', $from, $to),
                'store_outbound' => $this->movement('store_outbounds', 'shipment_date', 'item_quantity', $from, $to),
            ];
        }

        if ($focus === 'all' || $focus === 'opname') {
            $result['opname'] = [
                'store' => $this->statusCounts('store_opname_sessions', $from, $to),
                'warehouse' => $this->statusCounts('warehouse_stock_opnames', $from, $to),
            ];
        }

        return $result;
    }

    private function returnSummary(string $from, string $to): array
    {
        [$start, $end] = $this->range($from, $to);
        $rows = DB::table('store_returns')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('LOWER(status::text) AS status, COUNT(*)::int AS total')
            ->groupByRaw('LOWER(status::text)')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->total])
            ->values()
            ->all();

        return [
            'total' => array_sum(array_column($rows, 'count')),
            'by_status' => $rows,
        ];
    }

    private function movement(string $table, string $dateColumn, string $quantityColumn, string $from, string $to): array
    {
        [$start, $end] = $this->range($from, $to);
        $row = DB::table($table)
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw("COUNT(*)::int AS documents, COALESCE(SUM({$quantityColumn}), 0)::int AS items")
            ->first();

        return [
            'documents' => (int) ($row->documents ?? 0),
            'items' => (int) ($row->items ?? 0),
        ];
    }

    private function statusCounts(string $table, string $from, string $to): array
    {
        [$start, $end] = $this->range($from, $to);

        return DB::table($table)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('LOWER(status::text) AS status, COUNT(*)::int AS total')
            ->groupByRaw('LOWER(status::text)')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['status' => $row->status, 'count' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(string $from, string $to): array
    {
        return [
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        ];
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
