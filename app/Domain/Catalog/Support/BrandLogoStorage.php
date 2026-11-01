<?php

namespace App\Domain\Catalog\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class BrandLogoStorage
{
    public function store(UploadedFile $file): string
    {
        $extension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'jpg'
        );

        $fileName = (string) Str::uuid().'.'.$extension;
        $relativeDirectory = 'uploads/brands';
        $absoluteDirectory = public_path($relativeDirectory);

        if (! is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0755, true);
        }

        $file->move($absoluteDirectory, $fileName);

        return rtrim(request()->root(), '/').'/'.$relativeDirectory.'/'.$fileName;
    }

    public function delete(?string $imageUrl): void
    {
        if (empty($imageUrl)) {
            return;
        }

        $urlPath = parse_url($imageUrl, PHP_URL_PATH);

        if (! $urlPath) {
            return;
        }

        $marker = '/uploads/brands/';
        $position = strpos($urlPath, $marker);

        if ($position === false) {
            return;
        }

        $absolutePath = public_path('uploads/brands/'.basename($urlPath));

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
