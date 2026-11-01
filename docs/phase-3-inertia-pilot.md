# Fase 3: Pilot Inertia, Vue 3, dan Vite

Status: pilot berjalan
Tanggal: 2026-11-01
Branch: `chore/laravel-13-upgrade`

## 1. Tujuan

Fase 3 memasang fondasi frontend modern tanpa memutus halaman Blade yang sudah berjalan. Pilot dilakukan pada modul AI Analytics karena ukurannya kecil dan sudah punya query object serta action dari fase 2.

## 2. Dependency baru

Backend:

- `inertiajs/inertia-laravel` 3.6.0

Frontend:

- `@inertiajs/vue3` 3.10
- `vue` 3.5
- `vite` 8.3
- `laravel-vite-plugin` 3.2
- `@vitejs/plugin-vue` 6.0
- `typescript` 5.9
- `vue-tsc` 3.x
- `chart.js` 4.6

Script baru di `package.json`:

```bash
npm run dev:vite
npm run build:vite
npm run build:all
npm run typecheck
```

`build:all` menjalankan build Mix lama dan build Vite. Ini dibutuhkan selama masih ada halaman Blade dan halaman Inertia.

## 3. File baru

```text
app/Http/Middleware/HandleInertiaRequests.php
resources/views/app.blade.php
resources/js/app.ts
resources/js/env.d.ts
resources/js/pages/Analytics/Index.vue
resources/js/Pages -> resources/js/pages (lowercase, mengikuti default Inertia)
vite.config.mts
tsconfig.json
```

`app.blade.php` tetap memakai `layouts/contentNavbarLayout`, sehingga sidebar, navbar, tema, dan aset Materio yang sudah ada tetap dipakai. Halaman Inertia hanya mengganti area konten.

## 4. Perubahan AI Analytics

`AIAnalyticsController@index` sekarang mengembalikan `Inertia::render('Analytics/Index', ...)`.

Props yang dikirim:

- `latestRun`
- `recommendations`
- `summary`
- `forecastDaily`
- `filters`
- `stores`
- `urls` untuk index, run, dan review

Halaman Vue menangani:

- Kartu metrik.
- Grafik forecast dengan Chart.js.
- Info model aktif.
- Filter store, risiko, dan status lewat `router.get`.
- Tombol jalankan prediksi lewat `router.post`.
- Aksi setujui dan tolak lewat `router.patch`.
- Pagination lewat `Link` Inertia.
- Flash message dari shared props.

## 5. Middleware Inertia

`HandleInertiaRequests` didaftarkan di grup middleware `web` setelah `RecordBusinessAudit`.

Shared props:

- `auth.user` dengan field `id`, `name`, `username`, `email`.
- `flash.success` dan `flash.error`.

## 6. Validasi

| Pemeriksaan | Hasil |
|---|---|
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil, 634 modul |
| Build manifest | `public/build/manifest.json` |
| HTTP `/ai-analytics` | 200 |
| Inertia component | `Analytics/Index` |
| Props Inertia | `auth`, `flash`, `latestRun`, `recommendations`, `summary`, `forecastDaily`, `filters`, `stores`, `urls` |
| Test suite | 10 lulus, 1 gagal pada saat fase 3. Kegagalan diperbaiki pada fase 5. |
| Test gagal | `QueuedReportExportTest`, karena `report_exports` belum ada. Diperbaiki pada fase 5. |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |

## 7. Catatan

- Folder halaman memakai `resources/js/pages` huruf kecil karena default Inertia mencari path tersebut. Di Linux, `Pages` dan `pages` berbeda.
- `@inertiaHead` tidak dipasang di layout global karena membuat halaman Blade error ketika tidak ada variabel `$page`. Untuk sekarang judul halaman diatur oleh komponen `Head` di sisi client.
- Inertia devtools muncul pada environment lokal dan debug. Matikan di production jika tidak dipakai.
- Halaman Blade lain belum dimigrasikan. Keduanya hidup berdampingan.

## 8. Langkah berikutnya

1. Buat layout Vue bersama untuk sidebar, navbar, dan komponen tabel.
2. Migrasikan Monitoring dan Reports satu per satu, mulai dari halaman read-only.
3. Tambahkan helper URL yang konsisten, misalnya Ziggy, agar tidak perlu mengirim semua URL lewat props.
4. Tambahkan test Inertia untuk filter, pagination, dan aksi review.
5. Setelah modul pilot stabil, aktifkan `npm run build:all` di pipeline deploy.
