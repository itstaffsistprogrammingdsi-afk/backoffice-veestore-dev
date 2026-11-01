<?php

namespace App\Domain\Catalog\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view master-data-brands');
    }

    public function create(User $user): bool
    {
        return $user->can('create master-data-brands');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('update master-data-brands');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('delete master-data-brands');
    }
}
