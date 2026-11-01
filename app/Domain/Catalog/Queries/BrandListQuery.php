<?php

namespace App\Domain\Catalog\Queries;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandListQuery
{
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        $search = $this->normalizeSearch($search);

        return Brand::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'ILIKE', '%'.$search.'%'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function normalizeSearch(?string $search): string
    {
        $search = trim((string) $search);

        return preg_replace('/\s+/u', ' ', $search) ?? $search;
    }
}
