# Spesifikasi Mobile App VeeStore

Status: spesifikasi MVP, belum diimplementasikan di repo backoffice.
Tujuan: aplikasi pendamping untuk staf toko dan supervisor lapangan.

## 1. Pengguna dan peran

| Peran | Kebutuhan utama |
|---|---|
| Staf toko | Cek stok, buat transfer, terima barang, opname, cetak label. |
| Supervisor toko | Setujui transfer, koreksi opname, pantau pembelian shift. |
| Kurir/internal | Konfirmasi pengiriman dan serah terima barang. |
| Manajemen | Ringkasan performa toko dan approval cepat. |

## 2. Ruang lingkup MVP

1. Login pengguna toko dengan sesi perangkat.
2. Dashboard stok dan tugas hari ini.
3. Transfer toko ke toko: buat, setujui, kirim, terima.
4. Stock opname dengan pemindaian RFID/barcode.
5. Notifikasi approval dan barang masuk.
6. Ringkasan pembelian per shift.
7. Profil dan pengaturan perangkat.

Di luar MVP: e-commerce checkout, CRM penuh, laporan keuangan lengkap, dan aplikasi pelanggan.

## 3. Alur layar

### 3.1 Login

- Username/email dan password.
- Pemilihan toko jika pengguna memiliki akses lebih dari satu toko.
- Penyimpanan token perangkat dengan masa berlaku terbatas.
- Penanganan sesi kedaluwarsa tanpa kehilangan draft.

### 3.2 Dashboard

- Kartu: stok kritis, transfer menunggu, barang dalam perjalanan, opname berjalan.
- Daftar tugas dengan prioritas.
- Akses cepat ke pemindai dan transfer.

### 3.3 Transfer toko

- Daftar transfer dengan filter status dan tanggal.
- Form transfer: toko asal, toko tujuan, produk, qty, catatan.
- Detail transfer: timeline, produk, barang terkirim, aksi sesuai status.
- Penerimaan: input qty diterima per produk atau pindai RFID.
- Mode offline: draft transfer disimpan lokal, dikirim saat online.

### 3.4 Stock opname

- Pilih lokasi dan sesi opname.
- Pindai RFID/barcode, tampilkan selisih sistem vs fisik.
- Simpan progres lokal, sinkronkan bertahap.
- Ajukan approval dengan bukti foto.

### 3.5 Notifikasi

- Push notification untuk transfer baru, approval, barang masuk, dan stok kritis.
- Deep link ke detail terkait.

## 4. Kebutuhan backend

Backoffice saat ini belum menyediakan API mobile khusus. Yang perlu ditambahkan:

1. API Sanctum untuk login perangkat dan refresh token.
2. Endpoint transfer toko dengan kontrak yang sama seperti halaman web.
3. Endpoint stok per toko dan pencarian produk.
4. Endpoint opname dan unggah foto.
5. Endpoint notifikasi dan registrasi device token.
6. Idempotency key untuk aksi tulis saat koneksi tidak stabil.

## 5. Rekomendasi teknologi

| Opsi | Kelebihan | Catatan |
|---|---|---|
| Flutter | Satu basis kode Android/iOS, ekosistem pemindai kuat | Cocok jika tim nyaman Dart. |
| React Native | Reuse pengetahuan TypeScript/Vue | Perlu modul native untuk RFID tertentu. |
| PWA | Distribusi cepat, tanpa app store | Terbatas untuk pemindaian hardware dan background sync. |

Rekomendasi: Flutter untuk MVP karena kebutuhan pemindaian dan kamera lebih matang, dengan backend Laravel Sanctum.

## 6. Offline-first

- Simpan katalog produk, stok ringkas, dan draft transaksi di SQLite.
- Antrean aksi tulis dengan status `pending`, `syncing`, `failed`, `done`.
- Konflik diselesaikan dengan versi server dan timestamp perangkat.
- Transfer dan opname tidak boleh menghasilkan duplikasi saat retry.
- Sinkronisasi memakai delta dan batas waktu agar tidak membebani server.

## 7. Keamanan

- Token disimpan di secure storage.
- Wajib PIN/biometrik untuk membuka aplikasi.
- Sesi perangkat dapat dicabut dari backoffice.
- Audit setiap aksi tulis dengan user, perangkat, dan waktu.
- Tidak menyimpan kredensial vendor pembayaran di perangkat.

## 8. Roadmap

| Fase | Durasi | Hasil |
|---|---|---|
| 1. Discovery | 1 minggu | Validasi peran, alur, dan perangkat yang dipakai. |
| 2. API foundation | 2 minggu | Sanctum, endpoint stok, transfer, notifikasi. |
| 3. MVP app | 4 minggu | Login, dashboard, transfer, notifikasi. |
| 4. Opname | 3 minggu | Pemindaian, draft offline, approval. |
| 5. Pilot | 2 minggu | 1–2 toko, ukur waktu transfer dan akurasi stok. |
| 6. Rollout | 2 minggu | Pelatihan dan peluncuran bertahap. |

## 9. Kriteria penerimaan MVP

- Transfer dapat dibuat, disetujui, dikirim, dan diterima dari perangkat.
- Data stok dan status konsisten dengan backoffice setelah sinkronisasi.
- Tidak ada duplikasi transfer saat retry koneksi.
- Waktu muat dashboard di bawah 3 detik pada jaringan 4G.
- Semua aksi tulis tercatat di audit log.
