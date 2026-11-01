<?php

return [
    'max_days' => 31,
    'max_rows' => 20000,
    'chunk_size' => 500,
    // Pada lokal, jalankan ekspor langsung agar tombol unduh dapat dipakai tanpa
    // harus menyalakan queue worker. Production tetap memakai queue khusus;
    // koneksi juga dapat dioverride dengan REPORT_EXPORT_QUEUE_CONNECTION.
    'connection' => env(
        'REPORT_EXPORT_QUEUE_CONNECTION',
        env('APP_ENV') === 'local' ? 'sync' : 'report_exports'
    ),
];
