<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class MasterChangeHistoryReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_master_change_history_report_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.master-change-history.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.rows')
                ->has('data.summary')
                ->has('data.users')
                ->has('data.types')
                ->has('data.actions')
                ->has('data.filters'));
    }

    public function test_overnight_price_adjustment_report_uses_price_filter(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.overnight-price-adjustment.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.filters.jenis', 'Harga')
                ->where('data.filters.overnight', '1'));
    }

    public function test_master_change_history_report_accepts_filters(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.master-change-history.index', [
                'jenis' => 'Voucher',
                'aksi' => 'create',
                'from' => '2026-11-01',
                'to' => '2026-11-30',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('data.filters.jenis', 'Voucher')
                ->where('data.filters.aksi', 'create')
                ->where('data.filters.from', '2026-11-01')
                ->where('data.filters.to', '2026-11-30'));
    }
}
