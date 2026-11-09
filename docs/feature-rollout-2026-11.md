# Rollout Fitur Backoffice — November 2026

Dokumen ini merangkum analisis, implementasi, dan batas integrasi eksternal untuk paket fitur yang belum tersedia di backoffice.

## 1. Ringkasan implementasi

| Kebutuhan | Status | Implementasi |
|---|---|---|
| Transfer toko ke toko | Selesai | Halaman `/operations/store-transfers`, alur buat, setujui, kirim, terima, tolak, dan batal. |
| Sinkronisasi max diskon POS/e-commerce | Selesai | Halaman `/monitoring/discount-sync`, deteksi drift, pelanggaran cap, dan aksi sinkronisasi. |
| EDC BCA | Selesai | Halaman `/monitoring/edc-bca`, parsing metadata EDC, estimasi MDR, dan settlement. |
| Laporan pembelian per shift | Selesai | Halaman `/monitoring/shift-purchases`, agregasi item, produk, sesi, dan transaksi. |
| Doku dan Biteship | Selesai sebagian | Halaman `/settings/integrations` untuk kesiapan konfigurasi dan aktivitas. Integrasi API penuh tetap membutuhkan kredensial eksternal. |
| Mobile app | Dokumen | Roadmap dan spesifikasi MVP di `docs/deliverables/mobile-app.md`. |
| SOP | Dokumen | `docs/deliverables/sop-operasional.md`. |
| Presentasi | Dokumen | `docs/deliverables/presentasi.md`. |
| Training | Dokumen | `docs/deliverables/training.md`. |
| Legal/KBLI | Dokumen | `docs/deliverables/legal-kbli.md`. |

## 2. Keputusan teknis

### 2.1 Tanpa migration

Skema operasional dimiliki bersama oleh POS, WMS, dan e-commerce. Tidak ada kolom atau tabel baru. Fitur memakai kontrak yang sudah ada:

- Transfer toko memakai `store_orders` sebagai dokumen transfer dan `store_outbounds` bertipe `transfer` sebagai dokumen pengiriman.
- Sumber toko tersimpan di `store_outbounds.store_id`; toko tujuan di `store_orders.store_id`.
- Penghubung dokumen adalah `shipping_number` yang sama dengan `order_number` transfer.
- Detail produk memakai `store_order_details`; barang yang dikirim memakai `store_outbound_details`.
- Perpindahan barang memakai `items`, `store_items`, dan `item_location_histories`.

Konsekuensi:

- Transfer tidak membuat `store_inbounds`, karena kolom `warehouse_source_id` wajib dan mengarah ke gudang. Penerimaan transfer terlihat di halaman transfer dan histori lokasi barang.
- Hanya barang dengan RFID yang dapat ditransfer karena `store_outbound_details.rfid_tag_id` wajib terisi.
- Nomor transfer memakai prefiks `TRF-` dan dikecualikan dari daftar pesanan toko biasa.

### 2.2 Sinkronisasi max diskon

Sinkronisasi bekerja pada master `discounts.max_discount` dan `discount_products.applied_to`:

- `global` berarti berlaku POS dan e-commerce.
- `store` berarti POS.
- `ecommerce` berarti e-commerce.
- Drift e-commerce dihitung dari snapshot `ecommerce_order_detail_discounts` 90 hari terakhir.
- Pelanggaran cap POS dihitung dari `store_cart_details.discount_amount` yang melebihi `discounts.max_discount`.

Aksi sinkronisasi memerlukan izin `update master-data-discounts` dan tercatat otomatis oleh middleware audit.

### 2.3 EDC BCA

- Transaksi dikenali dari `payment_methods.name` yang mengandung BCA dan EDC/DEBIT/CC/KREDIT.
- Metadata terminal, approval code, batch, kartu, acquirer, dan merchant diparse dari `payment_details.detail`.
- MDR adalah estimasi dari konfigurasi, bukan nilai settlement bank final.
- Konfigurasi: `BACKOFFICE_EDC_BCA_CREDIT_MDR_PERCENT`, `BACKOFFICE_EDC_BCA_DEBIT_MDR_PERCENT`, `BACKOFFICE_EDC_BCA_SETTLEMENT_DAYS`.

### 2.4 Pembelian per shift

- Sumber pembelian: `store_visit_payments` yang terhubung ke `payments` berstatus `paid`, `store_carts`, dan `store_cart_details`.
- Shift diambil dari `store_attendances` lalu fallback ke `store_shift_schedule_details`.
- Sesi tanpa shift masuk ke kelompok `Tanpa Shift`.
- Detail menyediakan sesi, produk, dan transaksi per tanggal/store/shift.

### 2.5 Doku dan Biteship

Halaman integrasi hanya membaca konfigurasi dan aktivitas. Tidak ada panggilan API eksternal dari backoffice.

- Doku: status kredensial `DOKU_CLIENT_ID` dan `DOKU_SECRET_KEY`, aktivitas dari `payment_details` dengan metode mengandung Doku.
- Biteship: status `BITESHIP_API_KEY`, aktivitas dari `order_shipments` dengan provider `biteship`.
- Endpoint webhook tetap dimiliki aplikasi POS/e-commerce. Backoffice tidak menggantikan endpoint tersebut.

## 3. Daftar route baru

| Method | Route | Nama |
|---|---|---|
| GET | `/operations/store-transfers` | `operations.store-transfers.index` |
| GET | `/operations/store-transfers/stock` | `operations.store-transfers.stock` |
| POST | `/operations/store-transfers` | `operations.store-transfers.store` |
| GET | `/operations/store-transfers/{transfer}` | `operations.store-transfers.show` |
| PATCH | `/operations/store-transfers/{transfer}/approve` | `operations.store-transfers.approve` |
| PATCH | `/operations/store-transfers/{transfer}/ship` | `operations.store-transfers.ship` |
| PATCH | `/operations/store-transfers/{transfer}/receive` | `operations.store-transfers.receive` |
| PATCH | `/operations/store-transfers/{transfer}/reject` | `operations.store-transfers.reject` |
| PATCH | `/operations/store-transfers/{transfer}/cancel` | `operations.store-transfers.cancel` |
| GET | `/monitoring/discount-sync` | `monitoring.discount-sync.index` |
| POST | `/monitoring/discount-sync` | `monitoring.discount-sync.sync` |
| GET | `/monitoring/edc-bca` | `monitoring.edc-bca.index` |
| GET | `/monitoring/edc-bca/export` | `monitoring.edc-bca.export` |
| GET | `/monitoring/shift-purchases` | `monitoring.shift-purchases.index` |
| GET | `/monitoring/shift-purchases/detail` | `monitoring.shift-purchases.detail` |
| GET | `/monitoring/shift-purchases/export` | `monitoring.shift-purchases.export` |
| GET | `/settings/integrations` | `settings.integrations.edit` |
| POST | `/settings/integrations/verify` | `settings.integrations.verify` |

## 4. Izin yang dipakai

| Fitur | Izin |
|---|---|
| Transfer toko | `view branch-order-cabang` |
| Sinkronisasi max diskon | `view monitoring-diskon` dan `update master-data-discounts` |
| EDC BCA | `view monitoring-store-payment-transactions` |
| Pembelian per shift | `view monitoring-cashier-shift-report` |
| Integrasi Doku/Biteship | Cukup login (halaman pengaturan) |

## 5. Validasi yang sudah dijalankan

- Test fitur dan unit untuk seluruh modul baru.
- `npm run typecheck`.
- Pint dan PHPStan dijalankan pada tahap akhir.

## 6. Pekerjaan lanjutan di luar repo ini

1. Kredensial Doku dan Biteship dari vendor.
2. Aktivasi endpoint webhook di aplikasi POS/e-commerce.
3. Keputusan bisnis MDR dan jadwal settlement per acquiring bank.
4. Mobile app: backend API, autentikasi perangkat, dan desain offline-first.
5. Legal/KBLI: verifikasi kode KBLI dan perizinan melalui OSS.
