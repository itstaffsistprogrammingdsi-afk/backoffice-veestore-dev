<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('warehouse')->delete();

        DB::table('warehouse')->insert([
            ['warehouse_id' => 1, 'warehouse_code' => 'WH-A', 'warehouse_name' => 'Warehouse A'],
            ['warehouse_id' => 2, 'warehouse_code' => 'WH-B', 'warehouse_name' => 'Warehouse B'],
        ]);
    }
}