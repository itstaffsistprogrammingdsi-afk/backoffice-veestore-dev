# Pemisahan Operasional dan Laporan

Status: implemented
Tanggal: 2026-11-01
Branch: `main`

## 1. Keputusan arsitektur

Aplikasi dibagi menjadi dua area utama:

| Area | Fokus | Contoh modul |
|---|---|---|
| Operasional | Kerja harian warehouse, store, dan ecommerce | Master Data, Branch Operations, Ecommerce, Loyalty, Management, Settings |
| Laporan | Analitik, monitoring, laporan, dan export | Dashboard, AI Analytics, AI Assistant, Monitoring, Reports, Report Export |

Auth berdiri sendiri dan memakai root view sendiri.

Pemisahan ini bukan microservice. Aplikasi tetap modular monolith karena database operasional dan transaksi masih saling terikat. Yang dipisahkan adalah:

- Navigasi dan information architecture.
- Shell UI dan pengalaman pengguna.
- Batas modul dan route ke depan.
- Read model laporan dari alur transaksi operasional.

## 2. Implementasi

### 2.1 Area hub

Route baru:

| Route | Name | Komponen |
|---|---|---|
| `/operations` | `operations.index` | `Operations/Index` |
| `/reports` | `reports.index` | `Reports/Index` |

Root `/` sekarang mengarah ke area operasional untuk user yang sudah login.

### 2.2 Navigation service

`app/Support/Navigation.php` menjadi satu sumber navigasi:

- Mengelompokkan menu ke area Operasional dan Laporan.
- Menyembunyikan item berdasarkan permission.
- Menandai item yang sudah Inertia dan yang masih Blade.
- Dibagikan ke semua halaman Inertia lewat `HandleInertiaRequests`.

### 2.3 Shell Inertia

`resources/js/layouts/AppLayout.vue` menjadi shell utama:

- Sidebar area Operasional dan Laporan.
- Group menu yang bisa dibuka dan ditutup.
- Topbar dengan breadcrumb, judul halaman, user menu, dan logout.
- Responsive: drawer di mobile, sidebar tetap di desktop.
- Keyboard: skip link, focus visible, Escape menutup drawer dan user menu.
- Aksesibilitas: `aria-current`, `aria-expanded`, label tombol, dan overlay.
- Status UI: flash success dan error tampil konsisten.

`resources/views/app.blade.php` sekarang menjadi root Inertia penuh, bukan lagi membungkus layout Materio. Halaman Inertia memakai `AppLayout`, sedangkan halaman Blade lama tetap memakai layout lamanya sampai dimigrasikan.

### 2.4 Root view auth

Auth memakai `resources/views/auth.blade.php` dan tidak memakai sidebar operasional.

## 3. UI dan UX

Prinsip yang dipakai:

- Satu area aktif terlihat jelas di sidebar.
- Menu dikelompokkan berdasarkan pekerjaan, bukan berdasarkan tipe file.
- Aksi utama memakai satu warna aksen, bukan warna acak.
- Kontras teks memenuhi target keterbacaan.
- Fokus keyboard selalu terlihat.
- Tombol dan tautan memiliki label yang jelas.
- Mobile memakai drawer, bukan sidebar yang mengecil.
- Setiap halaman tetap memiliki state kosong dan pesan error.

## 4. Status migrasi

Sudah Inertia dan memakai shell baru:

- Area hub Operasional dan Laporan.
- AI Analytics dan AI Assistant.
- Settings, Loyalty, Management.
- Report Export.
- Branch Operations.
- Ecommerce.
- Auth.
- Seluruh Reports.
- Seluruh Master Data.
- Seluruh Monitoring.
- Dashboard.
- Operasional gudang: Stock Operations, Inbound Operations, Outbound Operations, Warehouse Layout, dan Store Layout.
- Operasional toko: Store Stock Operations, Store Inbound Operations, Store Outbound Operations, dan Customers & Visits.

Pemisahan operasional dan reporting:

- Area Operasional memakai halaman kerja: stok tersedia per gudang, antrean inbound, dan antrean outbound.
- Area Laporan dan Command Center tetap memakai halaman monitoring lengkap: Stok WMS, Warehouse Inbound, Warehouse Outbound, export, dan analitik.
- Menu `Stok WMS`, `Inbound Gudang`, dan `Outbound Gudang` tidak lagi ditampilkan di sidebar Operasional agar tidak duplikat.

## 5. Langkah berikutnya

1. Tambahkan aksi tulis operasional bila alur QC/release disetujui.
2. Tambahkan test navigasi dan permission sidebar.
3. Evaluasi performa query operasional saat volume dokumen meningkat.

## 6. Validasi

| Pemeriksaan | Hasil |
|---|---|
| Full test suite | 50 lulus, 892 assertion |
| `composer lint` | Lulus |
| `composer analyse` | 0 error |
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |
| HTTP `/operations` | 200, komponen `Operations/Index` |
| HTTP `/reports` | 200, komponen `Reports/Index` |
| Navigation prop | Area `operations` dan `reports` terkirim |
