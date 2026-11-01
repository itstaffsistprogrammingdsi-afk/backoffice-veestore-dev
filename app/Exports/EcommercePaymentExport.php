<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EcommercePaymentExport implements FromGenerator, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $customerName   = trim((string) $request->get('customer_name'));
        $email          = trim((string) $request->get('email'));
        $waNumber       = trim((string) $request->get('wa_number'));
        $orderNumber    = trim((string) $request->get('order_number'));
        $orderStatus    = $request->get('order_status');
        $paymentStatus  = $request->get('payment_status');
        $paymentMethod  = $request->get('payment_method_id');
        $startDate      = $request->get('start_date');
        $endDate        = $request->get('end_date');

        /*
         |--------------------------------------------------------------------------
         | Payment Detail Aggregation
         |--------------------------------------------------------------------------
         | Tambahan:
         | - Ambil payment_type dari payment_details.detail
         | - Buat keterangan payment gateway Midtrans
         */
        $paymentDetailAgg = DB::table('payment_details as pd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->selectRaw("
                pd.payment_id,

                COALESCE(SUM(pd.amount), 0) as total_payment_detail_amount,

                STRING_AGG(
                    DISTINCT COALESCE(pm.name, '-'),
                    ', '
                ) as payment_methods,

                STRING_AGG(
                    DISTINCT COALESCE(pd.transaction_ref, '-'),
                    ', '
                ) as transaction_refs,

                STRING_AGG(
                    DISTINCT COALESCE(pd.status::text, '-'),
                    ', '
                ) as payment_detail_statuses,

                STRING_AGG(
                    DISTINCT COALESCE(
                        NULLIF((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], ''),
                        '-'
                    ),
                    ', '
                ) as midtrans_payment_types,

                STRING_AGG(
                    DISTINCT
                    CASE
                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'qris'
                            THEN 'Payment Gateway Midtrans - QRIS'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bank_transfer'
                            THEN 'Payment Gateway Midtrans - Bank Transfer / Virtual Account'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'echannel'
                            THEN 'Payment Gateway Midtrans - Mandiri Bill Payment / E-Channel'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'gopay'
                            THEN 'Payment Gateway Midtrans - GoPay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'shopeepay'
                            THEN 'Payment Gateway Midtrans - ShopeePay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'cstore'
                            THEN 'Payment Gateway Midtrans - Convenience Store'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'credit_card'
                            THEN 'Payment Gateway Midtrans - Credit Card'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'akulaku'
                            THEN 'Payment Gateway Midtrans - Akulaku'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'kredivo'
                            THEN 'Payment Gateway Midtrans - Kredivo'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bca_klikpay'
                            THEN 'Payment Gateway Midtrans - BCA KlikPay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'bri_epay'
                            THEN 'Payment Gateway Midtrans - BRI ePay'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] = 'danamon_online'
                            THEN 'Payment Gateway Midtrans - Danamon Online Banking'

                        WHEN (regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1] IS NOT NULL
                            THEN CONCAT(
                                'Payment Gateway Midtrans - ',
                                UPPER(REPLACE((regexp_match(pd.detail, '\"payment_type\"\\s*:\\s*\"([^\"]+)\"'))[1], '_', ' '))
                            )

                        WHEN LOWER(COALESCE(pm.name, '')) LIKE '%midtrans%'
                            THEN 'Payment Gateway Midtrans - Method tidak tersedia di detail'

                        ELSE COALESCE(pm.name, '-')
                    END,
                    ', '
                ) as payment_method_descriptions
            ")
            ->groupBy('pd.payment_id');

        $itemAgg = DB::table('ecommerce_order_details as eod')
            ->leftJoin('products as pr', 'pr.id', '=', 'eod.product_id')
            ->selectRaw("
                eod.ecommerce_order_id,
                COALESCE(SUM(eod.quantity), 0) as total_qty,
                COALESCE(SUM(eod.total_price), 0) as total_item_price,
                COALESCE(SUM(eod.total_discount), 0) as total_item_discount,
                STRING_AGG(DISTINCT COALESCE(pr.name, '-'), CHR(10) ORDER BY COALESCE(pr.name, '-')) as product_names
            ")
            ->groupBy('eod.ecommerce_order_id');

        $query = DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoinSub($paymentDetailAgg, 'pda', function ($join) {
                $join->on('pda.payment_id', '=', 'p.id');
            })
            ->leftJoinSub($itemAgg, 'ia', function ($join) {
                $join->on('ia.ecommerce_order_id', '=', 'eo.id');
            })
            ->leftJoin('ecommerce_order_shipping_addresses as sa', 'sa.id', '=', 'eo.shipping_address_snapshot_id')
            ->leftJoin('provinces as prov', 'prov.code', '=', 'sa.province_code')
            ->leftJoin('regencies as reg', 'reg.code', '=', 'sa.regency_code')
            ->leftJoin('districts as dist', 'dist.code', '=', 'sa.district_code')
            ->leftJoin('villages as vill', 'vill.code', '=', 'sa.village_code')
            ->select([
                'eo.order_number',
                'eo.status as order_status',
                'eo.created_at as order_created_at',
                'eo.sub_total',
                'eo.discount_total',
                'eo.grand_total',

                'c.name as customer_name',
                'c.email',
                'c.wa_number',

                'p.status as payment_status',
                'p.transaction_number',
                'p.total_amount',
                'p.paid_amount',
                'p.change_amount',
                'p.tax_amount',
                'p.completed_at as payment_completed_at',

                DB::raw("COALESCE(pda.payment_methods, '-') as payment_methods"),
                DB::raw("COALESCE(pda.payment_method_descriptions, COALESCE(pda.payment_methods, '-')) as payment_method_descriptions"),
                DB::raw("COALESCE(pda.midtrans_payment_types, '-') as midtrans_payment_types"),
                DB::raw("COALESCE(pda.transaction_refs, '-') as transaction_refs"),
                DB::raw("COALESCE(pda.payment_detail_statuses, '-') as payment_detail_statuses"),

                DB::raw('COALESCE(ia.total_qty, 0) as total_qty'),
                DB::raw("COALESCE(ia.product_names, '-') as product_names"),

                DB::raw("COALESCE(prov.name, '-') as province_name"),
                DB::raw("COALESCE(reg.name, '-') as regency_name"),
                DB::raw("COALESCE(dist.name, '-') as district_name"),
                DB::raw("COALESCE(vill.name, '-') as village_name"),
                'sa.detail as address_detail',
            ])
            ->when($customerName !== '', function ($q) use ($customerName) {
                $q->where('c.name', 'ilike', "%{$customerName}%");
            })
            ->when($email !== '', function ($q) use ($email) {
                $q->where('c.email', 'ilike', "%{$email}%");
            })
            ->when($waNumber !== '', function ($q) use ($waNumber) {
                $q->where('c.wa_number', 'ilike', "%{$waNumber}%");
            })
            ->when($orderNumber !== '', function ($q) use ($orderNumber) {
                $q->where('eo.order_number', 'ilike', "%{$orderNumber}%");
            })
            ->when($orderStatus, function ($q) use ($orderStatus) {
                $q->where('eo.status', $orderStatus);
            })
            ->when($paymentStatus, function ($q) use ($paymentStatus) {
                $q->where('p.status', $paymentStatus);
            })
            ->when($paymentMethod, function ($q) use ($paymentMethod) {
                $q->whereExists(function ($sub) use ($paymentMethod) {
                    $sub->select(DB::raw(1))
                        ->from('payment_details as pdx')
                        ->whereColumn('pdx.payment_id', 'eo.payment_id')
                        ->where('pdx.payment_method_id', $paymentMethod);
                });
            })
            ->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('eo.created_at', '>=', $startDate);
            })
            ->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('eo.created_at', '<=', $endDate);
            })
            ->orderByDesc('eo.created_at');

        $this->rows = $query->orderBy('eo.id');

        $this->filters = [
            'customer_name'   => $customerName,
            'email'           => $email,
            'wa_number'       => $waNumber,
            'order_number'    => $orderNumber,
            'order_status'    => $orderStatus,
            'payment_status'  => $paymentStatus,
            'payment_method'  => $paymentMethod,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
        ];
    }

    public function title(): string
    {
        return 'Ecommerce Payment';
    }

    public function generator(): \Generator
    {
        $data = [
            ['LAPORAN ECOMMERCE PAYMENT'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Customer', ': ' . ($this->filters['customer_name'] ?: '-')],
            ['Email', ': ' . ($this->filters['email'] ?: '-')],
            ['No. WA', ': ' . ($this->filters['wa_number'] ?: '-')],
            ['Order Number', ': ' . ($this->filters['order_number'] ?: '-')],
            ['Order Status', ': ' . ($this->filters['order_status'] ?: '-')],
            ['Payment Status', ': ' . ($this->filters['payment_status'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['start_date'] ?: '-') . ' s/d ' . ($this->filters['end_date'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal Order',
                'Order Number',
                'Customer',
                'Email',
                'No. WA',
                'Order Status',
                'Payment Status',
                'Payment Method',
                'Midtrans Payment Type',
                'Transaction Ref',
                'Nama Produk Item',
                'Total Qty',
                'Sub Total',
                'Diskon',
                'Grand Total',
                'Paid Amount',
                'Alamat',
            ],
        ];

        yield from $data;
        $count = 0;
        foreach ($this->rows->lazy(config('report_exports.chunk_size', 500)) as $i => $row) {
            if (++$count > config('report_exports.max_rows', 20000)) {
                throw new \RuntimeException('Jumlah data melebihi batas export. Persempit filter.');
            }
            $alamat = trim(
                ($row->address_detail ?? '-') . ', ' .
                ($row->village_name ?? '-') . ', ' .
                ($row->district_name ?? '-') . ', ' .
                ($row->regency_name ?? '-') . ', ' .
                ($row->province_name ?? '-')
            );

            yield [
                $i + 1,
                $row->order_created_at ? \Carbon\Carbon::parse($row->order_created_at)->format('d-m-Y H:i') : '',
                $row->order_number ?? '',
                $row->customer_name ?? '',
                $row->email ?? '',
                $row->wa_number ?? '',
                $row->order_status ?? '',
                $row->payment_status ?? '',
                $row->payment_method_descriptions ?? $row->payment_methods ?? '',
                $row->midtrans_payment_types ?? '-',
                $row->transaction_refs ?? '',
                $row->product_names ?? '-',
                $row->total_qty ?? 0,
                $row->sub_total ?? 0,
                $row->discount_total ?? 0,
                $row->grand_total ?? 0,
                $row->paid_amount ?? 0,
                $alamat,
            ];
        }


    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 12;
        $lastRow = $sheet->getHighestRow();

        /*
         |--------------------------------------------------------------------------
         | Karena kolom bertambah dari Q menjadi R
         |--------------------------------------------------------------------------
         | Sebelumnya:
         | A:Q = 17 kolom
         |
         | Sekarang:
         | A:R = 18 kolom
         | Tambahan kolom:
         | J = Midtrans Payment Type
         */
        $sheet->mergeCells('A1:R1');

        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:R{$headerRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:R{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BFBFBF'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->freezePane('A13');

        return [];
    }
}
