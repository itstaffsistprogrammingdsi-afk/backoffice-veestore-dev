<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Support\BrandLogoStorage;
use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateBrand
{
    public function __construct(private readonly BrandLogoStorage $logos) {}

    public function handle(string $name, ?UploadedFile $logo = null): Brand
    {
        return DB::transaction(function () use ($name, $logo) {
            return Brand::create([
                'name' => $name,
                'image_url' => $logo ? $this->logos->store($logo) : null,
            ]);
        });
    }
}
