<?php

namespace App\Domain\Payments\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentReconciliationQuery
{
    public const MATCHED = 'Nominal cocok';

    public const CANCELLED = 'Order dibatalkan';

    /**
     * Perbandingan nilai order e-commerce dengan payments.total_amount.
     * Jumlah payment_details sukses ditampilkan terpisah, bukan sebagai bukti settlement.
     */
    public function base(array $filters): Builder
    {
        $paymentDetails = DB::table('payment_details')
            ->selectRaw("
                payment_id,
                COUNT(*)::int as detail_count,
                COALESCE(SUM(amount) FILTER (WHERE status::text = 'success'), 0)::numeric(18,2) as detail_success_amount
            ")
            ->groupBy('payment_id');

        $baseQuery = DB::table('ecommerce_orders as eo')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoinSub($paymentDetails, 'pd', 'pd.payment_id', '=', 'p.id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->select([
                'eo.id', 'eo.order_number', 'eo.status as order_status', 'eo.grand_total',
                'eo.payment_id', 'eo.payment_method_name', 'eo.created_at', 'c.name as customer_name',
                'p.id as payment_record_id', 'p.transaction_number', 'p.status as payment_status',
                'p.paid_amount as paid_amount_legacy', 'p.total_amount', 'p.completed_at',
                DB::raw('COALESCE(pd.detail_count, 0) as detail_count'),
                DB::raw('COALESCE(pd.detail_success_amount, 0) as detail_success_amount'),
                DB::raw("CASE
                    WHEN LOWER(COALESCE(eo.status::text, '')) = 'cancelled' THEN 'Order dibatalkan'
                    WHEN eo.payment_id IS NULL THEN 'Tanpa pembayaran'
                    WHEN p.id IS NULL THEN 'Referensi pembayaran hilang'
                    WHEN ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01 THEN 'Nominal berbeda'
                    WHEN COALESCE(pd.detail_count, 0) > 0
                        AND ABS(COALESCE(pd.detail_success_amount, 0) - COALESCE(p.total_amount, 0)) > 0.01
                        THEN 'Detail pembayaran berbeda'
                    WHEN p.status::text NOT IN ('paid','completed','success') THEN 'Pembayaran belum selesai'
                    ELSE 'Nominal cocok'
                END AS reconciliation_status"),
            ]);

        $this->applySearch($baseQuery, trim((string) ($filters['q'] ?? '')));

        if (($filters['order_status'] ?? '') !== '') {
            $baseQuery->whereRaw('eo.status::text = ?', [$filters['order_status']]);
        }

        if (($filters['payment_status'] ?? '') !== '') {
            $baseQuery->whereRaw('p.status::text = ?', [$filters['payment_status']]);
        }

        if (! empty($filters['start_date'])) {
            $baseQuery->whereRaw('eo.created_at::date >= ?', [$filters['start_date']]);
        }

        if (! empty($filters['end_date'])) {
            $baseQuery->whereRaw('eo.created_at::date <= ?', [$filters['end_date']]);
        }

        if (! empty($filters['store_id'])) {
            $baseQuery->where('eo.handled_by_store_id', $filters['store_id']);
        }

        if (! empty($filters['warehouse_id'])) {
            $baseQuery->where('eo.handled_by_warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['exception_only'])) {
            $baseQuery->whereRaw("LOWER(COALESCE(eo.status::text, '')) <> 'cancelled'")
                ->where(function ($query) {
                    $query->whereNull('eo.payment_id')
                        ->orWhereNull('p.id')
                        ->orWhereRaw('ABS(COALESCE(p.total_amount, 0) - COALESCE(eo.grand_total, 0)) > 0.01')
                        ->orWhereRaw('COALESCE(pd.detail_count, 0) > 0 AND ABS(COALESCE(pd.detail_success_amount, 0) - COALESCE(p.total_amount, 0)) > 0.01')
                        ->orWhereRaw("p.status::text NOT IN ('paid','completed','success')");
                });
        }

        return $baseQuery;
    }

    public function rows(array $filters, int $perPage = 20)
    {
        return $this->filtered($filters)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function exportRows(array $filters): Collection
    {
        return $this->filtered($filters)
            ->orderByDesc('created_at')
            ->get();
    }

    public function summary(array $filters): array
    {
        $query = $this->filtered($filters);

        $orderCount = (clone $query)->count();
        $matched = (clone $query)
            ->where('reconciliation_status', self::MATCHED)
            ->count();
        $exceptions = (clone $query)
            ->whereNotIn('reconciliation_status', [self::MATCHED, self::CANCELLED])
            ->count();
        $cancelledOrders = (clone $query)
            ->where('reconciliation_status', self::CANCELLED)
            ->count();
        $pendingPayment = (clone $query)
            ->where('reconciliation_status', 'Pembayaran belum selesai')
            ->count();
        $failedPayment = (clone $query)
            ->where('reconciliation_status', 'Pembayaran belum selesai')
            ->whereRaw("LOWER(COALESCE(payment_status::text, '')) IN ('failed','expired','denied','cancel','cancelled','failure')")
            ->count();

        return [
            'orders' => $orderCount,
            'matched' => $matched,
            'exceptions' => $exceptions,
            'cancelled_orders' => $cancelledOrders,
            'pending_payment' => $pendingPayment,
            'failed_payment' => $failedPayment,
            'needs_review' => max(0, $exceptions - $failedPayment),
            'without_payment' => (clone $query)
                ->where('reconciliation_status', 'Tanpa pembayaran')
                ->count(),
            'detail_mismatch' => (clone $query)
                ->whereNotIn('reconciliation_status', [self::CANCELLED])
                ->whereRaw('ABS(COALESCE(detail_success_amount, 0) - COALESCE(total_amount, 0)) > 0.01')
                ->count(),
            'total_value' => (float) (clone $query)->sum('grand_total'),
            'total_paid_amount' => (float) (clone $query)->sum('detail_success_amount'),
        ];
    }

    public function exceptions(array $filters, int $limit = 5): Collection
    {
        return $this->filtered($filters)
            ->whereNotIn('reconciliation_status', [self::MATCHED, self::CANCELLED])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    private function filtered(array $filters): Builder
    {
        $query = DB::query()->fromSub($this->base($filters), 'reconciliation');

        if (($filters['reconciliation_status'] ?? '') !== '') {
            $query->where('reconciliation_status', $filters['reconciliation_status']);
        }

        return $query;
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $query->where(function (Builder $sub) use ($like) {
            $sub->where('eo.order_number', 'ILIKE', $like)
                ->orWhere('p.transaction_number', 'ILIKE', $like)
                ->orWhere('c.name', 'ILIKE', $like);
        });
    }
}
