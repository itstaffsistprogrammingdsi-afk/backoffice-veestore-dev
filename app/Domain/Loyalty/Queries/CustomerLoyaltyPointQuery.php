<?php

namespace App\Domain\Loyalty\Queries;

use App\Models\CustomerLoyaltyPoint;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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

    public function summary(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $earned = DB::table('customer_loyalty_point_mutations')
            ->where('points', '>', 0)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('points');

        $redeemed = DB::table('customer_loyalty_point_mutations')
            ->where('points', '<', 0)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('points');

        return [
            'members' => (int) CustomerLoyaltyPoint::query()->count(),
            'total_balance' => (float) CustomerLoyaltyPoint::query()->sum('balance'),
            'earned_this_month' => (float) abs($earned),
            'redeemed_this_month' => (float) abs($redeemed),
        ];
    }
}
