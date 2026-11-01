<?php

namespace App\Domain\Loyalty\Queries;

use App\Models\CustomerLoyaltyPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerLoyaltyPointQuery
{
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return CustomerLoyaltyPoint::query()
            ->with(['customer'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('customer', function ($customer) use ($search) {
                    $customer->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhere('wa_number', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
