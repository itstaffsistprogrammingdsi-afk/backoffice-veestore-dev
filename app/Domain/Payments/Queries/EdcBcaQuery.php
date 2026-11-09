<?php

namespace App\Domain\Payments\Queries;

use App\Domain\Payments\Support\EdcMetadata;
use Illuminate\Support\Facades\DB;

class EdcBcaQuery
{
    public function build(
        string $q,
        ?string $storeId,
        ?string $methodId,
        ?string $status,
        ?string $from,
        ?string $to,
        int $perPage = 15
    ): array {
        $base = $this->baseQuery($q, $storeId, $methodId, $status, $from, $to);

        $rows = (clone $base)
            ->orderByDesc('event_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (object $row): object => $this->transformRow($row));

        $creditRate = (float) config('backoffice.edc_bca.credit_mdr_percent', 2.0);
        $debitRate = (float) config('backoffice.edc_bca.debit_mdr_percent', 1.0);

        $summary = DB::query()
            ->fromSub((clone $base)->reorder(), 'rows')
            ->selectRaw(
                "
                COUNT(*)::int as total_transactions,
                COALESCE(SUM(amount), 0)::numeric as gross_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method_name) LIKE '%debit%' THEN amount * ? / 100 ELSE amount * ? / 100 END), 0)::numeric as mdr_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method_name) LIKE '%debit%' THEN amount - (amount * ? / 100) ELSE amount - (amount * ? / 100) END), 0)::numeric as net_amount
                ",
                [$debitRate, $creditRate, $debitRate, $creditRate]
            )
            ->first();

        $byMethod = DB::query()
            ->fromSub((clone $base)->reorder(), 'rows')
            ->selectRaw(
                "
                payment_method_name,
                COUNT(*)::int as total_transactions,
                COALESCE(SUM(amount), 0)::numeric as gross_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method_name) LIKE '%debit%' THEN amount * ? / 100 ELSE amount * ? / 100 END), 0)::numeric as mdr_amount,
                COALESCE(SUM(CASE WHEN LOWER(payment_method_name) LIKE '%debit%' THEN amount - (amount * ? / 100) ELSE amount - (amount * ? / 100) END), 0)::numeric as net_amount
                ",
                [$debitRate, $creditRate, $debitRate, $creditRate]
            )
            ->groupBy('payment_method_name')
            ->orderByDesc('gross_amount')
            ->get();

        $byStore = DB::query()
            ->fromSub((clone $base)->reorder(), 'rows')
            ->selectRaw('
                store_name,
                COUNT(*)::int as total_transactions,
                COALESCE(SUM(amount), 0)::numeric as gross_amount
            ')
            ->groupBy('store_name')
            ->orderByDesc('gross_amount')
            ->get();

        $byDay = DB::query()
            ->fromSub((clone $base)->reorder(), 'rows')
            ->selectRaw('
                event_date,
                COUNT(*)::int as total_transactions,
                COALESCE(SUM(amount), 0)::numeric as gross_amount
            ')
            ->groupBy('event_date')
            ->orderBy('event_date')
            ->get();

        $methods = DB::table('payment_methods')
            ->whereNull('deleted_at')
            ->whereRaw("(
                (name ILIKE '%BCA%' AND (
                    name ILIKE '%EDC%'
                    OR name ILIKE '%DEBIT%'
                    OR name ILIKE '%CC%'
                    OR name ILIKE '%KREDIT%'
                ))
                OR name ILIKE '%EDC%'
            )")
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $statuses = DB::table('payment_details')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        $stores = DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'byMethod' => $byMethod,
            'byStore' => $byStore,
            'byDay' => $byDay,
            'methods' => $methods,
            'statuses' => $statuses,
            'stores' => $stores,
            'mdr' => [
                'credit_percent' => $creditRate,
                'debit_percent' => $debitRate,
                'settlement_days' => (int) config('backoffice.edc_bca.settlement_days', 1),
            ],
            'readiness' => [
                'has_edc_bca_method' => $methods->contains(fn (object $method): bool => str_contains(strtolower($method->name), 'edc')),
                'method_names' => $methods->pluck('name')->values()->all(),
            ],
            'filters' => [
                'q' => $q,
                'store_id' => $storeId,
                'method_id' => $methodId,
                'status' => $status,
                'from' => $from,
                'to' => $to,
                'per_page' => $perPage,
            ],
        ];
    }

    public function exportRows(
        string $q,
        ?string $storeId,
        ?string $methodId,
        ?string $status,
        ?string $from,
        ?string $to
    ): array {
        return $this->baseQuery($q, $storeId, $methodId, $status, $from, $to)
            ->orderByDesc('event_at')
            ->get()
            ->map(fn (object $row): array => (array) $this->transformRow($row))
            ->all();
    }

    private function baseQuery(
        string $q,
        ?string $storeId,
        ?string $methodId,
        ?string $status,
        ?string $from,
        ?string $to
    ) {
        return DB::table('payment_details as pd')
            ->join('payments as p', 'p.id', '=', 'pd.payment_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->leftJoin('store_visit_payments as svp', 'svp.payment_id', '=', 'p.id')
            ->leftJoin('store_customer_visits as scv', 'scv.id', '=', 'svp.visit_id')
            ->leftJoin('stores as store', 'store.id', '=', 'scv.store_id')
            ->leftJoin('users as cashier', 'cashier.id', '=', 'svp.cashier_user_id')
            ->leftJoin('ecommerce_orders as eo', 'eo.payment_id', '=', 'p.id')
            ->leftJoin('stores as ecommerce_store', 'ecommerce_store.id', '=', 'eo.handled_by_store_id')
            ->whereRaw("(
                (pm.name ILIKE '%BCA%' AND (
                    pm.name ILIKE '%EDC%'
                    OR pm.name ILIKE '%DEBIT%'
                    OR pm.name ILIKE '%CC%'
                    OR pm.name ILIKE '%KREDIT%'
                ))
                OR pm.name ILIKE '%EDC%'
            )")
            ->when($storeId, function ($query) use ($storeId): void {
                $query->where(function ($inner) use ($storeId): void {
                    $inner->where('scv.store_id', $storeId)
                        ->orWhere('eo.handled_by_store_id', $storeId);
                });
            })
            ->when($methodId, fn ($query) => $query->where('pm.id', $methodId))
            ->when($status, fn ($query) => $query->where('pd.status', $status))
            ->when($from, fn ($query) => $query->whereRaw('COALESCE(p.completed_at, p.created_at)::date >= ?', [$from]))
            ->when($to, fn ($query) => $query->whereRaw('COALESCE(p.completed_at, p.created_at)::date <= ?', [$to]))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('p.transaction_number', 'ILIKE', "%{$q}%")
                        ->orWhere('pd.transaction_ref', 'ILIKE', "%{$q}%")
                        ->orWhere('pd.ref', 'ILIKE', "%{$q}%")
                        ->orWhere('pm.name', 'ILIKE', "%{$q}%")
                        ->orWhere('store.name', 'ILIKE', "%{$q}%")
                        ->orWhere('ecommerce_store.name', 'ILIKE', "%{$q}%")
                        ->orWhere('eo.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('pd.detail', 'ILIKE', "%{$q}%");
                });
            })
            ->selectRaw("
                pd.id,
                pd.payment_id,
                pd.amount,
                pd.status::text as detail_status,
                pd.transaction_ref,
                pd.ref,
                pd.detail,
                COALESCE(p.completed_at, p.created_at) as event_at,
                COALESCE(p.completed_at, p.created_at)::date as event_date,
                p.transaction_number,
                p.status::text as payment_status,
                p.total_amount,
                p.completed_at,
                pm.name as payment_method_name,
                pm.type::text as payment_method_type,
                COALESCE(store.name, ecommerce_store.name, '-') as store_name,
                CASE
                    WHEN svp.id IS NOT NULL THEN 'store'
                    WHEN eo.id IS NOT NULL THEN 'ecommerce'
                    ELSE 'other'
                END as channel,
                cashier.name as cashier_name,
                eo.order_number as ecommerce_order_number
            ");
    }

    private function transformRow(object $row): object
    {
        $amount = (float) ($row->amount ?? 0);
        $method = (string) ($row->payment_method_name ?? '');
        $metadata = EdcMetadata::parse($row->detail ?? null);

        $row->amount = $amount;
        $row->total_amount = (float) ($row->total_amount ?? 0);
        $row->mdr_rate = EdcMetadata::mdrRate($method);
        $row->mdr_amount = EdcMetadata::estimateMdr($amount, $method);
        $row->net_amount = EdcMetadata::estimateSettlement($amount, $method);
        $row->metadata = $metadata;
        $row->channel_label = match ($row->channel) {
            'store' => 'POS',
            'ecommerce' => 'E-commerce',
            default => 'Lainnya',
        };

        return $row;
    }
}
