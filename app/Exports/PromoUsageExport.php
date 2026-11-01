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

class PromoUsageExport implements FromGenerator, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $promoId = $request->query('promo_id');
        $discountId = $request->query('discount_id');
        $storeId = $request->query('store_id');
        $orderStatus = $request->query('order_status');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $query = DB::table('ecommerce_order_details as od')
            ->join('ecommerce_orders as o', 'o.id', '=', 'od.ecommerce_order_id')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'od.product_id')
            ->leftJoin('ecommerce_order_detail_discounts as ds', 'ds.id', '=', 'od.discount_snapshot_id')
            ->leftJoin('discounts as d', 'd.id', '=', 'ds.master_id')
            ->leftJoin('promos as p', function ($join) {
                $join->whereRaw('p.id = COALESCE(d.promo_id, ds.promo_id)');
            })
            ->leftJoin('stores as s', function ($join) {
                $join->whereRaw('s.id = ANY(p.store_id)');
            })

            // Voucher checkout: ecommerce_orders -> ecommerce_order_vouchers -> vouchers
            ->leftJoin('ecommerce_order_vouchers as cov', 'cov.id', '=', 'o.checkout_voucher_snapshot_id')
            ->leftJoin('vouchers as cv', 'cv.id', '=', 'cov.master_id')

            // Voucher shipping: ecommerce_orders -> ecommerce_order_vouchers -> vouchers
            ->leftJoin('ecommerce_order_vouchers as sov', 'sov.id', '=', 'o.shipping_voucher_snapshot_id')
            ->leftJoin('vouchers as sv', 'sv.id', '=', 'sov.master_id')

            ->select([
                'od.id as order_detail_id',
                'od.ecommerce_order_id',
                'od.discount_snapshot_id',
                'od.quantity',
                'od.product_price',
                'od.product_discount',
                'od.total_discount',
                'od.total_price',

                'ds.id as detail_discount_snapshot_id',
                'ds.master_id as discount_master_id',
                'ds.promo_id as detail_promo_id',
                'ds.name as discount_name',
                'ds.description as discount_description',
                'ds.type as discount_type',
                'ds.value as discount_value',
                'ds.max_discount',
                'ds.min_purchase',
                'ds.priority',
                'ds.combinable',
                'ds.is_active',
                'ds.created_at as snapshot_created_at',

                'd.id as discount_id',
                DB::raw('COALESCE(d.promo_id, ds.promo_id) as promo_id'),

                'p.name as promo_name',
                'p.code as promo_code',

                'o.id as order_id',
                'o.order_number',
                'o.status as order_status',
                'o.created_at as order_created_at',
                'o.sub_total',
                'o.discount_total',
                'o.grand_total',
                'o.checkout_voucher_snapshot_id',
                'o.shipping_voucher_snapshot_id',

                'c.name as customer_name',
                'c.email',
                'c.wa_number',

                'pr.name as product_name',
                'pr.code as product_code',

                // Snapshot checkout voucher
                'cov.id as checkout_order_voucher_id',
                'cov.master_id as checkout_voucher_master_id',
                'cov.code as checkout_snapshot_voucher_code',
                'cov.name as checkout_snapshot_voucher_name',
                'cov.context as checkout_voucher_context',

                // Master checkout voucher
                'cv.id as checkout_voucher_id',
                'cv.code as checkout_voucher_code',
                'cv.name as checkout_voucher_name',

                // Snapshot shipping voucher
                'sov.id as shipping_order_voucher_id',
                'sov.master_id as shipping_voucher_master_id',
                'sov.code as shipping_snapshot_voucher_code',
                'sov.name as shipping_snapshot_voucher_name',
                'sov.context as shipping_voucher_context',

                // Master shipping voucher
                'sv.id as shipping_voucher_id',
                'sv.code as shipping_voucher_code',
                'sv.name as shipping_voucher_name',

                DB::raw("
                    COALESCE(
                        NULLIF(
                            TRIM(
                                COALESCE(cv.name, cov.name, '') ||
                                CASE
                                    WHEN COALESCE(cv.code, cov.code) IS NOT NULL
                                         AND COALESCE(cv.code, cov.code) <> ''
                                    THEN ' (' || COALESCE(cv.code, cov.code) || ')'
                                    ELSE ''
                                END
                            ),
                            ''
                        ),
                        o.checkout_voucher_snapshot_id::text,
                        '-'
                    ) as checkout_voucher_label
                "),
                DB::raw("
                    COALESCE(
                        NULLIF(
                            TRIM(
                                COALESCE(sv.name, sov.name, '') ||
                                CASE
                                    WHEN COALESCE(sv.code, sov.code) IS NOT NULL
                                         AND COALESCE(sv.code, sov.code) <> ''
                                    THEN ' (' || COALESCE(sv.code, sov.code) || ')'
                                    ELSE ''
                                END
                            ),
                            ''
                        ),
                        o.shipping_voucher_snapshot_id::text,
                        '-'
                    ) as shipping_voucher_label
                "),

                DB::raw("COALESCE(STRING_AGG(DISTINCT s.name, ', '), '-') as store_name"),
            ])
            ->where(function ($w) {
                $w->whereNotNull('ds.id')
                    ->orWhereNotNull('o.checkout_voucher_snapshot_id')
                    ->orWhereNotNull('o.shipping_voucher_snapshot_id');
            })
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('o.order_number', 'ILIKE', $like)
                        ->orWhere('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('pr.name', 'ILIKE', $like)
                        ->orWhere('ds.name', 'ILIKE', $like)
                        ->orWhere('p.name', 'ILIKE', $like)
                        ->orWhere('p.code', 'ILIKE', $like)
                        ->orWhere('o.status', 'ILIKE', $like)
                        ->orWhere('cov.name', 'ILIKE', $like)
                        ->orWhere('cov.code', 'ILIKE', $like)
                        ->orWhere('cv.name', 'ILIKE', $like)
                        ->orWhere('cv.code', 'ILIKE', $like)
                        ->orWhere('sov.name', 'ILIKE', $like)
                        ->orWhere('sov.code', 'ILIKE', $like)
                        ->orWhere('sv.name', 'ILIKE', $like)
                        ->orWhere('sv.code', 'ILIKE', $like);
                });
            })
            ->when($promoId, function ($qBuilder) use ($promoId) {
                $qBuilder->whereRaw('COALESCE(d.promo_id, ds.promo_id) = ?', [$promoId]);
            })
            ->when($discountId, function ($qBuilder) use ($discountId) {
                $qBuilder->where(function ($sub) use ($discountId) {
                    $sub->where('d.id', $discountId)
                        ->orWhere('ds.master_id', $discountId);
                });
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->whereRaw('?::uuid = ANY(p.store_id)', [$storeId]);
            })
            ->when($orderStatus, function ($qBuilder) use ($orderStatus) {
                $qBuilder->where('o.status', $orderStatus);
            })
            ->whereBetween(DB::raw('o.created_at::date'), [$from, $to])
            ->groupBy([
                'od.id',
                'od.ecommerce_order_id',
                'od.discount_snapshot_id',
                'od.quantity',
                'od.product_price',
                'od.product_discount',
                'od.total_discount',
                'od.total_price',

                'ds.id',
                'ds.master_id',
                'ds.promo_id',
                'ds.name',
                'ds.description',
                'ds.type',
                'ds.value',
                'ds.max_discount',
                'ds.min_purchase',
                'ds.priority',
                'ds.combinable',
                'ds.is_active',
                'ds.created_at',

                'd.id',
                'd.promo_id',

                'p.name',
                'p.code',

                'o.id',
                'o.order_number',
                'o.status',
                'o.created_at',
                'o.sub_total',
                'o.discount_total',
                'o.grand_total',
                'o.checkout_voucher_snapshot_id',
                'o.shipping_voucher_snapshot_id',

                'c.name',
                'c.email',
                'c.wa_number',

                'pr.name',
                'pr.code',

                'cov.id',
                'cov.master_id',
                'cov.name',
                'cov.code',
                'cov.context',

                'cv.id',
                'cv.name',
                'cv.code',

                'sov.id',
                'sov.master_id',
                'sov.name',
                'sov.code',
                'sov.context',

                'sv.id',
                'sv.name',
                'sv.code',
            ])
            ->orderByDesc('o.created_at')
            ->orderBy('o.order_number');

        $this->rows = $query->orderBy('od.id');

        $promoName = '-';
        if ($promoId) {
            $promoName = DB::table('promos')->where('id', $promoId)->value('name') ?? '-';
        }

        $discountName = '-';
        if ($discountId) {
            $discountName = DB::table('discounts')->where('id', $discountId)->value('name') ?? '-';
        }

        $storeName = '-';
        if ($storeId) {
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?? '-';
        }

        $this->filters = [
            'q' => $q,
            'promo_name' => $promoName,
            'discount_name' => $discountName,
            'store_name' => $storeName,
            'order_status' => $orderStatus ?: '-',
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Promo Usage';
    }

    public function generator(): \Generator
    {
        $data = [
            ['LAPORAN PENGGUNAAN PROMO & VOUCHER'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Store', ': ' . ($this->filters['store_name'] ?: '-')],
            ['Promo', ': ' . ($this->filters['promo_name'] ?: '-')],
            ['Diskon', ': ' . ($this->filters['discount_name'] ?: '-')],
            ['Status Order', ': ' . ($this->filters['order_status'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal Pakai',
                'Order Number',
                'Customer',
                'Email',
                'No. WA',
                'Store Promo',
                'Diskon',
                'Promo',
                'Kode Promo',
                'Voucher Checkout',
                'Context Checkout Voucher',
                'Voucher Shipping',
                'Context Shipping Voucher',
                'Produk',
                'Qty',
                'Harga Produk',
                'Diskon Produk',
                'Total Diskon',
                'Grand Total Order',
                'Status Order',
            ],
        ];

        yield from $data;
        $count = 0;
        foreach ($this->rows->lazy(config('report_exports.chunk_size', 500)) as $i => $row) {
            if (++$count > config('report_exports.max_rows', 20000)) {
                throw new \RuntimeException('Jumlah data melebihi batas export. Persempit filter.');
            }
            yield [
                $i + 1,
                $row->order_created_at ? \Carbon\Carbon::parse($row->order_created_at)->format('d-m-Y H:i') : '',
                $row->order_number ?? '',
                $row->customer_name ?? '',
                $row->email ?? '',
                $row->wa_number ?? '',
                $row->store_name ?? '',
                $row->discount_name ?? '',
                $row->promo_name ?? '',
                $row->promo_code ?? '',
                $row->checkout_voucher_label ?? '-',
                $row->checkout_voucher_context ?? '',
                $row->shipping_voucher_label ?? '-',
                $row->shipping_voucher_context ?? '',
                $row->product_name ?? '',
                $row->quantity ?? 0,
                $row->product_price ?? 0,
                $row->product_discount ?? 0,
                $row->total_discount ?? 0,
                $row->grand_total ?? 0,
                $row->order_status ?? '',
            ];
        }


    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 11;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:U1');

        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:U{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:U{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->freezePane('A12');

        return [];
    }
}
