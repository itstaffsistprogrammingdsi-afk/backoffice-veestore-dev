<?php

namespace App\Ai\Mcp\Tools;

use App\Ai\Mcp\AssistantTool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PromoLoyaltyTool extends AssistantTool
{
    public function name(): string
    {
        return 'promo_loyalty';
    }

    public function description(): string
    {
        return 'Data promo, voucher, dan loyalitas VeeStore: promo dan diskon aktif, voucher paling banyak dipakai, total diskon voucher, mutasi poin per tipe, saldo poin tertinggi, dan distribusi tier membership.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'date_from' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Default 29 hari lalu.'],
                'date_to' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Default hari ini.'],
                'focus' => [
                    'type' => 'string',
                    'enum' => ['all', 'promo', 'voucher', 'loyalty', 'membership'],
                    'default' => 'all',
                ],
                'limit' => ['type' => 'integer', 'default' => 10, 'min' => 3, 'max' => 50],
            ],
        ];
    }

    protected function execute(array $arguments): array
    {
        $from = $this->date($arguments['date_from'] ?? null) ?? CarbonImmutable::now()->subDays(29)->toDateString();
        $to = $this->date($arguments['date_to'] ?? null) ?? CarbonImmutable::now()->toDateString();
        $focus = $arguments['focus'] ?? 'all';
        $focus = in_array($focus, ['all', 'promo', 'voucher', 'loyalty', 'membership'], true) ? $focus : 'all';
        $limit = max(3, min((int) ($arguments['limit'] ?? 10), 50));

        $result = ['period' => ['date_from' => $from, 'date_to' => $to]];

        if ($focus === 'all' || $focus === 'promo') {
            $result['promo'] = $this->promo($from, $to, $limit);
        }

        if ($focus === 'all' || $focus === 'voucher') {
            $result['voucher'] = $this->voucher($from, $to, $limit);
        }

        if ($focus === 'all' || $focus === 'loyalty') {
            $result['loyalty'] = $this->loyalty($from, $to, $limit);
        }

        if ($focus === 'all' || $focus === 'membership') {
            $result['membership'] = $this->membership();
        }

        return $result;
    }

    private function promo(string $from, string $to, int $limit): array
    {
        $promos = DB::table('promos')
            ->whereNull('deleted_at')
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->orderBy('end_date')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'start_date', 'end_date', 'all_store', 'store_id'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'all_store' => (bool) $row->all_store,
                'store_ids' => $row->store_id,
            ])
            ->values()
            ->all();

        $discounts = DB::table('discounts')
            ->where('is_active', true)
            ->where('valid_from', '<=', $to.' 23:59:59')
            ->where('valid_until', '>=', $from.' 00:00:00')
            ->orderByDesc('priority')
            ->limit($limit)
            ->get(['id', 'name', 'type', 'value', 'max_discount', 'min_purchase', 'usage_limit', 'usage_count'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'type' => $row->type,
                'value' => (float) $row->value,
                'max_discount' => (float) $row->max_discount,
                'min_purchase' => (float) $row->min_purchase,
                'usage_count' => (int) $row->usage_count,
                'usage_limit' => $row->usage_limit === null ? null : (int) $row->usage_limit,
            ])
            ->values()
            ->all();

        return ['promos' => $promos, 'active_discounts' => $discounts];
    }

    private function voucher(string $from, string $to, int $limit): array
    {
        $usage = DB::table('voucher_usages as vu')
            ->join('vouchers as v', 'v.id', '=', 'vu.voucher_id')
            ->whereBetween('vu.used_at', [$this->start($from), $this->end($to)])
            ->selectRaw('v.id, v.code, v.name, v.type, COUNT(*)::int AS usage_count, COALESCE(SUM(vu.discount_amount), 0)::numeric AS discount_total')
            ->groupBy('v.id', 'v.code', 'v.name', 'v.type')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'voucher_id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'type' => $row->type,
                'usage_count' => (int) $row->usage_count,
                'discount_total' => (float) $row->discount_total,
            ])
            ->values()
            ->all();

        $totals = DB::table('voucher_usages')
            ->whereBetween('used_at', [$this->start($from), $this->end($to)])
            ->selectRaw('COUNT(*)::int AS usages, COALESCE(SUM(discount_amount), 0)::numeric AS discount_total')
            ->first();

        $active = DB::table('vouchers')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('valid_from', '<=', $to.' 23:59:59')
            ->where('valid_until', '>=', $from.' 00:00:00')
            ->count();

        return [
            'summary' => [
                'usages' => (int) ($totals->usages ?? 0),
                'discount_total' => (float) ($totals->discount_total ?? 0),
                'active_vouchers' => $active,
            ],
            'top_vouchers' => $usage,
        ];
    }

    private function loyalty(string $from, string $to, int $limit): array
    {
        $mutations = DB::table('customer_loyalty_point_mutations')
            ->whereBetween('created_at', [$this->start($from), $this->end($to)])
            ->selectRaw('LOWER(type::text) AS type, COUNT(*)::int AS mutations, COALESCE(SUM(points), 0)::numeric AS points')
            ->groupByRaw('LOWER(type::text)')
            ->orderByDesc('mutations')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'mutations' => (int) $row->mutations,
                'points' => (float) $row->points,
            ])
            ->values()
            ->all();

        $top = DB::table('customer_loyalty_points as lp')
            ->join('customers as c', 'c.id', '=', 'lp.customer_id')
            ->whereNull('c.deleted_at')
            ->orderByDesc('lp.balance')
            ->limit($limit)
            ->get(['c.id', 'c.name', 'lp.balance'])
            ->map(fn ($row) => [
                'customer_id' => $row->id,
                'customer_name' => $row->name,
                'balance' => (float) $row->balance,
            ])
            ->values()
            ->all();

        return ['mutations_by_type' => $mutations, 'top_balances' => $top];
    }

    private function membership(): array
    {
        return DB::table('customer_memberships as cm')
            ->join('memberships as m', 'm.id', '=', 'cm.membership_id')
            ->whereNull('m.deleted_at')
            ->whereRaw("LOWER(cm.status::text) = 'active'")
            ->selectRaw('m.name, COUNT(*)::int AS customers, COALESCE(AVG(cm.total_spend_12_month), 0)::numeric AS average_spend')
            ->groupBy('m.name')
            ->orderByDesc('customers')
            ->get()
            ->map(fn ($row) => [
                'tier' => $row->name,
                'customers' => (int) $row->customers,
                'average_spend' => (float) $row->average_spend,
            ])
            ->values()
            ->all();
    }

    private function start(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->startOfDay();
    }

    private function end(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->endOfDay();
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
