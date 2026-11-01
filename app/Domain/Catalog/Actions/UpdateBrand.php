<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Support\BrandLogoStorage;
use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateBrand
{
    public function __construct(private readonly BrandLogoStorage $logos) {}

    public function handle(Brand $brand, string $name, ?UploadedFile $logo = null): Brand
    {
        return DB::transaction(function () use ($brand, $name, $logo) {
            $logoUrl = $brand->image_url;

            if ($logo) {
                $newLogoUrl = $this->logos->store($logo);
                $this->logos->delete($brand->image_url);
                $logoUrl = $newLogoUrl;
            }

            $brand->update([
                'name' => $name,
                'image_url' => $logoUrl,
            ]);

            return $brand->refresh();
        });
    }
}
