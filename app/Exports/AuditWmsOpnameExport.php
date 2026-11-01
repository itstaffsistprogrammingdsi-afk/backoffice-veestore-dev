<?php

namespace App\Exports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditWmsOpnameExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $warehouseId = $this->request->query('warehouse_id');
        $q           = trim((string) $this->request->query('q', ''));
        $from        = $this->request->query('from');
        $to          = $this->request->query('to');

        $conditionExpr = $this->resolveConditionExpression();

        $query = DB::table('warehouse_stock_opnames as o')
            ->join('warehouse_stock_opname_details as d', 'd.warehouse_stock_opname_id', '=', 'o.id')
            ->leftJoin('rfid_tags as rt', 'rt.id', '=', 'd.rfid_tag_id')
            ->leftJoin('items as it', 'it.rfid_tag_id', '=', 'rt.id')
            ->leftJoin('products as p', 'p.id', '=', 'it.product_id')
            ->leftJoin('warehouses as w', 'w.id', '=', 'o.warehouse_id')
            ->when($warehouseId, fn ($qq) => $qq->where('o.warehouse_id', $warehouseId))
            ->when($from, fn ($qq) => $qq->whereDate('o.created_at', '>=', $from))
            ->when($to, fn ($qq) => $qq->whereDate('o.created_at', '<=', $to));

        $hasApprovedBy = Schema::hasTable('users')
            && Schema::hasColumn('warehouse_stock_opnames', 'approved_by');

        if ($hasApprovedBy) {
            $query->leftJoin('users as approved_user', 'approved_user.id', '=', 'o.approved_by');
        }

        if ($q !== '') {
            $like = '%' . strtolower($q) . '%';

            $query->where(function ($sub) use ($like, $conditionExpr) {
                $sub->whereRaw("LOWER(COALESCE(o.code, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(w.name, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.code, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.name, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(rt.value, '')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE((o.status)::text, '')) LIKE ?", [$like]);

                if ($hasApprovedBy) {
                    $sub->orWhereRaw("LOWER(COALESCE(approved_user.name, '')) LIKE ?", [$like]);
                }

                if ($conditionExpr !== 'NULL') {
                    $sub->orWhereRaw("LOWER(COALESCE(($conditionExpr)::text, '')) LIKE ?", [$like]);
                }
            });
        }

        $query->groupBy([
            'o.id',
            'o.code',
            'o.status',
            'o.created_at',
            'o.completed_at',
            'o.warehouse_id',
            'w.name',
            'p.id',
            'p.code',
            'p.name',
        ]);

        if ($hasApprovedBy) {
            $query->groupBy('approved_user.name');
        }

        $query->selectRaw("
            o.id AS opname_id,
            o.code AS kode_opname,
            (o.status)::text AS status_opname,
            o.created_at AS tanggal_dibuat,
            o.completed_at AS tanggal_selesai,
            COALESCE(MAX(d.scanned_at), o.completed_at, o.created_at) AS waktu_scan_terakhir,

            o.warehouse_id,
            w.name AS warehouse_name,

            " . ($hasApprovedBy ? "COALESCE(approved_user.name, '-')" : "'-'") . " AS approved_by_name,

            p.id AS product_id,
            p.code AS product_code,
            p.name AS product_name,

            SUM(CASE WHEN (d.status)::text = 'pending' THEN 1 ELSE 0 END) AS qty_pending,
            SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END) AS qty_system,
            SUM(CASE WHEN (d.status)::text IN ('match', 'extra') THEN 1 ELSE 0 END) AS qty_scan,
            SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END) AS qty_match,
            SUM(CASE WHEN (d.status)::text = 'missing' THEN 1 ELSE 0 END) AS qty_missing,
            SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END) AS qty_extra,

            (
                SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
            ) AS selisih,

            CASE
                WHEN (
                    SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                    - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
                ) = 0 THEN 'Balanced'
                WHEN (
                    SUM(CASE WHEN (d.status)::text = 'match' THEN 1 ELSE 0 END)
                    + SUM(CASE WHEN (d.status)::text = 'extra' THEN 1 ELSE 0 END)
                    - SUM(CASE WHEN (d.status)::text IN ('match', 'missing') THEN 1 ELSE 0 END)
                ) > 0 THEN 'Over'
                ELSE 'Shortage'
            END AS status_summary
        ");

        if ($conditionExpr !== 'NULL') {
            $query->selectRaw("
                COALESCE(
                    STRING_AGG(DISTINCT COALESCE(($conditionExpr)::text, '-'), ', '),
                    '-'
                ) AS kondisi_barang
            ");
        } else {
            $query->selectRaw("'-' AS kondisi_barang");
        }

        $query->selectRaw("
            COALESCE(
                STRING_AGG(DISTINCT COALESCE(rt.value, '-'), ', '),
                '-'
            ) AS daftar_rfid
        ");

        return $query
            ->orderByDesc('tanggal_dibuat')
            ->orderBy('warehouse_name')
            ->orderBy('product_name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Opname ID',
            'Kode Opname',
            'Status Opname',
            'Tanggal Dibuat',
            'Tanggal Selesai',
            'Waktu Scan Terakhir',
            'Warehouse ID',
            'Warehouse',
            'Disetujui Oleh',
            'Product ID',
            'Kode Produk',
            'Nama Produk',
            'Qty Pending',
            'Qty System',
            'Qty Scan',
            'Qty Match',
            'Qty Missing',
            'Qty Extra',
            'Selisih',
            'Status Summary',
            'Kondisi Barang',
            'Daftar RFID',
        ];
    }

    private function resolveConditionExpression(): string
    {
        $schema = DB::getSchemaBuilder();

        if ($schema->hasColumn('items', 'condition')) {
            return 'it.condition';
        }

        if ($schema->hasColumn('items', 'item_condition')) {
            return 'it.item_condition';
        }

        if ($schema->hasColumn('items', 'barang_condition')) {
            return 'it.barang_condition';
        }

        if ($schema->hasColumn('items', 'status')) {
            return 'it.status';
        }

        if ($schema->hasColumn('items', 'item_status')) {
            return 'it.item_status';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'condition')) {
            return 'd.condition';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'item_condition')) {
            return 'd.item_condition';
        }

        if ($schema->hasColumn('warehouse_stock_opname_details', 'item_status')) {
            return 'd.item_status';
        }

        return 'NULL';
    }
}
