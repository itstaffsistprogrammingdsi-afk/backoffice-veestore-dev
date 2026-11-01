<?php

namespace Tests\Feature\Management;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithBackofficeAdmin;
use Tests\TestCase;

class ManagementPagesTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBackofficeAdmin;

    public function test_management_pages_use_inertia(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('management.roles-backoffice.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/Roles')
                ->has('roles')
                ->has('filters')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('management.user-roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/UserRoles')
                ->has('users')
                ->has('roles')
                ->has('userRoleId')
                ->has('urls'));

        $this->actingAs($admin)
            ->get(route('management.role-permissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/RolePermissions')
                ->has('roles')
                ->has('permissionGroups')
                ->has('rolePermissions')
                ->has('updateUrl'));

        $this->actingAs($admin)
            ->get(route('management.request-labels.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Management/LabelRequests')
                ->has('rows')
                ->has('stores')
                ->has('filters')
                ->has('urls'));
    }
}
