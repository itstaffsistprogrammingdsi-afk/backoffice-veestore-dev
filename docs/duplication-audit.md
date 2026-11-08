# Audit Duplikasi Sistem VeeStore Backoffice

Tanggal: 2026-11-07
Status: temuan kritis sudah diimplementasikan, sisa temuan masuk backlog

## 1. Ringkasan eksekutif

Audit mencakup menu, route, controller, query, export, permission, migration, model, dan tabel database. Metode yang dipakai:

- Inventaris route aktual dengan `php artisan route:list --json`.
- Perbandingan menu sidebar (`app/Support/Navigation.php`) dengan menu legacy (`resources/menu/verticalMenu.json`).
- Pemindaian method controller/domain/export untuk body yang identik.
- Pemeriksaan tabel dan kolom langsung ke PostgreSQL.
- Pemeriksaan permission dan migration di database backoffice.

Hasil utama:

| Area | Temuan | Status |
|---|---|---:|
| Menu | 2 file menu duplikat, 1 URL menu tampil dua kali, 1 label menu duplikat | Diperbaiki |
| Menu | Sidebar Inertia dan menu legacy JSON mendefinisikan fitur yang berbeda | Sebagian, perlu satu registry |
| Route | 9 route menunjuk method yang tidak ada | Diperbaiki |
| Route | Approval order dan retur punya dua implementasi | Diperbaiki |
| Route | Halaman demografi pelanggan punya dua implementasi | Diperbaiki |
| Kode | 8 implementasi `normalizeDate` identik | Diperbaiki |
| Database | 1 permission duplikat di tabel `permissions` | Diperbaiki |
| Migration | 3 file migration permission untuk tabel yang sama | Diperbaiki |
| Tabel | Beberapa pasangan tabel paralel untuk konsep yang sama | Perlu keputusan skema |
| Menu | Command Center masih membaca sumber menu kedua | Perlu registry tunggal |

Route aplikasi turun dari 375 menjadi 364. Route rusak turun dari 9 menjadi 0. Duplikasi nama route turun dari 1 menjadi 0.

## 2. Duplikasi menu

### 2.1 Dua sumber menu yang tidak sinkron

Ada dua sumber menu:

- `app/Support/Navigation.php`, dipakai sidebar Inertia yang benar-benar tampil.
- `resources/menu/verticalMenu.json`, masih dipakai `MonitoringCommandCenterController`, `SystemMapTool`, `MenuServiceProvider`, dan `BackofficeRolePermissionSeeder`.

Keduanya tidak sinkron:

- Menu JSON memuat halaman yang tidak ada di sidebar: `monitoring/payment-reconciliation`, `monitoring/customer-membership`, `monitoring/data-customer`, `monitoring/diskon`, `monitoring/employee-shift-attendance`, `monitoring/request-voucher`, `monitoring/supplier-insight`, `monitoring/voucher-reject-report`, `monitoring/warehouse-inbound`, `monitoring/warehouse-outbound`, `monitoring/store-inbound`, `monitoring/store-outbound`, `reports/crm/customer-profiles`.
- Sidebar memuat halaman yang tidak ada di menu JSON: seluruh halaman operasional, `report-exports`, `reports/crm/customer-preference`, `reports/history-customer`, `monitoring/store-order-requests`, `monitoring/store-return-requests`.

Dampak: menu Command Center, system map AI, dan permission seeder dapat menampilkan/memberi akses ke fitur yang berbeda dari sidebar. Ini duplikasi definisi menu dan sumber bug permission.

Rekomendasi: jadikan satu `MenuRegistry` sebagai sumber tunggal untuk sidebar, Command Center, system map, dan permission seeder. `Navigation.php` dapat menjadi konsumen registry tersebut.

### 2.2 File menu duplikat

Dihapus:

- `resources/menu/verticalMenu copy.json`
- `resources/menu/verticalMenu copy 2.json`

Keduanya sisa template Materio, tidak direferensikan kode, dan isinya menu demo (Dashboards, Layouts, Front Pages, UI kit). Layout dan menu Blade legacy juga dihapus karena tidak ada halaman bisnis yang masih memakainya.

### 2.3 Item menu duplikat

Diperbaiki:

- `monitoring/barcode-request-report` tampil dua kali: "Laporan Cetak Ulang Label" di Stok & Logistik dan "Cetak Ulang Label" di Audit & Akses. Entri Stok & Logistik dihapus.
- Slug `monitoring-branch-expansion` dipakai pada grup dan child sehingga seeder membuat permission duplikat. Slug pada grup dihapus.
- "Demografi Pelanggan" tampil di Operasional dan Laporan. Menu Operasional dihapus; route lama diarahkan ke Reports/CRM.
- "Retur Cabang" (read-only) menduplikasi "Persetujuan Retur Toko". Menu Reports dihapus; route lama diarahkan ke antrean Operasional.

### 2.4 Duplikasi menu yang masih perlu keputusan

- `master-data.promos.index` (CRUD promo) dan `master-data.promo-setup.index` (wizard promo + diskon + voucher) sama-sama membuat data promo. Wizard adalah fitur berbeda, tetapi membingungkan jika dua-duanya menjadi pintu input promo.
- Tiga tampilan fulfillment: `monitoring.order-handled`, `monitoring.fulfillment-control`, dan `monitoring.ecommerce-order-fulfillment`. Yang terakhir tidak tampil di sidebar tetapi route-nya masih aktif.
- Halaman operasional stok/inbound/outbound dan halaman monitoring stok/inbound/outbound memakai query berbeda untuk data yang sama. Pemisahan kerja vs laporan disengaja, tetapi query baca perlu disatukan agar tidak ada dua logika angka.

## 3. Duplikasi route dan fungsi

### 3.1 Demografi pelanggan

Sebelumnya ada dua halaman penuh:

- `operations.customers.demographics`
- `reports.crm.demographics`

Keduanya memakai `CustomerDemographicsQuery` dan `CrmDemographicsExport` yang sama, dengan dua komponen Vue berbeda.

Perubahan:

- `Operations\CustomerDemographicsOperationsController` dan `Operations/CustomerDemographics.vue` dihapus.
- `GET /operations/customers/demographics` dan `/export` sekarang redirect 302 ke route Reports/CRM.
- `POST /operations/customers/demographics/birthday-greeting` tetap ada sebagai alias dan memanggil `CrmController@sendBirthdayGreeting`, sehingga hanya ada satu implementasi kirim email.
- Menu Operasional "Demografi Pelanggan" dihapus.

### 3.2 Retur cabang

Sebelumnya ada dua halaman:

- `operations.store-returns` untuk approval.
- `reports.retur-cabang` untuk daftar dan detail read-only.

Perubahan:

- `Reports\StoreReturnApprovalController` dan `Reports/Returns.vue` dihapus.
- `GET /reports/retur-cabang` redirect ke `operations.store-returns` dengan `status=all`.
- `GET /reports/retur-cabang/{return}` didelegasikan ke `Operations\StoreReturnApprovalController@show`, bukan query duplikat.
- Menu Reports "Retur Cabang" dan menu JSON "Branch Operations > Returns" dihapus.
- Route approve/reject Reports tetap tidak ada; hanya Operasional yang boleh mengubah status.

### 3.3 Persetujuan pesanan cabang

Sebelumnya ada dua implementasi approval pada tabel `store_orders`:

- `Operations\StoreOrderApprovalController` memakai `ApproveStoreOrder`.
- `Reports\OrderCabangController` punya `approve()` dan `reject()` sendiri, termasuk pemecahan order per gudang dan pembuatan `stock_reservations`.

Perubahan:

- `approve()`, `reject()`, dan helper khusus approval di `Reports\OrderCabangController` dihapus.
- Route `reports.order-cabang.approve`, `.reject`, `.store`, `.update`, `.destroy` dihapus.
- Halaman Reports/Order Cabang tetap menjadi laporan + aksi request/receive, dan menautkan approval ke antrean Operasional.
- Satu-satunya approval sekarang ada di `operations.store-orders`.

Catatan: route `.store` dan `.update` sebelumnya sudah rusak karena method-nya tidak ada. Route `.destroy` juga tidak dipakai UI.

### 3.4 Route rusak

Ditemukan 9 route yang menunjuk method tidak ada. Semua sudah ditangani:

| Route | Masalah | Tindakan |
|---|---|---|
| `master-data/api/product-prices/{id}` | `getUsedTypes` tidak ada | Route dihapus |
| `master-data/customers/{customer}` DELETE | `destroy` tidak ada | Method `destroy` ditambahkan |
| `master-data/suppliers/{supplier}/edit` | `edit` tidak ada, UI memakai modal | Route dihapus |
| `master-data/users/{user}/toggle-active` | `toggleActive` tidak ada, UI tidak memakai | Route dihapus |
| `management/roles-backoffice/create` | `create` tidak ada | Resource dibatasi |
| `management/roles-backoffice/{id}/edit` | `edit` tidak ada | Resource dibatasi |
| `monitoring/anomali-item/{item}/location-history` | `locationHistory` tidak ada | Route dihapus |
| `reports/order-cabang` POST | `store` tidak ada | Route dihapus |
| `reports/order-cabang/{order}` PUT | `update` tidak ada | Route dihapus |

### 3.5 Dead code di Command Center

Dihapus dari `MonitoringCommandCenterController`:

- `warehouseOutbound()`, duplikat `WarehouseOutboundController` dan tidak punya route.
- `systemHealth()` dan `pushAlert()`, tidak pernah dipanggil.
- `countTable()`, hanya dipakai oleh method duplikat yang dihapus.

### 3.6 Alias redirect legacy

Alias berikut sengaja dipertahankan agar URL lama tidak 404. Semua hanya redirect, bukan implementasi kedua:

- `monitoring/slow-deadstock*` dan `monitoring/store-slowstock*` -> `monitoring/product-slowmoving`
- `monitoring/promo-acquisition`, `promo-usage`, `referral-score-report`, `customer-loyalty-points-report`, `redeem-point-report`, `reports/voucher` -> `monitoring/promo-loyalty`
- `monitoring/stok-mendekati-kedaluwarsa` -> `monitoring/product-slowmoving`
- `loyalty/customer-visits` -> `operations/customers`
- `branch-operations/request-print-barcode` -> `branch-operations/request-label`
- `master-data/discounts` -> `master-data/diskons`
- `reports/retur-cabang` -> `operations.store-returns`

## 4. Duplikasi kode fungsi

### 4.1 Sudah diperbaiki

- `normalizeDate` identik di 8 controller monitoring dipindahkan ke `App\Support\Concerns\NormalizesDate`:
  `BarcodeRequestReportController`, `OmsetCabangController`, `PromoUsageController`, `VoucherRejectReportController`, `MonitoringDiskonController`, `StoreReturnRequestController`, `EcommerceOrderFulfillmentController`, `OmsetKotorSalesController`.

### 4.2 Belum diperbaiki, prioritas tinggi

- `LocationstoreController` dan `LocationwarehouseController`: sekitar 1.000 baris dengan pola hampir sama. Method yang duplikat: `treeData`, `enforceParentRules`, `lockScopedValue`, `findAncestorByTypes`, `attachPivotToTopOwner`, `getDescendantLocationIds`, `orderByTypeCase`, `redirectIndex`.
  Rekomendasi: base controller atau service `LocationTreeService` dengan parameter owner type, pivot table, view, dan prefix route.
- `OperationalStockQuery` dan `StoreStockOperationsQuery`: `fefoMeta` dan `movementMeta` identik.
  Rekomendasi: query object bersama.
- `ProductSlowmovingController` dan `PhysicalStockAccumulationController`: `productPriceQuery` hampir identik (sekitar 1.900 karakter).
- `AuditWmsOpnameController` dan `AuditWmsOpnameExport`: `resolveConditionExpression` identik.
- `StoreController`, `ProductCategoryController`, dan `BrandLogoStorage`: logika simpan gambar duplikat.
- `StoreController` dan `EcommerceBannerController`: `deleteImageFromUrl` duplikat.
- `StockOpnameVarianceReportController::canDecide` dan `StoreLabelPrintRequestController::canDecideLabelRequest`: logika keputusan duplikat.
- `WarehouseInboundExport` dan `WarehouseOutboundExport`: `formatPeriod` identik.
- `BarcodeRequestReportExport` dan `VoidCancelCustomerReportExport`: `styles` identik.

### 4.3 Duplikasi internal satu file

- `TrackingBarangController::getDailyExportRows` dan `getMonthlyExportRows` hampir identik.
- `OmsetKotorSalesController::buildEcommerceWarehouseRfidSubquery` dan `buildEcommerceStoreRfidSubquery` hampir identik.
- `StoreVoucherRequestController::approve` dan `reject` hampir identik.
- `CrmDashboardExport` punya `title`, `headings`, `rows`, dan `array` dengan body yang sama; ini kemungkinan bug implementasi, bukan sekadar duplikasi.

## 5. Duplikasi tabel dan model

### 5.1 Tabel paralel untuk konsep yang sama

| Tabel A | Tabel B | Catatan |
|---|---|---|
| `store_stock_barriers` | `warehouse_stock_barriers` | Kolom identik kecuali `store_id` vs `warehouse_id`. Kandidat kuat digabung menjadi `stock_barriers` dengan scope + location_id. |
| `store_qc` | `warehouse_qc` | Struktur sangat mirip, beda pada `store_inbound_id` vs `store_id/note/status`. Perlu disatukan atau diberi batas domain yang jelas. |
| `store_items` | `warehouse_items` | Sama-sama memetakan `item_id` ke lokasi. `items.current_location_id` dan `locations` sudah menyimpan lokasi; dua pivot ini perlu dievaluasi. |
| `store_locations` | `warehouse_locations` | Pivot owner untuk tree `locations`. Bisa dipertahankan, tetapi bisa disatukan dengan kolom owner type. |
| `store_orders` | `ecommerce_orders` | Bukan duplikat: kanal dan status berbeda. |
| `payments` | `store_visit_payments` | Bukan duplikat: `payments` adalah header pembayaran, `store_visit_payments` adalah relasi kunjungan. |

### 5.2 Tabel snapshot yang sengaja duplikat

Tabel berikut mirip tabel master, tetapi menyimpan snapshot historis. Jangan dihapus:

- `ecommerce_order_shipping_addresses` vs `ecommerce_shipping_addresses`
- `ecommerce_order_vouchers` vs `vouchers`
- `ecommerce_order_detail_discounts` vs `discounts`

### 5.3 Tabel tanpa referensi kode

Pemindaian `app/`, `resources/js/`, dan `database/` menemukan 31 tabel tanpa referensi langsung:

`discount_rules`, `ecommerce_shipping_addresses`, `goose_db_version`, `loyalty_audit_logs`, `migrations`, `national_holidays`, `order_shipment_events`, `order_shipments`, `partner_credit_tiers`, `partner_employee_codes`, `partner_fraud_alerts`, `partner_invoice_batch_items`, `partner_invoice_batches`, `partner_paylater_audit_logs`, `payment_exceptions`, `product_reviews`, `reprint_authorizations`, `spatial_ref_sys`, `store_attendance_leaves`, `store_attendance_logs`, `store_attendance_shift_exchanges`, `store_opname_reconciliation_results`, `store_receivable_invoices`, `store_receivable_payment_allocations`, `verification_codes`, `void_authorization_otps`, `warehouse_item_location_suggestions`, `warehouse_outbound_qc_photos`, `warehouse_return_inbound_details`, `warehouse_staging_outbound_details`, `warehouse_staging_outbounds`.

Sebagian adalah tabel domain lain yang belum diintegrasikan, bukan duplikat. Jangan drop tanpa review pemilik domain. `goose_db_version`, `spatial_ref_sys`, dan `migrations` adalah tabel infrastruktur.

## 6. Duplikasi permission dan migration

### 6.1 Permission duplikat

Ditemukan dua baris `permissions` dengan nama sama: `view master-promo-center`, dengan role yang berbeda. Migration baru:

- `database/migrations/2026_11_07_000002_deduplicate_backoffice_permissions.php`

Migration ini:

1. Menggabungkan `role_has_permissions` dan `model_has_permissions` ke permission kanonik.
2. Menghapus baris permission duplikat.
3. Menambahkan unique index `permissions_name_guard_name_unique`.

Sudah dijalankan di database `veestore_bo_dev`. Hasil: 0 duplikat, unique index aktif.

### 6.2 Permission legacy yang tumpang tindih

Contoh pasangan yang membingungkan:

- `view master-data-diskons` vs `view master-data-discounts`
- `view master-data-products-diskons` vs `view master-data-product-discounts`
- `view master-data-customers` vs `view master-customer`
- `view crm` vs `view crm-dashboard`
- `view monitoring.stok-percabang` vs `view monitoring-stok-percabang`
- `view monitoring.fulfillment-control` vs `view monitoring-fulfillment-control`

Sumber utamanya adalah slug menu JSON yang berbeda dari nama permission yang dipakai controller. Rekomendasi: satukan penamaan permission saat registry menu dibuat, lalu migrasikan role.

### 6.3 Migration permission duplikat

Sebelumnya ada tiga file untuk tabel permission yang sama:

- `database/migrations/2025_12_01_073257_create_permission_tables_immostore_backoffice.php` (aktif)
- `database/migrations/immostore_backoffice/2025_10_23_000001_create_permission_tables.php` (dihapus)
- `database/migrations_backoffice/2025_10_24_032632_create_permission_tables_uuid.php` (dihapus)

Dua file terakhir tidak pernah dijalankan otomatis karena berada di luar folder migration default. Menyimpannya hanya menciptakan dua sumber skema yang bisa berbeda.

## 7. Perubahan yang diimplementasikan

### File dihapus

- `app/Http/Controllers/Operations/CustomerDemographicsOperationsController.php`
- `app/Http/Controllers/Reports/StoreReturnApprovalController.php`
- `resources/js/pages/Operations/CustomerDemographics.vue`
- `resources/js/pages/Reports/Returns.vue`
- `resources/views/auth.blade.php` (root view auth duplikat dari `app.blade.php`)
- `resources/views/layouts/` (layout + menu Blade legacy yang tidak lagi dipakai)
- `app/Providers/MenuServiceProvider.php` (komposer menu Blade legacy)
- `resources/menu/verticalMenu copy.json`
- `resources/menu/verticalMenu copy 2.json`
- `database/migrations_backoffice/`
- `database/migrations/immostore_backoffice/`

### File ditambah

- `app/Support/Concerns/NormalizesDate.php`
- `database/migrations/2026_11_07_000002_deduplicate_backoffice_permissions.php`
- `tests/Feature/Shell/RoutingIntegrityTest.php`
- `docs/duplication-audit.md`

### Route dan menu

- Demografi Operasional menjadi redirect ke Reports/CRM.
- Retur Cabang menjadi redirect ke Operasional.
- Approval order hanya di Operasional.
- 11 route duplikat/rusak dihapus.
- Menu duplikat di `verticalMenu.json` dihapus.
- Duplikasi slug menu dihapus.

### Kode

- 8 duplikasi `normalizeDate` dihapus.
- Dead method Command Center dihapus.
- `Reports/OrderCabangController` turun sekitar 340 baris.
- `CustomerController::destroy` diimplementasikan agar route delete tidak lagi 500.
- Root view auth disatukan ke `resources/views/app.blade.php`; tiga pemanggilan `Inertia::setRootView('auth')` dihapus.

## 8. Verifikasi

| Pemeriksaan | Hasil |
|---|---|
| Route aktual | 364 (sebelum 375) |
| Route menunjuk method tidak ada | 0 (sebelum 9) |
| Nama route duplikat | 0 (sebelum 1) |
| Method+URI duplikat | 0 |
| Duplikat slug menu JSON | 0 |
| Permission duplikat | 0 |
| Full test suite | 172 lulus, 2.876 assertion |
| PHPStan | 0 error |
| TypeScript (`vue-tsc`) | Lulus |
| Pint | Lulus |
| Migration permission | Berhasil dijalankan |

## 9. Rekomendasi prioritas

### P1

1. Buat satu `MenuRegistry` untuk sidebar, Command Center, system map, dan seeder permission.
2. Satukan query operasional dan monitoring untuk stok/inbound/outbound agar angka tidak dihitung dua kali dengan logika berbeda.
3. Ekstrak `LocationstoreController` dan `LocationwarehouseController` ke base/service bersama.

### P2

4. Konsolidasikan `store_stock_barriers` + `warehouse_stock_barriers` setelah approval pemilik domain.
5. Konsolidasikan tiga halaman fulfillment menjadi satu halaman dengan tab.
6. Satukan permission legacy ke nama kanonik, lalu hapus permission yang tidak dipakai.
7. Ekstrak `productPriceQuery`, `resolveConditionExpression`, dan helper export yang masih duplikat.

### P3

8. Review 31 tabel tanpa referensi kode; tandai sebagai milik domain lain, arsip, atau hapus.
9. Regenerasi isi `docs/route-inventory.md`; banner peringatan sudah ditambahkan, tetapi daftar route belum dibuat ulang.
10. Perluas `tests/Feature/Shell/RoutingIntegrityTest.php` agar juga memeriksa permission legacy dan referensi tabel yang tidak terpakai.
