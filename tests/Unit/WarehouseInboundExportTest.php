<?php

namespace Tests\Unit;

use App\Exports\WarehouseInboundExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class WarehouseInboundExportTest extends TestCase
{
    public function test_it_exports_summary_origin_references_and_product_details(): void
    {
        $rows = collect([
            (object) [
                'id' => 'inbound-1',
                'event_date' => '2026-10-27 09:15:00+07',
                'warehouse_name' => 'Warehouse Tujuan',
                'warehouse_code' => 'WH-T',
                'inbound_type' => 'warehouse_transfer',
                'origin_name' => 'Warehouse Asal',
                'origin_reference' => 'OUT-001',
                'supplier_name' => '-',
                'invoice_number' => null,
                'delivery_order_number' => null,
                'batch_number' => 'BATCH-001',
                'outbound_reference_number' => 'OUT-001',
                'store_return_number' => '-',
                'received_by_name' => 'Penerima',
                'created_by_name' => 'Pembuat',
                'quantity_item' => 5,
                'total_sku' => 1,
                'total_qty' => 5,
                'grand_total' => 250000,
            ],
        ]);

        $detailMap = collect([
            'inbound-1' => collect([
                (object) [
                    'product_code' => 'SKU-001',
                    'product_name' => '=HYPERLINK("https://example.test")',
                    'quantity' => 5,
                    'expired_date' => '2027-10-27',
                    'price_idr' => 50000,
                    'exchange_currency' => 'IDR',
                    'exchange_rate' => 1,
                    'exchange_amount' => 250000,
                    'item_count' => 5,
                    'qty_good' => 4,
                    'qty_bad' => 1,
                    'qty_other' => 0,
                    'last_qc_at' => '2026-10-27 10:00:00+07',
                    'condition_summary' => 'Good: 4, Bad: 1',
                    'note' => 'Transfer antar warehouse',
                ],
            ]),
        ]);

        $export = new WarehouseInboundExport($rows, $detailMap, [
            'warehouse_name' => 'Warehouse Tujuan',
            'source_warehouse_name' => 'Warehouse Asal',
            'supplier_name' => 'Semua Supplier',
            'inbound_type' => 'Transfer Warehouse',
            'date_from' => '2026-10-27',
            'date_to' => '2026-10-27',
            'q' => 'OUT-001',
        ]);

        $data = $export->array();
        $detail = $data[15];

        $this->assertSame('Warehouse Inbound', $export->title());
        $this->assertSame(': 27-10-2026 s/d 27-10-2026', $data[3][1]);
        $this->assertSame([1, 1, 5, 250000.0], $data[12]);
        $this->assertSame('Transfer Warehouse', $detail[4]);
        $this->assertSame('Warehouse Asal', $detail[5]);
        $this->assertSame('OUT-001', $detail[6]);
        $this->assertSame("'=HYPERLINK(\"https://example.test\")", $detail[20]);
        $this->assertSame(4, $detail[28]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data);
        $export->styles($sheet);

        $this->assertSame('A15:AH16', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A16', $sheet->getFreezePane());
        $this->assertSame('#,##0', $sheet->getStyle('P16')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0', $sheet->getStyle('R16')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0', $sheet->getStyle('V16')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0', $sheet->getStyle('AB16')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0', $sheet->getStyle('AC16')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0.00', $sheet->getStyle('Z16')->getNumberFormat()->getFormatCode());
    }

    public function test_it_uses_detail_prices_for_the_value_summary(): void
    {
        $rows = collect([
            (object) [
                'id' => 'inbound-1',
                'quantity_item' => 2,
                'total_sku' => 1,
                'total_qty' => 2,
                'grand_total' => 999999,
            ],
        ]);

        $detailMap = collect([
            'inbound-1' => collect([
                (object) [
                    'quantity' => 2,
                    'price_idr' => 0,
                    'exchange_rate' => null,
                    'exchange_amount' => null,
                ],
            ]),
        ]);

        $export = new WarehouseInboundExport($rows, $detailMap);
        $data = $export->array();

        $this->assertSame([1, 1, 2, 0.0], $data[12]);
    }
}
