<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class MonitoringIndexTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_monitoring_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('monitoring.command-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Index')
                ->has('monitoringGroups'));

        $routes = [
            'monitoring.stok-wms',
            'monitoring.omset-kotor.index',
            'monitoring.store-inbound.index',
            'monitoring.supplier-insight.index',
            'monitoring.cashier-shift-report.index',
            'monitoring.tracking-barang.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component('Monitoring/Report'));
        }
    }
}
