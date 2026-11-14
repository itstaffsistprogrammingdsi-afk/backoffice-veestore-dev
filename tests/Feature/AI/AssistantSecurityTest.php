<?php

namespace Tests\Feature\AI;

use App\Ai\Agents\VeeAssistant;
use App\Ai\Mcp\ToolRegistry;
use App\Ai\Mcp\Tools\CustomerInsightTool;
use App\Ai\Mcp\Tools\DataSchemaTool;
use App\Ai\Mcp\Tools\MasterDataTool;
use App\Ai\Mcp\Tools\QueryDataTool;
use App\Ai\Mcp\Tools\SystemMapTool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AssistantSecurityTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_mass_customer_request_is_refused_before_model_call(): void
    {
        VeeAssistant::fake(['Seharusnya tidak dipakai.']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Data pelanggan lengkap'])
            ->assertOk()
            ->assertJsonPath('answer_status', 'forbidden')
            ->assertJsonCount(0, 'tool_calls')
            ->assertJsonPath('model', null);

        $answer = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Data pelanggan lengkap'])
            ->json('answer');

        $this->assertStringContainsString('tidak menampilkan data pribadi pelanggan secara massal', $answer);
    }

    public function test_master_data_customers_defaults_to_aggregate(): void
    {
        $result = $this->actingAs($this->admin())
            ->callTool(MasterDataTool::class, ['entity' => 'customers']);

        $this->assertSame('aggregate_only', $result['answer_status']);
        $this->assertSame([], $result['rows']);
        $this->assertArrayHasKey('total_customers', $result['summary']);
    }

    public function test_master_data_customer_detail_masks_pii(): void
    {
        $customer = DB::table('customers')->whereNull('deleted_at')->first();

        if ($customer === null) {
            $this->markTestSkipped('Tidak ada data customer untuk diuji.');
        }

        $search = mb_substr((string) $customer->name, 0, 3);

        if (mb_strlen($search) < 3) {
            $this->markTestSkipped('Nama customer terlalu pendek untuk pencarian aman.');
        }

        $result = $this->actingAs($this->admin())
            ->callTool(MasterDataTool::class, ['entity' => 'customers', 'search' => $search, 'limit' => 5]);

        $this->assertArrayHasKey('rows', $result);
        $this->assertNotEmpty($result['rows']);

        foreach ($result['rows'] as $row) {
            $this->assertStringContainsString('*', (string) $row['name']);
            $this->assertStringContainsString('*', (string) $row['email']);
            $this->assertStringContainsString('*', (string) $row['wa_number']);
        }
    }

    public function test_customer_insight_birthday_list_is_redacted(): void
    {
        DB::table('customers')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Budi Santoso',
            'email' => 'budi.'.Str::lower(Str::random(6)).'@example.test',
            'wa_number' => '081234567890',
            'gender' => 'male',
            'date_of_birth' => now()->startOfMonth()->toDateString(),
            'is_guest' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->actingAs($this->admin())->callTool(CustomerInsightTool::class, [
            'focus' => 'demographics',
        ]);

        $this->assertArrayHasKey('birthday_customers', $result);
        $this->assertNotEmpty($result['birthday_customers']);

        $row = collect($result['birthday_customers'])
            ->first(fn (array $item): bool => str_contains((string) ($item['email'] ?? ''), '@example.test'));

        $this->assertNotNull($row);
        $this->assertStringContainsString('*', (string) $row['name']);
        $this->assertStringContainsString('*', (string) $row['email']);
        $this->assertStringContainsString('*', (string) $row['wa_number']);
        $this->assertSame('[disembunyikan]', $row['date_of_birth']);
    }

    public function test_query_data_blocks_mass_customer_detail_even_for_admin(): void
    {
        $result = $this->actingAs($this->admin())->callTool(QueryDataTool::class, [
            'sql' => 'select name, email, wa_number from customers limit 20',
            'limit' => 20,
        ]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('forbidden', $result['answer_status']);
        $this->assertStringContainsString('Pembatasan privasi', $result['error']);
    }

    public function test_restricted_user_cannot_use_schema_or_customer_detail(): void
    {
        $user = $this->restrictedUser();

        $schema = $this->actingAs($user)->callTool(DataSchemaTool::class, ['connection' => 'operational']);
        $this->assertArrayHasKey('error', $schema);
        $this->assertSame('forbidden', $schema['answer_status']);

        $detail = $this->actingAs($user)->callTool(MasterDataTool::class, [
            'entity' => 'customers',
            'search' => 'budi',
        ]);
        $this->assertArrayHasKey('error', $detail);
        $this->assertSame('forbidden', $detail['answer_status']);

        $users = $this->actingAs($user)->callTool(MasterDataTool::class, ['entity' => 'users']);
        $this->assertArrayHasKey('error', $users);
        $this->assertSame('forbidden', $users['answer_status']);

        $tools = app(ToolRegistry::class)->toolsFor($user)->map(fn ($tool) => $tool->name())->all();
        $this->assertNotContains('data_schema', $tools);
    }

    public function test_answer_sanitizer_removes_placeholder_links_and_keeps_registered_link(): void
    {
        $validUrl = route('operations.stock.index');

        VeeAssistant::fake([
            "Buka [Operasional Stok](https://contoh/operations/stock) atau [Stok]({$validUrl}).",
        ]);

        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Di mana menu stok?'])
            ->assertOk();

        $answer = (string) $response->json('answer');

        $this->assertStringNotContainsString('contoh/', $answer);
        $this->assertStringContainsString('Operasional Stok', $answer);
        $this->assertStringContainsString($validUrl, $answer);
        $this->assertSame(1, $response->json('removed_links'));
    }

    public function test_future_period_is_not_reported_as_zero(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Omzet Februari 2031'])
            ->assertOk()
            ->assertJsonPath('answer_status', 'not_yet_occurred');

        $answer = (string) $response->json('answer');

        $this->assertStringContainsString('belum terjadi', $answer);
        $this->assertStringNotContainsString('Rp0 tercatat', $answer);
    }

    public function test_ambiguous_stock_question_asks_for_product_and_location(): void
    {
        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'stok kmrn brp?'])
            ->assertOk()
            ->assertJsonPath('answer_status', 'needs_clarification');

        $answer = (string) $response->json('answer');

        $this->assertStringContainsString('produk', $answer);
        $this->assertStringContainsString('lokasi', $answer);
    }

    public function test_email_and_phone_in_model_answer_are_masked(): void
    {
        VeeAssistant::fake([
            'Hubungi budi@example.com atau 081234567890.',
        ]);

        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Siapa kontak pelanggan?'])
            ->assertOk();

        $answer = (string) $response->json('answer');

        $this->assertStringNotContainsString('budi@example.com', $answer);
        $this->assertStringNotContainsString('081234567890', $answer);
        $this->assertStringContainsString('***', $answer);
    }

    public function test_system_map_does_not_expose_table_names_or_counts(): void
    {
        $result = $this->actingAs($this->admin())->callTool(SystemMapTool::class, [
            'search' => 'stok',
            'include_routes' => false,
        ]);

        $this->assertArrayHasKey('features', $result);
        $this->assertArrayHasKey('links', $result);
        $this->assertNull($result['routes']);

        foreach ($result['features'] as $feature) {
            $this->assertArrayNotHasKey('tables', $feature);
        }

        foreach ($result['menu'] as $item) {
            $this->assertArrayHasKey('link_available', $item);
        }
    }

    public function test_mcp_query_data_cannot_dump_customer_list(): void
    {
        config(['assistant.mcp.token' => 'test-mcp-token']);

        $response = $this->withToken('test-mcp-token')
            ->postJson(route('mcp.endpoint'), [
                'jsonrpc' => '2.0',
                'id' => 9,
                'method' => 'tools/call',
                'params' => [
                    'name' => 'query_data',
                    'arguments' => [
                        'sql' => 'select name, email from customers limit 20',
                        'limit' => 20,
                    ],
                ],
            ])
            ->assertOk();

        $payload = json_decode((string) $response->json('result.content.0.text'), true);

        $this->assertTrue($response->json('result.isError'));
        $this->assertSame('forbidden', $payload['answer_status']);
        $this->assertStringContainsString('Pembatasan privasi', $payload['error']);
    }

    /**
     * @param  class-string  $tool
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function callTool(string $tool, array $arguments): array
    {
        return app($tool)->run($arguments);
    }

    private function restrictedUser(): User
    {
        return User::query()->create([
            'name' => 'Restricted User',
            'username' => 'restricted-'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(8)).'@example.test',
            'password' => 'password',
        ]);
    }
}
