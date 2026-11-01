<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('uom')->truncate();
        DB::table('uom')->insert([
            ['uom_id' => 1, 'uom_code' => 'PCS', 'uom_name' => 'Pieces'],
        ]);
    }
}