<?php

namespace App\Models\Analytics;

use App\Models\Store;

class SourceStore extends Store
{
    protected $connection = 'pgsql';
    protected $table = 'stores';
}
