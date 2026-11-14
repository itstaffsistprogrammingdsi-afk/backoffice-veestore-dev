<?php

namespace App\Ai\Mcp;

use App\Ai\Mcp\Tools\BusinessOverviewTool;
use App\Ai\Mcp\Tools\CustomerInsightTool;
use App\Ai\Mcp\Tools\DataSchemaTool;
use App\Ai\Mcp\Tools\MasterDataTool;
use App\Ai\Mcp\Tools\OperationsPipelineTool;
use App\Ai\Mcp\Tools\ProductSalesTool;
use App\Ai\Mcp\Tools\PromoLoyaltyTool;
use App\Ai\Mcp\Tools\QueryDataTool;
use App\Ai\Mcp\Tools\StockHealthTool;
use App\Ai\Mcp\Tools\SystemMapTool;
use App\Ai\Security\AssistantAccessPolicy;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ToolRegistry
{
    /**
     * @return Collection<int, McpTool>
     */
    public function tools(): Collection
    {
        return collect([
            SystemMapTool::class,
            DataSchemaTool::class,
            MasterDataTool::class,
            QueryDataTool::class,
            BusinessOverviewTool::class,
            ProductSalesTool::class,
            StockHealthTool::class,
            OperationsPipelineTool::class,
            PromoLoyaltyTool::class,
            CustomerInsightTool::class,
        ])->map(fn (string $class) => app($class));
    }

    /**
     * @return list<array{name: string, description: string, inputSchema: array<string, mixed>}>
     */
    public function definitions(): array
    {
        return $this->tools()
            ->map(fn (McpTool $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, McpTool>
     */
    public function toolsFor(?User $user): Collection
    {
        $policy = app(AssistantAccessPolicy::class);

        return $this->tools()
            ->filter(fn (McpTool $tool): bool => $policy->canUseTool($tool->name(), $user))
            ->values();
    }

    public function find(string $name): McpTool
    {
        $tool = $this->tools()->first(fn (McpTool $tool) => $tool->name() === $name);

        if (! $tool) {
            throw new InvalidArgumentException("Tool [{$name}] tidak dikenal.");
        }

        return $tool;
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function call(string $name, array $arguments): array
    {
        return $this->find($name)->run($arguments);
    }

    /**
     * @return list<McpTool>
     */
    public function agentTools(): array
    {
        return $this->tools()->values()->all();
    }
}
