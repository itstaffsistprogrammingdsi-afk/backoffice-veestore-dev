# Data Dictionary Audit Ekosistem Retail

**Tanggal pemeriksaan:** 12 November 2026  
**Sumber:** introspeksi read-only `information_schema` pada database operasional `veestore_dev1` dan database backoffice `veestore_bo_dev`  
**Sifat:** dokumen analisis, bukan perubahan skema  
**Snapshot data:** 12 November 2026, 08:32 WIB

## 1. Topologi dan batas pemeriksaan

| Database | Dipakai oleh | Isi yang relevan untuk laporan |
|---|---|---|
| `veestore_dev1` (PostgreSQL, port 33067) | POS, WMS, e-commerce, backoffice | transaksi, stok, item, opname, pembayaran, order, retur, voucher, loyalty, sesi kasir, shipment |
| `veestore_bo_dev` (PostgreSQL, port 33067) | Backoffice | role/permission, `business_audit_logs`, `report_exports`, `failed_jobs`, tabel analitik |

Kedua database memakai timezone sesi `Asia/Jakarta`. Sebagian besar kolom waktu bertipe `timestamp with time zone` (`timestamptz`). Beberapa kolom sengaja atau tidak sengaja bertipe `timestamp without time zone`, yaitu `app_settings.created_at/updated_at`, `store_opname_sessions.started_at/snapshot_cutoff_at/rejected_at/recount_requested_at`, `store_opname_reconciliation_results.reconciled_at/created_at/updated_at`, dan `store_opname_approval_audits.reviewed_at/acted_at/created_at/updated_at`.

Database operasional bukan milik backoffice. Repository ini tidak memuat seluruh migrasi database operasional. Definisi di bawah disusun dari introspeksi kolom, nilai enum, data sampel, dokumentasi `docs/architecture.md`, `docs/erd.md`, dan kode pembaca di repository ini. Penulis tabel disimpulkan dari domain dan penggunaan kode; verifikasi final tetap membutuhkan repository POS, WMS, dan e-commerce.

## 2. Konvensi umum

- Primary key hampir seluruh tabel berupa UUID (`gen_random_uuid()`), kecuali `app_settings.key`.
- Uang memakai `numeric` dengan skala 2: umumnya `numeric(18,2)`, sebagian `numeric(15,2)` atau `numeric(16,2)`. Satuan uang adalah IDR.
- Kuantitas stok memakai integer dan dihitung dari jumlah baris item, bukan kolom `quantity` di tabel stok. Item adalah unit serial (satu baris `items` = satu unit fisik).
- Soft delete memakai `deleted_at` pada tabel master dan sebagian tabel transaksi. Tidak semua tabel transaksi memiliki `deleted_at`.
- Status transaksi memakai PostgreSQL enum atau varchar. Nama status tidak selalu sama antar tabel.
- Laporan di backoffice umumnya memakai `numeric` di SQL, lalu mengubahnya menjadi `float` di PHP. Ini menjadi sumber potensi selisih pembulatan.
- Tidak ada tabel jurnal, general ledger, purchase order, invoice AP, settlement bank, MDR, backup, atau e-faktur di database operasional.

## 3. Peta penulis tabel

| Domain | Tabel | Penulis utama (inferensi) |
|---|---|---|
| Katalog | `products`, `product_prices`, `product_categories`, `brands`, `units`, `product_specifications`, `product_images` | Backoffice (master data), dibaca POS/WMS/e-commerce |
| Inventori | `items`, `warehouse_items`, `store_items`, `item_conditions`, `item_condition_histories`, `item_location_histories`, `stock_reservations` | POS dan WMS; backoffice juga menulis reservasi dan status pada operasi tertentu |
| Inbound/outbound | `warehouse_inbounds`, `warehouse_inbound_details`, `warehouse_outbounds`, `warehouse_outbound_details`, `store_inbounds`, `store_inbound_details`, `store_outbounds`, `store_outbound_details` | WMS dan POS; backoffice menulis penerimaan/transfer tertentu |
| Opname/QC | `warehouse_stock_opnames`, `warehouse_stock_opname_details`, `warehouse_qc`, `store_opname_sessions`, `store_opname_session_items`, `store_opname_session_expected_items`, `store_opname_session_scans`, `store_opname_reconciliation_results`, `store_opname_approval_audits`, `store_qc` | WMS dan POS; approval opname toko juga melalui backoffice |
| Penjualan POS | `store_customer_visits`, `store_carts`, `store_cart_details`, `store_visit_payments`, `payments`, `payment_details`, `cashier_sessions`, `cashier_session_payment_summaries`, `void_transactions`, `void_transaction_items` | POS; backoffice hanya membaca dan melakukan approval void tertentu |
| E-commerce | `ecommerce_orders`, `ecommerce_order_details`, `ecommerce_order_vouchers`, `ecommerce_order_detail_discounts`, `ecommerce_carts`, `order_shipments`, `order_shipment_events`, `payment_exceptions` | E-commerce dan payment gateway; backoffice membaca dan menangani exception |
| Retur/transfer | `store_returns`, `store_return_details`, `store_orders`, `store_order_details` | POS/WMS; approval dan penerimaan juga melalui backoffice |
| Supplier/penerimaan | `suppliers`, `warehouse_inbounds`, `warehouse_inbound_details` | Backoffice master supplier; WMS menulis penerimaan |
| Harga | `product_prices` | Backoffice |
| Promo/voucher/loyalty | `promos`, `discounts`, `discount_products`, `discount_rules`, `vouchers`, `voucher_codes`, `voucher_usages`, `customer_loyalty_points`, `customer_loyalty_point_mutations`, `referrals`, `referrer_scores` | Backoffice (master promo), POS/e-commerce (pemakaian), backoffice (mutation approval) |
| Mitra/paylater | `partners`, `partner_credit_tiers`, `store_receivable_invoices`, `store_receivable_payment_allocations`, `partner_invoice_batches`, `partner_invoice_batch_items`, `partner_paylater_audit_logs`, `fraud_alerts` | Backoffice |
| SDM toko | `store_attendances`, `store_attendance_logs`, `store_attendance_shifts`, `store_shift_schedules`, `store_shift_schedule_details`, `store_attendance_leaves`, `store_attendance_shift_exchanges` | POS/backoffice |
| Audit/akses | `users`, `session_logs` di DB operasional; `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `business_audit_logs` di DB backoffice | Backoffice |
| Integrasi/queue | `order_shipments`, `order_shipment_events`, `stock_reservations`, `failed_jobs`, `report_exports`, `analytics_model_runs`, `analytics_sales_forecasts`, `analytics_stock_recommendations` | E-commerce/backoffice/AI service |

## 4. Kamus tabel

### 4.1 Stok dan item

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `items` | `id` | `product_id`→`products.id`; `current_location_id`→`locations.id`; `current_condition_id`→`item_conditions.id`; `rfid_tag_id`→`rfid_tags.id`; `warehouse_inbound_detail_id`→`warehouse_inbound_details.id` | `status`: `warehouse_stock`, `warehouse_processing`, `warehouse_waiting`, `in_delivery`, `store_stock`, `store_display`, `in_transaction`, `reserved`, `sold`, `returned`, `returned_from_void`, `missing`, `damaged`, `destroyed`, `repaired`, `qc_hold`, `unknown` | Tidak ada kolom qty; satu baris = satu unit | `created_at`, `updated_at` (timestamptz); `expired_date` (date) | Tidak | Sumber tunggal stok fisik lintas POS/WMS. Item bisa berada di `store_items` atau `warehouse_items`, tidak keduanya (0 duplikasi pada snapshot). |
| `warehouse_items` | `id` | `warehouse_id`→`warehouses.id`; `item_id`→`items.id` | Tidak ada status | Satu baris = satu unit | `created_at`, `updated_at` | Tidak | Keanggotaan item di gudang. Tidak ada unique `(warehouse_id,item_id)`; data saat ini tidak duplikat. |
| `store_items` | `id` | `store_id`→`stores.id`; `item_id`→`items.id` | `category`: `inventory`, `display` | Satu baris = satu unit | `created_at` | Tidak | Keanggotaan item di toko. Tidak ada unique `(store_id,item_id)`; data saat ini tidak duplikat. |
| `item_conditions` | `id` | - | `status`: `good`, `bad`, `good_after_repair` | - | `created_at`, `updated_at` | `deleted_at` | Kondisi saat ini dan histori kondisi. |
| `item_condition_histories` | `id` | `item_id`→`items.id`; `item_condition_id`→`item_conditions.id`; `changed_by`→`users.id` | `reference_type`: `warehouse_inbound_qc`, `store_inbound_qc`, `warehouse_outbound_qc`, `store_outbound_qc`, `warehouse_stock_opname`, `store_stock_opname`, `store_internal_return`, `store_bypass_process`, `store_repair_item` | - | `changed_at`, `created_at` | Tidak | Dipakai untuk aging kondisi dan PIC perubahan. |
| `item_location_histories` | `id` | `item_id`→`items.id`; `location_id`→`locations.id`; `previous_location_id`→`locations.id` | Tidak ada status | - | `created_at` | Tidak | Hanya perpindahan lokasi; bukan ledger stok berarah. |
| `store_stock_barriers` | `id` | `store_id`→`stores.id`; `product_id`→`products.id` | Tidak ada status | `stock_barrier` integer; `tolerance_percentage` numeric(5,2) | `created_at`, `updated_at` | Tidak | Ambang minimum per toko/produk. |
| `warehouse_stock_barriers` | `id` | `warehouse_id`→`warehouses.id`; `product_id`→`products.id` | Tidak ada status | `stock_barrier` integer; `tolerance_percentage` numeric(5,2) | `created_at`, `updated_at` | Tidak | Ambang minimum per gudang/produk. |
| `stock_reservations` | `id` | `product_id`→`products.id`; `reference_id` mengarah ke `store_orders.id` atau `ecommerce_orders.id`; `handler_id` mengarah ke `warehouses.id` atau `stores.id` | `status`: `active` (nilai lain mungkin `released`, `consumed`); `reference_type`: `store_order`, `ecommerce_order`; `handler_type`: `warehouse`, `store` | `quantity` integer | `reserved_at`, `expires_at`, `released_at`, `consumed_at`, `created_at`, `updated_at` | Tidak | Reservasi stok. Unique parsial `(reference_type, reference_id, product_id, handler_type, handler_id)` untuk status active. |

### 4.2 Inbound, outbound, dan lokasi

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `warehouses` | `id` | - | Tidak ada status | - | `created_at`, `updated_at` | `deleted_at` | 5 dari 6 gudang sudah soft-deleted pada snapshot. |
| `stores` | `id` | - | Tidak ada status | `inventory_stock_treshold`, `display_stock_treshold` integer | `created_at`, `updated_at` | `deleted_at` | 7 dari 11 toko sudah soft-deleted. |
| `locations` | `id` | `warehouse_id`/`store_id` (logis) | `type`: `warehouse`, `store`, `room`, `rack`, `layer`, `staging`, `store_display` | - | `created_at`, `updated_at` | `deleted_at` | Lokasi fisik. |
| `warehouse_locations` | `id` | `warehouse_id`→`warehouses.id`; `location_id`→`locations.id` | Tidak ada status | - | `created_at`, `updated_at` | Tidak | Pemetaan gudang-lokasi. |
| `store_locations` | `id` | `store_id`→`stores.id`; `location_id`→`locations.id` | Tidak ada status | - | `created_at`, `updated_at` | Tidak | Pemetaan toko-lokasi. |
| `warehouse_inbounds` | `id` | `warehouse_id`→`warehouses.id`; `supplier_id`→`suppliers.id`; `store_return_id`→`store_returns.id`; `source_warehouse_id`→`warehouses.id`; `created_by`/`received_by`→`users.id` | `inbound_type`: `supplier`, `store_return`, `warehouse_transfer` | `quantity_item` integer; `grand_total` integer (catatan: integer, bukan numeric) | `received_date`, `created_at`, `updated_at` | Tidak | 16 inbound; seluruhnya punya supplier, tetapi 0 punya `invoice_number`. `grand_total` bertipe integer sehingga tidak menyimpan sen. |
| `warehouse_inbound_details` | `id` | `warehouse_inbound_id`→`warehouse_inbounds.id`; `product_id`→`products.id` | Tidak ada status | `quantity` integer; `price_idr` numeric(18,2); `exchange_rate` numeric(18,2); `exchange_amount` numeric(18,2) | `expired_date` date; `created_at`, `updated_at` | Tidak | Harga beli per baris tersedia di `price_idr`, tetapi bukan PO/contract price. |
| `warehouse_outbounds` | `id` | `warehouse_id`→`warehouses.id`; `user_id`→`users.id`; `courier_id`→`couriers.id`; `order_id` mengarah ke `ecommerce_orders.id` atau `store_orders.id` | `order_ref`: `store`, `ecommerce`; `outbound_type`: `ecommerce`, `destroy`, `transfer`, `return_to_supplier`, `store` | `quantity_item` integer | `shipment_date`, `released_at`, `qc_passed_at`, `created_at`, `updated_at` | Tidak | Tidak ada status dokumen eksplisit; status terlihat dari `released_at`, `qc_passed_at`, dan tipe. |
| `warehouse_outbound_details` | `id` | `warehouse_outbound_id`→`warehouse_outbounds.id`; `item_id`→`items.id` | Tidak ada status | Satu baris = satu unit | `created_at`, `updated_at` | Tidak | Tidak ada qty; hitung baris. |
| `store_inbounds` | `id` | `store_id`→`stores.id`; `warehouse_source_id`→`warehouses.id`; `order_id`→`store_orders.id`; `received_by`→`users.id` | `qc_status`: `pending`, `partial_passed`, `all_passed`, `all_failed` | `quantity_item` integer; `grand_total` numeric(18,2) | `received_date`, `created_at`, `updated_at` | Tidak | 5 inbound toko. |
| `store_inbound_details` | `id` | `store_inbound_id`→`store_inbounds.id`; `item_id`→`items.id` | `has_been_qc` boolean | Satu baris = satu unit | `created_at`, `updated_at` | Tidak | Tidak ada qty. |
| `store_outbounds` | `id` | `store_id`→`stores.id`; `ecommerce_order_id`→`ecommerce_orders.id`; `store_cart_id`→`store_carts.id`; `courier_id`→`couriers.id`; `payment_id`→`payments.id`; `store_return_id`→`store_returns.id` | `outbound_type`: `ecommerce`, `destroy`, `transfer`, `return_to_supplier`, `return_to_warehouse`, `store` | `item_quantity` integer | `shipment_date`, `created_at`, `updated_at` | Tidak | Tidak ada status dokumen eksplisit. |
| `store_outbound_details` | `id` | `store_outbound_id`→`store_outbounds.id`; `item_id`→`items.id`; `rfid_tag_id`→`rfid_tags.id` | Tidak ada status | Satu baris = satu unit | `created_at`, `updated_at` | Tidak | Dipakai untuk fulfillment e-commerce dari toko. |

### 4.3 Opname dan QC

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `warehouse_stock_opnames` | `id` | `warehouse_id`→`warehouses.id`; `location_id`→`locations.id`; `user_id`→`users.id`; `approved_by`→`users.id` | `status`: `draft`, `in_progress`, `completed` | - | `created_at`, `updated_at`, `completed_at`, `approved_at` | Tidak | 1 sesi WMS pada snapshot. |
| `warehouse_stock_opname_details` | `id` | `warehouse_stock_opname_id`→`warehouse_stock_opnames.id`; `rfid_tag_id`→`rfid_tags.id`; `move_to_location_id`→`locations.id`; `change_to_condition_id`→`item_conditions.id`; aktor→`users.id` | `status`: `pending`, `match`, `missing`, `extra` | - | `scanned_at`, `created_at`, `updated_at` | Tidak | 10 baris. Satu baris per RFID. |
| `warehouse_qc` | `id` | `item_id`→`items.id`; `item_condition_id`→`item_conditions.id`; `warehouse_id`→`warehouses.id`; `performed_by`→`users.id`; `rfid_reader_id` | `qc_type`: `inbound`, `outbound`; `status`: `accepted`, `rejected` | - | `performed_at`, `created_at`, `updated_at` | Tidak | 148.064 baris, seluruhnya `inbound/accepted` pada snapshot. Tidak ada unique per item, sehingga bisa lebih dari satu QC per item. |
| `store_opname_sessions` | `id` | `store_id`→`stores.id`; `location_id`→`locations.id`; `created_by`/`approved_by`/`rejected_by`/`recount_requested_by`→`users.id` | `status` varchar: `pending_approval` (data), `draft`, `in_progress`, `completed`, `approved`, `rejected`, `recount_requested` (kode); `type` varchar: `partial` (data), `full` (kode) | - | `scheduled_at`, `started_at` (without tz), `finished_at`, `approved_at`, `rejected_at` (without tz), `snapshot_cutoff_at` (without tz), `created_at`, `updated_at` | Tidak | 1 sesi toko. `started_at`/`rejected_at` tanpa timezone, sedangkan `finished_at`/`approved_at` dengan timezone. |
| `store_opname_session_items` | `id` | `opname_session_id`→`store_opname_sessions.id`; `product_id`→`products.id`; `location_id`→`locations.id` | `discrepancy_reason` varchar bebas | `system_stock` integer; `physical_stock` integer | `created_at`, `updated_at` | Tidak | Selisih = physical - system. |
| `store_opname_session_expected_items` | `id` | `opname_session_id`, `opname_session_item_id`, `item_id`, `product_id`, `location_id`, `scanned_by` | Tidak ada status | - | `scanned_at`, `created_at`, `updated_at` | Tidak | Ekspektasi item per sesi. |
| `store_opname_session_scans` | `id` | `store_opname_session_id`, `opname_session_item_id`, `item_id`, `scanned_by` | Tidak ada status | - | `scanned_at`, `created_at`, `updated_at` | Tidak | Hasil scan RFID. |
| `store_opname_reconciliation_results` | `id` | `opname_session_id`→`store_opname_sessions.id`; `item_id`→`items.id` | `outcome` varchar; `movement_type`, `movement_resolution` | - | `reconciled_at` (without tz), `created_at`, `updated_at` | Tidak | 1 baris. |
| `store_opname_approval_audits` | `id` | `opname_session_id`→`store_opname_sessions.id`; `actor_id`→`users.id` | `action` varchar | - | `reviewed_at`, `acted_at`, `created_at`, `updated_at` (without tz) | Tidak | Jejak approval opname toko. |
| `store_qc` | `id` | `store_id`→`stores.id`; `item_id`→`items.id`; `item_condition_id`→`item_conditions.id`; `performed_by`→`users.id` | `qc_type` (inbound/outbound); tidak ada kolom status | - | `performed_at`, `created_at`, `updated_at` | Tidak | 37 baris, satu per item pada snapshot. |

### 4.4 Penjualan POS, pembayaran, dan sesi kasir

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `store_customer_visits` | `id` | `store_id`→`stores.id`; `customer_id`→`customers.id` | `status`: `active`, `completed`, `cancelled`, `abandoned`, `on_hold`; `payment_status`: `paid`, `unpaid`; `visit_type`: `kiosk`, `cashier` | - | `check_in_at`, `check_out_at`, `created_at`, `updated_at` | Tidak | 61 kunjungan. |
| `store_carts` | `id` | `store_id`→`stores.id`; `customer_id`→`customers.id`; `visit_id`→`store_customer_visits.id`; `voucher_id`→`vouchers.id` | `status`: `pending`, `paid`, `failed`, `cancelled`, `on_hold` | `voucher_discount` numeric(15,2); `point_amount` numeric(18,2) | `created_at`, `updated_at` | Tidak | Diskon header (voucher/poin) tidak dialokasikan per baris di tabel. |
| `store_cart_details` | `id` | `cart_id`→`store_carts.id`; `item_id`→`items.id`; `product_price_id`→`product_prices.id`; `product_base_price_id`→`product_prices.id`; `discount_id`→`discounts.id` | `void_status` varchar: `void`, `voided`, `gimmick`, atau NULL | `product_price_amount` numeric(18,2); `discount_amount` numeric(18,2); `product_base_price_amount` numeric(18,2); qty tidak ada (satu baris = satu item) | `created_at`, `updated_at` | Tidak | 28 baris. Laporan menghitung qty dari jumlah baris. |
| `store_visit_payments` | `id` | `visit_id`→`store_customer_visits.id`; `payment_id`→`payments.id`; `cashier_user_id`→`users.id`; `cashier_session_id`→`cashier_sessions.id` | `source` text | - | Tidak ada timestamp sendiri; memakai `payments` | Tidak | 13 baris. Tidak ada unique `(visit_id,payment_id)`; pembayaran split memungkinkan lebih dari satu baris per kunjungan. |
| `payments` | `id` | - | `status`: `pending`, `paid`, `failed`, `cancelled`, `expired`; `void_status` varchar; `app`: `warehouse`, `store`, `ecommerce`, `backoffice` | `total_amount`, `paid_amount`, `change_amount` numeric(18,2); `tax_amount` numeric(15,2) | `created_at`, `updated_at`, `completed_at`, `voided_at` | `deleted_at` | 45 baris. `paid_amount` adalah uang diterima pada POS (termasuk kembalian); untuk seluruh 32 pembayaran e-commerce nilainya 0. `total_amount` e-commerce sudah termasuk ongkir dan `payment_fee`. |
| `payment_details` | `id` | `payment_id`→`payments.id`; `payment_method_id`→`payment_methods.id` | `status`: `success`, `failed`, `refunded` | `amount` numeric(18,2) | `created_at`, `updated_at` | Tidak | 32 baris. Satu payment bisa punya beberapa detail (split method). Tidak ada unique `(payment_id,payment_method_id)`. |
| `payment_methods` | `id` | - | `type`: `cash`, `non_cash`, `api`, `receivable` | - | `created_at`, `updated_at` | `deleted_at` | Nama metode bisa berubah; laporan memakai nama saat ini. |
| `cashier_sessions` | `id` | `store_id`→`stores.id`; `user_id`→`users.id`; `closed_by`→`users.id` | `status`: `open`, `closed` | `opening_cash`, `expected_cash`, `actual_cash`, `difference`, `total_sales` numeric(15,2); `total_transactions` integer | `opened_at`, `closed_at`, `created_at`, `updated_at` | Tidak | `session_number` unik. 5 dari 9 sesi tertutup melewati tengah malam. |
| `cashier_session_payment_summaries` | `id` | `cashier_session_id`→`cashier_sessions.id`; `payment_method_id`→`payment_methods.id` | Tidak ada status | `total_amount` numeric(15,2); `transaction_count` integer | `created_at`, `updated_at` | Tidak | 8 baris untuk 10 sesi; sesi tanpa transaksi tidak punya baris. Nilainya tidak selalu sama dengan `cashier_sessions.total_sales`. |
| `void_transactions` | `id` | `payment_id`→`payments.id`; `visit_id`→`store_customer_visits.id`; `store_id`→`stores.id`; `cashier_user_id`/`authorized_by`→`users.id`; `cashier_session_id`→`cashier_sessions.id` | `status`: `pending`, `approved`, `rejected`, `expired` | `voided_amount` numeric; `points_reversed`; `items_restored_count` | `requested_at`, `authorized_at`, `rejected_at`, `created_at` | Tidak | 0 baris pada snapshot. Enum tidak memiliki `authorized` atau `cancelled`, walaupun kode laporan memeriksanya. |
| `void_transaction_items` | `id` | `void_transaction_id`→`void_transactions.id`; `item_id`/`cart_detail_id`/`store_cart_detail_id` | - | - | `created_at`, `updated_at` | Tidak | 0 baris. |

### 4.5 E-commerce, pembayaran, dan pengiriman

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `ecommerce_orders` | `id` | `customer_id`→`customers.id`; `payment_id`→`payments.id`; `handled_by_store_id`→`stores.id`; `handled_by_warehouse_id`→`warehouses.id`; `shipping_address_snapshot_id`; `shipping_method_id`; `checkout_voucher_snapshot_id`/`shipping_voucher_snapshot_id`→`ecommerce_order_vouchers.id` | `status`: `pending`, `paid`, `processed`, `shipped`, `received`, `cancelled`, `packing` | `sub_total`, `discount_total`, `grand_total` numeric(18,2); `payment_fee` numeric(15,2) | `created_at` | Tidak | 32 baris. `grand_total` = `sub_total` + ongkir + `payment_fee` - `discount_total` (diverifikasi pada data). `order_number` unik. |
| `ecommerce_order_details` | `id` | `ecommerce_order_id`→`ecommerce_orders.id`; `product_id`→`products.id`; `product_price_id`→`product_prices.id`; `product_base_price_id`→`product_prices.id`; `discount_snapshot_id`→`ecommerce_order_detail_discounts.id` | Tidak ada status | `quantity` integer; `product_price`, `product_discount`, `total_discount`, `total_price`, `product_base_price_amount` numeric(18,2) | `created_at`, `updated_at` | Tidak | 39 baris. `total_price` adalah nilai baris setelah diskon. |
| `ecommerce_order_vouchers` | `id` | `master_id`→`vouchers.id` | `type`: `fixed`, `percentage`; `context`: `ecommerce_checkout`, `ecommerce_shipping`, `store_checkout`, `store_customer_return` | `value`, `max_discount`, `min_purchase` numeric(15,2) | `valid_from`, `valid_until`, `created_at` | Tidak | Snapshot voucher pada order. |
| `ecommerce_order_detail_discounts` | `id` | `master_id`→`discounts.id`; `promo_id`→`promos.id` | `type`: `fixed`, `percentage` | `value`, `max_discount`, `min_purchase` numeric(15,2) | `valid_from`, `valid_until`, `created_at` | Tidak | Snapshot diskon per baris. |
| `ecommerce_carts` | `id` | `customer_id`→`customers.id`; `product_id`→`products.id` | Tidak ada status | `quantity` integer | `created_at`, `updated_at` | Tidak | 6 baris. Tidak memiliki `order_id`; sulit ditautkan ke order secara langsung. |
| `order_shipments` | `id` | `reference_id` mengarah ke order; `handler_id` mengarah ke store/warehouse | `integration_status`: `creating`, `failed`, `unknown`, `created`, `synced` (kode), dll; `provider_status` bebas (data: `confirmed`, `cancelled`, `delivered`, `picked`) | `quoted_shipping_price`, `provider_shipping_price` numeric(18,2) | `provider_status_at`, `last_attempt_at`, `next_retry_at`, `created_at`, `updated_at` | Tidak | 19 baris, seluruhnya `integration_status='created'`. |
| `order_shipment_events` | `id` | `shipment_id`→`order_shipments.id` | `event_type` text | `provider_shipping_price` numeric(18,2) | `occurred_at`, `received_at`, `created_at` | Tidak | 68 event. `payload_hash` unik per shipment. |
| `payment_exceptions` | `id` | `order_id`, `payment_id` (logis) | `kind` text; `status` text (`open`, resolved); `source` text | `amount`, `refund_amount` numeric(18,2) | `detected_at`, `alerted_at`, `resolved_at`, `created_at`, `updated_at` | Tidak | 0 baris. `resolved_by` varchar, bukan FK user. |
| `ecommerce_banners` | `id` | - | `status`: `active`, `non_active`, `scheduled`; `action_type` | - | `start_at`, `end_at`, `created_at`, `updated_at` | `deleted_at` | Master konten, bukan transaksi. |

### 4.6 Retur, pesanan cabang, dan transfer

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `store_orders` | `id` | `store_id`→`stores.id`; `warehouse_id`→`warehouses.id`; `created_by`/`approved_by`→`users.id` | `status`: `draft`, `requested`, `approved`, `rejected`, `processing`, `shipped`, `received`, `cancelled`, `packing`, `partial_approved`, `partial_received` | - | `requested_at`, `approved_at`, `date`, `created_at`, `updated_at` | Tidak | 10 baris. `order_number` unik. Nomor `TRF-%` adalah transfer, bukan permintaan barang. |
| `store_order_details` | `id` | `store_order_id`→`store_orders.id`; `product_id`→`products.id`; `product_price_id`→`product_prices.id` | - | `product_price` numeric(18,2); `requested_qty`, `approved_qty`, `received_qty` integer | `created_at`, `updated_at` | Tidak | 17 baris. |
| `store_returns` | `id` | `store_id`→`stores.id`; `warehouse_id`→`warehouses.id`; `approved_by`/`created_by`/`packing_by`/`shipped_by`→`users.id` | `status`: `shipped`, `pending_approval`, `draft`, `approved`, `partial_approved`, `rejected`, `received`, `close`, `requested`, `packing`, `packing_completed` | - | `approved_at`, `shipped_at`, `received_at`, `rejected_at`, `packing_started_at`, `packing_completed_at`, `created_at`, `updated_at` | Tidak | 1 baris, status `approved`. |
| `store_return_details` | `id` | `store_return_id`→`store_returns.id`; `item_id`→`items.id`; `store_id`→`stores.id` | `status`: `in_review`, `approved`, `rejected` | Satu baris = satu unit | `created_at`, `updated_at` | Tidak | 1 baris. Tidak ada `cart_id`, `payment_id`, atau `sale_id`, sehingga retur tidak bisa ditautkan langsung ke penjualan asal. |

### 4.7 Harga, supplier, dan produk

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `products` | `id` | `unit_id`→`units.id`; `category_id`→`product_categories.id`; `brand_id`→`brands.id`; `specification_id`→`product_specifications.id` | Tidak ada status | `weight_g` numeric(10,3) | `created_at`, `updated_at` | `deleted_at` | 1.446 baris. |
| `product_prices` | `id` | `product_id`→`products.id` | `price_type`: `base`, `store`, `ecommerce` | `price` numeric(18,2) | `valid_from`, `valid_to` date; `created_at`, `updated_at` | Tidak | 1.021 baris. Tidak ada kolom HPP/cost. Pada snapshot, 1.443 dari 1.444 produk memiliki `base = store = ecommerce`; hanya 1 produk yang base-nya di bawah store. |
| `suppliers` | `id` | - | Tidak ada status | - | `created_at`, `updated_at` | `deleted_at` | 4 supplier. Tidak ada payment term, credit limit, atau due date invoice. |
| `product_categories`, `brands`, `units`, `product_specifications`, `product_images` | `id` | produk terkait | `product_images.status`: `active`, `non_active` | - | `created_at`, `updated_at` | `deleted_at` pada sebagian | Master katalog. |

### 4.8 Promo, voucher, dan loyalty

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `promos` | `id` | `store_id` array | Tidak ada status; aktif dari `start_date`/`end_date` | - | `start_date`, `end_date`, `created_at`, `updated_at` | `deleted_at` | 3 baris. |
| `discounts` | `id` | `promo_id`→`promos.id` | `type`: `fixed`, `percentage`; `is_active` boolean | `value`, `max_discount`, `min_purchase` numeric(15,2); `usage_count` integer | `valid_from`, `valid_until`, `created_at`, `updated_at` | Tidak | 3 baris. |
| `discount_products` | (`discount_id`,`product_id`) | `discount_id`→`discounts.id`; `product_id`→`products.id` | `applied_to`: `store`, `ecommerce`, `global`; `store_id` array | - | - | Tidak | Satu diskon bisa berlaku untuk banyak produk; satu produk bisa punya banyak diskon. |
| `discount_rules` | `id` | `discount_id`→`discounts.id`; `scope_product_id`, `get_product_id`→`products.id` | `rule_type`: `qty_tier_percent`, `qty_tier_fixed`, `bogo`, `bundle`, `cap_per_tx`, `cap_per_day`, `time_window`, `weekday` | `threshold_amount`, `override_value`, `override_max_discount`, `get_discount_value` numeric(15,2) | `start_time`/`end_time` time; `created_at`, `updated_at` | Tidak | Aturan diskon kompleks. |
| `vouchers` | `id` | `store_voucher_request_id` (logis) | `type`: `fixed`, `percentage`; `context`: `ecommerce_checkout`, `ecommerce_shipping`, `store_checkout`, `store_customer_return`; `distribution_type`: `public`, `unique_code`; `is_active` boolean | `value`, `max_discount`, `min_purchase` numeric(15,2); `usage_limit`, `usage_count` integer | `valid_from`, `valid_until`, `created_at`, `updated_at` | `deleted_at` | 9 baris; `code` unik. |
| `voucher_codes` | `id` | `voucher_id`→`vouchers.id` | `is_active` boolean | - | `expires_at`, `created_at`, `updated_at` | Tidak | 18 kode unik. |
| `voucher_usages` | `id` | `voucher_id`→`vouchers.id`; `voucher_code_id`→`voucher_codes.id`; `customer_id`→`customers.id`; `store_cart_id`→`store_carts.id`; `ecommerce_order_id`→`ecommerce_orders.id` | Tidak ada status | `discount_amount` numeric(15,2) | `used_at`, `created_at`, `updated_at` | Tidak | 2 baris; keduanya store cart. Belum ada pemakaian e-commerce. |
| `customer_loyalty_points` | `id` | `customer_id`→`customers.id` | Tidak ada status | `balance` numeric | `created_at`, `updated_at` | Tidak | Saldo poin per akun. |
| `customer_loyalty_point_mutations` | `id` | `customer_id`→`customers.id`; `ref`/`ref_id` | `type`: `earn`, `redeem`, `expire`, `adjust`, `void_reversal`; `mutation_type`: `+`, `-`; `ref`: `payment`, `ecommerce_order`, `store_visit_payment`, dll | `points` numeric; `balance_after` numeric | `created_at`, `updated_at` | Tidak | 10 baris. |
| `referrals` | `id` | `referrer_id`, `referee_id`→`customers.id` | `status`: `pending`, `registered`, `first_transaction_done` | - | `created_at`, `updated_at` | Tidak | 0 baris. |

### 4.9 Pelanggan, mitra/paylater, dan piutang operasional

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `customers` | `id` | `registration_source_id` (polimorfik: store/e-commerce/backoffice); kode wilayah→`provinces`/`regencies`/`districts`/`villages` | `gender`: `male`, `female`; `registration_channel` varchar; `is_guest` boolean | - | `date_of_birth` date; `created_at`, `updated_at` | `deleted_at` | 22 baris, 1 soft-deleted. `email` unik. Sumber PII; akses dan ekspor harus dibatasi. |
| `customer_memberships` | `id` | `customer_id`→`customers.id`; `membership_id`→`memberships.id`; `previous_membership_id`→`memberships.id` | `status`: `active`, `non_active` | `total_spend_12_month` numeric(16,2) | `tier_evaluated_at`, `tier_valid_until`, `created_at`, `updated_at` | Tidak | Status dan evaluasi tier pelanggan. |
| `memberships`, `membership_benefits` | `id` | `membership_id`→`memberships.id` | Benefit `type` varchar | `memberships.earning_multiplier` numeric(8,2); `min_spend_treshold` numeric(16,2); `membership_benefits.value` varchar | `created_at`, `updated_at` | `deleted_at` pada keduanya | Master membership dan benefit. |
| `partners` | `id` | - | `status`: `active` (data), `non_active` (kode); `billing_cycle`; `late_fee_type` | `default_credit_limit` numeric(16,2); `late_fee_rate` numeric(10,4) | `created_at`, `updated_at` | `deleted_at` | 0 baris. |
| `partner_credit_tiers` | `id` | - | `tier_level`, `tier_name` | `credit_limit_multiplier` numeric(6,2); `max_per_transaction` numeric(16,2) | `created_at`, `updated_at` | `deleted_at` | 0 baris. |
| `store_receivable_invoices` | `id` | `store_id`→`stores.id`; `cart_id`→`store_carts.id`; `visit_id`→`store_customer_visits.id`; `customer_id`→`customers.id`; `partner_id`→`partners.id`; `employee_code_id` | `status` varchar: `open` (default), `paid`, `overdue` (kode) | `amount`, `paid_amount`, `late_fee` numeric(18,2)/(16,2) | `issued_at`, `due_date`, `notified_h3_at`, `notified_due_at`, `notified_overdue_at`, `created_at`, `updated_at` | Tidak | 0 baris, tetapi skema mendukung aging AR mitra yang sebenarnya. |
| `store_receivable_payment_allocations` | `id` | `payment_id`→`payments.id`; `invoice_id`→`store_receivable_invoices.id`; `store_id`→`stores.id` | Tidak ada status | `amount` numeric(18,2) | `created_at`, `updated_at` | Tidak | 0 baris. |
| `partner_invoice_batches` | `id` | `partner_id`→`partners.id`; `processed_by`→`users.id` | `status` varchar: `draft` (default), `issued`, `paid` (kode) | `total_amount`, `total_late_fee`, `total_paid` numeric(16,2) | `billing_period_start/end`, `due_date`, `issued_at`, `paid_at`, `created_at`, `updated_at` | Tidak | 0 baris. |
| `partner_invoice_batch_items` | `id` | `batch_id`→`partner_invoice_batches.id`; `invoice_id`→`store_receivable_invoices.id` | Tidak ada status | `amount`, `late_fee` numeric(16,2) | `created_at`, `updated_at` | Tidak | 0 baris. |
| `partner_paylater_audit_logs` | `id` | `partner_id`, `employee_code_id`, `invoice_id`, `batch_id`, `actor_id` | `event` varchar | - | `created_at`, `updated_at` | Tidak | 0 baris. Jejak audit paylater tersedia. |
| `fraud_alerts` | `id` | `store_id`, `cashier_user_id` (logis) | `severity`: `low`, `medium`, `high`, `critical`; `status`: `pending`, `investigating`, `resolved`, `dismissed` | - | `created_at`, `updated_at` | Tidak | 0 baris. |

### 4.10 Pengguna, akses, audit, dan pengaturan

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `users` (DB operasional) | `id` | - | `backoffice_access`, `store_access`, `wms_access`, `ecommerce_access` boolean | - | `created_at`, `updated_at` | `deleted_at` | 36 user, 10 soft-deleted. Tidak ada kolom outlet utama; akses outlet lewat relasi `user->stores()` (tabel pivot tidak terlihat di daftar tabel publik). |
| `session_logs` (DB operasional) | `id` | `user_id`→`users.id` | `status`: `success`, `failed`; `type`: `login`, `logout`; `app_type`: `warehouse`, `store`, `ecommerce`, `backoffice` | - | `last_activity_at`, `expired_at`, `logged_at` | Tidak | 595 baris; dipakai laporan akses pengguna. |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `model_has_permissions` (DB backoffice) | `id` / komposit | `model_id`→`users.id` (lintas database, tidak ada FK) | Nama role: `Superadmin`, `super-admin`, `Management`, `Finance`, `Marketing`, `Warehouse`, `Store Operations`, `HRD` | - | `created_at`, `updated_at` | Tidak | 179 permission pada snapshot. Hanya 1 user aktif dengan role Superadmin. |
| `business_audit_logs` (DB backoffice) | `id` | `user_id` (snapshot, tanpa FK) | `action`: `create`, `update`, `delete`, `approve`, `reject`; `app_type`; `response_status` | - | `occurred_at`, `created_at`, `updated_at` | Tidak | 4.534 baris. Hanya request POST/PUT/PATCH/DELETE yang dicatat middleware; GET/export tidak dicatat. |
| `app_settings` (DB operasional) | `key` | - | Tidak ada status | `value` jsonb | `created_at`, `updated_at` (without tz) | Tidak | Berisi konfigurasi, `pnl_expenses`, `receivables_payables`, marker sample/gimmick, dan token Fonnte. Tidak ada audit per perubahan nilai. |
| `report_exports` (DB backoffice) | `id` | `user_id`→`users.id` lintas DB | `status`: `queued`, `processing`, `completed`, `failed` | - | `created_at`, `updated_at` | Tidak | 0 baris. Hanya 3 tipe laporan yang masuk queue. |
| `failed_jobs` (DB backoffice) | `id` | - | `queue` | - | `failed_at` | Tidak | 0 baris. |

### 4.11 Integrasi, queue, dan analitik

| Tabel | PK | FK logis | Status dan arti | Nominal/kuantitas | Waktu | Soft delete | Catatan |
|---|---|---|---|---|---|---|---|
| `order_shipments` | `id` | lihat 4.5 | `integration_status`, `provider_status` | `quoted_shipping_price`, `provider_shipping_price` | lihat 4.5 | Tidak | Sumber data integration health kurir. |
| `order_shipment_events` | `id` | `shipment_id` | `event_type`, `provider_status` | `provider_shipping_price` | `occurred_at`, `received_at` | Tidak | Event webhook kurir. |
| `stock_reservations` | `id` | lihat 4.1 | `status`, `reference_type`, `handler_type` | `quantity` | lihat 4.1 | Tidak | Sumber stok tertahan. |
| `failed_jobs` | `id` | - | `queue` | - | `failed_at` | Tidak | Tidak ada data pada snapshot. |
| `analytics_model_runs` | `id` | - | `status` (kode AI) | - | `created_at`, `updated_at` | Tidak | 0 baris; di DB backoffice. |
| `analytics_sales_forecasts` | `id` | `product_id`, `store_id` (logis) | - | `forecast_qty` numeric | `forecast_date`, `created_at` | Tidak | 0 baris; data AI. |
| `analytics_stock_recommendations` | `id` | `product_id`, `warehouse_id`/`store_id` | - | `recommended_qty` numeric | `created_at` | Tidak | 0 baris; data AI. |

## 5. Jebakan data yang harus dipahami sebelum menulis laporan

1. **`payments.paid_amount` bukan sumber yang aman untuk e-commerce.** Seluruh 32 pembayaran e-commerce pada snapshot memiliki `paid_amount = 0`. Gunakan `COALESCE(NULLIF(paid_amount,0), total_amount)` atau `total_amount` untuk e-commerce.
2. **`payments.total_amount` e-commerce termasuk ongkir dan `payment_fee`.** Pada 24 Oktober 2026, total pembayaran e-commerce Rp992.500, sedangkan nilai produk (sub_total) Rp926.000; selisih Rp66.500 terdiri dari ongkir Rp52.000 dan payment fee Rp14.500. Laporan "omset produk" dan "penerimaan pembayaran" harus dibedakan.
3. **`ecommerce_orders.grand_total` = sub_total + ongkir + payment_fee - discount_total.** Jangan memakai `grand_total` sebagai omzet produk.
4. **`base` price bukan bukti HPP.** Tidak ada kolom cost/HPP di database. Pada snapshot, `base = store = ecommerce` untuk 1.443 dari 1.444 produk. Laporan laba kotor yang memakai `base` adalah estimasi terhadap harga dasar, bukan laba akuntansi.
5. **Diskon header tidak dialokasikan per baris.** `store_carts.voucher_discount` dan `point_amount` adalah diskon tingkat keranjang; `ecommerce_orders.discount_total` adalah diskon tingkat order. Laporan mengalokasikan pro-rata dan membulatkan per baris, sehingga jumlah baris bisa berbeda beberapa rupiah dari pembayaran (contoh Rp104 pada 27 Oktober 2026).
6. **Item adalah unit serial.** Stok = jumlah baris `items`, bukan `SUM(quantity)`. `store_cart_details` juga satu baris per unit; qty selalu 1 per baris.
7. **Tidak ada tautan retur ke penjualan asal.** `store_return_details` hanya menyimpan `item_id`, bukan `cart_detail_id`/`payment_id`. Laporan penjualan tidak bisa mengurangi retur secara akurat tanpa pencocokan item dan waktu.
8. **Status void tidak seragam.** `store_cart_details.void_status` memakai varchar dengan nilai `void`, `voided`, `gimmick`; enum `void_transaction_status` hanya `pending`, `approved`, `rejected`, `expired`. Kode laporan memeriksa `authorized` dan `cancelled` yang tidak ada di enum.
9. **Sebagian waktu tanpa timezone.** `store_opname_sessions.started_at`, `app_settings.created_at`, dan beberapa kolom opname lain bertipe `timestamp without time zone`, sedangkan mayoritas transaksi `timestamptz`. `COALESCE` antar keduanya dapat menimbulkan penafsiran zona waktu yang berbeda.
10. **Soft delete tidak seragam.** Tabel master punya `deleted_at`; tabel transaksi umumnya tidak. Laporan yang menghitung "data quality" atau master harus menambahkan `whereNull('deleted_at')` secara eksplisit.
11. **Tidak ada tabel jurnal, GL, PO, invoice AP, settlement bank, MDR, backup, atau e-faktur.** Laporan yang membutuhkan komponen tersebut tidak dapat dibuat dari database ini tanpa data tambahan.
12. **`business_audit_logs` mencatat request, bukan diff nilai.** Untuk DELETE, payload hanya berisi ID (dan periode), bukan nilai lama. Rekonstruksi nilai yang dihapus tidak selalu mungkin.
13. **`order_shipments` memiliki unique parsial yang ketat.** Unique pada `(reference_type,reference_id)`, `(provider,provider_mode,provider_reference_id)`, dan sejenisnya membatasi satu shipment per referensi; ini perlu diperhitungkan saat membuat laporan integrasi.
14. **Data snapshot sangat kecil untuk transaksi.** 45 pembayaran, 32 order e-commerce, 19 keranjang, 148.136 item. Kesimpulan angka harus dibaca sebagai verifikasi metodologi, bukan volume produksi.

## 6. Tabel yang tidak ditemukan dan dampaknya

| Kebutuhan laporan | Tabel/kolom yang dicari | Status | Dampak |
|---|---|---|---|
| Jurnal umum / General Ledger | tabel `journal_*`, `ledger_*`, `gl_*` | Tidak ada | GAP-04 tidak dapat dibuat |
| Purchase order / three-way matching | tabel `purchase_orders`, `purchase_order_items`, `supplier_invoices` | Tidak ada | GAP-05 hanya bisa parsial dari `warehouse_inbounds.invoice_number` (0 dari 16 terisi) |
| Invoice AP supplier | `invoice_number`, `due_date`, `payment_status` di penerimaan | Tidak ada | Aging AP tidak dapat dibuat |
| Settlement bank / MDR / chargeback | tabel/kolom settlement, fee, chargeback | Tidak ada | GAP-02 hanya bisa ke level gateway (`payment_details.detail` berisi `settlement_time`) |
| Pajak / e-faktur | `tax_amount` (semua 0), DPP, PPN, nomor faktur | Tidak ada | GAP-06 tidak dapat dibuat |
| Backup / restore / DR | tabel backup, job backup, restore test | Tidak ada | GAP-08 tidak dapat dibuat |
| Audit log generik | `business_audit_logs` (ada), `session_logs` (ada) | Ada sebagian | GAP-07 dapat dibuat parsial |
| AR mitra/paylater | `store_receivable_invoices`, `store_receivable_payment_allocations`, `partner_invoice_batches` | Ada, 0 baris | AR mitra dapat dibuat dari data yang ada, terpisah dari catatan manual |
| Integration health | `order_shipments`, `order_shipment_events`, `failed_jobs` | Ada | REC-06 dapat dibuat parsial |
| Kepatuhan harga kontrak | harga kontrak supplier | Tidak ada | Supplier price variance tidak dapat dibuat |
| Biaya kirim aktual | `provider_shipping_price` | Ada (19 baris) | Perbandingan ongkir kuotasi vs aktual dapat dibuat parsial |
| Funnel e-commerce | event view/cart/checkout | Tidak ada | REC/e-commerce funnel tidak dapat dibuat |
| Workforce | `store_attendances`, `store_attendance_logs`, `cashier_sessions` | Ada | REC-05 dapat dibuat parsial |
