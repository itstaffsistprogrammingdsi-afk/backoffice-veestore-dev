<?php

namespace Tests\Feature\MasterData;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class WarehouseLocationsTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_generator_creates_hierarchy_qr_and_deletes_subtree(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::query()->firstOrFail();
        $name = 'Test Lokasi '.Str::upper(Str::random(8));

        $response = $this->actingAs($admin)->post(route('master-data.locations-warehouse.generate'), [
            'name' => $name,
            'warehouse_id' => $warehouse->id,
            'rooms' => 1,
            'staging' => 1,
            'racks' => 1,
            'layers' => 1,
        ]);

        $response->assertRedirect(route('master-data.locations-warehouse.index'));

        $root = Location::query()
            ->where('type', 'warehouse')
            ->whereIn(
                'id',
                DB::table('warehouse_locations')
                    ->where('warehouse_id', $warehouse->id)
                    ->pluck('location_id')
            )
            ->firstOrFail();

        $room = Location::query()->where('name', $name)->where('type', 'room')->firstOrFail();
        $staging = Location::query()
            ->where('location_parent_id', $room->id)
            ->where('type', 'staging')
            ->firstOrFail();
        $rack = Location::query()
            ->where('location_parent_id', $room->id)
            ->where('type', 'rack')
            ->firstOrFail();
        $layers = Location::query()
            ->where('type', 'layer')
            ->whereIn('location_parent_id', [$staging->id, $rack->id])
            ->get();

        $this->assertStringStartsWith(rtrim($root->code, '/').'/R', $room->code);
        $this->assertStringStartsWith($room->code.'/STG', $staging->code);
        $this->assertStringStartsWith($room->code.'/RK', $rack->code);
        $this->assertCount(2, $layers);

        $this->assertDatabaseHas('warehouse_locations', [
            'location_id' => $room->id,
            'warehouse_id' => $warehouse->id,
        ]);

        $locationIds = [$room->id, $staging->id, $rack->id, ...$layers->pluck('id')->all()];

        $this->assertSame(
            count($locationIds),
            DB::table('location_rfid_tags')->whereIn('location_id', $locationIds)->count()
        );

        $this->actingAs($admin)
            ->get(route('master-data.locations-warehouse.qr', ['location' => $room->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml');

        $this->actingAs($admin)
            ->delete(route('master-data.locations-warehouse.destroy', ['location' => $room->id]))
            ->assertRedirect();

        foreach ($locationIds as $locationId) {
            $this->assertSoftDeleted('locations', ['id' => $locationId]);
        }
    }

    public function test_generator_creates_warehouse_root_when_missing(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::query()->create([
            'name' => 'Warehouse Test '.Str::upper(Str::random(6)),
            'code' => 'T.'.Str::upper(Str::random(6)),
            'contact_name' => 'Test',
            'contact_phone' => '0800000000',
        ]);
        $name = 'Root Test '.Str::upper(Str::random(6));

        $this->actingAs($admin)->post(route('master-data.locations-warehouse.generate'), [
            'name' => $name,
            'warehouse_id' => $warehouse->id,
            'rooms' => 1,
            'staging' => 0,
            'racks' => 0,
            'layers' => 0,
        ])->assertRedirect(route('master-data.locations-warehouse.index'));

        $root = Location::query()
            ->where('type', 'warehouse')
            ->whereIn(
                'id',
                DB::table('warehouse_locations')
                    ->where('warehouse_id', $warehouse->id)
                    ->pluck('location_id')
            )
            ->firstOrFail();

        $this->assertSame($warehouse->code, $root->code);
        $this->assertDatabaseHas('locations', [
            'name' => $name,
            'type' => 'room',
            'location_parent_id' => $root->id,
        ]);
    }

    public function test_generator_avoids_duplicate_room_names_and_codes(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::query()->firstOrFail();
        $name = 'Duplicate Test '.Str::upper(Str::random(6));

        foreach ([1, 2] as $attempt) {
            $this->actingAs($admin)->post(route('master-data.locations-warehouse.generate'), [
                'name' => $name,
                'warehouse_id' => $warehouse->id,
                'rooms' => 1,
                'staging' => 0,
                'racks' => 0,
                'layers' => 0,
            ])->assertRedirect(route('master-data.locations-warehouse.index'));
        }

        $rooms = Location::query()
            ->where('type', 'room')
            ->whereIn('name', [$name, $name.' 2'])
            ->orderBy('name')
            ->get();

        $this->assertCount(2, $rooms);
        $this->assertNotSame($rooms[0]->name, $rooms[1]->name);
        $this->assertNotSame($rooms[0]->code, $rooms[1]->code);
    }

    public function test_quick_add_creates_a_room_rack_and_layer(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::query()->firstOrFail();
        $root = Location::query()
            ->where('type', 'warehouse')
            ->whereIn(
                'id',
                DB::table('warehouse_locations')
                    ->where('warehouse_id', $warehouse->id)
                    ->pluck('location_id')
            )
            ->firstOrFail();

        $suffix = Str::upper(Str::random(6));
        $roomName = 'QA Room '.$suffix;
        $rackName = 'QA Rack '.$suffix;
        $layerName = 'QA Layer '.$suffix;

        $this->actingAs($admin)->post(route('master-data.locations-warehouse.quick-add'), [
            'warehouse_id' => $warehouse->id,
            'type' => 'room',
            'name' => $roomName,
        ])->assertRedirect(route('master-data.locations-warehouse.index'));

        $room = Location::query()->where('type', 'room')->where('name', $roomName)->firstOrFail();
        $this->assertStringStartsWith(rtrim($root->code, '/').'/R', $room->code);

        $this->actingAs($admin)->post(route('master-data.locations-warehouse.quick-add'), [
            'warehouse_id' => $warehouse->id,
            'type' => 'room',
            'name' => $roomName,
        ])->assertSessionHasErrors('name');

        $this->actingAs($admin)->post(route('master-data.locations-warehouse.quick-add'), [
            'warehouse_id' => $warehouse->id,
            'type' => 'rack',
            'parent_id' => $room->id,
            'name' => $rackName,
        ])->assertRedirect(route('master-data.locations-warehouse.index'));

        $rack = Location::query()->where('type', 'rack')->where('name', $rackName)->where('location_parent_id', $room->id)->firstOrFail();
        $this->assertStringStartsWith($room->code.'/RK', $rack->code);

        $this->actingAs($admin)->post(route('master-data.locations-warehouse.quick-add'), [
            'warehouse_id' => $warehouse->id,
            'type' => 'layer',
            'parent_id' => $rack->id,
            'name' => $layerName,
        ])->assertRedirect(route('master-data.locations-warehouse.index'));

        $layer = Location::query()->where('type', 'layer')->where('name', $layerName)->where('location_parent_id', $rack->id)->firstOrFail();
        $this->assertStringStartsWith($rack->code.'/L', $layer->code);
        $this->assertDatabaseHas('location_rfid_tags', ['location_id' => $layer->id]);
    }
}
