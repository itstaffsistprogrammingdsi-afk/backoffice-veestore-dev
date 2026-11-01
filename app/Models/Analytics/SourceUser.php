<?php

namespace App\Models\Analytics;

use App\Models\User;

class SourceUser extends User
{
    protected $connection = 'pgsql';
    protected $table = 'users';
}
