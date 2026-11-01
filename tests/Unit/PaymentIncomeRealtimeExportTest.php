<?php

namespace Tests\Unit;

use App\Exports\PaymentIncomeRealtimeExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class PaymentIncomeRealtimeExportTest extends TestCase
{
    public function test_it_exports_filters_summary_methods_and_transaction_details(): void
    {
        $summary = (object) [
            'total_transactions' => 1,
            'total_stores' => 1,
            'total_customers' => 1,
            'total_amount' => 150000,
            'total_paid' => 160000,
            'total_change' => 10000,
        ];

        $methods = collect([
            (object) [
                'payment_method_name' => 'Tunai',
                'total_transactions' => 1,
                'total_amount' => 150000,
                'first_used_at' => '2026-10-27 09:00:00',
                'last_used_at' => '2026-10-27 09:00:00',
            ],
        ]);

        $rows = collect([
            (object) [
                'income_at' => '2026-10-27 09:00:00',
                'income_source_label' => 'Transaksi Store',
                'store_name' => 'Store Jakarta',
                'store_code' => 'JKT',
                'customer_name' => '=HYPERLINK("https://example.test")',
                'reference_code' => 'VISIT-001',
                'transaction_number' => 'TRX-001',
                'payment_method_descriptions' => 'Tunai',
                'payment_status' => 'paid',
                'total_amount' => 150000,
                'paid_amount' => 160000,
                'change_amount' => 10000,
                'payment_source' => 'cashier',
                'transaction_refs' => 'REF-001',
                'raw_payment_status' => 'completed',
            ],
        ]);

        $export = new PaymentIncomeRealtimeExport($rows, $summary, $methods, [
            'start_date' => '2026-10-27',
            'end_date' => '2026-10-27',
            'store_name' => 'Store Jakarta',
            'payment_method_name' => 'Tunai',
            'payment_status' => 'paid',
            'keyword' => 'TRX-001',
        ]);

        $data = $export->array();
        $detailHeaderIndex = null;
        foreach ($data as $index => $row) {
            if (($row[0] ?? null) === 'No' && ($row[15] ?? null) === 'Status Asli') {
                $detailHeaderIndex = $index;
                break;
            }
        }

        $this->assertSame('Pemasukan Realtime', $export->title());
        $this->assertSame(': 27-10-2026 s/d 27-10-2026', $data[3][1]);
        $this->assertSame(['Total Pemasukan', 150000.0], $data[14]);
        $this->assertSame('Tunai', $data[20][1]);
        $this->assertIsInt($detailHeaderIndex);
        $this->assertSame("'=HYPERLINK(\"https://example.test\")", $data[$detailHeaderIndex + 1][5]);
        $this->assertSame(150000.0, $data[$detailHeaderIndex + 1][10]);
        $this->assertSame('Paid', $data[$detailHeaderIndex + 1][9]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data);
        $export->styles($sheet);

        $this->assertSame('A23:P24', $sheet->getAutoFilter()->getRange());
        $this->assertSame('A24', $sheet->getFreezePane());
    }
}
