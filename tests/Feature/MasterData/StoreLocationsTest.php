<?php

namespace Tests\Feature\MasterData;

use App\Models\Location;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class StoreLocationsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_generator_creates_store_hierarchy_with_qr_codes(): void
    {
        $admin = $this->admin();
        $store = Store::query()->firstOrFail();
        $name = 'Test Toko '.Str::upper(Str::random(8));

        $this->actingAs($admin)->post(route('master-data.locations-store.generate'), [
            'name' => $name,
            'store_id' => $store->id,
            'rooms' => 1,
            'staging' => 1,
            'racks' => 1,
            'layers' => 1,
        ])->assertRedirect(route('master-data.locations-store.index'));

        $root = Location::query()
            ->where('type', 'store')
            ->whereIn(
                'id',
                DB::table('store_locations')
                    ->where('store_id', $store->id)
                    ->pluck('location_id')
            )
            ->firstOrFail();

        $room = Location::query()->where('name', $name)->where('type', 'room')->firstOrFail();
        $staging = Location::query()->where('location_parent_id', $room->id)->where('type', 'staging')->firstOrFail();
        $rack = Location::query()->where('location_parent_id', $room->id)->where('type', 'rack')->firstOrFail();
        $layers = Location::query()
            ->where('type', 'layer')
            ->whereIn('location_parent_id', [$staging->id, $rack->id])
            ->get();

        $this->assertStringStartsWith(rtrim($root->code, '/').'/R', $room->code);
        $this->assertStringStartsWith($room->code.'/STG', $staging->code);
        $this->assertStringStartsWith($room->code.'/RK', $rack->code);
        $this->assertCount(2, $layers);

        $this->assertDatabaseHas('store_locations', [
            'location_id' => $room->id,
            'store_id' => $store->id,
        ]);

        $locationIds = [$room->id, $staging->id, $rack->id, ...$layers->pluck('id')->all()];

        $this->assertSame(
            count($locationIds),
            DB::table('location_rfid_tags')->whereIn('location_id', $locationIds)->count()
        );

        $this->actingAs($admin)
            ->get(route('master-data.locations-store.qr', ['location' => $room->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_quick_add_creates_a_store_room(): void
    {
        $admin = $this->admin();
        $store = Store::query()->firstOrFail();
        $roomName = 'QA Store Room '.Str::upper(Str::random(6));

        $this->actingAs($admin)->post(route('master-data.locations-store.quick-add'), [
            'store_id' => $store->id,
            'type' => 'room',
            'name' => $roomName,
        ])->assertRedirect(route('master-data.locations-store.index'));

        $room = Location::query()->where('type', 'room')->where('name', $roomName)->firstOrFail();

        $this->assertDatabaseHas('store_locations', [
            'location_id' => $room->id,
            'store_id' => $store->id,
        ]);
        $this->assertDatabaseHas('location_rfid_tags', ['location_id' => $room->id]);
    }
}
