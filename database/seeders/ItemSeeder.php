<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('item')->delete();

        DB::table('item')->insert([
            ['item_id' => 1, 'item_code' => 'ITM-001', 'item_name' => 'Widget Alpha'],
            ['item_id' => 2, 'item_code' => 'ITM-002', 'item_name' => 'Widget Beta'],
            ['item_id' => 3, 'item_code' => 'ITM-003', 'item_name' => 'Widget Gamma'],
        ]);
    }
}