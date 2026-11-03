<?php

namespace Tests\Feature\AI;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class McpTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assistant.mcp.token' => 'test-mcp-token']);
    }

    public function test_mcp_rejects_requests_without_token(): void
    {
        $this->postJson(route('mcp.endpoint'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertUnauthorized();
    }

    public function test_mcp_initializes_and_lists_tools(): void
    {
        $this->withToken('test-mcp-token')
            ->postJson(route('mcp.endpoint'), [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => ['protocolVersion' => '2024-11-05'],
            ])
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'veestore-backoffice')
            ->assertJsonPath('result.capabilities.tools.listChanged', false);

        $response = $this->withToken('test-mcp-token')
            ->postJson(route('mcp.endpoint'), [
                'jsonrpc' => '2.0',
                'id' => 2,
                'method' => 'tools/list',
            ])
            ->assertOk();

        $names = collect($response->json('result.tools'))->pluck('name');

        $this->assertTrue($names->contains('system_map'));
        $this->assertTrue($names->contains('query_data'));
        $this->assertTrue($names->contains('business_overview'));
        $this->assertTrue($names->contains('stock_health'));
    }

    public function test_mcp_can_call_system_map(): void
    {
        $response = $this->withToken('test-mcp-token')
            ->postJson(route('mcp.endpoint'), [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'system_map',
                    'arguments' => ['search' => 'stok'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('result.isError', false);

        $payload = json_decode($response->json('result.content.0.text'), true);

        $this->assertArrayHasKey('features', $payload);
        $this->assertArrayHasKey('navigation', $payload);
    }

    public function test_mcp_can_run_read_only_query_and_redacts_sensitive_columns(): void
    {
        $response = $this->withToken('test-mcp-token')
            ->postJson(route('mcp.endpoint'), [
                'jsonrpc' => '2.0',
                'id' => 4,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'query_data',
                    'arguments' => [
                        'sql' => 'select id, name, password from users limit 1',
                        'limit' => 5,
                    ],
                ],
            ])
            ->assertOk();

        $payload = json_decode($response->json('result.content.0.text'), true);

        $this->assertArrayHasKey('row_count', $payload);

        if ($payload['row_count'] > 0) {
            $this->assertSame('[REDACTED]', $payload['rows'][0]['password']);
        }
    }
}
