<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovementSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan dokumen & detail (aman untuk Postgres)
        foreach (
            [
                'receipt_detail',
                'receipt',
                'shipment_detail',
                'shipment',
                'adjustment_detail',
                'adjustment',
                'transfer_detail',
                'transfer'
            ] as $t
        ) {
            if (DB::getSchemaBuilder()->hasTable($t)) {
                DB::table($t)->delete();
            }
        }

        $t0 = Carbon::now()->subDays(5);

        /** ========== RECEIPT (INBOUND) ========== **/
        DB::table('receipt')->insert([
            ['id' => 1, 'receipt_no' => 'RCPT-0001', 'status' => 'POSTED', 'posted_at' => $t0->copy()->addHours(2)],
        ]);
        DB::table('receipt_detail')->insert([
            // Masuk ke WH-A / A-01
            ['id' => 1, 'receipt_id' => 1, 'warehouse_id' => 1, 'location_id' => 1, 'item_id' => 1, 'lot_no' => null,   'qty' => 100],
            ['id' => 2, 'receipt_id' => 1, 'warehouse_id' => 1, 'location_id' => 1, 'item_id' => 2, 'lot_no' => null,   'qty' =>  80],
            ['id' => 3, 'receipt_id' => 1, 'warehouse_id' => 1, 'location_id' => 2, 'item_id' => 3, 'lot_no' => 'LOT-A', 'qty' =>  50],
        ]);

        /** ========== TRANSFER (A-01 -> B-01) ========== **/
        DB::table('transfer')->insert([
            ['id' => 1, 'transfer_no' => 'TRF-0001', 'status' => 'POSTED', 'posted_at' => $t0->copy()->addHours(6)],
        ]);
        DB::table('transfer_detail')->insert([
            [
                'id' => 1,
                'transfer_id' => 1,
                'item_id' => 1,
                'lot_no' => null,
                'qty' => 30,
                'from_warehouse_id' => 1,
                'from_location_id' => 1,
                'to_warehouse_id'   => 2,
                'to_location_id'   => 3
            ],
        ]);

        /** ========== SHIPMENT (OUTBOUND) ========== **/
        DB::table('shipment')->insert([
            ['id' => 1, 'shipment_no' => 'SHP-0001', 'status' => 'POSTED', 'posted_at' => $t0->copy()->addHours(9)],
        ]);
        DB::table('shipment_detail')->insert([
            // Keluar dari WH-A / A-01 item-2 sebanyak 20
            ['id' => 1, 'shipment_id' => 1, 'warehouse_id' => 1, 'location_id' => 1, 'item_id' => 2, 'lot_no' => null, 'qty' => 20],
        ]);

        /** ========== ADJUSTMENT (+/-) ========== **/
        DB::table('adjustment')->insert([
            ['id' => 1, 'adjustment_no' => 'ADJ-0001', 'status' => 'POSTED', 'posted_at' => $t0->copy()->addHours(12)],
        ]);
        DB::table('adjustment_detail')->insert([
            // Koreksi +5 untuk ITM-003 LOT-A di A-02
            ['id' => 1, 'adjustment_id' => 1, 'warehouse_id' => 1, 'location_id' => 2, 'item_id' => 3, 'lot_no' => 'LOT-A', 'qty_adjust' =>  5],
            // Koreksi -2 untuk ITM-001 di B-01
            ['id' => 2, 'adjustment_id' => 1, 'warehouse_id' => 2, 'location_id' => 3, 'item_id' => 1, 'lot_no' => null,  'qty_adjust' => -2],
        ]);
    }
}