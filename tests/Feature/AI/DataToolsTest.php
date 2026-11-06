<?php

namespace Tests\Feature\AI;

use App\Ai\Mcp\ToolRegistry;
use App\Ai\Mcp\Tools\DataSchemaTool;
use App\Ai\Mcp\Tools\QueryDataTool;
use App\Ai\Mcp\Tools\SystemMapTool;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DataToolsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_query_tool_blocks_write_statements(): void
    {
        $result = app(QueryDataTool::class)->run([
            'sql' => 'delete from products',
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('SELECT', $result['error']);
    }

    public function test_query_tool_blocks_multiple_statements(): void
    {
        $result = app(QueryDataTool::class)->run([
            'sql' => 'select 1; select 2',
        ]);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_query_tool_returns_rows_and_limit_metadata(): void
    {
        $result = app(QueryDataTool::class)->run([
            'sql' => 'select code, name from products order by name',
            'limit' => 3,
        ]);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertLessThanOrEqual(3, $result['row_count']);
        $this->assertArrayHasKey('truncated', $result);
    }

    public function test_schema_tool_lists_tables(): void
    {
        $result = app(DataSchemaTool::class)->run([
            'connection' => 'operational',
            'search' => 'product',
        ]);

        $this->assertArrayHasKey('tables', $result);
        $this->assertNotEmpty($result['tables']);
    }

    public function test_product_sales_tool_groups_by_store_and_product(): void
    {
        $result = app(ToolRegistry::class)->call('product_sales', ['per_store' => 2]);

        $this->assertArrayNotHasKey('error', $result, json_encode($result));
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('qty', $result['rows'][0] ?? []);
        $this->assertArrayHasKey('store_name', $result['rows'][0] ?? []);
        $this->assertArrayHasKey('product_name', $result['rows'][0] ?? []);
    }

    public function test_system_map_tool_returns_features_and_navigation(): void
    {
        $result = app(SystemMapTool::class)->run([]);

        $this->assertArrayHasKey('navigation', $result);
        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('menu', $result);
        $this->assertNotEmpty($result['features']);
        $this->assertNotEmpty($result['menu']);

        foreach ($result['menu'] as $item) {
            $this->assertArrayHasKey('path', $item);
            $this->assertArrayHasKey('url', $item);

            if ($item['url'] !== '') {
                $this->assertStringStartsWith('/', $item['url']);
            }
        }
    }

    public function test_all_domain_tools_run_without_query_errors(): void
    {
        $registry = app(ToolRegistry::class);

        $arguments = [
            'business_overview' => [],
            'product_sales' => ['per_store' => 3],
            'stock_health' => [],
            'operations_pipeline' => [],
            'promo_loyalty' => [],
            'customer_insight' => ['focus' => 'demographics'],
            'master_data' => ['entity' => 'products'],
        ];

        foreach ($arguments as $name => $payload) {
            $result = $registry->call($name, $payload);

            $this->assertArrayNotHasKey('error', $result, "Tool {$name} failed: ".json_encode($result));
        }
    }
}
