<?php

return [
    'price_guard' => [
        'min_margin_percent' => (float) env('BACKOFFICE_MIN_PRICE_MARGIN_PERCENT', 20),
    ],

    'payment_sla_hours' => (int) env('BACKOFFICE_PAYMENT_SLA_HOURS', 24),

    'order_notifications' => [
        /*
        | Daftar email penerima notifikasi PO baru. Pisahkan dengan koma.
        | Kosongkan bila notifikasi email belum ingin diaktifkan.
        */
        'backoffice_emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('BACKOFFICE_ORDER_NOTIFICATION_EMAILS', ''))
        ))),
        'brand_listing_emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'BACKOFFICE_BRAND_LISTING_EMAILS',
                (string) env('BACKOFFICE_ORDER_NOTIFICATION_EMAILS', '')
            ))
        ))),
    ],
];
