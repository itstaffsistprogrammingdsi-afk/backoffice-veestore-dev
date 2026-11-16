# Kamus data audit, hasil introspeksi lokal

**Diperiksa 12 November 2026.** Sumber: information_schema.columns, information_schema.table_constraints, pg_type/pg_enum, dan pg_class pada PostgreSQL lokal. Koneksi pgsql menunjuk veestore_dev1, sedangkan immostore_backoffice menunjuk veestore_bo_dev, keduanya di 127.0.0.1:33067 dengan timezone sesi Asia/Jakarta. Keduanya database **berbeda** pada server yang sama. Ini membatasi klaim brief tentang satu database; data audit/role berada di database backoffice, sedangkan transaksi di database shared. Status produksi belum diverifikasi.

## Pola repository

Laravel 13, Inertia/Vue, PostgreSQL, Spatie Permission, dan Maatwebsite Excel (composer.json). Route laporan utama di routes/web.php:625-1129, controller di app/Http/Controllers/Monitoring dan Reports, ekspor di app/Exports, view di resources/js/pages, pengujian di tests/Feature/Monitoring dan Reports. Route berada di grup auth (routes/web.php:178). Model, migrasi lama, dan kode query hanya dipakai sebagai pembanding; database/schema/pgsql-schema.sql kosong. Tidak ada DDL dijalankan.

## Kode pembacaan tabel

Kolom PK dan FK berasal dari constraint aktual lokal. Kolom Status memuat tipe SQL; nilai enum di bawah. Nominal/kuantitas memuat presisi SQL; satuan uang belum dapat dipastikan dari skema saja, kecuali price_idr menyebut IDR. Waktu menampilkan tz untuk timestamptz dan tanpa tz untuk timestamp; timezone sesi Asia/Jakarta tidak membuktikan timezone penulis. Soft delete hanya berarti kolom deleted_at ada. Sistem penulis tidak tercatat dalam constraint; identifikasi endpoint tulis lintas empat repo masih diperlukan. Tiap nama tabel berlabel shared atau bo sesuai database lokal.

## Stok, lokasi, mutasi, dan opname

| Tabel | PK; FK | Status / nominal dan kuantitas | Waktu; soft delete | Penulis |
|---|---|---|---|---|
| shared/items | id; current_condition_id→item_conditions; current_location_id→locations; old_item_id→items; product_id→products; rfid_installed_by→users; rfid_tag_id→rfid_tags; warehouse_inbound_detail_id→warehouse_inbound_details | status:item_status; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_items | id; item_id→items; store_id→stores | category:store_item_category; — | created_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_items | id; item_id→items; warehouse_id→warehouses | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/stock_reservations | id; product_id→products | reference_type:text, handler_type:text, status:text; quantity:int4 | reserved_at tz, expires_at tz, released_at tz, consumed_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/item_location_histories | id; item_id→items; location_id→locations | —; — | created_at tz; tidak ada kolom | Belum dipastikan |
| shared/item_condition_histories | id; changed_by→users; item_condition_id→item_conditions; item_id→items | reference_type:item_condition_history_ref_type; — | changed_at tz, created_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_opname_sessions | id; recount_requested_by→users; rejected_by→users; approved_by→users; created_by→users; location_id→locations; store_id→stores | type:varchar, status:varchar; counting_round:int2 | scheduled_at tz, finished_at tz, approved_at tz, created_at tz, updated_at tz, started_at tanpa tz, snapshot_cutoff_at tanpa tz, rejected_at tanpa tz, recount_requested_at tanpa tz; tidak ada kolom | Belum dipastikan |
| shared/store_opname_session_items | id; location_id→locations; opname_session_id→store_opname_sessions; product_id→products | —; system_stock:int4, physical_stock:int4 | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_opname_session_scans | id; expected_item_id→store_opname_session_expected_items; item_condition_id→item_conditions; item_id→items; opname_session_id→store_opname_sessions; opname_session_item_id→store_opname_session_items; scanned_by→users | —; — | scanned_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_opname_reconciliation_results | id; item_id→items; opname_session_id→store_opname_sessions | outcome:varchar, movement_type:varchar; — | reconciled_at tanpa tz, created_at tanpa tz, updated_at tanpa tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_stock_opnames | id; approved_by→users; location_id→locations; user_id→users; warehouse_id→warehouses | status:warehouse_stock_opname_status; — | created_at tz, updated_at tz, completed_at tz, approved_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_stock_opname_details | id; change_to_condition_by→users; change_to_condition_id→item_conditions; created_by→users; move_to_location_by→users; move_to_location_id→locations; rfid_tag_id→rfid_tags; warehouse_stock_opname_id→warehouse_stock_opnames | status:warehouse_stock_opname_detail_status; — | created_at tz, updated_at tz, scanned_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_stock_barriers | id; product_id→products; store_id→stores | —; stock_barrier:int4, tolerance_percentage:numeric(5,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_stock_barriers | id; product_id→products; warehouse_id→warehouses | —; stock_barrier:int4, tolerance_percentage:numeric(5,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |

## Penjualan, order, payment, dan kasir

| Tabel | PK; FK | Status / nominal dan kuantitas | Waktu; soft delete | Penulis |
|---|---|---|---|---|
| shared/store_carts | id; customer_id→customers; store_id→stores; visit_id→store_customer_visits; voucher_id→vouchers | status:store_cart_status; voucher_discount:numeric(15,2), point_amount:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_cart_details | id; cart_id→store_carts; discount_id→discounts; item_id→items; product_base_price_id→product_prices; product_price_id→product_prices; void_transaction_id→void_transactions | void_status:varchar; product_price_amount:numeric(18,2), discount_amount:numeric(18,2), product_base_price_amount:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_visit_payments | id; cashier_session_id→cashier_sessions; cashier_user_id→users; payment_id→payments; visit_id→store_customer_visits | —; — | —; tidak ada kolom | Belum dipastikan |
| shared/ecommerce_orders | id; checkout_voucher_snapshot_id→ecommerce_order_vouchers; customer_id→customers; handled_by_store_id→stores; handled_by_warehouse_id→warehouses; payment_id→payments; shipping_address_snapshot_id→ecommerce_order_shipping_addresses; shipping_method_id→ecommerce_order_shipping_methods; shipping_voucher_snapshot_id→ecommerce_order_vouchers | status:ecommerce_order_status; sub_total:numeric(18,2), discount_total:numeric(18,2), grand_total:numeric(18,2), payment_fee:numeric(15,2) | created_at tz; tidak ada kolom | Belum dipastikan |
| shared/ecommerce_order_details | id; discount_snapshot_id→ecommerce_order_detail_discounts; ecommerce_order_id→ecommerce_orders; product_base_price_id→product_prices; product_id→products; product_price_id→product_prices | —; product_price:numeric(18,2), quantity:int4, product_discount:numeric(18,2), total_discount:numeric(18,2), total_price:numeric(18,2), product_base_price_amount:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/ecommerce_carts | id; customer_id→customers; product_id→products | —; quantity:int4 | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/payments | id; void_transaction_id→void_transactions | status:payment_main_status, void_status:varchar; total_amount:numeric(18,2), change_amount:numeric(18,2), paid_amount:numeric(18,2), tax_amount:numeric(15,2) | created_at tz, updated_at tz, deleted_at tz, completed_at tz, voided_at tz; ya | Belum dipastikan |
| shared/payment_details | id; payment_id→payments; payment_method_id→payment_methods | status:payment_status; amount:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/payment_exceptions | id; order_id→ecommerce_orders; payment_id→payments | provider_status:varchar, status:text; amount:numeric(18,2), refund_amount:numeric(18,2) | alerted_at tz, resolved_at tz, detected_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/cashier_sessions | id; closed_by→users; store_id→stores; user_id→users | status:cashier_session_status; opening_cash:numeric(15,2), expected_cash:numeric(15,2), actual_cash:numeric(15,2), difference:numeric(15,2), total_sales:numeric(15,2), total_transactions:int4 | opened_at tz, closed_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/cashier_session_payment_summaries | id; cashier_session_id→cashier_sessions; payment_method_id→payment_methods | —; total_amount:numeric(15,2), transaction_count:int4 | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/void_transactions | id; authorized_by→users; cashier_session_id→cashier_sessions; cashier_user_id→users; payment_id→payments; store_id→stores; visit_id→store_customer_visits | status:void_transaction_status; voided_amount:numeric(15,2), points_reversed:numeric(15,2), items_restored_count:int4 | requested_at tz, authorized_at tz, rejected_at tz, created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |
| shared/void_transaction_items | id; cart_detail_id→store_cart_details; void_transaction_id→void_transactions | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |

## Retur, transfer, supplier, dan penerimaan

| Tabel | PK; FK | Status / nominal dan kuantitas | Waktu; soft delete | Penulis |
|---|---|---|---|---|
| shared/store_returns | id; approved_by→users; created_by→users; packing_by→users; shipped_by→users; store_id→stores; warehouse_id→warehouses | status:store_return_status; — | approved_at tz, shipped_at tz, received_at tz, rejected_at tz, created_at tz, updated_at tz, packing_started_at tz, packing_completed_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_return_details | id; item_id→items; store_id→stores; store_return_id→store_returns | status:store_return_detail_status; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_orders | id; approved_by→users; created_by→users; store_id→stores; warehouse_id→warehouses | status:store_order_status; — | requested_at tz, approved_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_order_details | id; product_id→products; product_price_id→product_prices; store_order_id→store_orders | —; product_price:numeric(18,2), requested_qty:int4, approved_qty:int4, received_qty:int4 | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_inbounds | id; order_id→store_orders; received_by→users; store_id→stores; warehouse_source_id→warehouses | qc_status:store_inbounds_qc_status; quantity_item:int4, grand_total:numeric(18,2) | received_date tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_inbound_details | id; item_id→items; store_inbound_id→store_inbounds | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_outbounds | id; courier_id→couriers; ecommerce_order_id→ecommerce_orders; payment_id→payments; store_cart_id→store_carts; store_id→stores; store_return_id→store_returns | outbound_type:store_outbound_type; item_quantity:int4 | created_at tz, updated_at tz, shipment_date tz; tidak ada kolom | Belum dipastikan |
| shared/store_outbound_details | id; item_id→items; rfid_releaser_user_id→users; rfid_tag_id→rfid_tags; store_outbound_id→store_outbounds | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_inbounds | id; created_by→users; received_by→users; store_return_id→store_returns; supplier_id→suppliers; warehouse_id→warehouses | inbound_type:warehouse_inbound_type; quantity_item:int4, grand_total:int4 | received_date tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_inbound_details | id; product_id→products; warehouse_inbound_id→warehouse_inbounds | —; quantity:int4, exchange_rate:numeric(18,2), exchange_amount:numeric(18,2), price_idr:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_outbounds | id; courier_id→couriers; qc_passed_by→users; user_id→users; warehouse_id→warehouses | outbound_type:warehouse_outbound_type; quantity_item:int4 | shipment_date tz, created_at tz, updated_at tz, released_at tz, qc_passed_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_outbound_details | id; item_id→items; warehouse_outbound_id→warehouse_outbounds | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_staging_outbounds | id; location_rfid_tag_id→location_rfid_tags; warehouse_id→warehouses | —; quantity:int4 | created_at tz, updated_at tz, released_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_return_inbound_details | ?; item_id→items; product_id→products; warehouse_inbound_id→warehouse_inbounds | status:warehouse_return_inbound_detail_type; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/suppliers | id; FK tidak dideklarasikan | —; — | created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |
| shared/store_receivable_invoices | id; cart_id→store_carts; customer_id→customers; employee_code_id→partner_employee_codes; partner_id→partners; store_id→stores; visit_id→store_customer_visits | status:varchar; amount:numeric(18,2), late_fee:numeric(16,2), paid_amount:numeric(18,2) | issued_at tz, due_date tz, created_at tz, updated_at tz, notified_h3_at tz, notified_due_at tz, notified_overdue_at tz; tidak ada kolom | Belum dipastikan |
| shared/store_receivable_payment_allocations | id; invoice_id→store_receivable_invoices; payment_id→payments; store_id→stores | —; amount:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/partner_invoice_batches | id; partner_id→partners; processed_by→users | status:varchar; total_amount:numeric(16,2), total_late_fee:numeric(16,2), total_paid:numeric(16,2) | issued_at tz, paid_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/partner_invoice_batch_items | id; batch_id→partner_invoice_batches; invoice_id→store_receivable_invoices | —; amount:numeric(16,2), late_fee:numeric(16,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |

## Harga, promo, voucher, loyalti, pelanggan

| Tabel | PK; FK | Status / nominal dan kuantitas | Waktu; soft delete | Penulis |
|---|---|---|---|---|
| shared/products | id; brand_id→brands; category_id→product_categories; specification_id→product_specifications; unit_id→units | category_id:uuid; weight_g:numeric(10,3) | created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |
| shared/product_prices | id; product_id→products | price_type:price_type; price:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/promos | id; FK tidak dideklarasikan | —; — | created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |
| shared/discounts | id; promo_id→promos | type:product_discount_type; value:numeric(15,2), max_discount:numeric(15,2), min_purchase:numeric(15,2), usage_limit:int4, usage_count:int4, priority:int4 | valid_from tz, valid_until tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/discount_products | discount_id,product_id; discount_id→discounts; product_id→products | —; — | —; tidak ada kolom | Belum dipastikan |
| shared/vouchers | id; store_voucher_request_id→store_voucher_requests | type:voucher_type, distribution_type:voucher_distribution_type; value:numeric(15,2), max_discount:numeric(15,2), min_purchase:numeric(15,2), usage_limit:int4, usage_count:int4 | valid_from tz, valid_until tz, deleted_at tz, created_at tz, updated_at tz; ya | Belum dipastikan |
| shared/voucher_usages | id; voucher_code_id→voucher_codes; voucher_id→voucher_codes; customer_id→customers; ecommerce_order_id→ecommerce_orders; store_cart_id→store_carts; voucher_id→vouchers | —; discount_amount:numeric(15,2) | used_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/voucher_codes | id; voucher_id→vouchers | —; — | expires_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/ecommerce_order_vouchers | id; master_id→vouchers | type:voucher_type; value:numeric(15,2), max_discount:numeric(15,2), min_purchase:numeric(15,2), usage_limit:int4, usage_count:int4 | valid_from tz, valid_until tz, created_at tz; tidak ada kolom | Belum dipastikan |
| shared/customer_loyalty_points | id; customer_id→customers | —; balance:numeric(18,2) | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/customer_loyalty_point_mutations | id; customer_id→customers; customer_loyalty_point_id→customer_loyalty_points | type:loyalty_mutation_type, mutation_type:mutation_type; points:numeric(18,2), balance_after:numeric(18,2), used_points:numeric(18,2) | created_at tz, updated_at tz, expired_at tz; tidak ada kolom | Belum dipastikan |
| shared/customers | id; district_code→districts; province_code→provinces; regency_code→regencies; user_id→users; village_code→villages | —; — | created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |

## Akses, audit, pengaturan, dan integrasi

| Tabel | PK; FK | Status / nominal dan kuantitas | Waktu; soft delete | Penulis |
|---|---|---|---|---|
| shared/users | id; FK tidak dideklarasikan | —; — | created_at tz, updated_at tz, deleted_at tz; ya | Belum dipastikan |
| shared/store_users | store_id,user_id; store_id→stores; user_id→users | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/warehouse_users | user_id,warehouse_id; user_id→users; warehouse_id→warehouses | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/app_settings | key; FK tidak dideklarasikan | —; — | created_at tanpa tz, updated_at tanpa tz; tidak ada kolom | Backoffice untuk P&L/catatan manual; penulis lain belum pasti |
| shared/order_shipments | id; FK tidak dideklarasikan | reference_type:text, handler_type:text, courier_type:text, delivery_type:text, integration_status:text, provider_status:text; quoted_shipping_price:numeric(18,2), provider_shipping_price:numeric(18,2), attempt_count:int4 | provider_status_at tz, request_started_at tz, last_attempt_at tz, next_retry_at tz, provider_created_at tz, synced_at tz, created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| shared/order_shipment_events | id; shipment_id→order_shipments | event_type:text, provider_status:text; provider_shipping_price:numeric(18,2) | occurred_at tz, received_at tz, created_at tz; tidak ada kolom | Belum dipastikan |
| shared/session_logs | id; user_id→users | status:session_log_status, type:session_log_type, app_type:app_type; — | last_activity_at tz, expired_at tz, logged_at tz; tidak ada kolom | Belum dipastikan |
| shared/loyalty_audit_logs | id; approver_id→users; customer_id→customers; point_mutation_id→customer_loyalty_point_mutations; user_id→users | —; — | created_at tz, updated_at tz; tidak ada kolom | Belum dipastikan |
| bo/business_audit_logs | id; FK tidak dideklarasikan | app_type:varchar, response_status:int2; — | occurred_at tz, created_at tz, updated_at tz; tidak ada kolom | Backoffice POST/PUT/PATCH/DELETE (middleware) |
| bo/roles | id; FK tidak dideklarasikan | —; — | created_at tanpa tz, updated_at tanpa tz; tidak ada kolom | Backoffice (role/permission) |
| bo/permissions | id; FK tidak dideklarasikan | —; — | created_at tanpa tz, updated_at tanpa tz; tidak ada kolom | Backoffice (role/permission) |
| bo/model_has_roles | role_id,model_id,model_type; role_id→roles | model_type:varchar; — | —; tidak ada kolom | Backoffice (role/permission) |
| bo/role_has_permissions | permission_id,role_id; permission_id→permissions; role_id→roles | —; — | —; tidak ada kolom | Backoffice (role/permission) |
| bo/report_exports | id; FK tidak dideklarasikan | type:varchar, status:varchar; — | created_at tz, updated_at tz; tidak ada kolom | Backoffice (service ekspor) |
| bo/report_export_jobs | id; FK tidak dideklarasikan | —; reserved_at:int4, available_at:int4, created_at:int4 | —; tidak ada kolom | Belum dipastikan |
| bo/failed_jobs | id; FK tidak dideklarasikan | —; — | failed_at tanpa tz; tidak ada kolom | Belum dipastikan |

## Nilai enum relevan

Label berikut adalah nilai yang diizinkan tipe enum lokal, bukan definisi proses lengkap. Status berupa varchar/text perlu diprofilkan dari data dan dikonfirmasi pemilik proses.

| Tipe | Nilai |
|---|---|
| app_type | warehouse, store, ecommerce, backoffice |
| cashier_session_status | open, closed |
| discount_product_applied_to | store, ecommerce, global |
| ecommerce_order_status | pending, paid, processed, shipped, received, cancelled, packing |
| gender_type | male, female |
| item_condition_history_ref_type | warehouse_inbound_qc, store_inbound_qc, warehouse_outbound_qc, store_outbound_qc, warehouse_stock_opname, store_stock_opname, store_internal_return, store_bypass_process, store_repair_item |
| item_status | warehouse_stock, in_delivery, store_stock, sold, warehouse_processing, store_processing, missing, unknown, returned, reserved, damaged, destroyed, qc_hold, store_display, in_transaction, returned_from_void, repaired, warehouse_waiting |
| loyalty_mutation_type | earn, redeem, expire, adjust, referral_register, referral_first_tx, void_reversal |
| loyalty_point_mutation_ref | payment, ecommerce_order, store_visit_payment, customer, customer_loyalty_point_mutation, void_transaction |
| mutation_type | +, - |
| order_ref | store, ecommerce |
| payment_main_status | pending, paid, failed, cancelled, expired |
| payment_status | success, failed, refunded |
| price_type | base, store, ecommerce |
| product_discount_type | fixed, percentage |
| session_log_status | success, failed |
| session_log_type | login, logout |
| store_cart_status | pending, paid, failed, cancelled, on_hold |
| store_inbounds_qc_status | pending, partial_passed, all_passed, all_failed |
| store_item_category | inventory, display |
| store_order_status | draft, requested, approved, rejected, processing, shipped, received, cancelled, packing, partial_approved, partial_received |
| store_outbound_type | ecommerce, destroy, transfer, return_to_supplier, return_to_warehouse, store |
| store_return_detail_status | in_review, approved, rejected |
| store_return_status | shipped, pending_approval, draft, approved, partial_approved, rejected, received, close, requested, packing, packing_completed |
| void_transaction_status | pending, approved, rejected, expired |
| voucher_context | ecommerce_checkout, ecommerce_shipping, store_checkout, store_customer_return |
| voucher_distribution_type | public, unique_code |
| voucher_type | fixed, percentage |
| warehouse_inbound_type | supplier, store_return, warehouse_transfer |
| warehouse_outbound_type | ecommerce, destroy, transfer, return_to_supplier, store |
| warehouse_return_inbound_detail_type | match, extra, missing |
| warehouse_stock_opname_detail_status | match, missing, extra, pending |
| warehouse_stock_opname_status | draft, in_progress, completed |

## Celah verifikasi

- Constraint tidak menyatakan sistem penulis. POS, WMS, e-commerce, dan backoffice perlu dipetakan dari kode penulis masing-masing untuk menetapkan source of truth.
- store_receivable_invoices dan store_receivable_payment_allocations ada secara skema, tetapi kosong pada snapshot lokal. Laporan catatan piutang/hutang backoffice saat ini tetap memakai JSON manual di app_settings.
- payment_exceptions ada tetapi kosong pada snapshot lokal. order_shipments dan order_shipment_events berisi data; semantik status provider perlu kontrak integrasi.
- store_opname_sessions mencampur timestamptz dengan timestamp tanpa timezone. Bandingkan tanggal secara eksplisit sebelum agregasi lintas sumber.
- Presisi numeric tanpa (p,s) berarti tidak dibatasi oleh tipe kolom; jangan mengasumsikan dua desimal. Satuan quantity mengikuti transaksi/item, belum diverifikasi untuk seluruh laporan.
