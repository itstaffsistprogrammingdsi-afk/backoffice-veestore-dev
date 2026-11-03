<?php

namespace Tests\Feature\MasterData;

use App\Models\ProductCategory;
use App\Models\ProductCategoryGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ProductCategoryGroupTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_category_group_page_uses_inertia_and_module_navigation(): void
    {
        $this->actingAs($this->admin())
            ->get(route('master-data.product-category-groups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/SimpleCrud')
                ->where('masterDataModule.current', 'category-groups')
                ->has('masterDataModule.items', 8)
                ->has('items.data'));

        $this->actingAs($this->admin())
            ->get(route('master-data.product-categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/SimpleCrud')
                ->where('masterDataModule.current', 'categories')
                ->has('masterDataModule.items', 8));
    }

    public function test_category_group_can_be_created_updated_and_deleted(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('master-data.product-category-groups.store'), [
                'name' => 'QA Group',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('master-data.product-category-groups.index'))
            ->assertSessionHasNoErrors();

        $group = ProductCategoryGroup::query()->where('name', 'QA Group')->firstOrFail();
        $this->assertSame(5, $group->sort_order);

        $this->actingAs($admin)
            ->put(route('master-data.product-category-groups.update', ['product_category_group' => $group->id]), [
                'name' => 'QA Group Updated',
                'sort_order' => 7,
            ])
            ->assertRedirect(route('master-data.product-category-groups.index'))
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame('QA Group Updated', $group->name);
        $this->assertSame(7, $group->sort_order);

        $this->actingAs($admin)
            ->post(route('master-data.product-categories.store'), [
                'name' => 'QA Category',
                'group_id' => $group->id,
            ])
            ->assertRedirect(route('master-data.product-categories.index'))
            ->assertSessionHasNoErrors();

        $category = ProductCategory::query()->where('name', 'QA Category')->firstOrFail();
        $this->assertSame($group->id, $category->group_id);

        $this->actingAs($admin)
            ->delete(route('master-data.product-category-groups.destroy', ['product_category_group' => $group->id]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($category->fresh()->group_id);
        $this->assertSoftDeleted('product_category_groups', ['id' => $group->id]);
    }

    public function test_category_requires_an_active_category_group(): void
    {
        $this->actingAs($this->admin())
            ->post(route('master-data.product-categories.store'), [
                'name' => 'QA Category Without Group',
            ])
            ->assertSessionHasErrors('group_id');
    }
}
