<?php

return [
    'price_guard' => [
        'min_margin_percent' => (float) env('BACKOFFICE_MIN_PRICE_MARGIN_PERCENT', 20),
    ],

    'pos_app_url' => rtrim((string) env('POS_APP_URL', 'http://127.0.0.1:8001'), '/'),

    'ecommerce_rejection_handoff_enabled' => (bool) env(
        'BACKOFFICE_ECOMMERCE_REJECTION_HANDOFF_ENABLED',
        false
    ),

    'payment_sla_hours' => (int) env('BACKOFFICE_PAYMENT_SLA_HOURS', 24),

    'edc_bca' => [
        'credit_mdr_percent' => (float) env('BACKOFFICE_EDC_BCA_CREDIT_MDR_PERCENT', 2.0),
        'debit_mdr_percent' => (float) env('BACKOFFICE_EDC_BCA_DEBIT_MDR_PERCENT', 1.0),
        'settlement_days' => (int) env('BACKOFFICE_EDC_BCA_SETTLEMENT_DAYS', 1),
    ],

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
