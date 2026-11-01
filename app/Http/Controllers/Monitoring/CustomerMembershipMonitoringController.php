<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerMembershipMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'membership_id' => $this->nullableString($request->query('membership_id')),
            'status' => $this->nullableString($request->query('status')),
            'valid_from' => $this->nullableDate($request->query('valid_from')),
            'valid_to' => $this->nullableDate($request->query('valid_to')),
        ];

        $baseQuery = $this->customerMembershipQuery($filters);

        $summary = DB::query()
            ->fromSub(clone $baseQuery, 'customer_tiers')
            ->selectRaw("\n                COUNT(*)::int as total_customer,\n                COUNT(*) FILTER (WHERE membership_status = 'active')::int as total_active,\n                COUNT(*) FILTER (WHERE membership_id IS NULL)::int as total_unassigned,\n                COUNT(*) FILTER (\n                    WHERE tier_valid_until >= CURRENT_DATE\n                    AND tier_valid_until <= CURRENT_DATE + INTERVAL '30 days'\n                )::int as expiring_soon\n            ")
            ->first();

        $tierDistribution = DB::query()
            ->fromSub(clone $baseQuery, 'customer_tiers')
            ->selectRaw("\n                COALESCE(membership_name, 'Belum memiliki tier') as membership_name,\n                COUNT(*)::int as customer_count,\n                COALESCE(MAX(membership_sort_order), 9999) as membership_sort_order\n            ")
            ->groupBy('membership_name')
            ->orderBy('membership_sort_order')
            ->orderBy('membership_name')
            ->get();

        $rows = $baseQuery
            ->orderByRaw('CASE WHEN membership_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('membership_sort_order')
            ->orderBy('customer_name')
            ->paginate(25)
            ->withQueryString();

        $memberships = DB::table('memberships')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = DB::table('customer_memberships')
            ->selectRaw('status::text as status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('content.monitoring.customer-membership.index', compact(
            'rows',
            'summary',
            'tierDistribution',
            'memberships',
            'statuses',
            'filters'
        ));
    }

    private function customerMembershipQuery(array $filters)
    {
        $rankedMemberships = DB::table('customer_memberships as cm')
            ->select([
                'cm.customer_id',
                'cm.membership_id',
                'cm.status',
                'cm.total_spend_12_month',
                'cm.tier_evaluated_at',
                'cm.tier_valid_until',
                'cm.previous_membership_id',
                'cm.updated_at',
            ])
            ->selectRaw("\n                ROW_NUMBER() OVER (\n                    PARTITION BY cm.customer_id\n                    ORDER BY\n                        CASE WHEN cm.status::text = 'active' THEN 0 ELSE 1 END,\n                        cm.tier_evaluated_at DESC NULLS LAST,\n                        cm.updated_at DESC NULLS LAST\n                ) as membership_rank\n            ");

        $latestMemberships = DB::query()
            ->fromSub($rankedMemberships, 'ranked_memberships')
            ->where('membership_rank', 1);

        $historySummary = DB::table('customer_tier_histories as cth')
            ->select('cth.customer_id')
            ->selectRaw('COUNT(*)::int as tier_change_count')
            ->selectRaw('MAX(cth.created_at) as last_tier_change_at')
            ->groupBy('cth.customer_id');

        $query = DB::table('customers as c')
            ->leftJoinSub($latestMemberships, 'cm', function ($join) {
                $join->on('cm.customer_id', '=', 'c.id');
            })
            ->leftJoin('memberships as m', 'm.id', '=', 'cm.membership_id')
            ->leftJoin('memberships as previous_membership', 'previous_membership.id', '=', 'cm.previous_membership_id')
            ->leftJoinSub($historySummary, 'tier_history', function ($join) {
                $join->on('tier_history.customer_id', '=', 'c.id');
            })
            ->whereNull('c.deleted_at')
            ->select([
                'c.id as customer_id',
                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',
                'cm.membership_id',
                'm.name as membership_name',
                'm.earning_multiplier',
                'm.min_spend_treshold',
                'm.sort_order as membership_sort_order',
                'previous_membership.name as previous_membership_name',
                'cm.total_spend_12_month',
                'cm.tier_evaluated_at',
                'cm.tier_valid_until',
                'tier_history.tier_change_count',
                'tier_history.last_tier_change_at',
            ])
            ->selectRaw("COALESCE(cm.status::text, 'unassigned') as membership_status");

        if ($filters['q'] !== '') {
            $like = '%' . $filters['q'] . '%';

            $query->where(function ($sub) use ($like) {
                $sub->where('c.name', 'ILIKE', $like)
                    ->orWhere('c.email', 'ILIKE', $like)
                    ->orWhere('c.wa_number', 'ILIKE', $like)
                    ->orWhere('m.name', 'ILIKE', $like);
            });
        }

        if ($filters['membership_id']) {
            $query->where('cm.membership_id', $filters['membership_id']);
        }

        if ($filters['status'] === 'unassigned') {
            $query->whereNull('cm.membership_id');
        } elseif ($filters['status']) {
            $query->whereRaw('cm.status::text = ?', [$filters['status']]);
        }

        if ($filters['valid_from']) {
            $query->whereDate('cm.tier_valid_until', '>=', $filters['valid_from']);
        }

        if ($filters['valid_to']) {
            $query->whereDate('cm.tier_valid_until', '<=', $filters['valid_to']);
        }

        return $query;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
