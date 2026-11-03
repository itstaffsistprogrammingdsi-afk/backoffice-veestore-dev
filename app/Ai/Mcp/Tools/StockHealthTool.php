<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use App\Domain\Analytics\Queries\BusinessOverviewQuery;
use Illuminate\Support\Facades\DB;

class StockHealthTool extends AssistantTool
{
    public function name(): string
    {
        return 'stock_health';
    }

    public function description(): string
    {
        return 'Kesehatan stok VeeStore: unit dan SKU per gudang/toko, stok menipis, stok mendekati kedaluwarsa, dan daftar prioritas restock. Gunakan langsung untuk pertanyaan stok atau restock. Sertakan detail kedaluwarsa hanya jika diminta pengguna.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'scope' => [
                    'type' => 'string',
                    'description' => 'Pilih warehouse atau store untuk pertanyaan yang terbatas pada satu jenis lokasi. Gunakan all untuk transfer atau perbandingan lintas lokasi.',
                    'enum' => ['all', 'warehouse', 'store'],
                    'default' => 'all',
                ],
                'expiring_days' => [
                    'type' => 'integer',
                    'default' => 90,
                    'min' => 7,
                    'max' => 365,
                ],
                'include_expiring' => [
                    'type' => 'boolean',
                    'description' => 'True hanya untuk pertanyaan barang kedaluwarsa atau mendekati kedaluwarsa.',
                    'default' => false,
                ],
                'limit' => [
                    'type' => 'integer',
                    'default' => 15,
                    'min' => 5,
                    'max' => 50,
                ],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $scope = $arguments['scope'] ?? 'all';
        $scope = in_array($scope, ['all', 'warehouse', 'store'], true) ? $scope : 'all';
        $days = max(7, min((int) ($arguments['expiring_days'] ?? 90), 365));
        $limit = max(5, min((int) ($arguments['limit'] ?? 15), 50));
        $includeExpiring = (bool) ($arguments['include_expiring'] ?? false);

        $snapshot = app(BusinessOverviewQuery::class)->stockHealthSnapshot();
        $result = [
            'snapshot_at' => now()->toIso8601String(),
            'summary' => $snapshot['summary'],
            'low_stock' => $snapshot['low_stock'],
        ];

        if ($scope === 'all' || $scope === 'warehouse') {
            $result['warehouse_status'] = $this->statusBreakdown('warehouse');
            $result['warehouse_locations'] = $this->locationBreakdown('warehouse', $limit);
        }

        if ($scope === 'all' || $scope === 'store') {
            $result['store_status'] = $this->statusBreakdown('store');
            $result['store_locations'] = $this->locationBreakdown('store', $limit);
        }

        if ($includeExpiring) {
            $result['expiring'] = $this->expiring($days, $limit);
            $result['expiring_window_days'] = $days;
        }

        return $result;
    }

    private function statusBreakdown(string $scope): array
    {
        $query = $scope === 'warehouse'
            ? DB::table('items as i')
                ->join('warehouse_items as wi', 'wi.item_id', '=', 'i.id')
                ->join('warehouses as w', 'w.id', '=', 'wi.warehouse_id')
                ->whereNull('w.deleted_at')
            : DB::table('items as i')
                ->join('store_items as si', 'si.item_id', '=', 'i.id')
                ->join('stores as s', 's.id', '=', 'si.store_id')
                ->whereNull('s.deleted_at');

        return $query
            ->selectRaw('LOWER(i.status::text) AS status, COUNT(*)::int AS units, COUNT(DISTINCT i.product_id)::int AS skus')
            ->groupByRaw('LOWER(i.status::text)')
            ->orderByDesc('units')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'units' => (int) $row->units,
                'skus' => (int) $row->skus,
            ])
            ->values()
            ->all();
    }

    private function locationBreakdown(string $scope, int $limit): array
    {
        if ($scope === 'warehouse') {
            return DB::table('warehouses as w')
                ->leftJoin('warehouse_items as wi', 'wi.warehouse_id', '=', 'w.id')
                ->leftJoin('items as i', function ($join) {
                    $join->on('i.id', '=', 'wi.item_id')
                        ->whereRaw("LOWER(i.status::text) = 'warehouse_stock'");
                })
                ->whereNull('w.deleted_at')
                ->selectRaw('w.id, w.name, COUNT(i.id)::int AS units, COUNT(DISTINCT i.product_id)::int AS skus')
                ->groupBy('w.id', 'w.name')
                ->orderByDesc('units')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => [
                    'warehouse_id' => $row->id,
                    'name' => $row->name,
                    'units' => (int) $row->units,
                    'skus' => (int) $row->skus,
                ])
                ->values()
                ->all();
        }

        return DB::table('stores as s')
            ->leftJoin('store_items as si', 'si.store_id', '=', 's.id')
            ->leftJoin('items as i', function ($join) {
                $join->on('i.id', '=', 'si.item_id')
                    ->whereRaw("LOWER(i.status::text) IN ('store_stock', 'store_display')");
            })
            ->whereNull('s.deleted_at')
            ->selectRaw('s.id, s.name, COUNT(i.id)::int AS units, COUNT(DISTINCT i.product_id)::int AS skus')
            ->groupBy('s.id', 's.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'store_id' => $row->id,
                'name' => $row->name,
                'units' => (int) $row->units,
                'skus' => (int) $row->skus,
            ])
            ->values()
            ->all();
    }

    private function expiring(int $days, int $limit): array
    {
        return DB::table('items as i')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_items as si', 'si.item_id', '=', 'i.id')
            ->leftJoin('stores as s', 's.id', '=', 'si.store_id')
            ->whereRaw("LOWER(i.status::text) IN ('warehouse_stock', 'store_stock', 'store_display', 'reserved')")
            ->whereNotNull('i.expired_date')
            ->whereRaw("i.expired_date <= CURRENT_DATE + (? * INTERVAL '1 day')", [$days])
            ->selectRaw('p.id AS product_id, p.code, p.name, s.name AS store_name, i.expired_date::text, COUNT(*)::int AS units')
            ->groupBy('p.id', 'p.code', 'p.name', 's.name', 'i.expired_date')
            ->orderBy('i.expired_date')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_code' => $row->code,
                'product_name' => $row->name,
                'store_name' => $row->store_name,
                'expired_date' => $row->expired_date,
                'units' => (int) $row->units,
            ])
            ->values()
            ->all();
    }
}
