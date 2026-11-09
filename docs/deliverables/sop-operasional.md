# SOP Operasional Backoffice VeeStore

Dokumen ini menjadi acuan kerja harian untuk staf backoffice, supervisor toko, dan tim keuangan.

## 1. Peran dan tanggung jawab

| Peran | Tanggung jawab |
|---|---|
| Staf toko | Membuat permintaan transfer, menyiapkan barang, menerima barang. |
| Supervisor toko | Menyetujui transfer, memverifikasi qty, menangani selisih. |
| Admin backoffice | Memantau antrean, menyinkronkan diskon, memeriksa integrasi. |
| Tim keuangan | Meninjau MDR EDC BCA, settlement, dan laporan shift. |
| Tim IT | Menjaga kredensial Doku/Biteship dan endpoint webhook. |

## 2. SOP Transfer Toko ke Toko

### 2.1 Membuat transfer

1. Buka **Operasional → Transfer Toko ke Toko**.
2. Klik **Buat Transfer**.
3. Pilih toko asal dan toko tujuan. Keduanya tidak boleh sama.
4. Cari produk yang tersedia. Hanya barang dengan RFID yang dapat ditransfer.
5. Isi qty. Sistem menolak qty melebihi stok tersedia.
6. Isi catatan bila perlu, lalu simpan.
7. Status awal: **Menunggu persetujuan**.

### 2.2 Menyetujui

1. Supervisor membuka detail transfer.
2. Periksa produk, qty, dan ketersediaan.
3. Klik **Setujui** atau **Tolak**.
4. Jika ditolak, transfer tidak dapat dikirim. Buat transfer baru bila perlu.

### 2.3 Mengirim

1. Staf toko asal menyiapkan barang sesuai daftar.
2. Klik **Kirim dari Toko Asal**.
3. Sistem mengalokasikan barang, mencatat RFID, dan mengubah status barang menjadi dalam perjalanan.
4. Cetak atau simpan nomor transfer sebagai bukti.

### 2.4 Menerima

1. Staf toko tujuan membuka detail transfer.
2. Klik **Terima Barang**.
3. Isi qty diterima per produk. Qty yang belum diterima tetap dalam perjalanan.
4. Konfirmasi. Barang berpindah ke stok toko tujuan dan tercatat di histori lokasi.

### 2.5 Selisih dan pembatalan

- Jika qty fisik kurang, terima sebagian dan koordinasikan dengan toko asal.
- Transfer yang belum dikirim dapat dibatalkan oleh supervisor.
- Transfer yang sudah dikirim tidak dapat dibatalkan; selesaikan penerimaan atau buat retur sesuai kebijakan.

## 3. SOP Sinkronisasi Max Diskon

1. Buka **Laporan → Sinkronisasi Max Diskon**.
2. Filter **Max diskon berbeda** untuk melihat ketidaksesuaian POS dan e-commerce.
3. Periksa kolom Max POS, Max E-commerce, drift e-commerce, dan pelanggaran cap POS.
4. Klik **Samakan** pada grup yang bermasalah.
5. Pilih nilai baru:
   - **Ikuti POS** atau **Ikuti E-commerce** untuk menyamakan ke salah satu kanal.
   - **Tanpa batas** bila cap memang tidak diperlukan.
6. Pilih cakupan kanal:
   - **Global** agar POS dan e-commerce memakai aturan yang sama.
   - **POS saja** atau **E-commerce saja** bila memang berbeda.
7. Simpan dan verifikasi ulang daftar.

Catatan: sinkronisasi hanya untuk diskon persentase. Diskon nominal tidak memiliki cap.

## 4. SOP EDC BCA

1. Buka **Laporan → EDC BCA**.
2. Pilih periode dan toko.
3. Periksa total transaksi, gross, estimasi MDR, dan estimasi settlement.
4. Buka detail transaksi untuk melihat terminal, approval code, batch, dan kartu.
5. Bandingkan estimasi settlement dengan mutasi bank.
6. Jika ada selisih:
   - Pastikan metode pembayaran sudah benar.
   - Periksa apakah transaksi refund/void sudah tercatat.
   - Eskalasi ke tim keuangan bila selisih tetap ada.
7. Ekspor laporan untuk rekonsiliasi.

## 5. SOP Laporan Pembelian per Shift

1. Buka **Laporan → Pembelian per Shift**.
2. Pilih tanggal, store, shift, atau kasir.
3. Tinjau ringkasan gross, diskon, net, dan jumlah item.
4. Klik **Detail** untuk melihat sesi, produk, dan transaksi.
5. Ekspor untuk lampiran laporan harian.

## 6. SOP Pemeriksaan Integrasi

1. Buka **Operasional → Administrasi → Integrasi Doku & Biteship**.
2. Klik **Periksa Konfigurasi**.
3. Status **Siap** berarti kredensial wajib sudah terisi.
4. Status **Kredensial belum lengkap** berarti tim IT harus melengkapi environment.
5. Status **Belum diaktifkan** berarti integrasi memang dimatikan.
6. Periksa aktivitas 30 hari terakhir untuk memastikan tidak ada kegagalan menumpuk.

## 7. Checklist harian

- [ ] Antrean transfer menunggu persetujuan sudah ditinjau.
- [ ] Transfer dalam perjalanan sudah diterima atau dilaporkan.
- [ ] Tidak ada drift max diskon baru.
- [ ] Transaksi EDC BCA sudah direkonsiliasi.
- [ ] Laporan pembelian shift sudah diekspor.
- [ ] Integrasi Doku/Biteship tanpa kegagalan kritis.

## 8. Eskalasi

| Masalah | Kontak |
|---|---|
| Stok tidak cukup saat transfer | Supervisor toko dan admin stok. |
| Selisih penerimaan | Supervisor toko asal dan tujuan. |
| Drift diskon masif | Tim promo dan IT. |
| Selisih settlement EDC | Tim keuangan dan bank acquiring. |
| Integrasi gagal | Tim IT dan vendor. |
