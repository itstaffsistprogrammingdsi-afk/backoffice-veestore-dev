# Fase 5: Konsolidasi

Status: selesai untuk lingkup awal
Tanggal: 2026-11-01
Branch: `chore/laravel-13-upgrade`

## 1. Tujuan

Fase 5 merapikan utang teknis yang masih menghambat operasional dan pengujian. Fokus awal:

- Memperbaiki tabel dan koneksi report export.
- Memperbaiki konfigurasi queue dan failed jobs.
- Membuat seluruh test suite lulus.
- Menghapus controller template dan file sisa.

## 2. Report export

Masalah sebelumnya: tabel `report_exports` tidak ada di database mana pun, sementara kode memakai koneksi default `pgsql` yang mengarah ke database operasional.

Perbaikan:

- Migration `create_report_exports` sekarang memakai koneksi `immostore_backoffice`.
- Migration `create_failed_jobs_table` juga memakai koneksi `immostore_backoffice`.
- `ReportExportController`, `ReportExportService`, dan `GenerateReportExport` memakai koneksi `immostore_backoffice`.
- User lock tetap di database operasional karena tabel `users` ada di sana.
- Queue `report_exports` memakai koneksi `immostore_backoffice`.
- Failed jobs memakai koneksi `immostore_backoffice`.

Migration yang dijalankan:

```bash
php8.4 artisan migrate --database=immostore_backoffice \
  --path=database/migrations/2026_09_15_100000_create_report_exports.php --force

php8.4 artisan migrate --database=immostore_backoffice \
  --path=database/migrations/2026_10_03_170000_create_failed_jobs_table.php --force
```

Tabel yang sekarang ada di `veestore_bo_dev`:

- `report_exports`
- `report_export_jobs`
- `failed_jobs`

## 3. Queue

Konfigurasi `report_exports`:

```php
'connection' => env('REPORT_EXPORT_QUEUE_DB_CONNECTION', 'immostore_backoffice'),
'driver' => 'database',
'table' => 'report_export_jobs',
'queue' => 'exports',
```

Worker production:

```bash
php8.4 artisan queue:work report_exports --queue=exports --tries=1 --timeout=960
```

Pada environment lokal, `config/report_exports.php` tetap memakai `sync` agar export langsung selesai tanpa worker.

## 4. Test

`QueuedReportExportTest` sekarang:

- Memakai koneksi `immostore_backoffice`.
- Memulai dan mengakhiri transaksi pada koneksi tersebut.
- Berhasil menulis workbook di worker.
- Membersihkan file setelah test.

Hasil test suite:

| Pemeriksaan | Hasil |
|---|---|
| Full test suite | 15 lulus, 85 assertion |
| `QueuedReportExportTest` | 3 lulus |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

Test gagal lama sudah tidak ada.

## 5. Pembersihan

Dihapus:

- 41 controller template Materio di `app/Http/Controllers`:
  `cards`, `extended_ui`, `form_elements`, `form_layouts`, `icons`, `layouts`, `pages`, `tables`, `user_interface`, `authentications`.
- `routes/web copy.php`
- `softDeletesTz()`
- `test_pg.php`
- `documentation.html`
- `hire-us.html`

View template yang dihapus:

- 38 file dari `content/cards`, `extended_ui`, `form_elements`, `form_layouts`, `icons`, `layouts-example`, `pages`, `tables`, dan `user_interface`.
- View `content.authentications` tetap dipakai oleh `AuthController`.

Setelah pembersihan:

- Route aplikasi: 307.
- Controller bisnis: 96.
- Controller template: 0.
- View template: 0 untuk folder yang dihapus.

## 6. AI Assistant

Perbaikan keamanan:

- Route AI Assistant memakai permission `view ai-analytics`.
- Endpoint `POST /ai-assistant/ask` memakai rate limit 10 request per menit per user.
- Test permission tanpa role ditambahkan dan lulus.

## 7. CI dan static analysis

Ditambahkan:

- `larastan/larastan` 3.13.
- `phpstan.neon` dengan level 0 dan baseline `phpstan-baseline.neon`.
- `pint.json` dengan preset Laravel.
- Script Composer: `test`, `lint`, dan `analyse`.
- Workflow `.github/workflows/quality.yml`.

Workflow menjalankan:

- Pint pada file yang sudah dimigrasikan.
- PHPStan dengan baseline.
- `npm ci`.
- `npm run typecheck`.
- `npm run build:vite`.

Test PHP yang membutuhkan database belum masuk CI karena skema operasional belum tersedia di repository. Test tersebut tetap dijalankan manual di environment lokal.

## 8. Catatan

- Worker queue belum dijalankan di environment lokal, karena lokal memakai `sync`.
- Pastikan migration di atas dijalankan di setiap environment sebelum mengaktifkan export berbasis queue.
- `package-lock.json` sekarang tidak diabaikan agar `npm ci` dapat dipakai di CI.
- Baseline PHPStan berisi 54 error lama. Error baru akan langsung menggagalkan analisis.

## 9. Langkah berikutnya

1. Audit ulang view lama yang masih tersisa di modul bisnis.
2. Tambahkan test untuk filter dan pagination AI Analytics.
3. Tambahkan job queue untuk pertanyaan AI yang berat.
4. Tambahkan usage logging dan biaya per request AI.
