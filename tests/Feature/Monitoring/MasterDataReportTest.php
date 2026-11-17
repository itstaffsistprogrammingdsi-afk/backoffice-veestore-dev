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
                ->where('canDecide', true)
                ->where('urls.pos_stock_opname', rtrim((string) config('backoffice.pos_app_url'), '/').'/storage/stock-opname')
                ->has('urls.approve')
                ->has('urls.reject'));

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

    public function test_stock_opname_report_exposes_backoffice_decision_flags(): void
    {
        $admin = $this->admin();
        $store = DB::table('stores')->whereNull('deleted_at')->first();
        $location = DB::table('locations')->whereNull('deleted_at')->first();
        $product = DB::table('products')->whereNull('deleted_at')->first();
        $creatorId = DB::table('users')
            ->where('id', '!=', $admin->id)
            ->whereNull('deleted_at')
            ->value('id');

        $this->assertNotNull($store);
        $this->assertNotNull($location);
        $this->assertNotNull($product);
        $this->assertNotNull($creatorId);

        $sessionId = (string) Str::uuid();
        $sessionName = 'Opname Uji BO '.Str::upper(Str::random(6));

        DB::table('store_opname_sessions')->insert([
            'id' => $sessionId,
            'name' => $sessionName,
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
            'id' => (string) Str::uuid(),
            'opname_session_id' => $sessionId,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'system_stock' => 2,
            'physical_stock' => 1,
            'discrepancy_reason' => 'missing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('monitoring.stock-opname-variance.index', ['q' => $sessionName]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->where('data.rows.data.0.session_id', $sessionId)
                ->where('data.rows.data.0.can_approve', true)
                ->where('data.rows.data.0.can_reject', true)
                ->where('data.rows.data.0.can_decide', true)
                ->has('data.rows.data.0.approval_context.missing_items'));
    }
}
