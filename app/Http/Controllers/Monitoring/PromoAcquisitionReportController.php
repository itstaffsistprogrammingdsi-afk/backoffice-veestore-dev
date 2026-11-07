<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PromoAcquisitionReportController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Monitoring/Report', [
            'title' => 'Laporan Promo, Voucher, Poin & Referral',
            'description' => 'Ukur berapa akun yang mengambil voucher, memakai poin, dan berhasil mengajak referral pada periode yang dipilih.',
            'data' => $this->reportData($request),
            'filters' => $request->query(),
            'urls' => ['index' => url()->current()],
        ]);
    }

    public function reportData(Request $request): array
    {
        $filters = $this->filters($request);
        $start = Carbon::parse($filters['start_date'])->startOfDay();
        $end = Carbon::parse($filters['end_date'])->endOfDay();

        $voucherRows = DB::table('vouchers as v')
            ->leftJoin('voucher_usages as vu', function ($join) use ($start, $end) {
                $join->on('vu.voucher_id', '=', 'v.id')
                    ->whereBetween('vu.used_at', [$start, $end]);
            })
            ->whereNull('v.deleted_at')
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $q->where(function ($search) use ($like) {
                    $search->where('v.code', 'ILIKE', $like)
                        ->orWhere('v.name', 'ILIKE', $like);
                });
            })
            ->select([
                'v.id',
                'v.code',
                'v.name',
                'v.context as voucher_context',
                'v.type as voucher_type',
                'v.value',
                'v.usage_limit',
                'v.usage_count',
                'v.valid_from',
                'v.valid_until',
                'v.is_active',
            ])
            ->selectRaw('
                COUNT(vu.id)::int AS usage_count_in_period,
                COUNT(DISTINCT vu.customer_id)::int AS unique_customers,
                COALESCE(SUM(vu.discount_amount), 0)::numeric AS discount_amount,
                MAX(vu.used_at) AS last_used_at
            ')
            ->groupBy([
                'v.id',
                'v.code',
                'v.name',
                'v.context',
                'v.type',
                'v.value',
                'v.usage_limit',
                'v.usage_count',
                'v.valid_from',
                'v.valid_until',
                'v.is_active',
            ])
            ->orderByDesc('usage_count_in_period')
            ->orderByDesc('discount_amount')
            ->get()
            ->map(function ($row) {
                $now = now();
                $expired = $row->valid_until && Carbon::parse($row->valid_until)->isPast();
                $exhausted = $row->usage_limit !== null && (int) $row->usage_count >= (int) $row->usage_limit;

                return [
                    ...((array) $row),
                    'status_label' => ! $row->is_active ? 'Tidak aktif' : ($expired ? 'Kedaluwarsa' : ($exhausted ? 'Kuota habis' : 'Aktif')),
                    'remaining_quota' => $row->usage_limit === null ? null : max(0, (int) $row->usage_limit - (int) $row->usage_count),
                ];
            });

        $pointRows = DB::table('customer_loyalty_point_mutations')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                type::text AS mutation_type,
                COUNT(*)::int AS mutation_count,
                COUNT(DISTINCT customer_id)::int AS unique_customers,
                COALESCE(SUM(points), 0)::numeric AS total_points,
                COALESCE(SUM(points) FILTER (WHERE points > 0), 0)::numeric AS points_in,
                COALESCE(SUM(ABS(points)) FILTER (WHERE points < 0), 0)::numeric AS points_out,
                MAX(created_at) AS last_mutation_at
            ')
            ->groupByRaw('type::text')
            ->orderByDesc('mutation_count')
            ->get()
            ->map(function ($row) {
                $label = match ($row->mutation_type) {
                    'earn' => 'Poin dari transaksi',
                    'redeem' => 'Tukar poin',
                    'expire' => 'Poin kedaluwarsa',
                    'adjust' => 'Penyesuaian poin',
                    'referral_register' => 'Poin referral (registrasi)',
                    'referral_first_tx' => 'Poin referral (transaksi pertama)',
                    'void_reversal' => 'Pembatalan poin',
                    default => $row->mutation_type,
                };

                return [...((array) $row), 'mutation_label' => $label];
            });

        $referralRows = DB::table('referrals')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                status::text AS referral_status,
                COUNT(*)::int AS total_referral,
                COUNT(DISTINCT referrer_id)::int AS total_referrer,
                COUNT(DISTINCT referee_id)::int AS total_referee,
                MAX(created_at) AS last_referral_at
            ')
            ->groupByRaw('status::text')
            ->orderByDesc('total_referral')
            ->get()
            ->map(function ($row) {
                $label = match ($row->referral_status) {
                    'pending' => 'Menunggu',
                    'registered' => 'Terdaftar',
                    'first_transaction_done' => 'Transaksi pertama selesai',
                    default => $row->referral_status,
                };

                return [...((array) $row), 'status_label' => $label];
            });

        $voucherUsers = DB::table('voucher_usages')->whereBetween('used_at', [$start, $end])->distinct('customer_id')->count('customer_id');
        $pointUsers = DB::table('customer_loyalty_point_mutations')->whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id');
        $totalReferral = DB::table('referrals')->whereBetween('created_at', [$start, $end])->count();
        $successReferral = DB::table('referrals')
            ->whereBetween('created_at', [$start, $end])
            ->whereRaw("LOWER(status::text) IN ('registered','first_transaction_done','success','completed','approved')")
            ->count();

        $summary = [
            'voucher_users' => $voucherUsers,
            'voucher_usages' => (int) $voucherRows->sum('usage_count_in_period'),
            'voucher_discount_value' => (float) $voucherRows->sum('discount_amount'),
            'point_users' => $pointUsers,
            'point_mutations' => (int) $pointRows->sum('mutation_count'),
            'total_referral' => $totalReferral,
            'success_referral' => $successReferral,
            'referral_success_rate' => $totalReferral > 0 ? round(($successReferral / $totalReferral) * 100, 1) : 0,
        ];

        $topCustomerRows = DB::table('voucher_usages as vu')
            ->join('customers as c', 'c.id', '=', 'vu.customer_id')
            ->whereBetween('vu.used_at', [$start, $end])
            ->select(['c.id', 'c.name', 'c.email', 'c.wa_number'])
            ->selectRaw('
                COUNT(*)::int AS voucher_usage_count,
                COALESCE(SUM(vu.discount_amount), 0)::numeric AS discount_amount,
                MAX(vu.used_at) AS last_used_at
            ')
            ->groupBy('c.id', 'c.name', 'c.email', 'c.wa_number')
            ->orderByDesc('voucher_usage_count')
            ->orderByDesc('discount_amount')
            ->limit(10)
            ->get();

        return [
            'rows' => $voucherRows,
            'pointRows' => $pointRows,
            'referralRows' => $referralRows,
            'topCustomerRows' => $topCustomerRows,
            'summary' => $summary,
            'filters' => $filters,
        ];
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
            'start_date' => $from,
            'end_date' => $to,
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
}
