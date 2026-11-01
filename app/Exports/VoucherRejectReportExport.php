<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherRejectReportExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $rows;
    protected array $filters;
    protected array $mergeGroups = [];

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $storeId = $request->query('store_id');
        $status = $request->query('status');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $allowedStatuses = ['pending', 'approved', 'rejected'];
        if ($status && !in_array((string) $status, $allowedStatuses, true)) {
            $status = null;
        }

        $query = DB::table('store_voucher_requests as svr')
            ->leftJoin('stores as s', 's.id', '=', 'svr.store_id')
            ->leftJoin('store_voucher_request_details as svrd', 'svrd.store_voucher_request_id', '=', 'svr.id')
            ->leftJoin('products as p', 'p.id', '=', 'svrd.product_id')
            ->leftJoin('items as i', 'i.id', '=', 'svrd.item_id')
            ->leftJoin('vouchers as v', 'v.store_voucher_request_id', '=', 'svr.id')
            ->select([
                'svr.id as request_id',
                'svr.request_number',
                'svr.request_date',
                'svr.status',
                'svr.approved_at',
                'svr.notes',
                'svr.created_at as request_created_at',

                's.name as store_name',

                'svrd.item_id',
                'p.code as product_code',
                'p.name as product_name',
                'i.status as item_status',

                'v.code as voucher_code',
                'v.name as voucher_name',
                'v.valid_from',
                'v.valid_until',
                'v.usage_limit',
                'v.usage_count',
                'v.context',

                DB::raw("
                    CASE svr.status::text
                        WHEN 'pending' THEN 'Pending'
                        WHEN 'approved' THEN 'Approved'
                        WHEN 'rejected' THEN 'Rejected'
                        ELSE COALESCE(svr.status::text, '-')
                    END as status_label
                "),
            ])
            ->when($q !== '', function ($builder) use ($q) {
                $like = "%{$q}%";

                $builder->where(function ($sub) use ($like) {
                    $sub->where('svr.request_number', 'ILIKE', $like)
                        ->orWhere('s.name', 'ILIKE', $like)
                        ->orWhere('svr.notes', 'ILIKE', $like)
                        ->orWhere('p.code', 'ILIKE', $like)
                        ->orWhere('p.name', 'ILIKE', $like)
                        ->orWhere('v.code', 'ILIKE', $like)
                        ->orWhere('v.name', 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(svr.id AS TEXT)'), 'ILIKE', $like)
                        ->orWhere(DB::raw('CAST(svrd.item_id AS TEXT)'), 'ILIKE', $like);
                });
            })
            ->when($storeId, function ($builder) use ($storeId) {
                $builder->where('svr.store_id', $storeId);
            })
            ->when($status, function ($builder) use ($status) {
                $builder->where('svr.status', $status);
            })
            ->whereBetween(DB::raw('svr.request_date::date'), [$from, $to])
            ->orderByDesc('svr.request_date')
            ->orderByDesc('svr.created_at')
            ->orderBy('svr.request_number')
            ->orderBy('svrd.created_at');

        $dbRows = $query->get();

        $storeName = '-';
        if ($storeId) {
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?? '-';
        }

        $statusLabel = $status
            ? match ($status) {
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => '-',
            }
            : 'Semua Status';

        $this->filters = [
            'q' => $q,
            'store_name' => $storeName,
            'status' => $statusLabel,
            'from' => $from,
            'to' => $to,
        ];

        $rows = collect([
            collect(['LAPORAN VOUCHER REPORT']),
            collect(['']),
            collect(['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')]),
            collect(['Pencarian', ': ' . ($this->filters['q'] ?: '-')]),
            collect(['Store', ': ' . ($this->filters['store_name'] ?: '-')]),
            collect(['Status', ': ' . ($this->filters['status'] ?: '-')]),
            collect(['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')]),
            collect(['']),
            collect([
                'No',
                'Tanggal Request',
                'Request ID',
                'Request Number',
                'Store',
                'Status',
                'Notes',
                'Item ID',
                'Product Code',
                'Product Name',
                'Item Status',
                'Voucher Code',
                'Voucher Name',
                'Valid From',
                'Valid Until',
                'Usage Limit',
                'Usage Count',
                'Context',
            ]),
        ]);

        $excelRowStart = 10; // row data pertama setelah header

        $grouped = $dbRows->groupBy(fn ($row) => (string) $row->request_number . '|' . (string) $row->request_id);

        $no = 1;
        $currentExcelRow = $excelRowStart;

        foreach ($grouped as $group) {
            $group = $group->values();
            $first = $group->first();
            $startRow = $currentExcelRow;
            $endRow = $currentExcelRow + $group->count() - 1;

            foreach ($group as $detailRow) {
                $rows->push(collect([
                    $no,
                    $first->request_date ? \Carbon\Carbon::parse($first->request_date)->format('d-m-Y H:i') : '',
                    $first->request_id ?? '',
                    $first->request_number ?? '',
                    $first->store_name ?? '',
                    $first->status_label ?? '',
                    $first->notes ?? '',
                    $detailRow->item_id ?? '',
                    $detailRow->product_code ?? '',
                    $detailRow->product_name ?? '',
                    $detailRow->item_status ?? '',
                    $first->voucher_code ?? '',
                    $first->voucher_name ?? '',
                    $first->valid_from ? \Carbon\Carbon::parse($first->valid_from)->format('d-m-Y H:i') : '',
                    $first->valid_until ? \Carbon\Carbon::parse($first->valid_until)->format('d-m-Y H:i') : '',
                    $first->usage_limit ?? '',
                    $first->usage_count ?? '',
                    $first->context ?? '',
                ]));

                $currentExcelRow++;
            }

            if ($group->count() > 1) {
                $this->mergeGroups[] = [
                    'start' => $startRow,
                    'end' => $endRow,
                ];
            }

            $no++;
        }

        $this->rows = $rows;
    }

    public function title(): string
    {
        return 'Voucher Reject Report';
    }

    public function collection()
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 9;
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
                'startColor' => ['rgb' => 'DC3545'],
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // merge kolom request-level untuk request_number yang sama
                // kolom yang di-merge: A,B,C,D,E,F,G,L,M,N,O,P,Q,R
                $mergeColumns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'L', 'M', 'N', 'O', 'P', 'Q', 'R'];

                foreach ($this->mergeGroups as $group) {
                    foreach ($mergeColumns as $column) {
                        $sheet->mergeCells("{$column}{$group['start']}:{$column}{$group['end']}");
                    }

                    $sheet->getStyle("A{$group['start']}:R{$group['end']}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                }
            },
        ];
    }
}