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
            'monitoring.promo-acquisition.index' => ['rows', 'pointRows', 'referralRows', 'summary'],
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
                ->has('canDecide')
                ->where('urls.approve', route('monitoring.stock-opname-variance.approve', ['id' => '__ID__'])));

        $this->actingAs($admin)
            ->get(route('monitoring.referral-score-report.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/Report')
                ->has('data.affiliateRows')
                ->has('data.summary.total_affiliate_points'));
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

    public function test_stock_opname_session_can_be_approved_with_audit_trail(): void
    {
        $admin = $this->admin();
        $store = DB::table('stores')->whereNull('deleted_at')->first();
        $product = DB::table('products')->first();
        $location = DB::table('locations')->whereNull('deleted_at')->first();

        $this->assertNotNull($store);
        $this->assertNotNull($product);
        $this->assertNotNull($location);

        $sessionId = (string) Str::uuid();

        DB::table('store_opname_sessions')->insert([
            'id' => $sessionId,
            'name' => 'Opname Uji Approval',
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

        DB::table('store_opname_session_items')->insert([
            'id' => (string) Str::uuid(),
            'opname_session_id' => $sessionId,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'system_stock' => 10,
            'physical_stock' => 8,
            'discrepancy_reason' => 'Hilang',
            'discrepancy_notes' => 'Selisih uji approval',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('monitoring.stock-opname-variance.approve', ['id' => $sessionId]), [
                'reason' => 'Disetujui HO untuk penyesuaian',
            ])
            ->assertRedirect();

        $session = DB::table('store_opname_sessions')->where('id', $sessionId)->first();

        $this->assertSame('approved', $session->status);
        $this->assertSame($admin->id, $session->approved_by);

        $audit = DB::table('store_opname_approval_audits')->where('opname_session_id', $sessionId)->first();

        $this->assertNotNull($audit);
        $this->assertSame('approved', $audit->action);
        $this->assertSame($admin->id, $audit->actor_id);

        $decisions = json_decode($audit->item_decisions, true);

        $this->assertIsArray($decisions);
        $this->assertSame(-2, $decisions[0]['difference_qty']);
    }
}
