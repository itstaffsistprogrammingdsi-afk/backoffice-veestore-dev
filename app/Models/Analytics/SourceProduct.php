<?php

namespace App\Models\Analytics;

use App\Models\Product;

class SourceProduct extends Product
{
    protected $connection = 'pgsql';
    protected $table = 'products';
}
