<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait InteractsWithBackofficeAdmin
{
    protected function admin(): User
    {
        $id = DB::connection('immostore_backoffice')
            ->table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'Superadmin')
            ->value('model_has_roles.model_id');

        return User::query()->findOrFail($id);
    }
}
