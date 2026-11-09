<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class FifoFefoPriorityReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_fifo_fefo_priority_report_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.fifo-fefo-priority.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.rows')
                ->has('data.summary')
                ->has('data.stores')
                ->has('data.warehouses')
                ->has('data.products')
                ->has('data.categories')
                ->has('data.expiryStatuses')
                ->has('data.filters'));
    }

    public function test_fifo_fefo_priority_report_accepts_scope_and_expiry_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.fifo-fefo-priority.index', [
                'scope' => 'store',
                'expiry_status' => 'critical',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.scope', 'store')
                ->where('data.filters.expiry_status', 'critical'));
    }
}
