<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarcodeRequestReportExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $storeId = $request->query('store_id');
        $requestedBy = $request->query('requested_by');
        $printedBy = $request->query('printed_by');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $printCounts = DB::table('store_label_print_histories')
            ->select('label_print_request_id', 'item_id')
            ->selectRaw('MAX(COALESCE(print_index, 0))::int as total_print_count')
            ->groupBy('label_print_request_id', 'item_id');

        $query = DB::table('store_label_print_requests as slpr')
            ->leftJoin('stores as s', 's.id', '=', 'slpr.store_id')
            ->leftJoin('users as req_user', 'req_user.id', '=', 'slpr.requested_by')
            ->leftJoin('users as app_user', 'app_user.id', '=', 'slpr.approved_by')
            ->leftJoin('store_label_print_request_items as slpri', 'slpri.label_print_request_id', '=', 'slpr.id')
            ->leftJoin('items as i', 'i.id', '=', 'slpri.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('store_label_print_histories as slph', function ($join) {
                $join->on('slph.label_print_request_id', '=', 'slpr.id')
                    ->on('slph.item_id', '=', 'slpri.item_id');
            })
            ->leftJoinSub($printCounts, 'print_counts', function ($join) {
                $join->on('print_counts.label_print_request_id', '=', 'slpr.id')
                    ->on('print_counts.item_id', '=', 'slpri.item_id');
            })
            ->leftJoin('users as print_user', 'print_user.id', '=', 'slph.printed_by')
            ->select([
                'slpr.id as request_id',
                'slpr.status',
                'slpr.reason',
                'slpr.approved_at',
                'slpr.created_at as request_created_at',

                's.name as store_name',
                'req_user.name as requested_by_name',
                'app_user.name as approved_by_name',
                'print_user.name as printed_by_name',

                'slpri.item_id',
                'i.product_id',
                'i.status as item_status',

                'p.name as product_name',
                'p.code as product_code',

                'slph.print_index',
                'slph.printed_at',
                DB::raw('COALESCE(print_counts.total_print_count, 0)::int as total_print_count'),

                DB::raw("
                    CASE slpr.status::text
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'rejected' THEN 'Rejected'
                        WHEN 'printed' THEN 'Printed'
                        ELSE COALESCE(slpr.status::text, '-')
                    END as status_label
                "),
            ])
            ->when($q !== '', function ($qBuilder) use ($q) {
                $like = "%{$q}%";
                $qBuilder->where(function ($sub) use ($like) {
                    $sub->where('s.name', 'ILIKE', $like)
                        ->orWhere('req_user.name', 'ILIKE', $like)
                        ->orWhere('app_user.name', 'ILIKE', $like)
                        ->orWhere('print_user.name', 'ILIKE', $like)
                        ->orWhere('p.name', 'ILIKE', $like)
                        ->orWhere('p.code', 'ILIKE', $like)
                        ->orWhere(DB::raw('slpr.status::text'), 'ILIKE', $like)
                        ->orWhere('slpr.reason', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(slpr.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(slpri.item_id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($status, function ($qBuilder) use ($status) {
                $qBuilder->where('slpr.status', $status);
            })
            ->when($storeId, function ($qBuilder) use ($storeId) {
                $qBuilder->where('slpr.store_id', $storeId);
            })
            ->when($requestedBy, function ($qBuilder) use ($requestedBy) {
                $qBuilder->where('slpr.requested_by', $requestedBy);
            })
            ->when($printedBy, function ($qBuilder) use ($printedBy) {
                $qBuilder->where('slph.printed_by', $printedBy);
            })
            ->whereBetween(DB::raw('slpr.created_at::date'), [$from, $to])
            ->orderByDesc('slpr.created_at')
            ->orderBy('slpr.id');

        $this->rows = $query->get();

        $storeName = '-';
        if ($storeId) {
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?? '-';
        }

        $requestedByName = '-';
        if ($requestedBy) {
            $requestedByName = DB::table('users')->where('id', $requestedBy)->value('name') ?? '-';
        }

        $printedByName = '-';
        if ($printedBy) {
            $printedByName = DB::table('users')->where('id', $printedBy)->value('name') ?? '-';
        }

        $statusLabel = match ($status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'printed' => 'Printed',
            'rejected' => 'Rejected',
            default => '-',
        };

        $this->filters = [
            'q' => $q,
            'status' => $statusLabel,
            'store_name' => $storeName,
            'requested_by_name' => $requestedByName,
            'printed_by_name' => $printedByName,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Request & Cetak Label';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN REQUEST & CETAK LABEL'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Store', ': ' . ($this->filters['store_name'] ?: '-')],
            ['Requested By', ': ' . ($this->filters['requested_by_name'] ?: '-')],
            ['Printed By', ': ' . ($this->filters['printed_by_name'] ?: '-')],
            ['Status', ': ' . ($this->filters['status'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal Request',
                'Request ID',
                'Store',
                'Requested By',
                'Status',
                'Reason',
                'Approved By',
                'Approved At',
                'Product Code',
                'Product Name',
                'Item ID',
                'Item Status',
                'Printed By',
                'Print Index Riwayat',
                'Jumlah Cetak Label',
                'Jumlah Cetak Ulang',
                'Printed At',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->request_created_at ? \Carbon\Carbon::parse($row->request_created_at)->format('d-m-Y H:i') : '',
                $row->request_id ?? '',
                $row->store_name ?? '',
                $row->requested_by_name ?? '',
                $row->status_label ?? '',
                $row->reason ?? '',
                $row->approved_by_name ?? '',
                $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d-m-Y H:i') : '',
                $row->product_code ?? '',
                $row->product_name ?? '',
                $row->item_id ?? '',
                $row->item_status ?? '',
                $row->printed_by_name ?? '',
                $row->print_index ?? '',
                (int) ($row->total_print_count ?? 0),
                max((int) ($row->total_print_count ?? 0) - 1, 0),
                $row->printed_at ? \Carbon\Carbon::parse($row->printed_at)->format('d-m-Y H:i') : '',
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
