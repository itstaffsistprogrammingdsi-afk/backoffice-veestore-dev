<?php

namespace Tests\Feature\Loyalty;

use App\Support\Navigation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class LoyaltyPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_loyalty_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('loyalty.customer-loyalty-points.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Points')
                ->has('rows')
                ->has('summary')
                ->where('loyaltyModule.current', 'points')
                ->has('loyaltyModule.items', 8)
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('loyalty.customer-loyalty-point-mutations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Mutations')
                ->has('rows')
                ->has('clps')
                ->has('typeOptions')
                ->where('loyaltyModule.current', 'mutations')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('loyalty.customer-visits.index'))
            ->assertRedirect(route('operations.customers.index'));
    }

    public function test_loyalty_navigation_groups_all_loyalty_modules(): void
    {
        $navigation = Navigation::forUser($this->admin());
        $operations = collect($navigation['areas'])->firstWhere('key', 'operations');
        $loyaltyGroup = collect($operations['groups'])->firstWhere('label', 'Loyalty');

        $this->assertNotNull($loyaltyGroup);
        $this->assertEqualsCanonicalizing([
            'loyalty.customer-loyalty-points.index',
            'loyalty.customer-loyalty-point-mutations.index',
            'master-data.memberships.index',
            'master-data.membership-benefits.index',
            'settings.points.edit',
            'settings.app.customer-loyalty-point-transaction-reward.edit',
            'settings.app.affiliate-points.inviter.edit',
            'settings.app.affiliate-points.invitee.edit',
        ], collect($loyaltyGroup['items'])->pluck('route')->all());
    }

    public function test_mutation_page_uses_actual_point_columns(): void
    {
        $point = DB::table('customer_loyalty_points')->orderByDesc('updated_at')->first();
        $this->assertNotNull($point);

        DB::table('customer_loyalty_point_mutations')->insert([
            'customer_id' => $point->customer_id,
            'customer_loyalty_point_id' => $point->id,
            'type' => 'earn',
            'mutation_type' => '+',
            'points' => 10,
            'balance_after' => 10,
            'used_points' => 0,
            'description' => 'QA mutation',
            'has_expiration' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('loyalty.customer-loyalty-point-mutations.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loyalty/Mutations')
                ->where('rows.data.0.points', 10)
                ->where('rows.data.0.type', 'earn')
                ->where('rows.data.0.type_label', 'Earn')
                ->where('rows.data.0.balance_after', 10)
                ->where('rows.data.0.description', 'QA mutation'));
    }
}
