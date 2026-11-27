<?php

namespace Tests\Feature\AI;

use App\Ai\Agents\VeeAssistant;
use App\Ai\Mcp\ToolRegistry;
use App\Ai\Mcp\Tools\CustomerInsightTool;
use App\Ai\Mcp\Tools\DataSchemaTool;
use App\Ai\Mcp\Tools\MasterDataTool;
use App\Ai\Mcp\Tools\QueryDataTool;
use App\Ai\Mcp\Tools\SystemMapTool;
use App\Ai\Support\AssistantReferenceRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class AssistantSecurityTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_mass_customer_request_reaches_model_for_authorized_admin(): void
    {
        VeeAssistant::fake(['Berikut data pelanggan yang Anda minta.']);

        $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Data pelanggan lengkap'])
            ->assertOk()
            ->assertJsonPath('answer', 'Berikut data pelanggan yang Anda minta.');

        VeeAssistant::assertPrompted('Data pelanggan lengkap');
    }

    public function test_master_data_customers_returns_full_rows_for_authorized_admin(): void
    {
        DB::table('customers')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Vee List '.Str::upper(Str::random(6)),
            'email' => 'list.'.Str::lower(Str::random(6)).'@example.test',
            'wa_number' => '081234567891',
            'gender' => 'female',
            'is_guest' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->actingAs($this->admin())
            ->callTool(MasterDataTool::class, ['entity' => 'customers', 'limit' => 5]);

        $this->assertSame('ok', $result['answer_status']);
        $this->assertNotEmpty($result['rows']);

        foreach ($result['rows'] as $row) {
            $this->assertStringNotContainsString('*', (string) $row['name']);
        }
    }

    public function test_master_data_customer_detail_shows_full_pii_for_authorized_admin(): void
    {
        $name = 'Vee Customer '.Str::upper(Str::random(6));
        $email = 'vee.'.Str::lower(Str::random(6)).'@example.test';
        $phone = '081234567890';

        DB::table('customers')->insert([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'email' => $email,
            'wa_number' => $phone,
            'gender' => 'male',
            'is_guest' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->actingAs($this->admin())
            ->callTool(MasterDataTool::class, ['entity' => 'customers', 'search' => $name, 'limit' => 5]);

        $this->assertArrayHasKey('rows', $result);
        $this->assertNotEmpty($result['rows']);

        $row = collect($result['rows'])->firstWhere('name', $name);

        $this->assertNotNull($row);
        $this->assertSame($name, $row['name']);
        $this->assertSame($email, $row['email']);
        $this->assertSame($phone, $row['wa_number']);
    }

    public function test_customer_insight_birthday_list_shows_full_pii_for_authorized_admin(): void
    {
        $email = 'budi.'.Str::lower(Str::random(6)).'@example.test';
        $birthday = now()->startOfMonth()->toDateString();

        DB::table('customers')->insert([
            'id' => (string) Str::uuid(),
            'name' => 'Budi Santoso',
            'email' => $email,
            'wa_number' => '081234567890',
            'gender' => 'male',
            'date_of_birth' => $birthday,
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
            ->first(fn (array $item): bool => ($item['email'] ?? null) === $email);

        $this->assertNotNull($row);
        $this->assertSame('Budi Santoso', $row['name']);
        $this->assertSame($email, $row['email']);
        $this->assertSame('081234567890', $row['wa_number']);
        $this->assertSame($birthday, $row['date_of_birth']);
    }

    public function test_query_data_allows_mass_customer_detail_for_authorized_admin(): void
    {
        $result = $this->actingAs($this->admin())->callTool(QueryDataTool::class, [
            'sql' => 'select name, email, wa_number from customers limit 20',
            'limit' => 20,
        ]);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertArrayHasKey('rows', $result);

        foreach ($result['rows'] as $row) {
            $this->assertStringNotContainsString('*', (string) $row['name']);
        }
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

    public function test_tool_references_are_clickable_for_authorized_admin(): void
    {
        $registry = app(AssistantReferenceRegistry::class);

        $stock = $registry->forTool('stock_health', ['scope' => 'store'], $this->admin());
        $customer = $registry->forTool('master_data', ['entity' => 'customers'], $this->admin());

        $this->assertNotEmpty($stock);
        $this->assertSame(route('monitoring.stok-percabang'), $stock[0]['url']);
        $this->assertNotEmpty($customer);
        $this->assertSame(route('operations.customers.index'), $customer[0]['url']);
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

    public function test_email_and_phone_in_model_answer_are_shown(): void
    {
        VeeAssistant::fake([
            'Hubungi budi@example.com atau 081234567890.',
        ]);

        $response = $this->actingAs($this->admin())
            ->postJson(route('ai-assistant.chat'), ['message' => 'Siapa kontak pelanggan?'])
            ->assertOk();

        $answer = (string) $response->json('answer');

        $this->assertStringContainsString('budi@example.com', $answer);
        $this->assertStringContainsString('081234567890', $answer);
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

    public function test_mcp_query_data_can_read_customer_list(): void
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

        $this->assertFalse($response->json('result.isError'));
        $this->assertArrayNotHasKey('error', $payload);
        $this->assertArrayHasKey('row_count', $payload);
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
