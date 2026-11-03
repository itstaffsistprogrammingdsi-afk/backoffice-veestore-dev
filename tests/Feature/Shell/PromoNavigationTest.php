<?php

namespace Tests\Feature\Shell;

use App\Support\Navigation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PromoNavigationTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_promo_modules_are_grouped_under_promo_navigation(): void
    {
        $navigation = Navigation::forUser($this->admin());

        $operations = collect($navigation['areas'])->firstWhere('key', 'operations');
        $this->assertNotNull($operations);

        $promoGroup = collect($operations['groups'])->firstWhere('label', 'Promo');
        $this->assertNotNull($promoGroup);

        $this->assertEqualsCanonicalizing([
            'master-data.promos.index',
            'master-data.diskons.index',
            'master-data.products-diskons.index',
            'master-data.vouchers.index',
            'master-data.promo-setup.index',
        ], collect($promoGroup['items'])->pluck('route')->all());

        $ecommerceGroup = collect($operations['groups'])->firstWhere('label', 'Ecommerce');
        $this->assertNotNull($ecommerceGroup);
        $this->assertNotContains(
            'master-data.vouchers.index',
            collect($ecommerceGroup['items'])->pluck('route')->all()
        );
        $this->assertNotContains(
            'master-data.diskons.index',
            collect($ecommerceGroup['items'])->pluck('route')->all()
        );
    }

    public function test_promo_pages_share_module_navigation(): void
    {
        $this->actingAs($this->admin())
            ->get(route('master-data.promos.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/SimpleCrud')
                ->where('promoModule.current', 'promos')
                ->has('promoModule.items', 5));

        $this->actingAs($this->admin())
            ->get(route('master-data.vouchers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/SimpleCrud')
                ->where('promoModule.current', 'vouchers')
                ->has('promoModule.items', 5));

        $this->actingAs($this->admin())
            ->get(route('master-data.promo-setup.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/PromoSetup')
                ->where('promoModule.current', 'setup')
                ->has('promoModule.items', 5));
    }
}
