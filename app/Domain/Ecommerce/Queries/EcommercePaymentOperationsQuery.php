<?php

namespace App\Domain\Ecommerce\Queries;

use App\Domain\Ecommerce\Support\DokuPaymentChannelResolver;
use Illuminate\Support\Facades\DB;

class EcommercePaymentOperationsQuery
{
    public function build(
        string $q,
        ?string $paymentStatus,
        ?string $paymentMethodCode,
        ?string $from = null,
        ?string $to = null
    ): array {
        $base = $this->baseQuery($q, $paymentStatus, $paymentMethodCode, $from, $to);

        $summary = DB::query()
            ->fromSub((clone $base)->reorder(), 'payment_rows')
            ->selectRaw("
                COUNT(*)::int as total_orders,
                COUNT(*) FILTER (WHERE payment_status = 'paid')::int as paid_orders,
                COUNT(*) FILTER (WHERE payment_status = 'pending')::int as pending_orders,
                COUNT(*) FILTER (WHERE payment_status IN ('failed', 'cancelled'))::int as failed_orders,
                COALESCE(SUM(grand_total), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(GREATEST(grand_total - paid_amount, 0)), 0) as outstanding_amount
            ")
            ->first();

        $rows = $base
            ->orderByDesc('order_created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($row) {
                $row->payment_channel_name = DokuPaymentChannelResolver::resolve(
                    $row->payment_method_code,
                    $row->payment_method_name,
                    $row->detail_method_name,
                    $row->detail_payload
                ) ?? '-';

                unset($row->detail_method_name, $row->detail_payload);

                return $row;
            });

        $paymentMethods = DB::table('ecommerce_orders')
            ->select('payment_method_code', 'payment_method_name')
            ->whereNotNull('payment_method_code')
            ->where('payment_method_code', '<>', '')
            ->distinct()
            ->orderBy('payment_method_name')
            ->get();

        $paymentStatuses = DB::table('payments')
            ->selectRaw('DISTINCT status::text as status')
            ->whereNotNull('status')
            ->orderBy('status')
            ->pluck('status');

        return [
            'rows' => $rows,
            'summary' => $summary,
            'paymentMethods' => $paymentMethods,
            'paymentStatuses' => $paymentStatuses,
        ];
    }

    private function paymentDetailQuery()
    {
        return DB::table('payment_details as pd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'pd.payment_method_id')
            ->selectRaw('
                pd.payment_id,
                MAX(pm.name) as detail_method_name,
                MAX(pd.detail) as detail_payload
            ')
            ->groupBy('pd.payment_id');
    }

    private function baseQuery(
        string $q,
        ?string $paymentStatus,
        ?string $paymentMethodCode,
        ?string $from,
        ?string $to
    ) {
        $items = DB::table('ecommerce_order_details as eod')
            ->leftJoin('products as pr', 'pr.id', '=', 'eod.product_id')
            ->selectRaw("
                eod.ecommerce_order_id,
                COUNT(eod.id)::int as total_items,
                COALESCE(SUM(eod.quantity), 0)::int as total_qty,
                STRING_AGG(DISTINCT COALESCE(pr.name, '-'), ', ') as product_names
            ")
            ->groupBy('eod.ecommerce_order_id');

        return DB::table('ecommerce_orders as eo')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoinSub($items, 'ia', 'ia.ecommerce_order_id', '=', 'eo.id')
            ->leftJoinSub($this->paymentDetailQuery(), 'pch', 'pch.payment_id', '=', 'eo.payment_id')
            ->select([
                'eo.id',
                'eo.order_number',
                'eo.status as order_status',
                'eo.created_at as order_created_at',
                'eo.grand_total',
                'eo.payment_method_code',
                'eo.payment_method_name',
                'eo.payment_fee',
                'pch.detail_method_name',
                'pch.detail_payload',
                DB::raw("COALESCE(c.name, '-') as customer_name"),
                DB::raw("COALESCE(c.email, '-') as customer_email"),
                DB::raw("COALESCE(c.wa_number, '-') as customer_wa"),
                DB::raw('COALESCE(p.status::text, \'-\') as payment_status'),
                DB::raw("CASE WHEN p.status = 'paid' THEN COALESCE(p.total_amount, p.paid_amount, 0) ELSE COALESCE(p.paid_amount, 0) END as paid_amount"),
                DB::raw('COALESCE(p.total_amount, 0) as payment_total_amount'),
                DB::raw('COALESCE(p.transaction_number, \'-\') as transaction_number'),
                'p.completed_at as payment_completed_at',
                'p.note as payment_note',
                DB::raw('COALESCE(ia.total_items, 0) as total_items'),
                DB::raw('COALESCE(ia.total_qty, 0) as total_qty'),
                DB::raw('COALESCE(ia.product_names, \'-\') as product_names'),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('eo.order_number', 'ILIKE', "%{$q}%")
                        ->orWhere('p.transaction_number', 'ILIKE', "%{$q}%")
                        ->orWhere('c.name', 'ILIKE', "%{$q}%")
                        ->orWhere('c.email', 'ILIKE', "%{$q}%")
                        ->orWhere('c.wa_number', 'ILIKE', "%{$q}%");
                });
            })
            ->when($paymentStatus, fn ($query) => $query->where('p.status', $paymentStatus))
            ->when($paymentMethodCode, fn ($query) => $query->where('eo.payment_method_code', $paymentMethodCode))
            ->when($from, fn ($query) => $query->whereDate('eo.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('eo.created_at', '<=', $to));
    }
}
