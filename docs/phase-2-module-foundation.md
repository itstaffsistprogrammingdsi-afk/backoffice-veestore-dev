# Fase 2: Fondasi Modul

Status: pilot selesai
Tanggal: 2026-11-01
Branch: `chore/laravel-13-upgrade`

## 1. Tujuan

Fase 2 membangun pola kerja untuk memindahkan logika dari controller ke lapisan domain. Pilot dilakukan pada dua modul dengan risiko berbeda:

- Master Data, khusus alur Brand, sebagai contoh CRUD tulis dengan upload file.
- AI Analytics, sebagai contoh halaman baca, filter, dan aksi review.

## 2. Struktur baru

```text
app/
  Domain/
    Catalog/
      Actions/
        CreateBrand.php
        UpdateBrand.php
        DeleteBrand.php
      Exceptions/
        BrandInUseException.php
      Policies/
        BrandPolicy.php
      Queries/
        BrandListQuery.php
      Support/
        BrandLogoStorage.php
    Analytics/
      Actions/
        RunForecast.php
        ReviewStockRecommendation.php
      Queries/
        AnalyticsDashboardQuery.php
  Http/
    Requests/
      MasterData/
        StoreBrandRequest.php
        UpdateBrandRequest.php
      AIAnalytics/
        IndexAnalyticsRequest.php
        ReviewRecommendationRequest.php
```

Aturan yang dipakai:

- Controller hanya menangani HTTP: menerima request, memanggil action atau query, lalu mengembalikan response.
- Form Request menangani validasi dan otorisasi input.
- Action menangani satu use case tulis.
- Query Object menangani query baca yang kompleks.
- Policy menangani otorisasi model.
- Support menangani detail teknis seperti penyimpanan file.

## 3. Pilot Master Data: Brand

### 3.1 Perubahan

`BrandController` sekarang hanya memanggil:

- `BrandListQuery` untuk daftar brand dan pencarian.
- `StoreBrandRequest` dan `UpdateBrandRequest` untuk validasi serta otorisasi.
- `CreateBrand`, `UpdateBrand`, dan `DeleteBrand` untuk use case tulis.
- `BrandLogoStorage` untuk upload dan hapus logo.
- `BrandPolicy` untuk otorisasi.

Middleware permission di constructor controller dihapus untuk alur Brand. Pengecekan sekarang lewat Policy:

| Ability | Permission Spatie |
|---|---|
| `viewAny` | `view master-data-brands` |
| `create` | `create master-data-brands` |
| `update` | `update master-data-brands` |
| `delete` | `delete master-data-brands` |

Policy didaftarkan di `app/Providers/AuthServiceProvider.php`.

### 3.2 Perilaku yang dipertahankan

- Nama brand dinormalisasi dengan trim dan spasi tunggal.
- Duplikasi nama dicek tanpa membedakan huruf besar dan kecil.
- Error validasi tetap memakai error bag `createBrand` dan `editBrand`, sehingga modal di view tetap bekerja.
- Logo tetap disimpan ke `public/uploads/brands`.
- Brand yang masih dipakai produk tidak bisa dihapus.
- Pesan sukses dan pesan gagal tetap sama.

## 4. Pilot AI Analytics

### 4.1 Perubahan

`AIAnalyticsController` sekarang hanya memanggil:

- `IndexAnalyticsRequest` untuk filter `store_id`, `risk_level`, dan `status`.
- `AnalyticsDashboardQuery` untuk model run terakhir, rekomendasi, ringkasan, forecast harian, dan daftar store.
- `RunForecast` untuk memanggil service Python.
- `ReviewRecommendationRequest` dan `ReviewStockRecommendation` untuk aksi review.

Tidak ada lagi query mentah atau `DB::` di controller AI Analytics.

### 4.2 Perilaku yang dipertahankan

- Hanya model run berstatus `completed` yang dipakai.
- Rekomendasi diurutkan berdasarkan tingkat risiko, lalu jumlah rekomendasi.
- Ringkasan tetap menghitung total, kritis, tinggi, dan total unit restock.
- Forecast harian tetap diagregasi per tanggal.
- Review tetap menulis `status`, `review_notes`, `reviewed_by`, dan `reviewed_at`.

## 5. Test

Test baru:

- `tests/Feature/MasterData/BrandManagementTest.php`
- `tests/Feature/AIAnalytics/AnalyticsDashboardTest.php`
- `tests/Concerns/InteractsWithBackofficeAdmin.php`

Test Brand mencakup:

- Halaman index dapat dirender.
- Brand dapat dibuat.
- Brand dapat diperbarui.
- Brand dapat dihapus dengan soft delete.

Test AI Analytics mencakup:

- Halaman dashboard dapat dirender.

Hasil:

| Pemeriksaan | Hasil |
|---|---|
| Test suite | 10 lulus, 1 gagal pada saat fase 2. Kegagalan diperbaiki pada fase 5. |
| Test gagal | `QueuedReportExportTest`, karena tabel `report_exports` belum ada. Diperbaiki pada fase 5. |
| Smoke test Brand | 200 |
| Smoke test AI Analytics | 200 |
| Permission Brand, user berizin | 200 |
| Permission Brand, user tanpa izin | 403 |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

## 6. Catatan implementasi

- `whereHas('roles')` pada model `User` memakai koneksi default, sedangkan tabel role ada di database backoffice. Test helper `InteractsWithBackofficeAdmin` mengambil user Superadmin lewat koneksi `immostore_backoffice` untuk menghindari masalah ini.
- Policy Brand memanggil permission Spatie dari dalam policy. Pola ini aman karena nama ability policy berbeda dari nama permission.
- Superadmin tetap mendapatkan bypass lewat `Gate::before` yang sudah ada di `AuthServiceProvider`.

## 7. Sisa pekerjaan fase 2

1. Terapkan pola yang sama ke controller Master Data lain, mulai dari Brand, Category, Unit, lalu Supplier.
2. Tambahkan Policy untuk modul lain yang masih memakai middleware permission di constructor.
3. Tambahkan Query Object untuk laporan berat di Monitoring dan Reports.
4. Tambahkan test CRUD untuk modul yang dimigrasikan.
5. Hapus `DB::` baru dari modul yang sudah dimigrasikan, lalu ukur ulang jumlah query mentah.
6. `report_exports` sudah dipindahkan ke database backoffice pada fase 5.

## 8. Cara menjalankan test

```bash
php8.4 artisan test --compact
php8.4 artisan test --filter=BrandManagementTest
php8.4 artisan test --filter=AnalyticsDashboardTest
```
