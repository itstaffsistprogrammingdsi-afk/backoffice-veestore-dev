<?php

namespace App\Models\Analytics;

use App\Models\Warehouse;

class SourceWarehouse extends Warehouse
{
    protected $connection = 'pgsql';
    protected $table = 'warehouses';
}
