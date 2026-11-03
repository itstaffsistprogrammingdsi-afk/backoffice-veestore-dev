<?php

namespace App\Services\Assistant;

use App\Ai\Mcp\ToolRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssistantContext
{
    public function __construct(private readonly ToolRegistry $registry) {}

    public function model(): string
    {
        return (string) config('ai.providers.nine_router.models.text.default', 'ocg/deepseek-v4.1-flash');
    }

    public function suggestions(): array
    {
        return config('assistant.suggestions', []);
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    public function tools(): array
    {
        return $this->registry->tools()
            ->map(fn ($tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{key: string, label: string, menu: string, description: string, tables: int, questions: array<int, string>}>
     */
    public function features(): array
    {
        return collect(config('assistant.features', []))
            ->map(fn (array $feature) => [
                'key' => $feature['key'],
                'label' => $feature['label'],
                'menu' => $feature['menu'],
                'description' => $feature['description'],
                'tables' => count($feature['tables']),
                'questions' => $feature['questions'] ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    public function coverage(): array
    {
        return Cache::remember('assistant:coverage', now()->addMinutes(5), function () {
            $tables = [
                ['label' => 'Produk', 'table' => 'products'],
                ['label' => 'Customer', 'table' => 'customers'],
                ['label' => 'Transaksi', 'table' => 'payments'],
                ['label' => 'Order ecommerce', 'table' => 'ecommerce_orders'],
                ['label' => 'Stok fisik', 'table' => 'items'],
                ['label' => 'Toko', 'table' => 'stores'],
                ['label' => 'Gudang', 'table' => 'warehouses'],
                ['label' => 'Voucher', 'table' => 'vouchers'],
            ];

            return collect($tables)
                ->map(fn (array $row) => [
                    'label' => $row['label'],
                    'value' => (int) DB::table($row['table'])->count(),
                ])
                ->values()
                ->all();
        });
    }
}
