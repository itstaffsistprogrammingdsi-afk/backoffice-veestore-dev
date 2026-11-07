<?php

namespace Tests\Feature\MasterData;

use App\Models\Discount;
use App\Models\Promo;
use App\Models\Voucher;
use App\Models\VoucherCode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PromoSetupTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_promo_setup_page_exposes_voucher_options(): void
    {
        $this->actingAs($this->admin())
            ->get(route('master-data.promo-setup.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/PromoSetup')
                ->has('voucherContexts', 4)
                ->where('canCreateVoucher', true));
    }

    public function test_promo_setup_creates_promo_discount_and_voucher_in_one_flow(): void
    {
        $productId = DB::table('products')->value('id');

        if (! $productId) {
            $this->markTestSkipped('Tidak ada produk untuk diuji.');
        }

        $suffix = Str::upper(Str::random(6));
        $promoName = 'PROMO TEST '.$suffix;
        $promoCode = 'PT'.$suffix;

        $response = $this->actingAs($this->admin())->post(route('master-data.promo-setup.store'), [
            'promo_name' => $promoName,
            'promo_code' => $promoCode,
            'promo_description' => 'Deskripsi pengaturan promo.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'all_stores' => true,
            'store_ids' => [],
            'discount_name' => 'DISKON '.$suffix,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_purchase' => 100000,
            'max_discount' => 50000,
            'all_products' => false,
            'product_ids' => [$productId],
            'create_voucher' => true,
            'voucher_distribution_type' => 'public',
            'voucher_contexts' => ['store_checkout'],
            'voucher_is_active' => true,
        ]);

        $response->assertRedirect(route('master-data.promo-setup.index'));
        $response->assertSessionHas('success');

        $promo = Promo::query()->where('code', $promoCode)->first();
        $this->assertNotNull($promo);
        $this->assertSame($promoName, $promo->name);

        $discount = Discount::query()->where('promo_id', $promo->getKey())->first();
        $this->assertNotNull($discount);
        $this->assertSame('percentage', $discount->type);

        $voucher = Voucher::query()->where('name', $promoName)->first();
        $this->assertNotNull($voucher);
        $this->assertStringStartsWith('VCR-', (string) $voucher->code);
        $this->assertSame('percentage', $voucher->type);
        $this->assertSame('10.00', (string) $voucher->value);
        $this->assertSame('store_checkout', $voucher->context);
        $this->assertSame('public', $voucher->distribution_type);
        $this->assertTrue($voucher->is_active);
    }

    public function test_promo_setup_can_create_unique_code_voucher(): void
    {
        $productId = DB::table('products')->value('id');

        if (! $productId) {
            $this->markTestSkipped('Tidak ada produk untuk diuji.');
        }

        $suffix = Str::upper(Str::random(6));
        $promoName = 'PROMO UNIK '.$suffix;
        $promoCode = 'PU'.$suffix;
        $voucherName = 'VOUCHER UNIK '.$suffix;

        $response = $this->actingAs($this->admin())->post(route('master-data.promo-setup.store'), [
            'promo_name' => $promoName,
            'promo_code' => $promoCode,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'all_stores' => true,
            'store_ids' => [],
            'discount_name' => 'DISKON UNIK '.$suffix,
            'discount_type' => 'fixed',
            'discount_value' => 5000,
            'all_products' => false,
            'product_ids' => [$productId],
            'create_voucher' => true,
            'voucher_name' => $voucherName,
            'voucher_type' => 'fixed',
            'voucher_value' => 5000,
            'voucher_distribution_type' => 'unique_code',
            'voucher_usage_limit' => 2,
            'voucher_contexts' => ['store_checkout'],
            'voucher_is_active' => true,
        ]);

        $response->assertRedirect(route('master-data.promo-setup.index'));

        $voucher = Voucher::query()->where('name', $voucherName)->first();
        $this->assertNotNull($voucher);
        $this->assertSame('unique_code', $voucher->distribution_type);
        $this->assertSame(2, VoucherCode::query()->where('voucher_id', $voucher->getKey())->count());
    }
}
