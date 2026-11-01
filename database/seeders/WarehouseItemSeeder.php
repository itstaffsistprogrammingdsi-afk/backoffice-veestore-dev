<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('warehouse_item')->delete();

        DB::table('warehouse_item')->insert([
            // WH-A
            ['warehouse_item_id' => 1, 'warehouse_id' => 1, 'location_id' => 1, 'item_id' => 1, 'lot_no' => null],
            ['warehouse_item_id' => 2, 'warehouse_id' => 1, 'location_id' => 1, 'item_id' => 2, 'lot_no' => null],
            ['warehouse_item_id' => 3, 'warehouse_id' => 1, 'location_id' => 2, 'item_id' => 3, 'lot_no' => 'LOT-A'],
            // WH-B
            ['warehouse_item_id' => 4, 'warehouse_id' => 2, 'location_id' => 3, 'item_id' => 1, 'lot_no' => null],
        ]);
    }
}