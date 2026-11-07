<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Voucher Image
    |--------------------------------------------------------------------------
    |
    | Uploaded voucher images are stored on this disk and directory. The image
    | is kept as the source document for data extracted by the scanner.
    |
    */

    'image' => [
        'disk' => env('VOUCHER_IMAGE_DISK', 'public'),
        'directory' => env('VOUCHER_IMAGE_DIRECTORY', 'voucher-images'),
        'max_kb' => (int) env('VOUCHER_IMAGE_MAX_KB', 5120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voucher Image Scanner
    |--------------------------------------------------------------------------
    |
    | The scanner uses a vision capable model from the configured AI provider
    | to read voucher images and pre-fill the voucher form automatically.
    |
    */

    'ocr' => [
        'enabled' => (bool) env('VOUCHER_OCR_ENABLED', true),
        'provider' => env('VOUCHER_OCR_PROVIDER') ?: env('AI_PROVIDER', 'nine_router'),
        'model' => env('VOUCHER_OCR_MODEL') ?: env('NINE_ROUTER_MODEL'),
        'timeout' => (int) env('VOUCHER_OCR_TIMEOUT', 90),
    ],

];
