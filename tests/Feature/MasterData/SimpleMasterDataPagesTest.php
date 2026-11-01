<?php

namespace Tests\Feature\MasterData;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SimpleMasterDataPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_master_data_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $routes = [
            'master-data.partners.index' => 'MasterData/SimpleCrud',
            'master-data.memberships.index' => 'MasterData/SimpleCrud',
            'master-data.payment-methods.index' => 'MasterData/SimpleCrud',
            'master-data.product-specifications.index' => 'MasterData/SimpleCrud',
            'master-data.rfid-tags.index' => 'MasterData/SimpleCrud',
            'master-data.membership-benefits.index' => 'MasterData/SimpleCrud',
            'master-data.warehouses.index' => 'MasterData/SimpleCrud',
            'master-data.units.index' => 'MasterData/SimpleCrud',
            'master-data.suppliers.index' => 'MasterData/SimpleCrud',
            'master-data.item-conditions.index' => 'MasterData/SimpleCrud',
            'master-data.couriers.index' => 'MasterData/SimpleCrud',
            'master-data.diskons.index' => 'MasterData/SimpleCrud',
            'master-data.brands.index' => 'MasterData/SimpleCrud',
            'master-data.product-categories.index' => 'MasterData/SimpleCrud',
            'master-data.customers.index' => 'MasterData/SimpleCrud',
            'master-data.stores.index' => 'MasterData/SimpleCrud',
            'master-data.products.index' => 'MasterData/SimpleCrud',
            'master-data.users.index' => 'MasterData/SimpleCrud',
            'master-data.ecommerce-banners.index' => 'MasterData/SimpleCrud',
            'master-data.locations-store.index' => 'MasterData/SimpleCrud',
            'master-data.locations-warehouse.index' => 'MasterData/SimpleCrud',
            'master-data.promos.index' => 'MasterData/SimpleCrud',
            'master-data.vouchers.index' => 'MasterData/SimpleCrud',
            'master-data.product-prices.index' => 'MasterData/ProductPrices',
            'master-data.promo-setup.index' => 'MasterData/PromoSetup',
            'master-data.products-diskons.index' => 'MasterData/ProductDiscounts',
        ];

        foreach ($routes as $route => $component) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }
}
