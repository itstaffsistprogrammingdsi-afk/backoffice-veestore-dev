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

class StoreReturnRequestExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    protected $rows;
    protected array $filters;

    public function __construct(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->has('status') ? (string) $request->query('status') : '';
        $storeId = $request->query('store_id');
        $from = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to   = $request->query('to') ?: now()->toDateString();

        $hasWarehouseId = Schema::hasColumn('store_returns', 'warehouse_id');

        $detailAgg = DB::table('store_return_details as d')
            ->join('items as i', 'i.id', '=', 'd.item_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->selectRaw("
                d.store_return_id,
                COUNT(*)::int as total_lines,
                COUNT(DISTINCT i.product_id)::int as total_products,
                STRING_AGG(DISTINCT COALESCE(p.name, '-'), CHR(10) ORDER BY COALESCE(p.name, '-')) as product_names,
                COALESCE(SUM(CASE WHEN d.status = 'approved' THEN 1 ELSE 0 END), 0)::int as qty_approved
            ")
            ->groupBy('d.store_return_id');

        $query = DB::table('store_returns as r')
            ->leftJoin('stores as s', 's.id', '=', 'r.store_id')
            ->leftJoin('users as ua', 'ua.id', '=', 'r.approved_by')
            ->when($hasWarehouseId, fn ($q2) => $q2->leftJoin('warehouses as w', 'w.id', '=', 'r.warehouse_id'))
            ->leftJoinSub($detailAgg, 'da', function ($join) {
                $join->on('da.store_return_id', '=', 'r.id');
            })
            ->select([
                'r.return_number',
                'r.status',
                'r.created_at',
                'r.approved_at',
                'r.rejected_at',
                's.name as store_name',
                'ua.name as approved_by_name',
                'r.note_store',
                'r.note_warehouse',
                DB::raw("COALESCE(da.total_lines, 0) as total_lines"),
                DB::raw("COALESCE(da.total_products, 0) as total_products"),
                DB::raw("COALESCE(da.product_names, '-') as product_names"),
                DB::raw("COALESCE(da.qty_approved, 0) as qty_approved"),
            ]);

        if ($hasWarehouseId) {
            $query->addSelect([
                'w.name as warehouse_name',
            ]);
        } else {
            $query->addSelect([
                DB::raw('NULL::text as warehouse_name'),
            ]);
        }

        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($wq) use ($like, $hasWarehouseId) {
                $wq->where('r.return_number', 'ILIKE', $like)
                    ->orWhere('s.name', 'ILIKE', $like)
                    ->orWhere('r.note_store', 'ILIKE', $like);

                if ($hasWarehouseId) {
                    $wq->orWhere('w.name', 'ILIKE', $like);
                }
            });
        }

        if ($status !== '' && strtolower($status) !== 'all') {
            $query->where('r.status', $status);
        }

        if (!empty($storeId)) {
            $query->where('r.store_id', $storeId);
        }

        $query->whereBetween(DB::raw('r.created_at::date'), [$from, $to])
            ->orderByDesc('r.created_at');

        $this->rows = $query->get();

        $storeName = '-';
        if (!empty($storeId)) {
            $storeName = DB::table('stores')->where('id', $storeId)->value('name') ?? '-';
        }

        $this->filters = [
            'q' => $q,
            'status' => $status,
            'store_id' => $storeId,
            'store_name' => $storeName,
            'from' => $from,
            'to' => $to,
        ];
    }

    public function title(): string
    {
        return 'Store Return Requests';
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN STORE RETURN REQUESTS'],
            [''],
            ['Tanggal Export', ': ' . now()->format('d-m-Y H:i:s')],
            ['Pencarian', ': ' . ($this->filters['q'] ?: '-')],
            ['Status', ': ' . ($this->filters['status'] ?: 'Semua')],
            ['Store', ': ' . ($this->filters['store_name'] ?: '-')],
            ['Tanggal', ': ' . ($this->filters['from'] ?: '-') . ' s/d ' . ($this->filters['to'] ?: '-')],
            [''],
            [
                'No',
                'Tanggal',
                'No. Retur',
                'Store',
                'Warehouse',
                'Status',
                'Nama Produk',
                'Total Produk',
                'Total Baris',
                'Qty Approved',
                'Catatan Store',
                'Catatan Warehouse',
                'Approved By',
                'Approved At',
            ],
        ];

        foreach ($this->rows->values() as $i => $row) {
            $data[] = [
                $i + 1,
                $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('d-m-Y H:i') : '',
                $row->return_number ?? '',
                $row->store_name ?? '',
                $row->warehouse_name ?? '',
                $row->status ?? '',
                $row->product_names ?? '-',
                $row->total_products ?? 0,
                $row->total_lines ?? 0,
                $row->qty_approved ?? 0,
                $row->note_store ?? '',
                $row->note_warehouse ?? '',
                $row->approved_by_name ?? '',
                $row->approved_at ? \Carbon\Carbon::parse($row->approved_at)->format('d-m-Y H:i') : '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 9;
        $lastRow = $sheet->getHighestRow();

        $sheet->mergeCells('A1:N1');

        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
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

        $sheet->getStyle("A{$headerRow}:N{$lastRow}")->applyFromArray([
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

        $sheet->getColumnDimension('G')->setWidth(40);
        $sheet->freezePane('A10');

        return [];
    }
}