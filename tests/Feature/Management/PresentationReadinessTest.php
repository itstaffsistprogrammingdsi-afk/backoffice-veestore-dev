<?php

namespace Tests\Feature\Management;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class PresentationReadinessTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_presentation_readiness_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('management.presentation-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/PresentationReadiness')
                ->has('products')
                ->has('storeGroups')
                ->has('dummyStores')
                ->has('dummyWarehouses')
                ->has('summary')
                ->where('canArchive', true)
                ->has('urls'));
    }

    public function test_dummy_product_can_be_archived_with_confirmation(): void
    {
        $product = $this->product('Headset Doraemon '.Str::random(5));

        $this->actingAs($this->admin())
            ->post(route('management.presentation-readiness.archive-products'), [
                'ids' => [$product->id],
                'confirmation' => 'ARSIPKAN',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull(DB::table('products')->where('id', $product->id)->value('deleted_at'));
    }

    public function test_real_product_is_not_archived_by_dummy_cleanup(): void
    {
        $product = $this->product('Produk Immoderma Asli '.Str::random(5));

        $this->actingAs($this->admin())
            ->post(route('management.presentation-readiness.archive-products'), [
                'ids' => [$product->id],
                'confirmation' => 'ARSIPKAN',
            ])
            ->assertRedirect();

        $this->assertNull(DB::table('products')->where('id', $product->id)->value('deleted_at'));
    }

    public function test_wrong_confirmation_does_not_archive_data(): void
    {
        $product = $this->product('Dummy Product '.Str::random(5));

        $this->actingAs($this->admin())
            ->post(route('management.presentation-readiness.archive-products'), [
                'ids' => [$product->id],
                'confirmation' => 'SALAH',
            ])
            ->assertSessionHasErrors('confirmation');

        $this->assertNull(DB::table('products')->where('id', $product->id)->value('deleted_at'));
    }

    public function test_duplicate_store_can_be_archived(): void
    {
        $suffix = Str::lower(Str::random(6));
        $first = Store::query()->create(['name' => 'Store Duplikat '.$suffix, 'address' => 'Jl. A '.$suffix]);
        $second = Store::query()->create(['name' => 'store duplikat '.$suffix, 'address' => 'Jl. B '.$suffix]);

        $this->actingAs($this->admin())
            ->post(route('management.presentation-readiness.archive-stores'), [
                'ids' => [$second->id],
                'confirmation' => 'ARSIPKAN',
            ])
            ->assertRedirect();

        $this->assertNull(DB::table('stores')->where('id', $first->id)->value('deleted_at'));
        $this->assertNotNull(DB::table('stores')->where('id', $second->id)->value('deleted_at'));
    }

    private function product(string $name): Product
    {
        return Product::query()->create([
            'name' => $name,
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'unit_id' => DB::table('units')->value('id'),
            'category_id' => DB::table('product_categories')->value('id'),
        ]);
    }
}
