<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PaymentSlaReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $threshold = (int) config('backoffice.payment_sla_hours', 24);
        $approaching = (int) ceil($threshold * 0.75);

        $base = DB::table('ecommerce_orders as eo')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->leftJoin('customers as c', 'c.id', '=', 'eo.customer_id')
            ->whereRaw("LOWER(eo.status::text) NOT IN ('cancelled', 'canceled', 'received')")
            ->where(function ($query): void {
                $query->whereRaw("LOWER(COALESCE(eo.status::text, '')) = 'pending'")
                    ->orWhereRaw("LOWER(COALESCE(p.status::text, '')) NOT IN ('paid', 'settlement', 'capture', 'success', 'completed')");
            })
            ->selectRaw("
                eo.id,
                eo.order_number,
                eo.status::text as order_status,
                eo.created_at as order_created_at,
                COALESCE(c.name, '-') as customer_name,
                COALESCE(NULLIF(eo.payment_method_name, ''), '-') as payment_method_name,
                COALESCE(eo.grand_total, 0)::numeric as amount,
                COALESCE(p.status::text, eo.status::text, '-') as payment_status,
                GREATEST(0, FLOOR(EXTRACT(EPOCH FROM (NOW() - eo.created_at)) / 3600))::int as age_hours
            ");

        $filtered = DB::query()
            ->fromSub($base, 'order_sla')
            ->selectRaw("
                order_sla.*,
                CASE
                    WHEN order_sla.age_hours >= ? THEN 'Melewati SLA'
                    WHEN order_sla.age_hours >= ? THEN 'Mendekati SLA'
                    ELSE 'Dalam SLA'
                END as sla_status
            ", [$threshold, $approaching])
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $like = '%'.$filters['q'].'%';

                $query->where(function ($search) use ($like): void {
                    $search->where('order_number', 'ILIKE', $like)
                        ->orWhere('customer_name', 'ILIKE', $like)
                        ->orWhere('payment_method_name', 'ILIKE', $like)
                        ->orWhere('payment_status', 'ILIKE', $like);
                });
            })
            ->when($filters['sla_status'] === 'overdue', fn ($query) => $query->where('age_hours', '>=', $threshold))
            ->when($filters['sla_status'] === 'approaching', fn ($query) => $query->whereBetween('age_hours', [$approaching, max($approaching, $threshold - 1)]))
            ->when($filters['sla_status'] === 'on_time', fn ($query) => $query->where('age_hours', '<', $approaching));

        $summary = DB::query()
            ->fromSub(clone $filtered, 'summary')
            ->selectRaw("
                COUNT(*)::int as total_pending,
                COUNT(*) FILTER (WHERE sla_status = 'Melewati SLA')::int as total_overdue,
                COUNT(*) FILTER (WHERE sla_status = 'Mendekati SLA')::int as total_approaching,
                COUNT(*) FILTER (WHERE sla_status = 'Dalam SLA')::int as total_on_time,
                COALESCE(SUM(amount), 0)::numeric as total_amount,
                COALESCE(AVG(age_hours), 0)::numeric as average_age_hours
            ")
            ->first();

        $rows = (clone $filtered)
            ->orderByDesc('age_hours')
            ->orderByDesc('order_created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return Inertia::render('Monitoring/Report', [
            'title' => 'SLA Pembayaran E-commerce',
            'description' => 'Pantau pesanan e-commerce yang belum menyelesaikan pembayaran dalam batas '.$threshold.' jam.',
            'data' => [
                'rows' => $rows,
                'summary' => (array) $summary,
                'slaStatuses' => [
                    ['value' => 'overdue', 'label' => 'Melewati SLA'],
                    ['value' => 'approaching', 'label' => 'Mendekati SLA'],
                    ['value' => 'on_time', 'label' => 'Dalam SLA'],
                ],
                'filters' => $filters,
                'slaThresholdHours' => $threshold,
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function filters(Request $request): array
    {
        $slaStatus = trim((string) $request->query('sla_status', ''));
        $perPage = (int) ($request->query('per_page', 25) ?: 25);

        if (! in_array($slaStatus, ['overdue', 'approaching', 'on_time'], true)) {
            $slaStatus = '';
        }

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'sla_status' => $slaStatus,
            'per_page' => $perPage,
        ];
    }
}
