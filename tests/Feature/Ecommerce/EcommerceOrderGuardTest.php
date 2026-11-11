<?php

namespace Tests\Feature\Ecommerce;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class EcommerceOrderGuardTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    protected $connectionsToTransact = ['pgsql', 'pos'];

    public function test_invalid_status_filter_is_rejected_without_server_error(): void
    {
        $this->actingAs($this->admin())
            ->get(route('ecommerce.orders.index', ['status' => 'bukan-status']))
            ->assertRedirect();
    }

    public function test_invalid_order_uuid_returns_not_found(): void
    {
        $this->actingAs($this->admin())
            ->get('/ecommerce/orders/bukan-uuid')
            ->assertNotFound();
    }

    public function test_order_can_be_cancelled_without_choosing_a_handler_location(): void
    {
        $order = DB::connection('pgsql')
            ->table('ecommerce_orders')
            ->where('status', 'paid')
            ->first();

        if (! $order) {
            $this->markTestSkipped('Tidak ada order berstatus paid untuk pengujian.');
        }

        $this->actingAs($this->admin())
            ->put(route('ecommerce.orders.update', ['id' => $order->id]), [
                'status' => 'cancelled',
            ])
            ->assertRedirect(route('ecommerce.orders.index'));

        $this->assertSame(
            'cancelled',
            (string) DB::connection('pgsql')
                ->table('ecommerce_orders')
                ->where('id', $order->id)
                ->value('status')
        );
    }

    public function test_notification_open_rejects_protocol_relative_redirect(): void
    {
        $this->actingAs($this->admin())
            ->get(route('notifications.open', [
                'key' => 'guard-test',
                'redirect' => '//evil.example',
            ]))
            ->assertRedirect(route('reports.business-overview.index'));
    }
}
