<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerLoyaltyAccountReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $pointStats = DB::table('customer_loyalty_point_mutations')
            ->when($start, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
            ->select('customer_id')
            ->selectRaw("
                COALESCE(SUM(points) FILTER (WHERE type::text IN ('earn','referral_register','referral_first_tx')), 0)::numeric AS earned_points,
                COALESCE(-SUM(points) FILTER (WHERE type::text = 'redeem'), 0)::numeric AS redeemed_points,
                COALESCE(-SUM(points) FILTER (WHERE type::text = 'expire'), 0)::numeric AS expired_points,
                COALESCE(SUM(points) FILTER (WHERE type::text = 'adjust'), 0)::numeric AS adjusted_points,
                COUNT(*)::int AS mutation_count,
                MAX(created_at) AS last_mutation_at
            ")
            ->groupBy('customer_id');

        $voucherStats = DB::table('voucher_usages')
            ->when($start, fn ($q) => $q->whereBetween('used_at', [$start, $end]))
            ->select('customer_id')
            ->selectRaw('
                COUNT(*)::int AS used_voucher_count,
                COALESCE(SUM(discount_amount), 0)::numeric AS used_voucher_value,
                MAX(used_at) AS last_voucher_at
            ')
            ->groupBy('customer_id');

        $balanceStats = DB::table('customer_loyalty_points')
            ->select('customer_id')
            ->selectRaw('COALESCE(SUM(balance), 0)::numeric AS point_balance, MAX(updated_at) AS balance_updated_at')
            ->groupBy('customer_id');

        $base = DB::table('customers as c')
            ->leftJoinSub($balanceStats, 'balance', fn ($join) => $join->on('balance.customer_id', '=', 'c.id'))
            ->leftJoinSub($pointStats, 'points', fn ($join) => $join->on('points.customer_id', '=', 'c.id'))
            ->leftJoinSub($voucherStats, 'vouchers', fn ($join) => $join->on('vouchers.customer_id', '=', 'c.id'))
            ->leftJoin('districts as d', 'd.code', '=', 'c.district_code')
            ->leftJoin('regencies as r', 'r.code', '=', 'c.regency_code')
            ->leftJoin('provinces as pr', 'pr.code', '=', 'c.province_code')
            ->whereNull('c.deleted_at')
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('c.wa_number', 'ILIKE', $like)
                        ->orWhere('c.referral_code', 'ILIKE', $like);
                });
            })
            ->when($filters['has_activity'], function ($q) {
                $q->where(function ($activity) {
                    $activity->whereRaw('COALESCE(balance.point_balance, 0) <> 0')
                        ->orWhereRaw('COALESCE(points.mutation_count, 0) > 0')
                        ->orWhereRaw('COALESCE(vouchers.used_voucher_count, 0) > 0');
                });
            });

        $rows = $base
            ->select([
                'c.id',
                'c.name',
                'c.email',
                'c.wa_number',
                'c.referral_code',
                'c.created_at',
                'd.name as district_name',
                'r.name as regency_name',
                'pr.name as province_name',
            ])
            ->selectRaw('COALESCE(balance.point_balance, 0)::numeric AS point_balance')
            ->selectRaw('balance.balance_updated_at')
            ->selectRaw('COALESCE(points.earned_points, 0)::numeric AS earned_points')
            ->selectRaw('COALESCE(points.redeemed_points, 0)::numeric AS redeemed_points')
            ->selectRaw('COALESCE(points.expired_points, 0)::numeric AS expired_points')
            ->selectRaw('COALESCE(points.adjusted_points, 0)::numeric AS adjusted_points')
            ->selectRaw('COALESCE(points.mutation_count, 0)::int AS mutation_count')
            ->selectRaw('points.last_mutation_at')
            ->selectRaw('COALESCE(vouchers.used_voucher_count, 0)::int AS used_voucher_count')
            ->selectRaw('COALESCE(vouchers.used_voucher_value, 0)::numeric AS used_voucher_value')
            ->selectRaw('vouchers.last_voucher_at')
            ->orderByRaw('COALESCE(balance.point_balance, 0) DESC')
            ->orderBy('c.name')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $customerIds = collect($rows->items())->pluck('id')->all();
        $usedVouchers = $this->usedVouchers($customerIds, $start, $end);
        $availableVouchers = $this->availableVouchers($customerIds);

        $rows->through(function ($row) use ($usedVouchers, $availableVouchers) {
            $available = $availableVouchers->get($row->id, collect());
            $used = $usedVouchers->get($row->id, collect());

            $row->available_voucher_count = $available->count();
            $row->available_voucher_value = (float) $available->sum('potential_discount');
            $row->details = $available
                ->map(fn ($voucher) => [
                    'voucher_code' => $voucher->code,
                    'voucher_name' => $voucher->name,
                    'status_label' => 'Tersedia',
                    'context' => $voucher->context,
                    'valid_until' => $voucher->valid_until,
                    'used_at' => null,
                    'discount_amount' => $voucher->potential_discount,
                    'note' => $voucher->min_purchase ? 'Min. belanja '.number_format((float) $voucher->min_purchase, 0, ',', '.') : null,
                ])
                ->concat($used->map(fn ($voucher) => [
                    'voucher_code' => $voucher->code,
                    'voucher_name' => $voucher->name,
                    'status_label' => 'Sudah digunakan',
                    'context' => $voucher->context,
                    'valid_until' => $voucher->valid_until,
                    'used_at' => $voucher->used_at,
                    'discount_amount' => $voucher->discount_amount,
                    'note' => $voucher->transaction_number ? 'Transaksi '.$voucher->transaction_number : null,
                ]))
                ->values()
                ->all();

            return $row;
        });

        $summary = [
            'total_accounts' => $rows->total(),
            'total_point_balance' => (float) DB::table('customer_loyalty_points')->sum('balance'),
            'total_earned_points' => (float) DB::table('customer_loyalty_point_mutations')
                ->whereBetween('created_at', [$start, $end])
                ->whereRaw("type::text IN ('earn','referral_register','referral_first_tx')")
                ->sum('points'),
            'total_redeemed_points' => (float) abs(DB::table('customer_loyalty_point_mutations')
                ->whereBetween('created_at', [$start, $end])
                ->whereRaw("type::text = 'redeem'")
                ->sum('points')),
            'total_used_vouchers' => $usedVouchers->flatten(1)->count(),
            'total_used_voucher_value' => (float) $usedVouchers->flatten(1)->sum('discount_amount'),
            'total_available_vouchers' => $availableVouchers->flatten(1)->count(),
        ];

        $topMembers = DB::table('customers as c')
            ->joinSub($balanceStats, 'balance', fn ($join) => $join->on('balance.customer_id', '=', 'c.id'))
            ->whereNull('c.deleted_at')
            ->select(['c.id', 'c.name', 'c.email', 'c.wa_number'])
            ->selectRaw('COALESCE(balance.point_balance, 0)::numeric AS point_balance')
            ->orderByDesc('point_balance')
            ->limit(10)
            ->get();

        return Inertia::render('Monitoring/Report', [
            'title' => 'Loyalty Poin & Voucher per Akun',
            'description' => 'Lihat saldo poin, poin masuk/keluar, daftar voucher yang masih tersedia, dan voucher yang sudah digunakan oleh setiap akun.',
            'data' => [
                'rows' => $rows,
                'topMembers' => $topMembers,
                'summary' => $summary,
                'filters' => $filters,
                'memberships' => DB::table('memberships')->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            ],
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    private function usedVouchers(array $customerIds, Carbon $start, Carbon $end)
    {
        if ($customerIds === []) {
            return collect();
        }

        return DB::table('voucher_usages as vu')
            ->join('vouchers as v', 'v.id', '=', 'vu.voucher_id')
            ->leftJoin('store_carts as sc', 'sc.id', '=', 'vu.store_cart_id')
            ->leftJoin('ecommerce_orders as eo', 'eo.id', '=', 'vu.ecommerce_order_id')
            ->leftJoin('payments as p', 'p.id', '=', 'eo.payment_id')
            ->whereIn('vu.customer_id', $customerIds)
            ->whereBetween('vu.used_at', [$start, $end])
            ->select([
                'vu.customer_id',
                'vu.used_at',
                'vu.discount_amount',
                'v.code',
                'v.name',
                'v.context',
                'v.valid_until',
                DB::raw('COALESCE(p.transaction_number, eo.order_number, sc.id::text) AS transaction_number'),
            ])
            ->orderByDesc('vu.used_at')
            ->get()
            ->groupBy('customer_id');
    }

    private function availableVouchers(array $customerIds)
    {
        if ($customerIds === []) {
            return collect();
        }

        $vouchers = DB::table('vouchers')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereRaw('COALESCE(usage_count, 0) < usage_limit');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'value', 'max_discount', 'min_purchase', 'context', 'valid_until']);

        $usedIds = DB::table('voucher_usages')
            ->whereIn('customer_id', $customerIds)
            ->pluck('voucher_id')
            ->unique()
            ->all();

        return collect($customerIds)->mapWithKeys(function ($customerId) use ($vouchers, $usedIds) {
            return [$customerId => $vouchers
                ->reject(fn ($voucher) => in_array($voucher->id, $usedIds, true))
                ->map(function ($voucher) {
                    $potential = $voucher->type === 'percentage'
                        ? (float) $voucher->value
                        : (float) $voucher->value;

                    return (object) [
                        ...((array) $voucher),
                        'potential_discount' => $voucher->type === 'percentage'
                            ? (float) ($voucher->max_discount ?: $potential)
                            : $potential,
                    ];
                })
                ->values()];
        });
    }

    private function filters(Request $request): array
    {
        $from = $this->date($request->query('start_date')) ?: now()->startOfMonth()->toDateString();
        $to = $this->date($request->query('end_date')) ?: now()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'q' => trim((string) $request->query('q', '')),
            'has_activity' => $request->boolean('has_activity', true),
            'start_date' => $from,
            'end_date' => $to,
            'per_page' => $this->perPage($request->query('per_page')),
        ];
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 25);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;
    }
}
