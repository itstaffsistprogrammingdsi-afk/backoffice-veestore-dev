# Matriks Kelayakan Data untuk Laporan Baru

**Tanggal:** 12 November 2026  
**Dasar:** `docs/AUDIT_GAP_LAPORAN_EKOSISTEM_RETAIL.md` bagian 4, 5, dan 6  
**Sumber data:** `veestore_dev1` dan `veestore_bo_dev`  
**Keputusan:** laporan hanya dibuat jika tabel dan kolom sumber benar-benar ada. Tidak ada angka pengganti.

## 1. Ringkasan

| Kelayakan | Jumlah item | Arti |
|---|---:|---|
| Penuh | 1 | seluruh field minimum tersedia dan dapat dihitung langsung |
| Parsial | 17 | sebagian field tersedia; laporan wajib berlabel "Parsial" dan menyebut komponen yang tidak ada |
| Tidak layak | 6 | membutuhkan data yang tidak ada di database; jangan dibuat sebagai laporan angka |

Item yang layak Penuh pada tahap ini hanya **AR Mitra/Paylater berbasis invoice** (`store_receivable_invoices` + `store_receivable_payment_allocations`), dengan catatan bahwa tabelnya masih kosong pada snapshot. Item lain yang menyangkut keuangan penuh (GL, pajak, settlement bank, backup, three-way matching) tidak layak.

## 2. Matriks GAP-01 sampai GAP-08

| ID | Laporan | Tabel dan kolom sumber | Kelayakan | Apa yang bisa dihitung | Apa yang tidak bisa dan kenapa | Cara penyajian yang jujur |
|---|---|---|---|---|---|---|
| GAP-01 | Rekonsiliasi stok lintas sistem | `items.status`, `items.current_location_id`, `items.current_condition_id`, `warehouse_items`, `store_items`, `stock_reservations`, `warehouse_outbounds`, `store_outbounds`, `item_location_histories` | Parsial | On-hand per SKU/lokasi dari `items` berstatus `warehouse_stock`/`store_stock`/`store_display`; reservasi aktif per produk/lokasi; qty in-transit dari outbound yang belum diterima; selisih status item vs lokasi; umur item dari histori | Tidak ada saldo stok independen per sistem. POS, WMS, dan e-commerce membaca satu tabel `items`; e-commerce tidak punya saldo stok sendiri. Tidak ada ledger mutasi berarah yang bisa membuktikan saldo awal + mutasi = saldo akhir. Tidak ada kolom "sumber transaksi terakhir" per SKU. | Judul: "Rekonsiliasi Status Stok Lintas Kanal (Parsial)". Tampilkan on-hand, reserved, in-transit, dan item tanpa lokasi. Sebut bahwa ini rekonsiliasi status item, bukan rekonsiliasi dua ledger independen. Jangan menyebut "selisih antar sistem" jika tidak ada dua saldo independen. |
| GAP-02 | Rekonsiliasi settlement payment ke bank | `payments` (`total_amount`, `paid_amount`, `status`, `completed_at`, `app`), `payment_details` (`amount`, `status`, `transaction_ref`, `detail` JSON), `ecommerce_orders` (`grand_total`, `payment_fee`), `payment_exceptions` (`kind`, `amount`, `status`, `refund_amount`) | Parsial | Perbandingan order vs payment vs status gateway; pembacaan `settlement_time` dan `transaction_status` dari `payment_details.detail`; exception payment; refund yang tercatat di `payment_exceptions` | Tidak ada settlement bank, MDR/fee gateway, nilai neto settlement, chargeback, atau rekonsiliasi rekening koran. `payment_details.detail` hanya memuat respons gateway, bukan mutasi bank. Tidak ada tanggal settlement bank. | Judul: "Perbandingan Order vs Payment vs Gateway (Parsial)". Tampilkan bruto, status gateway, `settlement_time`, dan exception. Untuk rekonsiliasi bank, gunakan pola unggah CSV rekening koran di memori: file diproses terhadap order/payment, hasil ditampilkan dan diekspor, tidak disimpan ke database. Beri label "Tidak termasuk MDR/fee bank". |
| GAP-03 | Rekonsiliasi persediaan ke akuntansi | `items`, `product_prices` (`price_type='base'`), `warehouse_inbound_details.price_idr`, `store_opname_session_items`, `warehouse_stock_opname_details`, `store_return_details` | Tidak layak untuk rekonsiliasi akuntansi; Parsial untuk kuantitas/nilai estimasi | Kuantitas persediaan per lokasi/kondisi; nilai estimasi memakai harga `base` atau `price_idr`; selisih opname; retur dan write-off yang tercatat di opname | Tidak ada COGS, average/FIFO cost, jurnal persediaan, saldo subledger, atau saldo GL. `product_prices.base` bukan HPP (1.443/1.444 produk base = store). Tidak ada write-off ledger. | Jangan membuat "Rekonsiliasi Persediaan ke Akuntansi". Jika dibuat, judulnya "Valuasi Persediaan Estimasi (Parsial)" dan sebut basis harga yang dipakai. Rekonsiliasi ke GL menunggu data jurnal. |
| GAP-04 | Jurnal dan rekonsiliasi General Ledger | Tidak ada tabel jurnal/GL | Tidak layak | Tidak ada yang bisa dihitung sebagai jurnal | Tidak ada chart of accounts, debit/kredit, posting, control total, atau jurnal penjualan/pajak/COGS. | Jangan dibuat. Catat kebutuhan data: tabel jurnal, COA, periode akuntansi, dan proses posting. |
| GAP-05 | Three-way matching pembelian | `warehouse_inbounds` (`supplier_id`, `invoice_number`, `delivery_order_number`, `grand_total`, `received_date`), `warehouse_inbound_details` (`quantity`, `price_idr`) | Tidak layak untuk three-way matching; Parsial untuk daftar penerimaan vs nomor invoice | Daftar penerimaan per supplier; total qty dan nilai penerimaan; perbandingan `grand_total` header vs jumlah baris; keberadaan nomor invoice | Tidak ada purchase order, purchase order item, invoice supplier terpisah, due date, payment status, atau approval invoice. `invoice_number` kosong pada 16 dari 16 penerimaan. Tidak ada toleransi harga/kuantitas. | Jangan menyebut three-way matching. Jika perlu, buat "Daftar Penerimaan Supplier (Parsial)" tanpa klaim matching. Catat kebutuhan data PO dan invoice AP. |
| GAP-06 | Pajak dan e-faktur | `payments.tax_amount` (semua 0), `ecommerce_orders` (tidak ada kolom pajak), tidak ada tabel pajak/e-faktur | Tidak layak | Tidak ada DPP/PPN yang dapat dihitung | Tidak ada tarif pajak, DPP, PPN keluaran/masukan, nomor faktur, status e-faktur, atau retur pajak. `tax_amount` tidak terisi. | Jangan dibuat. Catat kebutuhan data pajak dan e-faktur. |
| GAP-07 | Segregation of Duties dan access certification | `users` (`backoffice_access`, `store_access`, `wms_access`, `ecommerce_access`, `deleted_at`), `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `session_logs`, `business_audit_logs`, `store_opname_approval_audits`, `partner_paylater_audit_logs` | Parsial | Daftar user, role, permission, akses aplikasi, last login, akun dormant, user soft-deleted yang masih punya role, akses lintas outlet (jika relasi user-store tersedia), approval opname/paylater, konflik maker-approver dari audit log terbatas | Tidak ada access certification campaign, target review, risk score, atau daftar konflik SoD yang disetujui. Relasi user-store tidak terlihat sebagai tabel publik di snapshot; perlu konfirmasi. `business_audit_logs` tidak menyimpan diff nilai. | Judul: "Review Akses dan SoD (Parsial)". Tampilkan user, role, akses, last login, dan exception. Jangan menyebut "access certification" penuh. |
| GAP-08 | Backup, restore, dan disaster recovery | Tidak ada tabel backup/restore/DR | Tidak layak | Tidak ada status backup yang bisa dihitung | Tidak ada metadata backup, umur backup, lokasi salinan, hasil restore, RTO/RPO, job failure, atau tanggal DR test. `failed_jobs` bukan backup. | Jangan dibuat. Catat kebutuhan integrasi dengan sistem backup eksternal. |

## 3. Matriks REC-01 sampai REC-08

| ID | Laporan | Tabel dan kolom sumber | Kelayakan | Apa yang bisa dihitung | Apa yang tidak bisa dan kenapa | Cara penyajian yang jujur |
|---|---|---|---|---|---|---|
| REC-01 | Daily Control Tower | `payments`, `payment_exceptions`, `ecommerce_orders`, `order_shipments`, `stock_reservations`, `store_opname_sessions`, `warehouse_stock_opnames`, `failed_jobs` (BO DB), `business_audit_logs` | Parsial | Sales hari ini, payment exception, order backlog, shipment gagal/retry, stok di bawah barrier, opname belum approval, failed job, aktivitas audit | Tidak ada incident management, SLA antar sistem, atau status integrasi real-time. Tidak ada data bank. | Judul: "Control Tower Harian (Parsial)". Setiap kartu menyebut source of truth dan waktu data terakhir. Exception yang tidak punya data (bank/GL) tidak ditampilkan. |
| REC-02 | Outlet Profitability | `store_carts`, `store_cart_details`, `store_visit_payments`, `payments`, `ecommerce_orders`, `product_prices.base`, `store_opname_session_items`, `store_returns`, `store_attendances` | Parsial | Penjualan per outlet, diskon, nilai base, selisih opname, retur, jam kerja/kehadiran | Tidak ada beban per outlet. `app_settings.pnl_expenses` hanya total periode, tanpa alokasi outlet. Tidak ada HPP. Tidak ada biaya tenaga kerja. | Judul: "Kontribusi Outlet (Parsial)". Tampilkan penjualan, margin terhadap harga dasar, shrinkage, dan retur. Jangan menyebut laba outlet. Beban hanya total, bukan alokasi. |
| REC-03 | Product Profitability | `store_cart_details`, `ecommerce_order_details`, `payments`, `payment_details`, `product_prices.base`, `store_return_details`, `warehouse_stock_opname_details` | Parsial | Margin setelah diskon produk/voucher terhadap harga dasar; retur per produk (jika item tertaut); write-off dari opname per produk; payment fee e-commerce dapat dialokasikan dari `ecommerce_orders.payment_fee` | Tidak ada HPP, biaya kirim aktual per produk, alokasi fee gateway per produk, atau write-off ledger. Retur tidak tertaut ke penjualan asal. | Judul: "Estimasi Profitabilitas Produk (Parsial)". Tampilkan margin terhadap harga dasar dan komponen yang tersedia; sebut fee/ongkir/HPP tidak dialokasikan. |
| REC-04 | Customer LTV dan Churn | `customers`, `store_customer_visits`, `store_visit_payments`, `payments`, `ecommerce_orders`, `ecommerce_order_details`, `customer_loyalty_point_mutations` | Parsial | Nilai belanja per pelanggan, frekuensi, recency, repeat purchase, churn risk berbasis tanggal transaksi terakhir | Tidak ada biaya akuisisi, campaign cost, atau atribusi marketing. Tidak ada definisi churn yang disetujui. | Judul: "LTV dan Churn (Parsial)". Tampilkan nilai, frekuensi, recency; definisikan churn yang dipakai. Jangan menyebut CAC/CLV finansial. |
| REC-05 | Workforce Productivity | `store_attendances`, `store_attendance_logs`, `store_attendance_shifts`, `cashier_sessions`, `store_visit_payments`, `void_transactions` | Parsial | Sales per jam kerja dari clock in/out, transaksi per kasir, void rate, selisih jam terjadwal vs aktual, attendance variance | Tidak ada biaya tenaga kerja, target produktivitas, atau data jadwal penuh di luar `store_shift_schedules`. Tidak ada definisi "jam kerja efektif". | Judul: "Produktivitas Tenaga Kerja (Parsial)". Tampilkan penjualan per jam dan variance kehadiran; sebut tidak termasuk biaya tenaga kerja. |
| REC-06 | Integration Health | `order_shipments` (`integration_status`, `last_error_code`, `last_error_message`, `attempt_count`, `next_retry_at`, `synced_at`), `order_shipment_events`, `failed_jobs`, `payment_details` (Doku), `stock_reservations` | Parsial | Status integrasi Biteship, retry, error terakhir, event terakhir, failed job, aktivitas Doku 30 hari | Tidak ada event bus/queue lintas POS-WMS-e-commerce, latency per sistem, dead-letter event, atau duplicate event. Tidak ada status sinkronisasi stok POS vs WMS. | Judul: "Kesehatan Integrasi (Parsial)". Tampilkan shipment gagal/retry, failed job, dan aktivitas gateway. Sebut cakupan hanya integrasi yang tercatat. |
| REC-07 | Exception Aging | `payment_exceptions` (`detected_at`, `resolved_at`, `resolved_by`, `status`, `amount`), `order_shipments` (error), `store_orders` (created_by/approved_by), `store_returns`, `store_opname_sessions` (approved_by), `payments` pending | Parsial | Umur exception, nilai, status, PIC jika kolom tersedia; exception pembayaran, shipment, order, retur, opname, pembayaran pending | Tidak ada tabel exception terpadu. Tidak semua exception punya PIC atau target waktu. Tidak ada escalation. `payment_exceptions` kosong. | Judul: "Exception Aging (Parsial)". Tampilkan jenis, nilai, umur, PIC yang benar-benar ada; kolom PIC kosong jika data tidak ada. Jangan mengarang target waktu. |
| REC-08 | Cutoff dan Late Posting | `payments.completed_at` vs `created_at`, `ecommerce_orders.created_at` vs `payments.completed_at`, `business_audit_logs.occurred_at`, `order_shipment_events.occurred_at` vs `received_at`, `store_opname_sessions` timestamp | Parsial | Transaksi yang dibayar setelah tanggal order; event yang diterima terlambat; perubahan master di luar jam kerja; selisih waktu input vs kejadian | Tidak ada periode akuntansi tertutup, cutoff resmi, atau tabel late posting. Tidak ada backdate flag. | Judul: "Cutoff dan Late Posting (Parsial)". Tampilkan selisih waktu dan daftar transaksi terlambat; sebut tidak ada periode tutup buku. |

## 4. Matriks bagian 5: laporan yang tersedia tetapi belum memadai

| ID | Laporan | Tabel dan kolom sumber | Kelayakan | Apa yang bisa dihitung | Apa yang tidak bisa dan kenapa | Cara penyajian yang jujur |
|---|---|---|---|---|---|---|
| 5.1 | Payment Reconciliation (order vs payment vs settlement) | Lihat GAP-02 | Parsial | Order vs payment vs status gateway vs `settlement_time` | Tidak ada settlement bank, MDR, fee, chargeback, atau neto | "Perbandingan Order vs Payment vs Gateway (Parsial)"; bank statement upload di memori |
| 5.2 | Laba Rugi | `store_carts`, `store_cart_details`, `ecommerce_orders`, `ecommerce_order_details`, `payments`, `product_prices.base`, `app_settings.pnl_expenses` | Parsial | Omset produk, diskon, nilai base, beban manual, laba operasional estimasi | Tidak ada HPP, payment fee POS, ongkir sebagai beban, pajak, penyusutan, provisi, alokasi outlet, GL | "Estimasi Laba Operasional (Parsial)"; jangan sebut laba rugi akuntansi; tampilkan komponen yang tidak termasuk |
| 5.3 | Piutang dan Hutang | Manual: `app_settings.receivables_payables`; AR mitra: `store_receivable_invoices`, `store_receivable_payment_allocations`, `partner_invoice_batches`, `partners`; AP supplier: `warehouse_inbounds` | Parsial untuk AR mitra; Tidak layak untuk AP aging | AR mitra: invoice, due date, amount, paid, late fee, aging, payment allocation, batch; catatan manual: daftar dan status | AP supplier tidak punya invoice/due date/payment. Catatan manual bukan subledger. Tidak ada credit note/debit note. | Pisahkan "AR Mitra (Paylater)" dari "Catatan Piutang/Hutang Manual". Jangan menyebut aging AP/AR untuk catatan manual. |
| 5.4 | Supplier Performance | `suppliers`, `warehouse_inbounds`, `warehouse_inbound_details`, `warehouse_qc`, `store_return_details`, `items` | Parsial | Penerimaan, qty, nilai, damage rate, return rate, produk bermasalah, harga beli per penerimaan | Tidak ada PO, due date, kontrak, on-time/in-full, lead time kontrak, price variance, invoice discrepancy, claim recovery | "Kinerja Supplier (Parsial)": kualitas barang dan volume. Jangan menghitung OTIF tanpa PO. |
| 5.5 | Inventory Management KPI | `items`, `warehouse_items`, `store_items`, `store_cart_details`, `ecommerce_order_details`, `product_prices.base`, `store_opname_session_items`, `stock_reservations` | Parsial | Inventory turnover (qty), days of inventory, sell-through, stockout, shrinkage dari opname, excess stock, near-expiry, dead stock | Tidak ada HPP/COGS untuk GMROI dan nilai shrinkage; tidak ada lost sales; forecast accuracy hanya jika data AI tersedia (0 baris) | "KPI Persediaan (Parsial)": KPI berbasis qty dan estimasi nilai base. Sebut GMROI/lost sales tidak dihitung. |
| 5.6 | E-commerce Analytics | `ecommerce_orders`, `ecommerce_order_details`, `ecommerce_carts`, `customers`, `payments`, `product_reviews` | Parsial | Repeat purchase, revenue per order, return/refund yang tercatat, konversi order vs cart (kasar) | Tidak ada product view, add-to-cart event, checkout step, payment failure funnel, CAC, cohort retention, promo attribution, search without results | "Analitik E-commerce (Parsial)": transaksi dan repeat purchase. Jangan menyebut funnel/conversion penuh. |
| 5.7 | Fulfillment dan Kurir | `order_shipments` (`quoted_shipping_price`, `provider_shipping_price`, `provider_status`, `provider_status_at`), `order_shipment_events`, `ecommerce_orders`, `store_outbounds`, `warehouse_outbounds` | Parsial | Status pickup/delivery dari event, AWB, biaya kuotasi vs aktual, order cycle time paid-to-delivered, failed delivery/return-to-origin jika event tersedia | Tidak ada SLA pickup/delivery per kurir, first-attempt success, claim kurir, atau berat/dimensi aktual | "Performa Fulfillment & Kurir (Parsial)": status event dan biaya. Sebut SLA per kurir tidak tersedia. |
| 5.8 | Promo Profitability | `promos`, `discounts`, `discount_products`, `vouchers`, `voucher_usages`, `ecommerce_order_details`, `ecommerce_order_detail_discounts`, `store_cart_details`, `store_carts` | Parsial | Pemakaian promo/voucher, diskon, margin setelah diskon terhadap harga base, repeat order setelah promo, promo abuse sederhana | Tidak ada incremental sales, cannibalization, biaya promo per pihak, campaign cost, atau baseline tanpa promo | "Profitabilitas Promo (Parsial)": usage dan margin terhadap harga base. Jangan menyebut ROI/break-even tanpa biaya promo dan baseline. |

## 5. Prioritas implementasi yang disarankan

| Fase | Item | Alasan | Status kelayakan |
|---|---|---|---|
| Fase 1a | Perbaikan laporan yang sudah ada (F-01, F-03, F-04, F-09, F-10, F-11, F-12) | Bug hitung/keamanan yang sudah terbukti, tidak menunggu data baru | Bisa dikerjakan setelah keputusan process owner untuk definisi |
| Fase 1b | Rekonsiliasi stok status lintas kanal (GAP-01) | Risiko overselling; data status/reservasi tersedia | Parsial |
| Fase 1c | Perbandingan order vs payment vs gateway (GAP-02) | Dapat mendeteksi pembayaran tidak cocok; settlement bank belum ada | Parsial |
| Fase 1d | Review akses dan SoD (GAP-07) | Kontrol internal; data role/audit tersedia | Parsial |
| Fase 2a | AR Mitra/Paylater berbasis invoice (5.3) | Skema invoice, due date, pembayaran, allocation, batch sudah ada | Penuh (operasional), bukan AR akuntansi |
| Fase 2b | Exception Aging (REC-07) | Menggabungkan exception yang sudah ada | Parsial |
| Fase 2c | Integration Health (REC-06) | Shipment error/retry dan failed job tersedia | Parsial |
| Fase 2d | Supplier Performance (5.4) | Kualitas barang dan volume tersedia | Parsial |
| Fase 2e | Inventory KPI (5.5) | Qty dan estimasi nilai base tersedia | Parsial |
| Fase 2f | Outlet/Product Profitability (REC-02/03) | Margin terhadap base tersedia; HPP/fee belum | Parsial |
| Fase 3a | Customer LTV/Churn (REC-04) | Data transaksi pelanggan tersedia | Parsial |
| Fase 3b | E-commerce Analytics (5.6) | Repeat purchase tersedia; funnel tidak | Parsial |
| Fase 3c | Promo Profitability (5.8) | Usage tersedia; incremental/ROI tidak | Parsial |
| Fase 3d | Workforce Productivity (REC-05) | Attendance dan sales tersedia | Parsial |
| Tidak dibuat | GAP-03, GAP-04, GAP-05, GAP-06, GAP-08, AP aging | Data sumber tidak ada | Tidak layak |

## 6. Performa dan rekomendasi index

Pengujian `EXPLAIN (ANALYZE, BUFFERS)` pada query contoh:

| Query | Rencana | Waktu | Catatan |
|---|---|---:|---|
| Stok WMS: `items` join `warehouse_items` filter `status='warehouse_stock'` | Parallel seq scan `items` + parallel hash join `warehouse_items` | ±53 ms untuk 148 ribu item | Tidak ada index pada `items(status)`; join `warehouse_items(item_id)` juga tidak diindeks. Aman untuk snapshot, tetapi perlu index jika volume naik. |
| Pembayaran POS per periode | Seq scan `store_visit_payments` + seq scan `payments` | ±0,1 ms untuk 45 baris | Tidak ada index `payments(completed_at,status)` atau `store_visit_payments(visit_id)`. |
| Order e-commerce per periode | Seq scan `ecommerce_orders` + `payments` | ±0,1 ms untuk 32 baris | Index `ecommerce_orders(created_at,status)` sudah ada; `payments` belum punya index waktu/status. |
| Payment detail per periode | Seq scan `payment_details` + `payments` | ±0,2 ms untuk 32 baris | Tidak ada index `payment_details(payment_id)`. |

Rekomendasi untuk DBA (jangan dibuat oleh backoffice tanpa persetujuan):

1. `payments (status, completed_at)` dan `payments (deleted_at)` untuk laporan pembayaran.
2. `payment_details (payment_id)` untuk agregasi metode pembayaran.
3. `store_visit_payments (visit_id)`, `(payment_id)`, `(cashier_session_id)` untuk join laporan POS.
4. `ecommerce_order_details (ecommerce_order_id)` sudah ada; tambahkan `product_id` jika perlu drill-down.
5. `items (status)` atau partial index per status stok untuk laporan stok; `warehouse_items (item_id)` dan `store_items (item_id)` untuk join.
6. `warehouse_inbounds (received_date, supplier_id)` untuk supplier insight.
7. `order_shipments (integration_status, next_retry_at)` sudah ada sebagian; tambahkan `(updated_at)` untuk integration health.
8. `business_audit_logs (occurred_at, action)` di DB backoffice.

Tanpa index baru, laporan wajib memakai periode, filter lokasi, dan pagination. Jangan menjalankan laporan stok snapshot tanpa batas lokasi pada volume produksi.

## 7. Pertanyaan untuk process owner

Pertanyaan berikut mengubah angka laporan. Jangan dijawab dengan asumsi.

1. **Definisi omzet:** apakah ongkir dan payment fee e-commerce termasuk "penjualan/omzet"? Saat ini Ringkasan Usaha memakai nilai pembayaran termasuk ongkir + fee (Rp1.117.500 pada 24 Oktober), sedangkan Omset Kotor memakai nilai produk (Rp1.050.999,99). Mana yang menjadi definisi resmi?
2. **HPP:** apakah `product_prices.base` adalah harga pokok (HPP), harga dasar/MSRP, atau harga normal? Jika bukan HPP, dari mana sumber HPP yang benar? Tanpa jawaban ini, "Laba Rugi" tidak boleh disebut laba.
3. **Tanggal transaksi:** untuk laporan penjualan, apakah memakai tanggal pembayaran, tanggal order, atau tanggal pengiriman? Saat ini store memakai tanggal pembayaran dan e-commerce memakai tanggal order.
4. **Retur:** bagaimana retur toko mengurangi penjualan jika `store_return_details` tidak menyimpan `cart_detail_id`/`payment_id`? Apakah POS akan menautkan retur ke penjualan asal, atau laporan harus memakai pencocokan item + tanggal?
5. **Pembulatan diskon:** apakah selisih pembulatan alokasi voucher/poin per baris (contoh Rp104 pada 27 Oktober) dapat diterima, atau laporan harus merekonsiliasi ke nilai pembayaran?
6. **Shift lintas hari:** sesi kasir yang buka tanggal 24 dan tutup tanggal 27 harus masuk omzet tanggal buka atau tanggal tutup?
7. **`paid_amount` e-commerce:** apakah POS/e-commerce akan mengisi `paid_amount` untuk e-commerce, atau backoffice harus selalu memakai `total_amount` sebagai fallback? Jangan mengubah sistem lain tanpa persetujuan.
8. **Beban operasional:** siapa yang boleh menambah/menghapus beban laba rugi? Apakah perlu approval, periode terkunci, dan jejak nilai lama?
9. **AR/AP:** apakah catatan manual piutang/hutang akan dipensiunkan dan diganti AR Mitra/Paylater? Apakah data invoice/PO supplier tersedia di sistem lain?
10. **Akses:** laporan mana yang boleh dilihat outlet/gudang sendiri, dan mana yang hanya HO? Apakah row-level access harus diterapkan pada semua laporan stok dan penjualan?
11. **Ekspor sensitif:** ekspor laporan apa saja yang wajib dicatat ke audit log? Apakah semua ekspor atau hanya data pelanggan/keuangan/audit?
12. **Bank statement:** apakah pola unggah CSV rekening koran di memori (tanpa simpan) disetujui untuk rekonsiliasi settlement? Format bank mana yang perlu didukung?
13. **Batas periode dan retensi:** berapa hari maksimum periode laporan/ekspor yang diizinkan? Apakah 31 hari cukup, atau perlu 90/365 hari dengan queue?
14. **WMS:** apakah process owner dapat mengonfirmasi daftar laporan WMS setelah login berhasil? Verifikasi database menunjukkan modul WMS tersedia, tetapi menu internal WMS belum diverifikasi lewat login.
15. **Pajak:** apakah `payments.tax_amount` akan diisi oleh POS/e-commerce? Jika tidak, GAP-06 tetap tidak layak.
