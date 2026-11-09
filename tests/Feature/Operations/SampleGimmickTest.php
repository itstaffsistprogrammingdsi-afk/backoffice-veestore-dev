<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class SampleGimmickTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_sample_gimmick_page_uses_inertia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('operations.sample-gimmick.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Operations/SampleGimmick')
                ->has('rows')
                ->has('summary')
                ->has('stores')
                ->has('categories')
                ->has('filters')
                ->has('urls'));
    }

    public function test_store_item_can_be_marked_as_sample_and_restored(): void
    {
        $storeItemId = DB::table('store_items')->value('id');

        $this->assertNotNull($storeItemId);

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'sample',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $marker = json_decode((string) DB::table('app_settings')->where('key', 'store_sample_gimmick:'.$storeItemId)->value('value'), true);

        $this->assertSame('sample', $marker['type'] ?? null);
        $this->assertSame('inventory', DB::table('store_items')->where('id', $storeItemId)->value('category'));

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'inventory',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('inventory', DB::table('store_items')->where('id', $storeItemId)->value('category'));
        $this->assertNull(DB::table('app_settings')->where('key', 'store_sample_gimmick:'.$storeItemId)->value('value'));
    }

    public function test_invalid_category_is_rejected(): void
    {
        $storeItemId = DB::table('store_items')->value('id');

        $this->actingAs($this->admin())
            ->post(route('operations.sample-gimmick.mark'), [
                'ids' => [$storeItemId],
                'category' => 'invalid',
            ])
            ->assertSessionHasErrors('category');
    }
}
