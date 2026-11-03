<?php

return [
    'recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('STOCK_BARRIER_ALERT_EMAILS', ''))
    ))),
];
