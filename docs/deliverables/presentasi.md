# Kerangka Presentasi VeeStore Backoffice

Durasi target: 30–40 menit termasuk demo dan tanya jawab.

## Slide 1 — Judul

- VeeStore Backoffice: operasional toko, gudang, dan e-commerce dalam satu kendali.
- Nama pembicara, tanggal, dan tujuan presentasi.

## Slide 2 — Masalah yang diselesaikan

- Stok antar toko tidak seimbang.
- Diskon POS dan e-commerce tidak konsisten.
- Transaksi EDC sulit direkonsiliasi.
- Laporan pembelian per shift masih manual.
- Integrasi pembayaran dan pengiriman belum terpantau.

## Slide 3 — Gambaran solusi

- Satu backoffice untuk master data, operasional, dan laporan.
- Alur transfer toko yang tercatat.
- Kontrol max diskon lintas kanal.
- Rekonsiliasi EDC BCA.
- Laporan pembelian per shift.
- Halaman kesiapan integrasi.

## Slide 4 — Arsitektur singkat

- Laravel + Inertia + Vue 3.
- Database operasional dan database backoffice.
- Integrasi Doku dan Biteship melalui endpoint webhook aplikasi POS/e-commerce.
- Audit otomatis untuk aksi tulis.

## Slide 5 — Transfer toko ke toko

- Alur: buat → setujui → kirim → terima.
- Validasi stok dan RFID.
- Penerimaan sebagian.
- Dampak: stok antar toko lebih cepat seimbang dan jejak audit jelas.

Demo: buat transfer, setujui, kirim, terima.

## Slide 6 — Sinkronisasi max diskon

- Deteksi max diskon berbeda antara POS dan e-commerce.
- Deteksi drift snapshot e-commerce dan pelanggaran cap POS.
- Aksi samakan dalam satu klik.
- Dampak: mengurangi risiko harga tidak konsisten dan kebocoran margin.

Demo: filter mismatch, samakan ke global.

## Slide 7 — EDC BCA

- Metadata terminal, approval code, batch, dan kartu.
- Estimasi MDR kredit dan debit.
- Estimasi settlement.
- Dampak: rekonsiliasi lebih cepat dan selisih lebih mudah dilacak.

Demo: buka detail transaksi dan ekspor.

## Slide 8 — Laporan pembelian per shift

- Agregasi per tanggal, store, dan shift.
- Detail sesi, produk, dan transaksi.
- Dampak: evaluasi kinerja shift dan komposisi pembelian.

Demo: filter shift dan buka detail.

## Slide 9 — Integrasi Doku dan Biteship

- Status kredensial dan environment.
- Aktivitas 30 hari terakhir.
- Panduan environment variable.
- Dampak: tim IT dan operasional punya satu sumber status.

Demo: periksa konfigurasi.

## Slide 10 — Dampak operasional

- Waktu transfer lebih singkat.
- Diskon lebih konsisten.
- Rekonsiliasi pembayaran lebih cepat.
- Laporan shift tersedia real-time.
- Risiko integrasi lebih terlihat.

## Slide 11 — Roadmap

- Mobile app untuk staf toko.
- Otomasi settlement EDC.
- Integrasi penuh Doku/Biteship setelah kredensial tersedia.
- Perluasan laporan dan AI assistant.

## Slide 12 — Penutup

- Ringkasan nilai bisnis.
- Permintaan keputusan: pilot toko, kredensial vendor, dan jadwal training.
- Sesi tanya jawab.

## Catatan demo

1. Siapkan data uji: dua toko, produk dengan RFID, dan sesi kasir.
2. Jalankan demo dalam urutan: transfer → diskon → EDC → shift → integrasi.
3. Siapkan fallback screenshot bila koneksi bermasalah.
4. Batasi demo menjadi 12 menit.
