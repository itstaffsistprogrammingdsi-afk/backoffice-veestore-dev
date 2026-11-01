<?php

namespace Tests\Feature\MasterData;

use App\Models\Brand;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_brand_index_can_be_rendered(): void
    {
        $this->actingAs($this->admin())
            ->get(route('master-data.brands.index'))
            ->assertOk();
    }

    public function test_brand_can_be_created_updated_and_deleted(): void
    {
        $admin = $this->admin();
        $name = 'Test Brand '.Str::upper(Str::random(6));

        $this->actingAs($admin)
            ->post(route('master-data.brands.store'), ['name' => $name])
            ->assertRedirect(route('master-data.brands.index'));

        $brand = Brand::query()->where('name', $name)->firstOrFail();

        $this->assertDatabaseHas('brands', [
            'id' => $brand->getKey(),
            'name' => $name,
        ]);

        $updatedName = $name.' Updated';

        $this->actingAs($admin)
            ->put(route('master-data.brands.update', $brand), ['name' => $updatedName])
            ->assertRedirect(route('master-data.brands.index'));

        $this->assertSame($updatedName, $brand->fresh()->name);

        $this->actingAs($admin)
            ->delete(route('master-data.brands.destroy', $brand))
            ->assertRedirect();

        $this->assertSoftDeleted('brands', ['id' => $brand->getKey()]);
    }
}
