# Audit performa backoffice

Tanggal audit: 2026-11-04
Fokus: jalur operasional dan laporan saat tabel berisi puluhan sampai ratusan ribu baris.

## Perubahan pada audit ini

- Batas `per_page` pada request web sekarang 1–100, dengan nilai default 20 untuk input yang tidak valid. Ini mencegah satu URL meminta seluruh tabel melalui nilai halaman yang sangat besar.
- Tabel audit aktivitas dan status notifikasi dibaca serta ditulis melalui koneksi `immostore_backoffice`, sesuai pemisahan database aplikasi. Filter tanggal audit memakai rentang waktu setengah terbuka sehingga indeks `occurred_at` tetap dapat dipakai.
- Migration audit/notifikasi menargetkan database backoffice. Migration tambahan memberi indeks gabungan untuk daftar export milik user, status export aktif, antrean export, serta audit berdasarkan aksi dan waktu.
- Query stok warehouse/store membaca agregat stok dan barrier melalui cursor database, sehingga tidak membuat `Collection` penuh tambahan untuk hasil query tersebut. Proses berikutnya tetap membangun peta stok penuh di PHP; ini menurunkan salinan sementara, tetapi belum membuat halaman stok aman untuk ratusan ribu kombinasi.

## Temuan utama

### Prioritas tinggi: stok operasional dimuat sekaligus ke PHP

`OperationalStockQuery` dan `StoreStockOperationsQuery` mengambil seluruh agregat stok, barrier, kecepatan outbound, kebutuhan import, stok hilang, dan rekonsiliasi. Data kemudian digabung, dikelompokkan, dan diurutkan di PHP sebelum seluruh daftar dikirim ke Inertia. Ukuran `per_page` tidak membatasi jalur ini karena query tersebut tidak memakai paginator.

Pada data besar, biaya tumbuh bersama kombinasi produk dan lokasi, lalu diduplikasi oleh array PHP, payload JSON, dan state browser. Halaman ini masih perlu read model SQL yang mengagregasi serta mem-paginasi produk di database; detail lokasi hanya diambil untuk produk pada halaman aktif. Daftar import, missing stock, dan rekonsiliasi juga perlu paginator tersendiri.

### Prioritas tinggi: agregasi CRM mengulang pemindaian lintas tabel

`CustomerDemographicsQuery::build()` menjalankan beberapa distribusi dan ringkasan dari basis query yang menggabungkan kunjungan, pembayaran, order ecommerce, membership, dan poin. Ranking histori membership serta poin dibangun ulang untuk ringkasan, grafik, dan tabel. Paginasi tabel sudah ada, tetapi agregasi sebelum tabel tetap memproses kumpulan data yang luas pada tiap request.

Langkah berikutnya adalah mengukur query di staging dengan `EXPLAIN (ANALYZE, BUFFERS)`, lalu mempertimbangkan ringkasan harian atau materialized view yang memiliki kebijakan refresh jelas. Skema operasional dikelola bersama POS/WMS/ecommerce, jadi indeks atau perubahan skema pada database tersebut harus dirilis bersama pemilik database.

### Prioritas tinggi: sejumlah export masih sinkron dan mengumpulkan semua baris

Antrian export sudah tersedia untuk Ecommerce Payment, Promo Usage, dan Monitoring Data Customer. Export laporan lain masih memakai `Excel::download()` langsung. Contohnya CRM dashboard, demografi customer, voucher, pembayaran store, dan sebagian laporan stok. Beberapa jalur membuat `Collection` penuh sebelum workbook dibentuk.

Gunakan antrian untuk export besar, baca hasil query bertahap, beri batas/rentang filter yang jelas, dan tetapkan retensi file hasil. `ShouldAutoSize` juga perlu dihindari untuk lembar yang sangat besar karena pengukuran lebar kolom memeriksa isi baris; gunakan lebar kolom tetap pada export besar.

### Prioritas sedang: pencarian substring sulit memanfaatkan indeks B-tree

Banyak pencarian memakai `ILIKE '%kata%'`. Pada tabel produk dan customer besar, indeks B-tree biasa tidak membantu pola ini. PostgreSQL `pg_trgm` dapat menjadi opsi untuk kolom pencarian yang sering dipakai, setelah rencana query dan beban tulis diperiksa di staging.

### Prioritas sedang: sebagian query mengubah kolom waktu/status saat filter

Ekspresi seperti `occurred_at::date`, `LOWER(status::text)`, dan `COALESCE(timestamp_a, timestamp_b)` dapat membuat indeks biasa tidak terpakai. Perbaikan audit center menghapus cast tanggal untuk filter waktu. Untuk laporan lain, gunakan rentang waktu pada kolom mentah bila semantiknya sama dan verifikasi rencana query sebelum mengubah perilaku status.

## Panduan indeks operasional untuk divalidasi

Jangan menerapkan indeks ini langsung tanpa rencana query dan koordinasi database. Kandidat yang sesuai pola join saat ini:

- `warehouse_items(item_id, warehouse_id)` dan `store_items(item_id, store_id)`.
- `items(status, product_id)`; inbound/outbound detail pada foreign key dokumen dan item.
- `stock_reservations(handler_type, status, handler_id, product_id)`.
- `customer_memberships(customer_id, status, tier_evaluated_at, updated_at)` dan `customer_loyalty_points(customer_id, updated_at, created_at)`.
- `store_customer_visits(customer_id, created_at)`, payment bridge pada `visit_id`/`payment_id`, dan order ecommerce pada `customer_id`, `payment_id`, serta waktu transaksi yang benar-benar dipakai.

Pilih kolom dan urutan indeks berdasarkan `EXPLAIN (ANALYZE, BUFFERS)` pada data representatif. Indeks mempercepat baca tertentu tetapi menambah biaya insert/update dan ruang disk.

## Batas audit

Repo ini mengelola sebagian tabel backoffice, tetapi tidak memiliki migration untuk mayoritas tabel POS, WMS, ecommerce, dan laporan operasional. Perbaikan indeks, partitioning, arsip transaksi, serta kapasitas Redis/queue worker harus diuji dan dirilis bersama pemilik database/infrastruktur. Angka performa belum dilaporkan karena benchmark representatif pada dataset produksi/staging belum dijalankan.
