<?php

namespace Tests\Feature\Monitoring;

use App\Models\Discount;
use App\Models\Promo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class DiscountSyncTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_discount_sync_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('monitoring.discount-sync.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Monitoring/DiscountSync')
                ->has('rows')
                ->has('summary')
                ->has('channelOptions')
                ->has('activeOptions')
                ->has('filters')
                ->has('urls'));
    }

    public function test_discount_caps_can_be_synchronized_across_channels(): void
    {
        $fixture = $this->fixture();

        $this->actingAs($this->admin())
            ->get(route('monitoring.discount-sync.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('rows.data.0.mismatch', true)
                ->where('rows.data.0.pos_cap', 50000)
                ->where('rows.data.0.ecommerce_cap', 75000));

        $this->actingAs($this->admin())
            ->post(route('monitoring.discount-sync.sync'), [
                'discount_ids' => [$fixture['pos']->id, $fixture['ecommerce']->id],
                'target_max_discount' => 60000,
                'channel_scope' => 'global',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('60000.00', (string) $fixture['pos']->refresh()->max_discount);
        $this->assertSame('60000.00', (string) $fixture['ecommerce']->refresh()->max_discount);

        $this->assertSame(2, DB::table('discount_products')
            ->whereIn('discount_id', [$fixture['pos']->id, $fixture['ecommerce']->id])
            ->where('applied_to', 'global')
            ->whereNull('store_id')
            ->count());
    }

    private function fixture(): array
    {
        $productId = DB::table('products')->value('id');
        $this->assertNotNull($productId);

        $suffix = Str::upper(Str::random(6));

        $promo = Promo::query()->create([
            'name' => 'PROMO SYNC '.$suffix,
            'description' => 'Promo sync test',
            'code' => 'PS'.$suffix,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'all_store' => true,
            'store_id' => null,
        ]);

        $pos = Discount::query()->create([
            'promo_id' => $promo->id,
            'name' => 'DISKON POS '.$suffix,
            'type' => 'percentage',
            'value' => 10,
            'max_discount' => 50000,
            'min_purchase' => 0,
            'priority' => 100,
            'combinable' => false,
            'is_active' => true,
        ]);

        $ecommerce = Discount::query()->create([
            'promo_id' => $promo->id,
            'name' => 'DISKON ECOMMERCE '.$suffix,
            'type' => 'percentage',
            'value' => 10,
            'max_discount' => 75000,
            'min_purchase' => 0,
            'priority' => 100,
            'combinable' => false,
            'is_active' => true,
        ]);

        DB::table('discount_products')->insert([
            [
                'discount_id' => $pos->id,
                'product_id' => $productId,
                'applied_to' => 'store',
                'store_id' => null,
            ],
            [
                'discount_id' => $ecommerce->id,
                'product_id' => $productId,
                'applied_to' => 'ecommerce',
                'store_id' => null,
            ],
        ]);

        return compact('promo', 'pos', 'ecommerce');
    }
}
