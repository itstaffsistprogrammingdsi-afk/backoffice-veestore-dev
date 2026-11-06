<?php

namespace App\Domain\Analytics\Queries;

use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerDemographicsQuery
{
    private const AGE_GROUPS = [
        'under_18' => '< 18 tahun',
        '18_24' => '18–24 tahun',
        '25_34' => '25–34 tahun',
        '35_44' => '35–44 tahun',
        '45_54' => '45–54 tahun',
        '55_plus' => '55+ tahun',
        'unknown' => 'Usia belum diisi',
    ];

    private const SORTS = [
        'name' => ['customers.name', 'asc'],
        'newest' => ['customers.created_at', 'desc'],
        'oldest' => ['customers.created_at', 'asc'],
        'age_youngest' => ['customers.date_of_birth', 'desc'],
        'age_oldest' => ['customers.date_of_birth', 'asc'],
        'visits' => ['visit_stats.visit_count', 'desc'],
        'last_visit' => ['visit_stats.last_visit_at', 'desc'],
        'spending' => ['total_spending', 'desc'],
        'points' => ['loyalty_points', 'desc'],
    ];

    public function build(array $input, int $perPage = 20): array
    {
        $filters = $this->normalizeFilters($input);
        $base = $this->baseQuery($filters);
        $birthdayMonth = $filters['birth_month'] !== ''
            ? (int) $filters['birth_month']
            : (int) now()->month;

        return [
            'filters' => $filters,
            'ageGroups' => self::AGE_GROUPS,
            'registrationChannels' => $this->channelOptions(),
            'memberships' => $this->memberships(),
            'stores' => $this->stores(),
            'provinces' => $this->provinces(),
            'selectedRegions' => $this->selectedRegions($filters),
            'birthdayMonth' => $birthdayMonth,
            'birthdayMonthLabel' => $this->monthLabel($birthdayMonth),
            'summary' => $this->summary(clone $base, $birthdayMonth),
            'genderRows' => $this->genderDistribution(clone $base),
            'ageRows' => $this->ageDistribution(clone $base),
            'channelRows' => $this->distribution(
                clone $base,
                "COALESCE(NULLIF(customers.registration_channel::text, ''), 'Belum diisi')"
            ),
            'tierRows' => $this->tierDistribution(clone $base),
            'provinceRows' => $this->regionDistribution(clone $base, 'province'),
            'regencyRows' => $this->regionDistribution(clone $base, 'regency'),
            'districtRows' => $this->regionDistribution(clone $base, 'district'),
            'villageRows' => $this->regionDistribution(clone $base, 'village'),
            'sourceRows' => $this->sourceDistribution(clone $base),
            'registrationTrend' => $this->registrationTrend(clone $base),
            'birthdayCustomers' => $this->birthdayCustomers(clone $base, $birthdayMonth),
            'rows' => $this->rows($filters, $perPage),
        ];
    }

    public function exportRows(array $input): Collection
    {
        $filters = $this->normalizeFilters($input);

        return $this->baseQuery($filters)
            ->select($this->rowColumns())
            ->orderBy('customers.name')
            ->get();
    }

    private function normalizeFilters(array $input): array
    {
        $gender = strtolower(trim((string) ($input['gender'] ?? '')));
        $gender = in_array($gender, ['male', 'female'], true) ? $gender : '';

        $ageGroup = trim((string) ($input['age_group'] ?? ''));
        $ageGroup = array_key_exists($ageGroup, self::AGE_GROUPS) ? $ageGroup : '';

        $birthMonth = (int) ($input['birth_month'] ?? 0);
        $birthMonth = $birthMonth >= 1 && $birthMonth <= 12 ? (string) $birthMonth : '';

        $membership = trim((string) ($input['membership'] ?? ''));

        if (
            $membership !== 'none'
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $membership) !== 1
        ) {
            $membership = '';
        }

        $isGuest = trim((string) ($input['is_guest'] ?? ''));
        $isGuest = in_array($isGuest, ['yes', 'no'], true) ? $isGuest : '';

        $sort = trim((string) ($input['sort'] ?? ''));
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'name';

        return [
            'q' => trim((string) ($input['q'] ?? '')),
            'gender' => $gender,
            'age_group' => $ageGroup,
            'birth_month' => $birthMonth,
            'registration_channel' => trim((string) ($input['registration_channel'] ?? '')),
            'membership' => $membership,
            'store_id' => trim((string) ($input['store_id'] ?? '')),
            'province_code' => trim((string) ($input['province_code'] ?? '')),
            'regency_code' => trim((string) ($input['regency_code'] ?? '')),
            'district_code' => trim((string) ($input['district_code'] ?? '')),
            'village_code' => trim((string) ($input['village_code'] ?? '')),
            'is_guest' => $isGuest,
            'joined_from' => $this->normalizeDate($input['joined_from'] ?? null),
            'joined_to' => $this->normalizeDate($input['joined_to'] ?? null),
            'sort' => $sort,
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $rankedMemberships = DB::table('customer_memberships as cm')
            ->select([
                'cm.customer_id',
                'cm.membership_id',
                'cm.status',
                'cm.total_spend_12_month',
                'cm.tier_evaluated_at',
                'cm.tier_valid_until',
                'cm.updated_at',
            ])
            ->selectRaw("
                ROW_NUMBER() OVER (
                    PARTITION BY cm.customer_id
                    ORDER BY
                        CASE WHEN cm.status::text = 'active' THEN 0 ELSE 1 END,
                        cm.tier_evaluated_at DESC NULLS LAST,
                        cm.updated_at DESC NULLS LAST
                ) AS row_rank
            ");

        $latestMemberships = DB::query()
            ->fromSub($rankedMemberships, 'ranked_memberships')
            ->where('row_rank', 1);

        $rankedPoints = DB::table('customer_loyalty_points as clp')
            ->select(['clp.customer_id', 'clp.balance'])
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY clp.customer_id ORDER BY clp.updated_at DESC NULLS LAST, clp.created_at DESC NULLS LAST) AS row_rank');

        $latestPoints = DB::query()
            ->fromSub($rankedPoints, 'ranked_points')
            ->where('row_rank', 1);

        $visitStats = DB::table('store_customer_visits as v')
            ->select('v.customer_id')
            ->selectRaw('
                COUNT(*)::int AS visit_count,
                COUNT(DISTINCT v.store_id)::int AS visited_store_count,
                MAX(COALESCE(v.check_in_at, v.created_at)) AS last_visit_at
            ')
            ->groupBy('v.customer_id');

        $posStats = DB::table('store_visit_payments as svp')
            ->join('store_customer_visits as pv', 'pv.id', '=', 'svp.visit_id')
            ->join('payments as p', 'p.id', '=', 'svp.payment_id')
            ->whereNull('p.deleted_at')
            ->whereRaw('LOWER(p.status::text) = ?', ['paid'])
            ->select('pv.customer_id')
            ->selectRaw('
                COUNT(DISTINCT p.id)::int AS pos_transactions,
                COALESCE(SUM(p.total_amount), 0)::numeric AS pos_spending
            ')
            ->groupBy('pv.customer_id');

        $ecommerceStats = DB::table('ecommerce_orders as eo')
            ->join('payments as ep', 'ep.id', '=', 'eo.payment_id')
            ->whereNull('ep.deleted_at')
            ->whereRaw('LOWER(ep.status::text) = ?', ['paid'])
            ->select('eo.customer_id')
            ->selectRaw('
                COUNT(*)::int AS ecom_orders,
                COALESCE(SUM(eo.grand_total), 0)::numeric AS ecom_spending
            ')
            ->groupBy('eo.customer_id');

        return Customer::query()
            ->leftJoinSub($latestMemberships, 'lm', fn ($join) => $join->on('lm.customer_id', '=', 'customers.id'))
            ->leftJoin('memberships as membership_tier', 'membership_tier.id', '=', 'lm.membership_id')
            ->leftJoinSub($latestPoints, 'lp', fn ($join) => $join->on('lp.customer_id', '=', 'customers.id'))
            ->leftJoinSub($visitStats, 'visit_stats', fn ($join) => $join->on('visit_stats.customer_id', '=', 'customers.id'))
            ->leftJoinSub($posStats, 'pos_stats', fn ($join) => $join->on('pos_stats.customer_id', '=', 'customers.id'))
            ->leftJoinSub($ecommerceStats, 'ecom_stats', fn ($join) => $join->on('ecom_stats.customer_id', '=', 'customers.id'))
            ->leftJoin('provinces as province', 'province.code', '=', 'customers.province_code')
            ->leftJoin('regencies as regency', 'regency.code', '=', 'customers.regency_code')
            ->leftJoin('districts as district', 'district.code', '=', 'customers.district_code')
            ->leftJoin('villages as village', 'village.code', '=', 'customers.village_code')
            ->leftJoin('stores as registration_store', DB::raw('registration_store.id::text'), '=', 'customers.registration_source_id')
            ->when($filters['q'] !== '', function (Builder $query) use ($filters) {
                $like = '%'.$filters['q'].'%';

                $query->where(function (Builder $where) use ($like) {
                    $where->where('customers.name', 'ILIKE', $like)
                        ->orWhere('customers.email', 'ILIKE', $like)
                        ->orWhere('customers.wa_number', 'ILIKE', $like)
                        ->orWhere('customers.referral_code', 'ILIKE', $like)
                        ->orWhere('customers.address', 'ILIKE', $like)
                        ->orWhere('customers.registration_source_id', 'ILIKE', $like)
                        ->orWhere('registration_store.name', 'ILIKE', $like);
                });
            })
            ->when($filters['gender'] !== '', fn (Builder $query) => $query->whereRaw("LOWER(COALESCE(customers.gender::text, '')) = ?", [$filters['gender']]))
            ->when($filters['province_code'] !== '', fn (Builder $query) => $query->where('customers.province_code', $filters['province_code']))
            ->when($filters['regency_code'] !== '', fn (Builder $query) => $query->where('customers.regency_code', $filters['regency_code']))
            ->when($filters['district_code'] !== '', fn (Builder $query) => $query->where('customers.district_code', $filters['district_code']))
            ->when($filters['village_code'] !== '', fn (Builder $query) => $query->where('customers.village_code', $filters['village_code']))
            ->when($filters['registration_channel'] !== '', fn (Builder $query) => $query->whereRaw('customers.registration_channel::text = ?', [$filters['registration_channel']]))
            ->when($filters['birth_month'] !== '', fn (Builder $query) => $query->whereRaw('EXTRACT(MONTH FROM customers.date_of_birth) = ?', [(int) $filters['birth_month']]))
            ->when($filters['membership'] === 'none', fn (Builder $query) => $query->whereNull('lm.membership_id'))
            ->when($filters['membership'] !== '' && $filters['membership'] !== 'none', fn (Builder $query) => $query->where('lm.membership_id', $filters['membership']))
            ->when($filters['store_id'] !== '', fn (Builder $query) => $query->where('customers.registration_source_id', $filters['store_id']))
            ->when($filters['is_guest'] === 'yes', fn (Builder $query) => $query->whereRaw('customers.is_guest IS TRUE'))
            ->when($filters['is_guest'] === 'no', fn (Builder $query) => $query->whereRaw('customers.is_guest IS NOT TRUE'))
            ->when($filters['joined_from'] !== '', fn (Builder $query) => $query->whereDate('customers.created_at', '>=', $filters['joined_from']))
            ->when($filters['joined_to'] !== '', fn (Builder $query) => $query->whereDate('customers.created_at', '<=', $filters['joined_to']))
            ->when($filters['age_group'] !== '', function (Builder $query) use ($filters) {
                $ageSql = 'EXTRACT(YEAR FROM AGE(CURRENT_DATE, customers.date_of_birth))';

                match ($filters['age_group']) {
                    'under_18' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} < 18"),
                    '18_24' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} BETWEEN 18 AND 24"),
                    '25_34' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} BETWEEN 25 AND 34"),
                    '35_44' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} BETWEEN 35 AND 44"),
                    '45_54' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} BETWEEN 45 AND 54"),
                    '55_plus' => $query->whereNotNull('customers.date_of_birth')->whereRaw("{$ageSql} >= 55"),
                    'unknown' => $query->whereNull('customers.date_of_birth'),
                    default => null,
                };
            });
    }

    private function summary(Builder $query, int $birthdayMonth): array
    {
        $row = $query->selectRaw('
            COUNT(*)::int AS total_customers,
            COUNT(*) FILTER (WHERE customers.is_guest IS NOT TRUE)::int AS registered_customers,
            COUNT(*) FILTER (WHERE customers.is_guest IS TRUE)::int AS guest_customers,
            COUNT(*) FILTER (WHERE customers.date_of_birth IS NOT NULL)::int AS with_birth_date,
            COUNT(*) FILTER (WHERE customers.province_code IS NOT NULL)::int AS with_location,
            COUNT(*) FILTER (
                WHERE customers.date_of_birth IS NOT NULL
                AND EXTRACT(MONTH FROM customers.date_of_birth) = ?
            )::int AS birthday_this_month,
            COUNT(*) FILTER (
                WHERE customers.email IS NOT NULL
                AND customers.date_of_birth IS NOT NULL
                AND customers.gender IS NOT NULL
                AND customers.province_code IS NOT NULL
            )::int AS complete_profiles,
            COUNT(*) FILTER (WHERE lm.membership_id IS NOT NULL)::int AS member_customers,
            COALESCE(AVG(
                CASE
                    WHEN customers.date_of_birth IS NOT NULL
                    THEN EXTRACT(YEAR FROM AGE(CURRENT_DATE, customers.date_of_birth))
                END
            ), 0)::numeric AS average_age,
            COALESCE(SUM(COALESCE(visit_stats.visit_count, 0)), 0)::int AS total_visits,
            COALESCE(SUM(COALESCE(pos_stats.pos_transactions, 0)), 0)::int AS pos_transactions,
            COALESCE(SUM(COALESCE(pos_stats.pos_spending, 0)), 0)::numeric AS pos_spending,
            COALESCE(SUM(COALESCE(ecom_stats.ecom_orders, 0)), 0)::int AS ecom_orders,
            COALESCE(SUM(COALESCE(ecom_stats.ecom_spending, 0)), 0)::numeric AS ecom_spending,
            COALESCE(SUM(COALESCE(pos_stats.pos_spending, 0) + COALESCE(ecom_stats.ecom_spending, 0)), 0)::numeric AS total_spending
        ', [$birthdayMonth])->first();

        $totalCustomers = (int) ($row->total_customers ?? 0);
        $completeProfiles = (int) ($row->complete_profiles ?? 0);

        return [
            'totalCustomers' => $totalCustomers,
            'registeredCustomers' => (int) ($row->registered_customers ?? 0),
            'guestCustomers' => (int) ($row->guest_customers ?? 0),
            'withBirthDate' => (int) ($row->with_birth_date ?? 0),
            'withLocation' => (int) ($row->with_location ?? 0),
            'birthdayThisMonth' => (int) ($row->birthday_this_month ?? 0),
            'completeProfiles' => $completeProfiles,
            'profileCompletion' => $totalCustomers > 0
                ? (int) round(($completeProfiles / $totalCustomers) * 100)
                : 0,
            'memberCustomers' => (int) ($row->member_customers ?? 0),
            'averageAge' => (float) round((float) ($row->average_age ?? 0), 1),
            'totalVisits' => (int) ($row->total_visits ?? 0),
            'posTransactions' => (int) ($row->pos_transactions ?? 0),
            'posSpending' => (float) ($row->pos_spending ?? 0),
            'ecomOrders' => (int) ($row->ecom_orders ?? 0),
            'ecomSpending' => (float) ($row->ecom_spending ?? 0),
            'totalSpending' => (float) ($row->total_spending ?? 0),
        ];
    }

    private function genderDistribution(Builder $query): Collection
    {
        $expression = "CASE LOWER(COALESCE(customers.gender::text, ''))
            WHEN 'male' THEN 'Laki-laki'
            WHEN 'm' THEN 'Laki-laki'
            WHEN 'laki-laki' THEN 'Laki-laki'
            WHEN 'female' THEN 'Perempuan'
            WHEN 'f' THEN 'Perempuan'
            WHEN 'perempuan' THEN 'Perempuan'
            ELSE 'Belum diisi'
        END";

        $rows = $query
            ->selectRaw("{$expression} AS label, COUNT(*)::int AS total")
            ->groupByRaw($expression)
            ->get()
            ->keyBy('label');

        return collect(['Laki-laki', 'Perempuan', 'Belum diisi'])
            ->map(fn (string $label) => [
                'label' => $label,
                'total' => (int) ($rows->get($label)->total ?? 0),
            ])
            ->values();
    }

    private function ageDistribution(Builder $query): Collection
    {
        $ageSql = 'EXTRACT(YEAR FROM AGE(CURRENT_DATE, customers.date_of_birth))';
        $expression = "CASE
            WHEN customers.date_of_birth IS NULL THEN 'Usia belum diisi'
            WHEN {$ageSql} < 18 THEN '< 18 tahun'
            WHEN {$ageSql} BETWEEN 18 AND 24 THEN '18–24 tahun'
            WHEN {$ageSql} BETWEEN 25 AND 34 THEN '25–34 tahun'
            WHEN {$ageSql} BETWEEN 35 AND 44 THEN '35–44 tahun'
            WHEN {$ageSql} BETWEEN 45 AND 54 THEN '45–54 tahun'
            ELSE '55+ tahun'
        END";

        $rows = $query
            ->selectRaw("{$expression} AS label, COUNT(*)::int AS total")
            ->groupByRaw($expression)
            ->get()
            ->keyBy('label');

        return collect(self::AGE_GROUPS)
            ->values()
            ->map(fn (string $label) => [
                'label' => $label,
                'total' => (int) ($rows->get($label)->total ?? 0),
            ])
            ->values();
    }

    private function distribution(Builder $query, string $expression, ?int $limit = null): Collection
    {
        return $query
            ->selectRaw("{$expression} AS label, COUNT(*)::int AS total")
            ->groupByRaw($expression)
            ->orderByDesc('total')
            ->when($limit !== null, fn ($builder) => $builder->limit($limit))
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values();
    }

    private function tierDistribution(Builder $query): Collection
    {
        $expression = "COALESCE(membership_tier.name, 'Belum memiliki tier')";

        return $query
            ->selectRaw("
                {$expression} AS label,
                COUNT(*)::int AS total,
                MIN(COALESCE(membership_tier.sort_order, 9999))::int AS sort_order
            ")
            ->groupByRaw($expression)
            ->orderBy('sort_order')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values();
    }

    private function regionDistribution(Builder $query, string $alias): Collection
    {
        return $this->distribution(
            $query,
            "COALESCE(NULLIF({$alias}.name, ''), 'Belum diisi')",
            10
        );
    }

    private function sourceDistribution(Builder $query): Collection
    {
        return $this->distribution(
            $query,
            $this->registrationSourceLabelSql(),
            10
        );
    }

    private function registrationSourceLabelSql(): string
    {
        return "COALESCE(
            NULLIF(registration_store.name, ''),
            CASE
                WHEN LOWER(COALESCE(customers.registration_source_id::text, '')) LIKE 'android%' THEN 'Aplikasi Mobile'
                WHEN LOWER(COALESCE(customers.registration_source_id::text, '')) LIKE 'ios%' THEN 'Aplikasi Mobile'
                WHEN LOWER(COALESCE(customers.registration_source_id::text, '')) LIKE 'web%' THEN 'Website'
                ELSE NULLIF(customers.registration_source_id::text, '')
            END
        )";
    }

    private function registrationTrend(Builder $query): Collection
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(11);

        $rows = $query
            ->selectRaw("TO_CHAR(DATE_TRUNC('month', customers.created_at), 'YYYY-MM') AS period, COUNT(*)::int AS total")
            ->where('customers.created_at', '>=', $start)
            ->groupByRaw("DATE_TRUNC('month', customers.created_at)")
            ->get()
            ->keyBy('period');

        return collect(range(0, 11))
            ->map(function (int $offset) use ($start, $rows) {
                $month = $start->addMonths($offset);
                $period = $month->format('Y-m');

                return [
                    'period' => $period,
                    'label' => $this->monthLabel((int) $month->month).' '.$month->format('Y'),
                    'total' => (int) ($rows->get($period)->total ?? 0),
                ];
            })
            ->values();
    }

    private function birthdayCustomers(Builder $query, int $birthdayMonth): Collection
    {
        return $query
            ->whereRaw('EXTRACT(MONTH FROM customers.date_of_birth) = ?', [$birthdayMonth])
            ->select([
                'customers.id',
                'customers.name',
                'customers.wa_number',
                'customers.email',
                'customers.date_of_birth',
                'province.name as province_name',
                'regency.name as regency_name',
                'membership_tier.name as membership_name',
            ])
            ->orderByRaw('EXTRACT(DAY FROM customers.date_of_birth)')
            ->limit(12)
            ->get();
    }

    private function rows(array $filters, int $perPage): LengthAwarePaginator
    {
        [$column, $direction] = self::SORTS[$filters['sort']];

        $query = $this->baseQuery($filters)
            ->select($this->rowColumns());

        if ($filters['sort'] === 'visits') {
            $query->orderByRaw('COALESCE(visit_stats.visit_count, 0) DESC NULLS LAST');
        } elseif ($filters['sort'] === 'last_visit') {
            $query->orderByRaw('visit_stats.last_visit_at DESC NULLS LAST');
        } elseif ($filters['sort'] === 'points') {
            $query->orderByRaw('COALESCE(lp.balance, 0) DESC');
        } elseif ($filters['sort'] === 'spending') {
            $query->orderByRaw('(COALESCE(pos_stats.pos_spending, 0) + COALESCE(ecom_stats.ecom_spending, 0)) DESC');
        } elseif ($column === 'customers.date_of_birth') {
            $query->orderByRaw("customers.date_of_birth {$direction} NULLS LAST");
        } else {
            $query->orderBy($column, $direction);
        }

        return $query
            ->orderBy('customers.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function rowColumns(): array
    {
        $ageSql = 'EXTRACT(YEAR FROM AGE(CURRENT_DATE, customers.date_of_birth))';
        $ageGroupSql = "CASE
            WHEN customers.date_of_birth IS NULL THEN 'Usia belum diisi'
            WHEN {$ageSql} < 18 THEN '< 18 tahun'
            WHEN {$ageSql} BETWEEN 18 AND 24 THEN '18–24 tahun'
            WHEN {$ageSql} BETWEEN 25 AND 34 THEN '25–34 tahun'
            WHEN {$ageSql} BETWEEN 35 AND 44 THEN '35–44 tahun'
            WHEN {$ageSql} BETWEEN 45 AND 54 THEN '45–54 tahun'
            ELSE '55+ tahun'
        END";

        return [
            'customers.*',
            'province.name as province_name',
            'regency.name as regency_name',
            'district.name as district_name',
            'village.name as village_name',
            DB::raw($this->registrationSourceLabelSql().' as registration_source_name'),
            'membership_tier.name as membership_name',
            'membership_tier.sort_order as membership_sort_order',
            'lm.status as membership_status',
            'lm.total_spend_12_month as membership_total_spend_12_month',
            'lm.tier_valid_until as membership_valid_until',
            'lm.tier_evaluated_at as membership_evaluated_at',
            DB::raw('COALESCE(lp.balance, 0) AS loyalty_points'),
            DB::raw('COALESCE(visit_stats.visit_count, 0) AS visit_count'),
            DB::raw('COALESCE(visit_stats.visited_store_count, 0) AS visited_store_count'),
            'visit_stats.last_visit_at',
            DB::raw('COALESCE(pos_stats.pos_transactions, 0) AS pos_transactions'),
            DB::raw('COALESCE(pos_stats.pos_spending, 0) AS pos_spending'),
            DB::raw('COALESCE(ecom_stats.ecom_orders, 0) AS ecom_orders'),
            DB::raw('COALESCE(ecom_stats.ecom_spending, 0) AS ecom_spending'),
            DB::raw('(COALESCE(pos_stats.pos_spending, 0) + COALESCE(ecom_stats.ecom_spending, 0)) AS total_spending'),
            DB::raw("CASE WHEN customers.date_of_birth IS NULL THEN NULL ELSE {$ageSql}::int END AS age"),
            DB::raw("{$ageGroupSql} AS age_group"),
        ];
    }

    private function channelOptions(): array
    {
        return Customer::query()
            ->whereRaw("NULLIF(registration_channel::text, '') IS NOT NULL")
            ->selectRaw('DISTINCT registration_channel::text as registration_channel')
            ->orderByRaw('registration_channel::text')
            ->pluck('registration_channel')
            ->values()
            ->all();
    }

    private function memberships(): Collection
    {
        return DB::table('memberships')
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function stores(): Collection
    {
        return DB::table('stores')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    private function provinces(): Collection
    {
        return DB::table('provinces')
            ->orderBy('name')
            ->get(['code', 'name']);
    }

    private function selectedRegions(array $filters): array
    {
        $selected = [
            'regency' => null,
            'district' => null,
            'village' => null,
        ];

        if ($filters['regency_code'] !== '') {
            $selected['regency'] = DB::table('regencies')
                ->where('code', $filters['regency_code'])
                ->first(['code', 'name']);
        }

        if ($filters['district_code'] !== '') {
            $selected['district'] = DB::table('districts')
                ->where('code', $filters['district_code'])
                ->first(['code', 'name']);
        }

        if ($filters['village_code'] !== '') {
            $selected['village'] = DB::table('villages')
                ->where('code', $filters['village_code'])
                ->first(['code', 'name']);
        }

        return $selected;
    }

    private function normalizeDate(mixed $value): string
    {
        $value = trim((string) $value);

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) !== 1) {
            return '';
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        return checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : '';
    }

    private function monthLabel(int $month): string
    {
        $labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $labels[max(0, min(11, $month - 1))];
    }
}
