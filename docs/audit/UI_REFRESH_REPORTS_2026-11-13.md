# UI Refresh Reports

Tanggal: 13 November 2026
Branch: `ui/refresh-reports`
Lingkup: seluruh area Laporan/Reports, termasuk halaman Monitoring yang dipakai dari menu Laporan.
Stack: Laravel 13, Inertia, Vue 3, TypeScript, Vite, Bootstrap 5.

## Ringkasan

Audit dilakukan pada 18 route di 3 breakpoint: 1440, 768, dan 375 px. Pengujian memakai Chrome headless, axe-core dengan tag WCAG 2.0, 2.1, dan 2.2 AA, serta Lighthouse desktop.

Hasil utama:

- Pelanggaran axe pada 18 route turun dari 14 menjadi 0.
- Horizontal overflow mobile pada CRM (27 px) dan Demografi (3 px) sudah hilang.
- Kontras 50 sel harga di Kontrol Margin Harga sudah lolos.
- Region tabel yang bisa digeser sekarang dapat difokus dengan keyboard.
- Label filter dan nama tombol icon sudah punya nama aksesibel.
- Ringkasan pembayaran tidak lagi memakai istilah campuran Inggris dan Indonesia.
- Menu Laporan tetap membuka `reports/business-overview` lebih dulu.
- Dark mode diaudit dengan axe pada 18 route, hasil 0 pelanggaran.
- Pengaturan kepadatan tabel dan kolom tersedia di 5 tabel: Kontrol Margin, Produk Pemasok, Profil Pelanggan, Order Cabang, dan Riwayat Pelanggan. Ringkasan Usaha, Laba Operasional, dan laporan Monitoring memakai tombol kepadatan yang sama.
- Lima tabel rincian Ringkasan Usaha sekarang memakai progressive disclosure `Lihat rincian`, sehingga tinggi awal halaman turun dari 5.309 px menjadi 4.855 px.
- Preferensi tabel tersimpan di localStorage dan bertahan setelah reload.
- Istilah halaman `Order Handled`, `Fulfillment & SLA`, `Void & Cancel`, dan beberapa judul halaman berbahasa Inggris sudah diganti ke Bahasa Indonesia.

## 1. Temuan audit

| Prioritas | Temuan | Bukti | Dampak |
|---|---|---|---|
| Critical | Overflow horizontal di CRM dan Demografi pada 375 px | `scrollWidth` 402 px pada viewport 375 px; tombol Ekspor terpotong | Pengguna mobile tidak bisa menjangkau aksi |
| Critical | Kontras teks margin gagal | axe: 50 node `color-contrast` di Kontrol Margin Harga | Angka margin sulit dibaca, gagal WCAG AA |
| Critical | Input tanggal dan select tanpa label terasosiasi | axe: `label` dan `select-name` di CRM, Produk Pemasok, Order Cabang, Riwayat Pelanggan | Pembaca layar tidak tahu isi field |
| Critical | Region tabel tidak bisa difokus keyboard | axe: `scrollable-region-focusable` di Ringkasan Usaha, Laba Operasional, Stok per Cabang | Tabel lebar tidak bisa digeser tanpa mouse |
| Critical | Tombol cari icon tanpa nama aksesibel | axe: `button-name` di Profil Pelanggan | Aksi utama tidak terbaca pembaca layar |
| Critical | Deskripsi rekonsiliasi memakai nama kolom database | Teks `total_amount` dan `payment_details` di header | Istilah teknis membingungkan pengguna awam |
| High | Halaman Laporan memakai 6 kartu KPI identik, 4 kartu domain identik, dan 20 kartu modul identik | Screenshot `reports-hub` sebelum | Tidak ada hierarki, halaman terasa seperti template |
| High | Produk Pemasok memakai 10 kartu statistik identik dan beberapa metrik duplikat | Screenshot sebelum | Angka penting tenggelam |
| High | Eyebrow huruf kapital, label tabel huruf kapital, dan istilah Inggris/Indonesia bercampur | `REPORTS / CRM`, `STOCK`, `MATCHED`, `FINDINGS` | Tidak konsisten dan sulit dipindai |
| High | Header mobile CRM dan Pusat Komando punya ruang kosong besar | `flex-basis` 22rem dan 24rem berlaku sebagai tinggi saat layout menjadi kolom | Konten terdorong jauh ke bawah |
| High | Status transaksi pelanggan tampil mentah | `paid`, `shipped`, `processing` di tabel profil | Pengguna awam tidak paham |
| High | Istilah domain tanpa konteks | `Base`, `Store`, `Ecommerce`, `Margin` | Salah tafsir saat membandingkan harga |
| High | Empty state generik | `Belum ada data.` | Tidak menjelaskan penyebab atau langkah berikutnya |
| Medium | Format tanggal dan waktu tidak seragam | `13-11-2026 22:55:24` vs `13 Nov 2026` | Data sulit dibandingkan |
| Medium | Tanda panah dekoratif pada hampir semua kartu | Semua kartu modul di hub | Menambah noise visual |
| Medium | Sebagian tabel masih memakai tampilan penuh | Laba Operasional dan Ringkasan Usaha punya kepadatan, tetapi belum punya pemilih kolom | Kontrol kolom belum merata di semua tabel |
| Medium | Halaman laporan lain masih memakai progress bar Inertia | Skeleton baru tersedia di laporan Monitoring | State loading belum seragam di semua halaman |
| Low | Radius dan bayangan belum seragam antar halaman | Beberapa halaman memakai `0.9rem`, `1rem`, dan shadow lokal | Bahasa visual kurang konsisten |

## 2. Design tokens dan komponen

Sumber token tetap `resources/css/tokens.css` dan `resources/css/theme.css`. Brand tidak berubah: biru dan kuning VeeStore, Inter, permukaan terang, aksen biru, sesuai `DESIGN.md`.

Token dan aturan yang ditambahkan atau diperketat:

- `--app-topbar-h: 78px` untuk offset sticky table header.
- `.text-warning` dan `.text-info` memakai `--app-warning-ink` dan `--app-info-ink` yang lolos kontras.
- Header tabel tidak lagi huruf kapital, ukuran minimal 12 px, bobot 600.
- `.report-table-wrap` menjadi penanda tabel yang dapat difokus keyboard.
- `.report-table-scroll` menahan horizontal scroll untuk tabel lebar seperti Monitoring.
- Sticky header tabel aktif pada desktop 1200 px ke atas untuk tabel yang tidak butuh scroll horizontal.
- `.page-header-eyebrow` memakai sentence case, ukuran 12 px, tanpa letter spacing lebar.
- Ringkasan laporan memakai pola satu angka utama, metrik pendukung, dan label yang menjelaskan periode.
- Status memakai `bg-label-success`, `bg-label-warning`, `bg-label-danger`, `bg-label-info` dengan teks dan ikon, bukan warna saja.

Komponen yang diperbarui:

- `AppLayout`: nama aksesibel tombol pencarian dan menu akun.
- `MenuSearch`: nama aksesibel menyertakan shortcut yang terlihat.
- `SearchableSelect`: nama aksesibel menyertakan nilai yang sedang tampil.
- `useStatusLabels`: status transaksi dan pesanan memakai label Indonesia.
- Page header laporan, ringkasan KPI, filter, tabel, pagination, empty state, dan status badge.
- Hub Laporan: ringkasan periode, navigasi topik, dan direktori laporan.
- Produk Pemasok: empat metrik utama dan satu baris metrik pendukung.
- CRM: ringkasan lima metrik dan tabel yang bisa difokus keyboard.
- Pusat Komando dan laporan Monitoring: label ringkasan Bahasa Indonesia dan tabel yang bisa difokus keyboard.
- `ReportTableControls`: tombol kepadatan Nyaman/Ringkas, menu kolom, preset Tampilan sederhana dan Tampilan lengkap, serta panel bottom sheet di mobile.
- `useReportTable`: composable kepadatan dan visibilitas kolom yang tersimpan di localStorage.

## 3. Perubahan per file

PHP:

- `app/Support/Navigation.php`: area Laporan membuka `reports.business-overview.index`, ikon audit diganti valid.
- `app/Domain/Analytics/Queries/BusinessOverviewQuery.php`: tautan tindak lanjut menuju halaman Inertia dengan filter, bukan endpoint JSON.
- `app/Http/Controllers/Monitoring/MonitoringCommandCenterController.php`: URL order menuju halaman pesanan, label rekonsiliasi manusiawi, format waktu WIB, ikon valid.
- `app/Http/Controllers/Monitoring/OrderHandledController.php`: ikon tab valid.
- `app/Services/BranchOperationNotificationService.php`: ikon notifikasi valid.
- `tests/Feature/Shell/RoutingIntegrityTest.php`: regresi area Laporan menuju Business Overview.

CSS:

- `resources/css/tokens.css`: token tinggi topbar.
- `resources/css/theme.css`: kontras teks semantik dan header tabel.
- `resources/css/components.css`: page header, tabel fokus, dan sticky header.

Vue:

- `resources/js/layouts/AppLayout.vue`
- `resources/js/components/MenuSearch.vue`
- `resources/js/components/SearchableSelect.vue`
- `resources/js/composables/useStatusLabels.ts`
- `resources/js/composables/useReportTable.ts`
- `resources/js/components/ui/ReportTableControls.vue`
- `resources/js/pages/Reports/Index.vue`
- `resources/js/pages/Reports/BusinessOverview.vue`
- `resources/js/pages/Reports/PriceMargin.vue`
- `resources/js/pages/Reports/SupplierProducts.vue`
- `resources/js/pages/Reports/Crm.vue`
- `resources/js/pages/Reports/CrmCustomers.vue`
- `resources/js/pages/Reports/CrmDemographics.vue`
- `resources/js/pages/Reports/CrmCustomerProfile.vue`
- `resources/js/pages/Reports/OrderCabang.vue`
- `resources/js/pages/Reports/HistoryCustomer.vue`
- `resources/js/pages/Reports/HistoryCustomerEcom.vue`
- `resources/js/pages/Reports/ProfitLoss.vue`
- `resources/js/pages/Reports/ReceivablePayable.vue`
- `resources/js/pages/Reports/CustomerPreference.vue`
- `resources/js/pages/Reports/Voucher.vue`
- `resources/js/pages/Reports/ExportList.vue`
- `resources/js/pages/Reports/ExportStatus.vue`
- `resources/js/pages/Monitoring/Index.vue`
- `resources/js/pages/Monitoring/Report.vue`
- `resources/js/pages/Monitoring/DiscountSync.vue`

## 4. Verifikasi

Perintah:

- `npx vue-tsc --noEmit`: lulus.
- `npm run build:vite`: lulus.
- `php artisan test tests/Feature/Reports tests/Feature/Shell`: 41 test lulus, 767 assertion.
- `vendor/bin/pint --test`: lulus.
- axe-core pada 18 route light mode: 0 pelanggaran WCAG 2.2 AA.
- axe-core pada 18 route dark mode: 0 pelanggaran WCAG 2.2 AA.
- Cek overflow mobile 375 px pada CRM, Demografi, Ringkasan Usaha, Kontrol Margin, dan Produk Pemasok: 0 overflow.
- Uji kontrol tabel: 9 menjadi 5 kolom pada preset sederhana, kepadatan compact aktif, dan preferensi bertahan setelah reload.
- Uji Riwayat Pelanggan: tabel POS 6 menjadi 5 kolom, tabel e-commerce menampilkan 7 kolom, preferensi per kanal tersimpan terpisah.
- Uji laporan Monitoring: tombol Nyaman/Ringkas mengubah kelas `is-compact` pada tabel.
- Uji Ringkasan Usaha dan Laba Operasional: tombol kepadatan mengubah kelas `is-table-compact` pada root halaman.
- Uji progressive disclosure: 5 panel rincian Ringkasan Usaha tertutup secara default, bisa dibuka dengan keyboard, dan tinggi awal turun 454 px.
- Uji panel kolom mobile: lebar 343 px di viewport 375 px, tidak overflow.
- Loading laporan Monitoring sekarang memakai skeleton baris, bukan spinner kosong.
- Workflow `.github/workflows/ui-quality.yml` ditambahkan untuk `npm ci`, typecheck, build Vite, dan Pint.
- Uji tugas profesional: Kontrol Margin (filter + terapkan + ekspor) 3 interaksi; Ringkasan Usaha (periode + ekspor + buka rincian) 3 interaksi; Monitoring (filter + terapkan + ekspor) 3 interaksi. Lulus.
- Protokol uji 5 detik disiapkan di `docs/audit/UJI_5_DETIK_REPORTS.md`. Pelaksanaan dengan 2 pengguna non-teknis belum dilakukan.
- Screenshot kontrol tabel: `docs/audit/ui-refresh-reports/after-table-controls-desktop.png` dan `after-table-controls-mobile.png`.
- Screenshot progressive disclosure: `docs/audit/ui-refresh-reports/after-overview-disclosure.png`.

Lighthouse desktop, sebelum dan sesudah:

| Halaman | Performance sebelum | Performance sesudah | Accessibility | Best Practices |
|---|---:|---:|---:|---:|
| Ringkasan Usaha | 78 | 80 | 100 | 100 |
| Pusat Laporan | 86 | 87 | 100 | 100 |
| Kontrol Margin | 90 | 77 | 100 | 100 |
| Rekonsiliasi Pembayaran | 71 | 86 | 100 | 100 |

Uji ulang dengan `config:cache` dan `view:cache`: Ringkasan Usaha 67, Pusat Laporan 85, Kontrol Margin 90, Rekonsiliasi Pembayaran 85. Accessibility dan Best Practices tetap 100.

Catatan performa: skor didominasi `server-response-time` 1 sampai 3,7 detik dari `php artisan serve` dan query database lokal. Skor 90 perlu diukur pada server produksi, bukan dari dev server. Peluang frontend yang tersisa adalah unused CSS dan unused JavaScript sekitar 300 ms.

Screenshot before dan after tersedia di:

- `docs/audit/ui-refresh-reports/` untuk pasangan before/after halaman utama.
- `/tmp/ui-audit/before2/*-{desktop,tablet,mobile}-viewport.png` dan `/tmp/ui-audit/after-final/*-{desktop,tablet,mobile}.png` untuk set lengkap 3 breakpoint.
- `/tmp/ui-audit/dark/*.png` untuk screenshot dark mode.

## 5. Sisa pekerjaan dan rekomendasi

1. Tambahkan pemilih kolom untuk Ringkasan Usaha dan Laba Operasional.
2. Perluas progressive disclosure ke rincian lain yang masih panjang di Ringkasan Usaha.
3. Rapikan sisa istilah campur bahasa pada dataset Monitoring yang belum disentuh.
4. Jalankan protokol uji 5 detik di `docs/audit/UJI_5_DETIK_REPORTS.md` dengan 2 pengguna non-teknis, lalu catat hasilnya.
5. Ukur Lighthouse di server produksi dan optimalkan query laporan yang lambat.
6. Tambahkan job PHPUnit dengan database backoffice ke CI, lalu tambahkan axe dan Lighthouse budget.
