<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoidCancelCustomerReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $requestedBy = $request->query('requested_by');
        $authorizedBy = $request->query('authorized_by');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $query = DB::table('void_transactions as vt')
            ->leftJoin('stores as s', 's.id', '=', 'vt.store_id')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'vt.cashier_user_id')
            ->leftJoin('users as authorizer', 'authorizer.id', '=', 'vt.authorized_by')
            ->leftJoin('payments as p', 'p.id', '=', 'vt.payment_id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'vt.visit_id')
            ->select([
                'vt.id',
                'vt.reason',
                'vt.status',
                'vt.voided_amount',
                'vt.items_restored_count',
                'vt.requested_at',
                'vt.authorized_at',
                'vt.rejected_at',
                'vt.rejection_reason',

                's.name as store_name',
                'cashier.name as cashier_name',
                'authorizer.name as authorized_by_name',

                'p.transaction_number',
                'p.status as payment_status',

                'scv.visit_code',
                'scv.visit_type',

                DB::raw("
                    CASE
                        WHEN vt.status::text = 'approved' THEN 'Void Approved'
                        WHEN vt.status::text = 'authorized' THEN 'Void Approved'
                        WHEN vt.status::text = 'rejected' THEN 'Void Rejected'
                        WHEN vt.status::text = 'requested' THEN 'Void Requested'
                        WHEN vt.status::text = 'cancelled' THEN 'Cancel'
                        ELSE COALESCE(vt.status::text, '-')
                    END as status_label
                "),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";

                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('cashier.name', 'ILIKE', $like)
                        ->orWhere('authorizer.name', 'ILIKE', $like)
                        ->orWhere('vt.reason', 'ILIKE', $like)
                        ->orWhere('vt.rejection_reason', 'ILIKE', $like)
                        ->orWhere('p.transaction_number', 'ILIKE', $like)
                        ->orWhere('scv.visit_code', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.payment_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.visit_id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.voided_amount AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(vt.items_restored_count AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('vt.status::text'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where(DB::raw('vt.status::text'), $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('vt.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('vt.cashier_user_id', $requestedBy);
            })
            ->when($authorizedBy, function ($qBuilder) use ($authorizedBy) {
                $qBuilder->where('vt.authorized_by', $authorizedBy);
            })
            ->whereNotNull('vt.requested_at')
            ->whereBetween(DB::raw('vt.requested_at::date'), [$from, $to])
            ->orderByDesc('vt.requested_at')
            ->orderByDesc('vt.id');

        $rows = $query->get();

        $voidIds = $rows->pluck('id')->filter()->values();

        $itemNamesMap = collect();

        if ($voidIds->isNotEmpty() && Schema::hasTable('void_transaction_items')) {
            if (Schema::hasColumn('void_transaction_items', 'item_id')) {
                $itemNamesMap = DB::table('void_transaction_items as vti')
                    ->leftJoin('items as i', 'i.id', '=', 'vti.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        DB::raw("STRING_AGG(DISTINCT COALESCE(pr.name, '-'), ', ' ORDER BY COALESCE(pr.name, '-')) as item_names"),
                    ])
                    ->groupBy('vti.void_transaction_id')
                    ->pluck('item_names', 'void_transaction_id');
            } elseif (Schema::hasColumn('void_transaction_items', 'cart_detail_id')) {
                $itemNamesMap = DB::table('void_transaction_items as vti')
                    ->leftJoin('store_cart_details as scd', 'scd.id', '=', 'vti.cart_detail_id')
                    ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        DB::raw("STRING_AGG(DISTINCT COALESCE(pr.name, '-'), ', ' ORDER BY COALESCE(pr.name, '-')) as item_names"),
                    ])
                    ->groupBy('vti.void_transaction_id')
                    ->pluck('item_names', 'void_transaction_id');
            } elseif (Schema::hasColumn('void_transaction_items', 'store_cart_detail_id')) {
                $itemNamesMap = DB::table('void_transaction_items as vti')
                    ->leftJoin('store_cart_details as scd', 'scd.id', '=', 'vti.store_cart_detail_id')
                    ->leftJoin('items as i', 'i.id', '=', 'scd.item_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'i.product_id')
                    ->whereIn('vti.void_transaction_id', $voidIds)
                    ->select([
                        'vti.void_transaction_id',
                        DB::raw("STRING_AGG(DISTINCT COALESCE(pr.name, '-'), ', ' ORDER BY COALESCE(pr.name, '-')) as item_names"),
                    ])
                    ->groupBy('vti.void_transaction_id')
                    ->pluck('item_names', 'void_transaction_id');
            }
        }

        $this->rows = $rows->map(function ($row) use ($itemNamesMap) {
            $row->item_names = $itemNamesMap[$row->id] ?? '-';
            return $row;
        });

        $storeName = '-';
        if ($storeId) {
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?? '-';
        }

        $requestedByName = '-';
        if ($requestedBy) {
            $requestedByName = DB::table('users')->where('id', $requestedBy)->value('name') ?? '-';
        }

        $authorizedByName = '-';
        if ($authorizedBy) {
            $authorizedByName = DB::table('users')->where('id', $authorizedBy)->value('name') ?? '-';
        }

        $this->filters = [
            'q' => $q,
            'status' => $status ?: '-',
            'store_name' => $storeName,
            'requested_by_name' => $requestedByName,
            'authorized_by_name' => $authorizedByName,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Void Cancel Customer';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN VOID & CANCEL CUSTOMER'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Store', ': ' . ($this->filters['store_name'] ?: '-')],
            ['Kasir Peminta', ': ' . ($this->filters['requested_by_name'] ?: '-')],
            ['Otorisasi Oleh', ': ' . ($this->filters['authorized_by_name'] ?: '-')],
            ['Status', ': ' . ($this->filters['status'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal Request',
                'Jam Request',
                'Waktu Approval',
                'Transaction Number',
                'Visit Code',
                'Store',
                'Status',
                'Qty Item',
                'Nama Item',
                'Nominal Void',
                'Kasir Peminta',
                'Otorisasi Oleh',
                'Reason',
                'Payment Status',
                'Visit Type',
                'Rejected At',
                'Alasan Reject',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $statusText = strtolower((string) ($row->status ?? ''));
            $isApproved = in_array($statusText, ['approved', 'authorized'], true);

            $data[] = [
                $i + 1,
                $row->requested_at ? \Carbon\Carbon::parse($row->requested_at)->format('d-m-Y') : '',
                $row->requested_at ? \Carbon\Carbon::parse($row->requested_at)->format('H:i') : '',
                $isApproved && $row->authorized_at ? \Carbon\Carbon::parse($row->authorized_at)->format('d-m-Y H:i') : '',
                $row->transaction_number ?? '',
                $row->visit_code ?? '',
                $row->store_name ?? '',
                $row->status_label ?? '',
                $row->items_restored_count ?? 0,
                $row->item_names ?? '-',
                $row->voided_amount ?? 0,
                $row->cashier_name ?? '',
                $row->authorized_by_name ?? '',
                $row->reason ?? '',
                $row->payment_status ?? '',
                $row->visit_type ?? '',
                $row->rejected_at ? \Carbon\Carbon::parse($row->rejected_at)->format('d-m-Y H:i') : '',
                $row->rejection_reason ?? '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 11;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:R1');

        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:R{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '198754'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:R{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
            ],
        ]);

        return [];
    }
}