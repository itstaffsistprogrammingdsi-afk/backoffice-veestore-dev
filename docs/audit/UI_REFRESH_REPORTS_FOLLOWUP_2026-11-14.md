# Tindak Lanjut Laporan dan Monitoring

Tanggal: 14 November 2026
Branch: `ui/refresh-reports`

## 1. Filter E-commerce pada Pemasukan & Pembayaran

- Backend `PaymentIncomeRealtimeController` sekarang menerima filter `channel` dengan nilai `store`, `ecommerce`, atau kosong untuk semua kanal.
- Filter diterapkan pada data utama, ringkasan, metode pembayaran, dan data per jam.
- UI menampilkan filter **Kanal** dengan pilihan **Semua kanal**, **Toko**, dan **E-commerce**.
- Saat kanal E-commerce dipilih, filter cabang disembunyikan dan nilai `store_id` dibersihkan.

Hasil uji untuk periode bulan ini:

| Kanal | Transaksi | Nominal | Transaksi toko | Transaksi e-commerce |
|---|---:|---:|---:|---:|
| Semua | 7 | Rp 107.100 | 3 | 4 |
| Toko | 3 | Rp 39.000 | 3 | 0 |
| E-commerce | 4 | Rp 68.100 | 0 | 4 |

## 2. Nominal dan Responsivitas Pembayaran E-commerce

- Kolom nominal diprioritaskan pada urutan kolom: Total Pesanan, Total Pembayaran, Nominal Dibayar, Sisa Tagihan, Subtotal, Diskon, Pajak, dan Kembalian.
- Kolom **Sisa Tagihan** ditambahkan dari `grand_total - paid_amount`, dan bernilai 0 untuk order yang dibatalkan.
- Status pesanan dan pembayaran sekarang tampil dalam Bahasa Indonesia, misalnya `Dibayar`, `Dibatalkan`, `Kedaluwarsa`.
- Pada layar di bawah 768 px, tabel Pembayaran E-commerce menampilkan versi ringkas: No. Pesanan, Total Pesanan, dan Status Pembayaran. Tidak ada overflow halaman.
- Kolom pertama tetap sticky saat tabel digeser pada tablet.
- Nilai mata uang memakai format `id-ID` dengan pemisah ribuan dan rata kanan.

## 3. Filter Periode Cepat

Komponen bersama `ReportPeriodPresets` menyediakan **Hari ini**, **Minggu ini**, **Bulan ini**, dan **Periode tertentu**.

Diterapkan pada:

- CRM
- Produk Pemasok
- Order Cabang
- Riwayat Pelanggan
- Preferensi Produk Pelanggan
- Demografi Pelanggan
- Semua halaman Monitoring yang memiliki pasangan tanggal (`start_date`/`end_date`, `date_from`/`date_to`, atau `from`/`to`)

Ringkasan Usaha dan Pusat Laporan sudah memiliki filter cepat sebelumnya. Halaman yang tidak memakai periode transaksi, seperti Kontrol Margin Harga dan Catatan Piutang, tidak dipaksakan.

Uji klik pada 8 halaman berhasil mengubah URL dan memuat ulang data dengan tanggal yang benar.

## 4. Urutan Produk Terlaris

- Urutan sekarang memakai `SUM(qty)` menurun, lalu nominal penjualan, lalu nama produk.
- Produk yang belum terjual ikut ditampilkan di bagian bawah dengan qty 0, transaction 0, dan nominal Rp 0.
- Filter cabang, kategori, merek, dan pencarian tetap berlaku.
- Ekspor mengikuti urutan dan cakupan yang sama.

Uji data: baris teratas memiliki qty 4, 4, 3, 3, 2, dan halaman terakhir menampilkan produk dengan qty 0.

## 5. Void & Pembatalan

Penyebab data terasa berbeda:

- Halaman **Void & Pembatalan** membaca tabel `void_transactions`, yaitu pengajuan void transaksi POS.
- Pada database ini `void_transactions` berisi 0 baris, sehingga halaman menampilkan 0 data. Ini bukan data dummy.
- Tiga pembayaran berstatus `cancelled` yang ada adalah pembayaran e-commerce (`INV-EO-...`), bukan void POS, sehingga tidak masuk laporan ini.
- Deskripsi halaman sekarang menjelaskan sumber data dan mengarahkan pembatalan e-commerce ke laporan Pembayaran E-commerce dengan status pesanan Dibatalkan.
- Pilihan status filter sekarang selalu tersedia: requested, approved, authorized, rejected, dan cancelled.

Jika pembatalan e-commerce ingin digabung ke halaman Void & Pembatalan, itu perlu keputusan produk karena model datanya berbeda dari void POS.

## Verifikasi

- `npx vue-tsc --noEmit`: lulus.
- `npm run build:vite`: lulus.
- axe pada 4 halaman yang diubah: 0 pelanggaran WCAG 2.2 AA.
- Overflow mobile 375 px pada 4 halaman yang diubah: 0.
- Filter cepat diuji pada 8 halaman dan berhasil mengubah URL serta memuat data.
