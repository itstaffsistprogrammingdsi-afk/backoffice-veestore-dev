<?php

namespace App\Domain\Loyalty\Queries;

use App\Models\CustomerLoyaltyPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerLoyaltyPointMutationQuery
{
    private const TYPE_LABELS = [
        'earn' => 'Earn',
        'redeem' => 'Redeem',
        'expire' => 'Expire',
        'adjust' => 'Adjustment',
        'referral_register' => 'Referral Register',
        'referral_first_tx' => 'Referral First Transaction',
        'void_reversal' => 'Void Reversal',
    ];

    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));

        $paginator = DB::table('customer_loyalty_point_mutations as clpm')
            ->join('customers as c', 'c.id', '=', 'clpm.customer_id')
            ->leftJoin('customer_loyalty_points as clp', 'clp.id', '=', 'clpm.customer_loyalty_point_id')
            ->when($search !== '', function ($query) use ($search): void {
                $like = "%{$search}%";

                $query->where(function ($inner) use ($like): void {
                    $inner->where('c.name', 'ILIKE', $like)
                        ->orWhere('c.email', 'ILIKE', $like)
                        ->orWhere('c.wa_number', 'ILIKE', $like)
                        ->orWhere('clpm.description', 'ILIKE', $like)
                        ->orWhere('clpm.ref', 'ILIKE', $like);
                });
            })
            ->when($type !== '', fn ($query) => $query->whereRaw('clpm.type::text = ?', [$type]))
            ->when(! empty($filters['clp']), fn ($query) => $query->where('clpm.customer_loyalty_point_id', $filters['clp']))
            ->when($filters['from'] ?? null, fn ($query) => $query->whereDate('clpm.created_at', '>=', $filters['from']))
            ->when($filters['to'] ?? null, fn ($query) => $query->whereDate('clpm.created_at', '<=', $filters['to']))
            ->orderByDesc('clpm.created_at')
            ->select([
                'clpm.id',
                'clpm.customer_id',
                'clpm.customer_loyalty_point_id',
                'clpm.type',
                'clpm.mutation_type',
                'clpm.points',
                'clpm.balance_after',
                'clpm.used_points',
                'clpm.description',
                'clpm.ref',
                'clpm.ref_id',
                'clpm.created_at',
                'clpm.expired_at',
                'clpm.has_expiration',
                'c.name as customer_name',
                'c.email as customer_email',
                'c.wa_number as customer_wa_number',
                'clp.balance as current_balance',
            ])
            ->paginate($perPage)
            ->withQueryString();

        $paginator->getCollection()->transform(function (object $row): object {
            $row->type_label = self::TYPE_LABELS[$row->type] ?? ucfirst((string) $row->type);
            $row->points = (float) ($row->points ?? 0);
            $row->balance_after = (float) ($row->balance_after ?? 0);
            $row->used_points = (float) ($row->used_points ?? 0);
            $row->current_balance = (float) ($row->current_balance ?? 0);
            $row->has_expiration = (bool) $row->has_expiration;

            return $row;
        });

        return $paginator;
    }

    public function customerPoints(int $limit = 100): Collection
    {
        return CustomerLoyaltyPoint::query()
            ->with('customer')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    public function typeOptions(): array
    {
        return DB::table('customer_loyalty_point_mutations')
            ->selectRaw('type::text as type')
            ->whereNotNull('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type')
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => self::TYPE_LABELS[$type] ?? ucfirst($type),
            ])
            ->values()
            ->all();
    }
}
