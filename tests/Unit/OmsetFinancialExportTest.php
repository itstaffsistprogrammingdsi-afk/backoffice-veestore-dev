<?php

namespace Tests\Unit;

use App\Exports\OmsetCabangExport;
use App\Exports\OmsetKotorSalesExport;
use PHPUnit\Framework\TestCase;

class OmsetFinancialExportTest extends TestCase
{
    public function test_omset_kotor_export_keeps_headings_and_columns_aligned(): void
    {
        $row = (object) [
            'trx_datetime' => '2026-11-01 09:00:00',
            'channel' => 'store',
            'transaction_number' => 'TRX-001',
            'customer_name' => 'Customer',
            'product_code' => 'SKU-1',
            'product_name' => 'Produk',
            'display_item_id' => 'ITEM-1',
            'rfid_value' => 'RFID-1',
            'qty' => 1,
            'selling_price' => 100000,
            'gross_sales' => 100000,
            'base_price_amount' => 70000,
            'base_price_total' => 70000,
            'discount_names' => 'Promo',
            'product_discount_amount' => 10000,
            'voucher_names' => 'Voucher',
            'allocated_order_discount' => 5000,
            'total_discount_amount' => 15000,
            'final_paid_amount' => 85000,
        ];

        $export = new OmsetKotorSalesExport(collect([$row]));
        $mapped = $export->map($row);

        $this->assertCount(count($export->headings()), $mapped);
        $this->assertSame(85000.0, $mapped[19]);
        $this->assertSame(15000.0, $mapped[20]);
        $this->assertSame(17.65, $mapped[21]);
    }

    public function test_omset_cabang_export_keeps_headings_and_columns_aligned(): void
    {
        $row = (object) [
            'store_name' => 'Store',
            'store_code' => 'STR',
            'total_order' => 2,
            'total_produk' => 3,
            'total_barang_diambil' => 4,
            'total_barang_keluar' => 4,
            'total_barang_diterima' => 4,
            'total_sisa_belum_keluar' => 0,
            'total_omset_kotor' => 200000,
            'total_diskon_produk' => 20000,
            'total_voucher_promo' => 10000,
            'total_omset_bersih' => 170000,
            'total_omset_kotor_store' => 170000,
            'total_omset_kotor_ecommerce' => 0,
            'total_harga_base' => 120000,
        ];

        $export = new OmsetCabangExport(collect([$row]));
        $mapped = $export->map($row);

        $this->assertCount(count($export->headings()), $mapped);
        $this->assertSame(200000.0, $mapped[8]);
        $this->assertSame(170000.0, $mapped[11]);
        $this->assertSame(50000.0, $mapped[15]);
        $this->assertSame(29.41, $mapped[16]);
    }
}
