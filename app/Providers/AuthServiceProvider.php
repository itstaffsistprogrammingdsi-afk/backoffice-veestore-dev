<?php

namespace App\Providers;

use App\Domain\Catalog\Policies\BrandPolicy;
use App\Models\Brand;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Brand::class => BrandPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ✅ Bypass semua ability untuk pengguna dengan role Superadmin/super-admin
        // (tetap gunakan guard & koneksi yang sudah kamu set di Spatie Permission)
        Gate::before(function ($user, string $ability) {
            // method_exists agar aman jika someday user tidak memakai HasRoles
            if (method_exists($user, 'hasRole') &&
                $user->hasRole(['Superadmin', 'super-admin'])) {
                return true; // langsung lolos semua gate/permission
            }

            return null; // lanjut ke pengecekan permission biasa
        });
    }
}
