# Catatan Upgrade Laravel 13

Status: selesai di branch `chore/laravel-13-upgrade`
Tanggal: 2026-10-31
Baseline: `main` commit `19b9330`

## 1. Hasil

| Komponen | Sebelum | Sesudah |
|---|---|---|
| Laravel | 10.x (10.50.4) | 13.39.0 |
| PHP | 8.4.26 | 8.4.26 |
| Sanctum | 3.3.3 | 4.3.3 |
| Tinker | 2.11.1 | 3.0.2 |
| Spatie Permission | 5.11.0 | 6.25.0 |
| Collision | 7.12.0 | 8.9.5 |
| PHPUnit | 10.5.66 | 12.5.42 |
| Laravel Ignition | 2.9.1 | 2.12.1 |
| Maatwebsite Excel | 3.1.70 | 3.1.70 |
| PhpSpreadsheet | 1.30.2 | 1.30.5 |

Aplikasi tetap memakai struktur Laravel 10, yaitu `bootstrap/app.php`, `app/Http/Kernel.php`, dan `app/Console/Kernel.php`. Laravel 11 ke atas masih mendukung struktur ini, sehingga migrasi struktur skeleton tidak dilakukan pada fase ini.

## 2. Perubahan dependency

`composer.json` diubah ke:

- `laravel/framework` `^13.0`
- `laravel/sanctum` `^4.0`
- `laravel/tinker` `^3.0`
- `spatie/laravel-permission` `^6.25`
- `phpunit/phpunit` `^12.0`
- `nunomaduro/collision` `^8.1`
- `spatie/laravel-ignition` `^2.9`
- `maatwebsite/excel` `^3.1.70`

Ditambahkan `config.platform.php` = `8.4.26` agar Composer tetap memakai baseline PHP 8.4 walaupun perintah dijalankan dari PHP 8.5. Runtime aplikasi tetap harus memakai PHP 8.4.

## 3. Perubahan kode

1. `app/Http/Kernel.php`: namespace middleware Spatie diubah dari `Spatie\Permission\Middlewares` ke `Spatie\Permission\Middleware`.
2. `config/sanctum.php`: kunci middleware disesuaikan dengan Sanctum 4, yaitu `authenticate_session`, `encrypt_cookies`, dan `validate_csrf_token`.
3. `app/Http/Middleware/VerifyCsrfToken.php`: extends `Illuminate\Foundation\Http\Middleware\PreventRequestForgery`, menggantikan alias lama `VerifyCsrfToken`.

Tidak ada perubahan pada route, controller, model, atau view pada fase ini.

## 4. Validasi

| Pemeriksaan | Hasil |
|---|---|
| `php8.4 artisan --version` | Laravel Framework 13.39.0 |
| `php8.4 artisan package:discover` | 10 package ditemukan |
| `php8.4 artisan route:list --json` | 315 route total, tidak ada error |
| `php8.4 artisan route:cache` | Berhasil |
| `php8.4 artisan view:cache` | Berhasil |
| `php8.4 artisan config:cache` | Berhasil |
| `composer validate` | Valid |
| `composer check-platform-reqs` | Semua requirement terpenuhi |
| Smoke test 119 route GET tanpa parameter | 114 status 200, 4 redirect 302, 1 error 500 |
| Smoke test permission Spatie | User berizin 200, user tanpa izin 403 |

Smoke test memakai user nyata, `Gate::before` hanya dipakai pada pengujian halaman agar semua modul bisa dibuka. Pengujian permission dilakukan terpisah tanpa bypass.

## 5. Temuan yang belum diperbaiki

### 5.1 Route `management.roles-backoffice.create` dan `.edit`

Route mengarah ke method `create` dan `edit` yang tidak ada di `BackofficeRoleController`. Halaman index sudah memakai modal, sehingga route ini kemungkinan sisa. Ini masalah lama, bukan akibat upgrade. Perlu dihapus atau diberi redirect.

### 5.2 Test `QueuedReportExportTest`

Test gagal karena tabel `report_exports` tidak ada di database mana pun saat ini. Tabel ini juga tidak ada di `veestore_dev1` maupun `veestore_bo_dev`. Hasil ini sama sebelum upgrade. Status: diperbaiki pada fase 5, tabel dipindahkan ke database backoffice dan test sudah lulus.

### 5.3 PHP default sistem

PHP default di mesin ini masih 8.5. Selalu jalankan perintah dengan `php8.4`, atau ubah default alternative ke 8.4.

## 6. Cara menjalankan

```bash
php8.4 /home/wafiq/.local/bin/composer install
php8.4 artisan config:clear
php8.4 artisan route:clear
php8.4 artisan view:clear
php8.4 artisan serve
```

Untuk production, tetap gunakan PHP 8.4 dan jalankan `php8.4 artisan optimize` setelah konfigurasi final.

## 7. Langkah berikutnya

1. Perbaiki dua route mati di `BackofficeRoleController`.
2. Selesai: koneksi dan migration `report_exports` sudah diperbaiki pada fase 5.
3. Mulai fase 2: struktur modul, Form Request, Policy, dan Query Object untuk dua modul pilot.
4. Tambahkan Larastan dan CI sebelum migrasi frontend ke Inertia.
