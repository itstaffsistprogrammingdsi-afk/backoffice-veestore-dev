<?php

namespace App\Domain\Loyalty\Queries;

use App\Models\CustomerLoyaltyPoint;
use App\Models\CustomerLoyaltyPointMutation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CustomerLoyaltyPointMutationQuery
{
    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return CustomerLoyaltyPointMutation::query()
            ->with(['loyaltyPoint.customer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('loyaltyPoint.customer', function ($customer) use ($search) {
                    $customer->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('wa_number', 'ILIKE', "%{$search}%");
                });
            })
            ->when(in_array($filters['type'] ?? null, ['in', 'out'], true), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['clp']), fn ($query) => $query->where('customer_loyalty_point_id', $filters['clp']))
            ->when($filters['from'] ?? null, fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'] ?? null, fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function customerPoints(int $limit = 50): Collection
    {
        return CustomerLoyaltyPoint::query()
            ->with('customer')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }
}
