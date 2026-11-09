<?php

namespace App\Domain\Promotions\Queries;

use Illuminate\Support\Facades\DB;

class DiscountSyncQuery
{
    public function build(string $q, ?string $channel, ?string $active, int $perPage = 10): array
    {
        $base = $this->groupedQuery($q, $active);

        $grouped = DB::query()->fromSub($base, 'groups');

        if ($channel === 'mismatch') {
            $grouped->whereRaw('groups.has_pos AND groups.has_ecommerce AND groups.pos_cap IS DISTINCT FROM groups.ecommerce_cap');
        } elseif ($channel === 'pos') {
            $grouped->whereRaw('groups.has_pos AND NOT groups.has_ecommerce');
        } elseif ($channel === 'ecommerce') {
            $grouped->whereRaw('groups.has_ecommerce AND NOT groups.has_pos');
        } elseif ($channel === 'global') {
            $grouped->where('groups.has_global', true);
        } elseif ($channel === 'unmapped') {
            $grouped->where('groups.has_unmapped', true);
        }

        $rows = $grouped
            ->orderByDesc('groups.has_active')
            ->orderBy('groups.promo_name')
            ->paginate($perPage)
            ->withQueryString();

        $details = $this->discountDetails($rows->getCollection());
        $drift = $this->driftCounts($rows->getCollection()->pluck('group_key')->all());

        $rows->getCollection()->transform(function (object $row) use ($details, $drift): object {
            $row->discount_count = (int) $row->discount_count;
            $row->product_count = (int) $row->product_count;
            $row->has_pos = (bool) $row->has_pos;
            $row->has_ecommerce = (bool) $row->has_ecommerce;
            $row->has_global = (bool) $row->has_global;
            $row->has_unmapped = (bool) $row->has_unmapped;
            $row->has_active = (bool) $row->has_active;
            $row->pos_cap = $row->pos_cap !== null ? (float) $row->pos_cap : null;
            $row->ecommerce_cap = $row->ecommerce_cap !== null ? (float) $row->ecommerce_cap : null;
            $row->max_cap = $row->max_cap !== null ? (float) $row->max_cap : null;
            $row->max_percent = $row->max_percent !== null ? (float) $row->max_percent : null;
            $row->max_fixed = $row->max_fixed !== null ? (float) $row->max_fixed : null;
            $row->mismatch = $row->has_pos && $row->has_ecommerce
                && $this->capsDiffer($row->pos_cap, $row->ecommerce_cap);
            $row->channel_label = $this->channelLabel($row);
            $row->pos_cap_label = $row->pos_cap !== null ? $row->pos_cap : null;
            $row->ecommerce_cap_label = $row->ecommerce_cap !== null ? $row->ecommerce_cap : null;
            $row->discounts = $details->get($row->group_key, []);
            $row->ecommerce_drift = (int) ($drift['ecommerce'][$row->group_key] ?? 0);
            $row->pos_cap_violations = (int) ($drift['pos'][$row->group_key] ?? 0);

            return $row;
        });

        return [
            'rows' => $rows,
            'summary' => $this->summary($base),
            'filters' => [
                'q' => $q,
                'channel' => $channel,
                'active' => $active,
                'per_page' => $perPage,
            ],
            'channelOptions' => [
                ['value' => '', 'label' => 'Semua kanal'],
                ['value' => 'mismatch', 'label' => 'Max diskon berbeda'],
                ['value' => 'pos', 'label' => 'Hanya POS'],
                ['value' => 'ecommerce', 'label' => 'Hanya E-commerce'],
                ['value' => 'global', 'label' => 'Global (POS + E-commerce)'],
                ['value' => 'unmapped', 'label' => 'Belum dipetakan ke produk'],
            ],
            'activeOptions' => [
                ['value' => '', 'label' => 'Semua status'],
                ['value' => '1', 'label' => 'Aktif'],
                ['value' => '0', 'label' => 'Tidak aktif'],
            ],
        ];
    }

    private function groupedQuery(string $q, ?string $active)
    {
        return DB::table('discounts as d')
            ->leftJoin('promos as p', 'p.id', '=', 'd.promo_id')
            ->leftJoin('discount_products as dp', 'dp.discount_id', '=', 'd.id')
            ->whereNull('p.deleted_at')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('p.name', 'ILIKE', "%{$q}%")
                        ->orWhere('p.code', 'ILIKE', "%{$q}%")
                        ->orWhere('d.name', 'ILIKE', "%{$q}%");
                });
            })
            ->when($active === '1', fn ($query) => $query->where('d.is_active', true))
            ->when($active === '0', fn ($query) => $query->where('d.is_active', false))
            ->groupByRaw("COALESCE(d.promo_id::text, d.id::text), d.promo_id, COALESCE(p.name, d.name), COALESCE(p.code, '-')")
            ->selectRaw("
                COALESCE(d.promo_id::text, d.id::text) as group_key,
                d.promo_id,
                COALESCE(p.name, d.name) as promo_name,
                COALESCE(p.code, '-') as promo_code,
                COUNT(DISTINCT d.id)::int as discount_count,
                COUNT(DISTINCT dp.product_id)::int as product_count,
                COALESCE(BOOL_OR(dp.applied_to::text IN ('store', 'global')), false) as has_pos,
                COALESCE(BOOL_OR(dp.applied_to::text IN ('ecommerce', 'global')), false) as has_ecommerce,
                COALESCE(BOOL_OR(dp.applied_to::text = 'global'), false) as has_global,
                COALESCE(BOOL_OR(dp.applied_to IS NULL), false) as has_unmapped,
                COALESCE(BOOL_OR(d.is_active), false) as has_active,
                MAX(CASE WHEN d.type::text = 'percentage' THEN d.max_discount END) as max_cap,
                MAX(CASE WHEN d.type::text = 'percentage' AND dp.applied_to::text IN ('store', 'global') THEN d.max_discount END) as pos_cap,
                MAX(CASE WHEN d.type::text = 'percentage' AND dp.applied_to::text IN ('ecommerce', 'global') THEN d.max_discount END) as ecommerce_cap,
                MAX(CASE WHEN d.type::text = 'percentage' THEN d.value END) as max_percent,
                MAX(CASE WHEN d.type::text = 'fixed' THEN d.value END) as max_fixed,
                STRING_AGG(DISTINCT d.name, ', ') as discount_names,
                MAX(d.updated_at) as last_updated
            ");
    }

    private function discountDetails($rows)
    {
        $promoIds = $rows->pluck('promo_id')->filter()->unique()->values()->all();
        $discountIds = $rows->filter(fn ($row) => $row->promo_id === null)->pluck('group_key')->filter()->values()->all();

        if ($promoIds === [] && $discountIds === []) {
            return collect();
        }

        return DB::table('discounts as d')
            ->leftJoin('discount_products as dp', 'dp.discount_id', '=', 'd.id')
            ->where(function ($query) use ($promoIds, $discountIds): void {
                if ($promoIds !== []) {
                    $query->whereIn('d.promo_id', $promoIds);
                }

                if ($discountIds !== []) {
                    if ($promoIds !== []) {
                        $query->orWhereIn('d.id', $discountIds);
                    } else {
                        $query->whereIn('d.id', $discountIds);
                    }
                }
            })
            ->groupBy('d.id', 'd.name', 'd.type', 'd.value', 'd.max_discount', 'd.is_active', 'd.promo_id')
            ->selectRaw("
                COALESCE(d.promo_id::text, d.id::text) as group_key,
                d.id,
                d.name,
                d.type::text as type,
                d.value,
                d.max_discount,
                d.is_active,
                COALESCE(BOOL_OR(dp.applied_to::text IN ('store', 'global')), false) as has_pos,
                COALESCE(BOOL_OR(dp.applied_to::text IN ('ecommerce', 'global')), false) as has_ecommerce,
                COUNT(DISTINCT dp.product_id)::int as product_count
            ")
            ->orderBy('d.name')
            ->get()
            ->groupBy('group_key')
            ->map(fn ($items) => $items->map(fn (object $item): array => [
                'id' => (string) $item->id,
                'name' => (string) $item->name,
                'type' => (string) $item->type,
                'value' => (float) $item->value,
                'max_discount' => $item->max_discount !== null ? (float) $item->max_discount : null,
                'is_active' => (bool) $item->is_active,
                'has_pos' => (bool) $item->has_pos,
                'has_ecommerce' => (bool) $item->has_ecommerce,
                'product_count' => (int) $item->product_count,
            ])->values()->all());
    }

    /**
     * @param  array<int, string>  $groupKeys
     * @return array{ecommerce: array<string, int>, pos: array<string, int>}
     */
    private function driftCounts(array $groupKeys): array
    {
        if ($groupKeys === []) {
            return ['ecommerce' => [], 'pos' => []];
        }

        $since = now()->subDays(90);

        $ecommerce = DB::table('ecommerce_order_detail_discounts as snapshot')
            ->join('discounts as master', 'master.id', '=', 'snapshot.master_id')
            ->where('snapshot.created_at', '>=', $since)
            ->whereRaw('snapshot.max_discount IS DISTINCT FROM master.max_discount')
            ->selectRaw('COALESCE(master.promo_id::text, master.id::text) as group_key, COUNT(*)::int as total')
            ->groupByRaw('COALESCE(master.promo_id::text, master.id::text)')
            ->pluck('total', 'group_key')
            ->all();

        $pos = DB::table('store_cart_details as detail')
            ->join('discounts as master', 'master.id', '=', 'detail.discount_id')
            ->where('master.type', 'percentage')
            ->whereNotNull('master.max_discount')
            ->where('detail.created_at', '>=', $since)
            ->whereRaw('detail.discount_amount > master.max_discount')
            ->selectRaw('COALESCE(master.promo_id::text, master.id::text) as group_key, COUNT(*)::int as total')
            ->groupByRaw('COALESCE(master.promo_id::text, master.id::text)')
            ->pluck('total', 'group_key')
            ->all();

        return [
            'ecommerce' => array_intersect_key($ecommerce, array_flip($groupKeys)),
            'pos' => array_intersect_key($pos, array_flip($groupKeys)),
        ];
    }

    private function summary($base): object
    {
        $summary = DB::query()
            ->fromSub($base, 'groups')
            ->selectRaw('
                COUNT(*)::int as total_groups,
                COUNT(*) FILTER (WHERE groups.has_pos AND groups.has_ecommerce AND groups.pos_cap IS DISTINCT FROM groups.ecommerce_cap)::int as mismatch_groups,
                COUNT(*) FILTER (WHERE groups.has_pos AND NOT groups.has_ecommerce)::int as pos_only_groups,
                COUNT(*) FILTER (WHERE groups.has_ecommerce AND NOT groups.has_pos)::int as ecommerce_only_groups,
                COUNT(*) FILTER (WHERE groups.has_global)::int as global_groups,
                COUNT(*) FILTER (WHERE groups.has_unmapped)::int as unmapped_groups,
                COUNT(*) FILTER (WHERE groups.has_active)::int as active_groups,
                COALESCE(SUM(groups.discount_count), 0)::int as total_discounts,
                COALESCE(SUM(groups.product_count), 0)::int as total_products
            ')
            ->first();

        $since = now()->subDays(90);

        $summary->ecommerce_drift = (int) DB::table('ecommerce_order_detail_discounts as snapshot')
            ->join('discounts as master', 'master.id', '=', 'snapshot.master_id')
            ->where('snapshot.created_at', '>=', $since)
            ->whereRaw('snapshot.max_discount IS DISTINCT FROM master.max_discount')
            ->count();

        $summary->pos_cap_violations = (int) DB::table('store_cart_details as detail')
            ->join('discounts as master', 'master.id', '=', 'detail.discount_id')
            ->where('master.type', 'percentage')
            ->whereNotNull('master.max_discount')
            ->where('detail.created_at', '>=', $since)
            ->whereRaw('detail.discount_amount > master.max_discount')
            ->count();

        return $summary;
    }

    private function capsDiffer(?float $pos, ?float $ecommerce): bool
    {
        if ($pos === null && $ecommerce === null) {
            return false;
        }

        return $pos !== $ecommerce;
    }

    private function channelLabel(object $row): string
    {
        if ($row->has_pos && $row->has_ecommerce) {
            return $row->has_global ? 'Global' : 'POS + E-commerce';
        }

        if ($row->has_pos) {
            return 'POS';
        }

        if ($row->has_ecommerce) {
            return 'E-commerce';
        }

        return 'Belum dipetakan';
    }
}
