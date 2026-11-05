<?php

namespace Tests\Unit;

use App\Exports\WarehouseOutboundExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class WarehouseOutboundExportTest extends TestCase
{
    public function test_it_exports_outbound_statuses_and_item_details(): void
    {
        $rows = collect([
            (object) [
                'id' => 'outbound-1',
                'event_date' => '2026-10-27 09:15:00+07',
                'warehouse_name' => 'Warehouse Jakarta',
                'warehouse_code' => 'WH-JKT',
                'outbound_type' => 'ecommerce',
                'invoice_number' => 'INV-001',
                'delivery_order_number' => 'DO-001',
                'order_number' => 'SO-001',
                'order_ref' => 'ORD-REF-001',
                'courier_name' => 'Kurir Cepat',
                'shipping_number' => 'RESI-001',
                'quantity_item' => 2,
                'total_sku' => 1,
                'total_items' => 2,
                'released_at' => '2026-10-27 09:30:00+07',
                'qc_passed_at' => '2026-10-27 09:45:00+07',
                'outbound_by_name' => 'Operator WMS',
                'qc_passed_by_name' => 'Petugas QC',
                'destination_store_id' => 'store-1',
                'destination_store_name' => 'Toko Jakarta',
                'destination_store_code' => 'ST-JKT',
                'store_received_qty' => 0,
                'store_variance_qty' => 2,
                'store_recording_status' => 'not_recorded',
                'store_recording_status_label' => 'Belum tercatat',
                'store_received_at' => null,
                'document_status' => 'complete',
                'document_status_label' => 'Resi tersedia',
            ],
        ]);

        $detailMap = collect([
            'outbound-1' => collect([
                (object) [
                    'product_code' => 'SKU-001',
                    'product_name' => '=HYPERLINK("https://example.test")',
                    'qty' => 2,
                    'qty_good' => 2,
                    'qty_bad' => 0,
                    'qty_other' => 0,
                    'condition_summary' => 'Good: 2',
                ],
            ]),
        ]);

        $export = new WarehouseOutboundExport($rows, $detailMap, [
            'warehouse_name' => 'Warehouse Jakarta',
            'courier_name' => 'Kurir Cepat',
            'outbound_type' => 'Ecommerce',
            'release_status' => 'Sudah release',
            'qc_status' => 'QC lolos',
            'date_from' => '2026-10-27',
            'date_to' => '2026-10-27',
            'q' => 'INV-001',
        ]);

        $data = $export->array();
        $detail = $data[19];

        $this->assertSame('Warehouse Outbound', $export->title());
        $this->assertSame(': 27-10-2026 s/d 27-10-2026', $data[3][1]);
        $this->assertSame([1, 1, 2, 1, 1, 0, 0, 1, 1], $data[16]);
        $this->assertSame('Ecommerce', $detail[4]);
        $this->assertSame('Sudah release', $detail[15]);
        $this->assertSame('QC lolos', $detail[17]);
        $this->assertSame("'=HYPERLINK(\"https://example.test\")", $detail[21]);
        $this->assertSame(2, $detail[23]);
        $this->assertSame('Toko Jakarta', $detail[27]);
        $this->assertSame('ST-JKT', $detail[28]);
        $this->assertSame(0, $detail[29]);
        $this->assertSame(2, $detail[30]);
        $this->assertSame('Belum tercatat', $detail[31]);
        $this->assertSame('Resi tersedia', $detail[33]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data);
        $export->styles($sheet);

        $this->assertSame('A19:AH20', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A20', $sheet->getFreezePane());
    }
}
