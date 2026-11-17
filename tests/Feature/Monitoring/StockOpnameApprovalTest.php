<?php

namespace Tests\Feature\Monitoring;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StockOpnameApprovalTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_backoffice_can_approve_opname_and_adjust_stock(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin);
        $reviewedAt = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at');

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [$fixture['item_id']],
                'reason' => 'Approval setelah pemeriksaan fisik dan audit.',
                'reviewed_at' => $reviewedAt,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $session = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->first();
        $item = DB::table('items')->where('id', $fixture['item_id'])->first();

        $this->assertSame('completed', $session->status);
        $this->assertSame($admin->id, $session->approved_by);
        $this->assertNotNull($session->approved_at);
        $this->assertSame('missing', $item->status);
        $this->assertNull($item->current_location_id);
        $this->assertSame($fixture['bad_condition_id'], $item->current_condition_id);

        $audit = DB::table('store_opname_approval_audits')
            ->where('opname_session_id', $fixture['session_id'])
            ->where('action', 'approved')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->actor_id);
        $this->assertSame($fixture['item_id'], json_decode($audit->item_decisions, true)[0]['item_id']);

        $this->assertSame(1, DB::table('item_condition_histories')
            ->where('item_id', $fixture['item_id'])
            ->where('item_condition_id', $fixture['bad_condition_id'])
            ->count());
    }

    public function test_approval_requires_all_unresolved_items_confirmed_as_missing(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin);
        $reviewedAt = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at');

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [],
                'reason' => 'Approval tanpa konfirmasi item missing.',
                'reviewed_at' => $reviewedAt,
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending_approval', DB::table('store_opname_sessions')
            ->where('id', $fixture['session_id'])
            ->value('status'));
    }

    public function test_manual_exception_blocks_approval(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin, 'manual_exception');
        $reviewedAt = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at');

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [],
                'reason' => 'Approval saat masih ada manual exception.',
                'reviewed_at' => $reviewedAt,
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending_approval', DB::table('store_opname_sessions')
            ->where('id', $fixture['session_id'])
            ->value('status'));
    }

    public function test_backoffice_can_reject_opname_and_request_recount(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin);

        $reason = 'Selisih material perlu dihitung ulang oleh tim toko.';

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.reject', $fixture['session_id']), [
                'reason' => $reason,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $session = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->first();

        $this->assertSame('recount_required', $session->status);
        $this->assertSame(2, (int) $session->counting_round);
        $this->assertSame($admin->id, $session->rejected_by);
        $this->assertSame($reason, $session->rejection_reasons);
        $this->assertSame($reason, $session->recount_reason);

        $this->assertSame(1, DB::table('store_opname_approval_audits')
            ->where('opname_session_id', $fixture['session_id'])
            ->where('action', 'rejected')
            ->count());
    }

    public function test_creator_cannot_approve_own_session(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin, 'unresolved_discrepancy', $admin->id);
        $reviewedAt = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at');

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [$fixture['item_id']],
                'reason' => 'Percobaan approval sesi sendiri.',
                'reviewed_at' => $reviewedAt,
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending_approval', DB::table('store_opname_sessions')
            ->where('id', $fixture['session_id'])
            ->value('status'));
    }

    public function test_user_who_scanned_the_session_cannot_approve(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin);
        $reviewedAt = DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at');

        DB::table('store_opname_session_scans')
            ->where('opname_session_id', $fixture['session_id'])
            ->update(['scanned_by' => $admin->id]);

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [$fixture['item_id']],
                'reason' => 'Percobaan approval oleh petugas pemindai.',
                'reviewed_at' => $reviewedAt,
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending_approval', DB::table('store_opname_sessions')
            ->where('id', $fixture['session_id'])
            ->value('status'));
    }

    public function test_stale_review_token_blocks_approval(): void
    {
        $admin = $this->admin();
        $fixture = $this->fixture($admin);
        $staleReviewedAt = Carbon::parse(
            DB::table('store_opname_sessions')->where('id', $fixture['session_id'])->value('updated_at')
        )->subMinute();

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', $fixture['session_id']), [
                'missing_item_ids' => [$fixture['item_id']],
                'reason' => 'Approval dengan token review yang sudah kedaluwarsa.',
                'reviewed_at' => $staleReviewedAt->toIso8601String(),
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('pending_approval', DB::table('store_opname_sessions')
            ->where('id', $fixture['session_id'])
            ->value('status'));
    }

    public function test_user_without_opname_permission_cannot_decide(): void
    {
        $fixture = $this->fixture($this->admin());
        $user = User::create([
            'name' => 'User Tanpa Izin Opname',
            'username' => Str::lower(Str::random(10)),
            'email' => Str::lower(Str::random(10)).'@example.test',
            'password' => 'password',
            'backoffice_access' => true,
        ]);

        $this->actingAs($user)
            ->post(route('monitoring.stock-opname-variance.reject', $fixture['session_id']), [
                'reason' => 'Percobaan penolakan tanpa izin.',
            ])
            ->assertForbidden();
    }

    private function fixture(User $admin, string $outcome = 'unresolved_discrepancy', ?string $creatorId = null): array
    {
        $store = DB::table('stores')->whereNull('deleted_at')->first();
        $location = DB::table('locations')->whereNull('deleted_at')->first();
        $product = DB::table('products')->whereNull('deleted_at')->first();
        $goodConditionId = DB::table('item_conditions')->where('status', 'good')->value('id');
        $badConditionId = DB::table('item_conditions')->where('status', 'bad')->value('id');
        $creatorId ??= DB::table('users')
            ->where('id', '!=', $admin->id)
            ->whereNull('deleted_at')
            ->value('id');

        $this->assertNotNull($store);
        $this->assertNotNull($location);
        $this->assertNotNull($product);
        $this->assertNotNull($goodConditionId);
        $this->assertNotNull($badConditionId);
        $this->assertNotNull($creatorId);

        $sessionId = (string) Str::uuid();
        $sessionItemId = (string) Str::uuid();
        $expectedItemId = (string) Str::uuid();
        $itemId = (string) Str::uuid();

        DB::table('items')->insert([
            'id' => $itemId,
            'product_id' => $product->id,
            'current_location_id' => $location->id,
            'current_condition_id' => $goodConditionId,
            'status' => 'store_stock',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_items')->insert([
            'id' => (string) Str::uuid(),
            'store_id' => $store->id,
            'item_id' => $itemId,
            'category' => 'inventory',
            'created_at' => now(),
        ]);

        DB::table('store_opname_sessions')->insert([
            'id' => $sessionId,
            'name' => 'Opname Uji Approval '.Str::upper(Str::random(6)),
            'type' => 'partial',
            'store_id' => $store->id,
            'location_id' => $location->id,
            'status' => 'pending_approval',
            'scheduled_at' => now(),
            'started_at' => now(),
            'finished_at' => now(),
            'counting_round' => 1,
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_opname_session_items')->insert([
            'id' => $sessionItemId,
            'opname_session_id' => $sessionId,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'system_stock' => 1,
            'physical_stock' => 0,
            'discrepancy_reason' => 'missing',
            'discrepancy_notes' => 'Tidak ditemukan saat hitung fisik.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_opname_session_expected_items')->insert([
            'id' => $expectedItemId,
            'opname_session_id' => $sessionId,
            'opname_session_item_id' => $sessionItemId,
            'item_id' => $itemId,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'rfid_tag_value' => 'RFID-'.Str::upper(Str::random(8)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_opname_session_scans')->insert([
            'id' => (string) Str::uuid(),
            'opname_session_id' => $sessionId,
            'opname_session_item_id' => $sessionItemId,
            'expected_item_id' => $expectedItemId,
            'item_id' => $itemId,
            'identifier' => 'RFID-'.Str::upper(Str::random(8)),
            'is_additional' => false,
            'scanned_at' => now(),
            'scanned_by' => $creatorId,
            'item_condition_id' => $badConditionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('store_opname_reconciliation_results')->insert([
            'id' => (string) Str::uuid(),
            'opname_session_id' => $sessionId,
            'item_id' => $itemId,
            'outcome' => $outcome,
            'reconciled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'session_id' => $sessionId,
            'item_id' => $itemId,
            'bad_condition_id' => $badConditionId,
        ];
    }
}
