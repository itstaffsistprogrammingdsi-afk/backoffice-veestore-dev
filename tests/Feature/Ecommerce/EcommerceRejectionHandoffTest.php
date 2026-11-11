<?php

namespace Tests\Feature\Ecommerce;

use App\Services\EcommerceRejectionHandoffService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class EcommerceRejectionHandoffTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    protected $connectionsToTransact = ['pgsql', 'pos'];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('backoffice.ecommerce_rejection_handoff_enabled', true);
    }

    public function test_rejection_page_is_hidden_when_feature_flag_is_disabled(): void
    {
        config()->set('backoffice.ecommerce_rejection_handoff_enabled', false);

        $this->actingAs($this->admin())
            ->get(route('ecommerce.rejections.index'))
            ->assertNotFound();
    }

    public function test_rejection_page_uses_inertia_and_exposes_handoff_context(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ecommerce.rejections.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ecommerce/Rejections')
                ->has('cases')
                ->has('summary')
                ->has('stores')
                ->has('warehouses')
                ->has('filters')
                ->has('urls.resolve')
                ->where('handoffError', null));
    }

    public function test_rejection_page_lists_pending_case_with_order_context(): void
    {
        $admin = $this->admin();
        $order = DB::connection('pgsql')->table('ecommerce_orders')->first();
        $this->assertNotNull($order);

        $caseId = (string) Str::uuid();
        $now = now();

        DB::connection('pos')->table('ecommerce_fulfillment_rejection_cases')->insert([
            'id' => $caseId,
            'ecommerce_order_id' => $order->id,
            'store_id' => $order->handled_by_store_id ?? $order->handled_by_warehouse_id,
            'requested_by_user_id' => $admin->id,
            'request_key' => (string) Str::uuid(),
            'mode' => 'strict',
            'reason' => 'inventory_shortage',
            'note' => 'Stok tidak mencukupi.',
            'status' => 'pending_backoffice_review',
            'evidence_hash' => hash('sha256', $caseId),
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($admin)
            ->get(route('ecommerce.rejections.index', ['order_id' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Ecommerce/Rejections')
                ->where('cases.total', 1)
                ->where('cases.data.0.id', $caseId)
                ->where('cases.data.0.order.order_number', $order->order_number)
                ->where('cases.data.0.reason_label', 'Stok tidak mencukupi'));
    }

    public function test_backoffice_reassign_resolves_case_and_publishes_outbox(): void
    {
        $admin = $this->admin();
        $order = DB::connection('pgsql')
            ->table('ecommerce_orders')
            ->whereIn('status', ['paid', 'processed', 'packing'])
            ->first();

        $this->assertNotNull($order, 'Butuh order e-commerce aktif untuk pengujian.');

        $targetStore = DB::connection('pgsql')
            ->table('stores')
            ->whereNull('deleted_at')
            ->where('id', '<>', $order->handled_by_store_id)
            ->first(['id', 'name']);

        $this->assertNotNull($targetStore);

        $caseId = (string) Str::uuid();
        $outboxId = (string) Str::uuid();
        $now = now();

        DB::connection('pos')->table('ecommerce_fulfillment_rejection_cases')->insert([
            'id' => $caseId,
            'ecommerce_order_id' => $order->id,
            'store_id' => $order->handled_by_store_id ?? $targetStore->id,
            'requested_by_user_id' => $admin->id,
            'request_key' => (string) Str::uuid(),
            'mode' => 'strict',
            'reason' => 'inventory_shortage',
            'note' => 'Stok toko tidak mencukupi saat scan.',
            'status' => 'pending_backoffice_review',
            'evidence_hash' => hash('sha256', $caseId),
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('pos')->table('ecommerce_rejection_outbox')->insert([
            'id' => $outboxId,
            'event_key' => 'ecommerce.fulfillment_rejection.pending:'.$caseId,
            'event_type' => 'ecommerce.fulfillment_rejection.pending_backoffice_review',
            'aggregate_type' => 'ecommerce_fulfillment_rejection',
            'aggregate_id' => $caseId,
            'payload' => json_encode(['case_id' => $caseId], JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'available_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($admin)
            ->post(route('ecommerce.rejections.resolve', ['id' => $caseId]), [
                'decision' => 'reassign_store',
                'target_id' => $targetStore->id,
                'note' => 'Dipindahkan ke toko yang masih memiliki stok.',
            ])
            ->assertRedirect(route('ecommerce.rejections.index'));

        $case = DB::connection('pos')
            ->table('ecommerce_fulfillment_rejection_cases')
            ->where('id', $caseId)
            ->first();

        $this->assertSame('store_rejected', $case->status);
        $this->assertSame($admin->id, $case->decided_by_user_id);
        $this->assertNotNull($case->decided_at);
        $this->assertStringStartsWith('[Backoffice] ', (string) $case->decision_note);

        $outbox = DB::connection('pos')
            ->table('ecommerce_rejection_outbox')
            ->where('id', $outboxId)
            ->first();

        $this->assertSame('published', $outbox->status);
        $this->assertNotNull($outbox->published_at);

        $updatedOrder = DB::connection('pgsql')
            ->table('ecommerce_orders')
            ->where('id', $order->id)
            ->first(['handled_by_store_id', 'handled_by_warehouse_id']);

        $this->assertSame($targetStore->id, $updatedOrder->handled_by_store_id);
        $this->assertNull($updatedOrder->handled_by_warehouse_id);
    }

    public function test_order_update_handoff_resolves_pending_case(): void
    {
        $admin = $this->admin();
        $order = DB::connection('pgsql')->table('ecommerce_orders')->first();
        $this->assertNotNull($order);

        $caseId = (string) Str::uuid();
        $now = now();

        DB::connection('pos')->table('ecommerce_fulfillment_rejection_cases')->insert([
            'id' => $caseId,
            'ecommerce_order_id' => $order->id,
            'store_id' => $order->handled_by_store_id ?? $order->handled_by_warehouse_id,
            'requested_by_user_id' => $admin->id,
            'request_key' => (string) Str::uuid(),
            'mode' => 'strict',
            'reason' => 'item_issue',
            'note' => 'Kondisi item tidak sesuai.',
            'status' => 'pending_backoffice_review',
            'evidence_hash' => hash('sha256', $caseId),
            'requested_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $resolved = app(EcommerceRejectionHandoffService::class)
            ->resolveForOrderUpdate(
                (string) $order->id,
                (string) $admin->id,
                'Diselesaikan dari pembaruan order Backoffice.'
            );

        $this->assertTrue($resolved);
        $this->assertSame(
            'store_rejected',
            (string) DB::connection('pos')
                ->table('ecommerce_fulfillment_rejection_cases')
                ->where('id', $caseId)
                ->value('status')
        );
    }

    public function test_voucher_request_approval_activates_store_voucher(): void
    {
        $admin = $this->admin();
        $store = DB::connection('pgsql')->table('stores')->whereNull('deleted_at')->first();
        $this->assertNotNull($store);

        $requestId = (string) Str::uuid();
        $voucherId = (string) Str::uuid();
        $now = now();

        DB::table('store_voucher_requests')->insert([
            'id' => $requestId,
            'request_number' => 'VR-TEST-'.Str::upper(Str::random(6)),
            'store_id' => $store->id,
            'request_date' => $now,
            'status' => 'pending',
            'created_by' => $admin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('vouchers')->insert([
            'id' => $voucherId,
            'code' => 'RET-TEST-'.Str::upper(Str::random(6)),
            'name' => 'Voucher Retur Uji',
            'description' => 'Voucher untuk pengujian integrasi approval.',
            'type' => 'fixed',
            'value' => 50000,
            'max_discount' => 50000,
            'min_purchase' => 0,
            'usage_limit' => 1,
            'usage_count' => 0,
            'is_active' => false,
            'distribution_type' => 'public',
            'context' => 'store_customer_return',
            'store_voucher_request_id' => $requestId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($admin)
            ->put(route('branch-operations.request-voucher.approve', ['id' => $requestId]))
            ->assertRedirect(route('branch-operations.request-voucher.index'));

        $this->assertSame(
            1,
            (int) DB::table('vouchers')->where('id', $voucherId)->where('is_active', true)->count()
        );
        $this->assertSame(
            'approved',
            (string) DB::table('store_voucher_requests')->where('id', $requestId)->value('status')
        );
    }
}
