<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Exceptions\BrandInUseException;
use App\Domain\Catalog\Support\BrandLogoStorage;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteBrand
{
    public function __construct(private readonly BrandLogoStorage $logos) {}

    public function handle(Brand $brand): void
    {
        $usedCount = Product::query()
            ->where('brand_id', $brand->getKey())
            ->count();

        if ($usedCount > 0) {
            throw new BrandInUseException($brand->name, $usedCount);
        }

        DB::transaction(function () use ($brand) {
            $logoUrl = $brand->image_url;
            $brand->delete();
            $this->logos->delete($logoUrl);
        });
    }
}
