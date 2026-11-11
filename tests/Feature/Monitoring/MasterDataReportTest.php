<?php

namespace Tests\Feature\Monitoring;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class MasterDataReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_new_master_data_reports_are_available(): void
    {
        $admin = $this->admin();

        $routes = [
            'monitoring.branch-expansion.index' => ['topRegions', 'summary'],
            'monitoring.loyalty-accounts.index' => ['rows', 'topMembers', 'summary'],
            'monitoring.pending-transactions.index' => ['rows', 'pendingByType', 'summary'],
            'monitoring.transaction-anomalies.index' => ['rows', 'flowRows', 'paymentMethodRows', 'summary'],
            'monitoring.warehouse-user-audit.index' => ['rows', 'userSummary', 'summary'],
            'monitoring.stock-opname-variance.index' => ['rows', 'opnameItems', 'storeSummary', 'periodRows', 'reasonRows', 'summary'],
            'monitoring.stock-movements.index' => ['rows', 'dailyRows', 'monthlyRows', 'summary'],
            'monitoring.promo-loyalty.index' => ['promoRows', 'pointRows', 'referralRows', 'summary'],
            'monitoring.user-monitoring.index' => ['rows', 'dailyRows', 'appSummary', 'summary'],
        ];

        foreach ($routes as $route => $keys) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(function (Assert $page) use ($keys) {
                    $page->component('Monitoring/Report');

                    foreach ($keys as $key) {
                        $page->has("data.{$key}");
                    }
                });
        }

        $this->actingAs($admin)
            ->get(route('monitoring.stock-opname-variance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('canDecide', false)
                ->where('urls.pos_stock_opname', rtrim((string) config('backoffice.pos_app_url'), '/').'/storage/stock-opname')
                ->missing('urls.approve')
                ->missing('urls.reject'));

        $this->actingAs($admin)
            ->get(route('monitoring.promo-loyalty.index', ['dataset' => 'referralRows']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.activeDataset', 'referralRows')
                ->has('data.referralRows'));
    }

    public function test_report_navigation_contains_new_menus(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('urls.branch_expansion')
                ->has('urls.loyalty_accounts')
                ->has('urls.pending_transactions')
                ->has('urls.anomalies')
                ->has('urls.warehouse_audit')
                ->has('urls.stock_opname')
                ->has('urls.stock_movements')
                ->has('urls.promo_acquisition')
                ->has('urls.user_access'));

        $this->actingAs($this->admin())
            ->get(route('monitoring.command-center.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Index')
                ->where('monitoringGroups', fn ($groups) => collect($groups)->pluck('items')->flatten(1)->pluck('url')->contains(fn ($url) => str_contains((string) $url, 'monitoring/branch-expansion'))));
    }

    public function test_stock_opname_report_is_read_only_and_points_to_pos(): void
    {
        $admin = $this->admin();
        $store = DB::table('stores')->whereNull('deleted_at')->first();
        $location = DB::table('locations')->whereNull('deleted_at')->first();

        $this->assertNotNull($store);
        $this->assertNotNull($location);

        $sessionId = (string) Str::uuid();

        DB::table('store_opname_sessions')->insert([
            'id' => $sessionId,
            'name' => 'Opname Uji Read Only',
            'type' => 'partial',
            'store_id' => $store->id,
            'location_id' => $location->id,
            'status' => 'pending_approval',
            'scheduled_at' => now(),
            'started_at' => now(),
            'finished_at' => now(),
            'counting_round' => 1,
            'created_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/monitoring/stock-opname-variance/{$sessionId}/approve", [
                'reason' => 'Percobaan approval dari Backoffice',
            ])
            ->assertNotFound();

        $session = DB::table('store_opname_sessions')->where('id', $sessionId)->first();

        $this->assertSame('pending_approval', $session->status);
        $this->assertNull($session->approved_by);
        $this->assertNull($session->approved_at);
    }
}
