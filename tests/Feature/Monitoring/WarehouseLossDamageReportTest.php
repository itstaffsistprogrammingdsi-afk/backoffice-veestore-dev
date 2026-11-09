<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class WarehouseLossDamageReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_warehouse_loss_damage_report_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.warehouse-loss-damage.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.rows')
                ->has('data.summary')
                ->has('data.warehouses')
                ->has('data.issueTypes')
                ->has('data.filters'));
    }

    public function test_warehouse_loss_damage_report_accepts_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.warehouse-loss-damage.index', [
                'q' => 'produk',
                'issue_type' => 'Hilang',
                'from' => '2026-10-01',
                'to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.filters.q', 'produk')
                ->where('data.filters.issue_type', 'Hilang')
                ->where('data.filters.from', '2026-10-01')
                ->where('data.filters.to', '2026-10-31'));
    }
}
