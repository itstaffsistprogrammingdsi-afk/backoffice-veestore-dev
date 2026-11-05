<?php

namespace App\Services\Assistant;

use App\Ai\Mcp\ToolRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AssistantContext
{
    public function __construct(private readonly ToolRegistry $registry) {}

    public function defaultMode(): string
    {
        $keys = array_keys((array) config('assistant.modes', []));
        $configured = (string) config('assistant.default_mode', 'auto');

        return in_array($configured, $keys, true) ? $configured : (string) ($keys[0] ?? 'auto');
    }

    /**
     * @return list<array{key: string, label: string, description: string, models: list<string>}>
     */
    public function modes(): array
    {
        $aliases = (array) config('ai.providers.nine_router.models.text', []);

        return collect((array) config('assistant.modes', []))
            ->map(function (array $mode, string $key) use ($aliases): array {
                $models = collect($mode['models'] ?? [])
                    ->map(fn (string $alias): ?string => $aliases[$alias] ?? null)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'label' => (string) ($mode['label'] ?? $key),
                    'description' => (string) ($mode['description'] ?? ''),
                    'models' => $models,
                ];
            })
            ->filter(fn (array $mode): bool => $mode['models'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function modelsFor(?string $mode = null): array
    {
        $modes = $this->modes();
        $selected = collect($modes)->firstWhere('key', $mode ?? $this->defaultMode())
            ?? collect($modes)->firstWhere('key', $this->defaultMode())
            ?? $modes[0]
            ?? null;

        return $selected['models'] ?? [];
    }

    public function model(?string $mode = null): string
    {
        $models = $this->modelsFor($mode);

        if ($models !== []) {
            return $models[0];
        }

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
