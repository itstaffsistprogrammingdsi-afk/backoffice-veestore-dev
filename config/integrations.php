<?php

return [
    'doku' => [
        'label' => 'Doku',
        'enabled' => (bool) env('DOKU_ENABLED', false),
        'environment' => (string) env('DOKU_ENVIRONMENT', 'sandbox'),
        'base_url' => (string) env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'),
        'client_id' => env('DOKU_CLIENT_ID'),
        'secret_key' => env('DOKU_SECRET_KEY'),
        'webhook_url' => env('DOKU_WEBHOOK_URL'),
        'docs_url' => (string) env('DOKU_DOCS_URL', 'https://developers.doku.com'),
        'required' => ['client_id', 'secret_key'],
    ],

    'biteship' => [
        'label' => 'Biteship',
        'enabled' => (bool) env('BITESHIP_ENABLED', false),
        'environment' => (string) env('BITESHIP_ENVIRONMENT', 'sandbox'),
        'base_url' => (string) env('BITESHIP_BASE_URL', 'https://api.biteship.com'),
        'api_key' => env('BITESHIP_API_KEY'),
        'webhook_url' => env('BITESHIP_WEBHOOK_URL'),
        'docs_url' => (string) env('BITESHIP_DOCS_URL', 'https://biteship.com/id/docs'),
        'required' => ['api_key'],
    ],
];
