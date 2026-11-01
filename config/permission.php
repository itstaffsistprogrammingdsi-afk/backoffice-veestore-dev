<?php

return [

    // Paksa semua query Spatie Permission ke koneksi ini
    'connection' => env('PERMISSION_DB_CONNECTION', 'immostore_backoffice'),

    'models' => [
        // Pakai model turunan yang sudah di-flag ke koneksi immostore_backoffice
        'permission' => App\Models\Backoffice\Permission::class,
        'role'       => App\Models\Backoffice\Role::class,
    ],

    // Guard yang dipakai user login backoffice
    'guard_name' => 'web',

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'model_morph_key' => 'model_id', // kolom uuid user
    ],

    'teams' => false,
    'display_permission_in_exception' => false,
    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => null,
    ],
];
