<?php

return [
    'order_notifications' => [
        /*
        | Daftar email penerima notifikasi PO baru. Pisahkan dengan koma.
        | Kosongkan bila notifikasi email belum ingin diaktifkan.
        */
        'backoffice_emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('BACKOFFICE_ORDER_NOTIFICATION_EMAILS', ''))
        ))),
    ],
];
