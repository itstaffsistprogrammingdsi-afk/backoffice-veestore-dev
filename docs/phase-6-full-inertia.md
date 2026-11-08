# Fase 6: Migrasi Full Inertia

Status: selesai
Tanggal: 2026-11-01
Branch: `main`

## 1. Tujuan

Memindahkan seluruh halaman bisnis dari Blade ke Inertia + Vue 3 + TypeScript. Template Materio dan halaman demo tidak menjadi prioritas.

## 2. Halaman yang sudah Inertia

| Modul | Halaman | Komponen |
|---|---|---|
| AI Analytics | `/ai-analytics` | `Analytics/Index` |
| AI Assistant | `/ai-assistant` | `Assistant/Index` |
| Settings | `/settings/app/points` | `Settings/Points` |
| Settings | `/settings/app/affiliate-points/inviter` | `Settings/AffiliatePoints` |
| Settings | `/settings/app/affiliate-points/invitee` | `Settings/AffiliatePoints` |
| Settings | `/settings/app/customer-loyalty-point-transaction-reward` | `Settings/TransactionReward` |
| Loyalty | `/loyalty/customer-loyalty-points` | `Loyalty/Points` |
| Loyalty | `/loyalty/customer-loyalty-point-mutations` | `Loyalty/Mutations` |
| Loyalty | `/loyalty/customer-visits` | `Loyalty/Visits` |
| Management | `/management/roles-backoffice` | `Management/Roles` |
| Management | `/management/user-roles` | `Management/UserRoles` |
| Management | `/management/role-permissions` | `Management/RolePermissions` |
| Management | `/management/request-labels` | `Management/LabelRequests` |
| Report Export | `/report-exports` | `Reports/ExportList` |
| Report Export | `/report-exports/{id}` | `Reports/ExportStatus` |
| Reports | `/reports/supplier-products` | `Reports/SupplierProducts` |
| Reports | `/reports/voucher` | `Reports/Voucher` |
| Reports | `/reports/crm` | `Reports/Crm` |
| Reports | `/reports/crm/customer-profiles` | `Reports/CrmCustomers` |
| Reports | `/reports/crm/demographics` | `Reports/CrmDemographics` |
| Reports | `/reports/crm/customers/{id}` | `Reports/CrmCustomerProfile` |
| Reports | `/reports/history-customer` | `Reports/HistoryCustomer` |
| Reports | `/reports/order-cabang` | `Reports/OrderCabang` |
| Reports | `/reports/retur-cabang` | Redirect ke `operations.store-returns` (audit duplikasi 2026-11-07) |
| Master Data | `/master-data/partners` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/memberships` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/payment-methods` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/product-specifications` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/rfid-tags` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/membership-benefits` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/warehouses` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/units` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/suppliers` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/item-conditions` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/couriers` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/diskons` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/brands` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/product-categories` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/customers` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/stores` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/products` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/users` | `MasterData/SimpleCrud` |
| Master Data | `/master-data/ecommerce-banners` | `MasterData/SimpleCrud` |
| Branch Operations | `/branch-operations/request-label` | `BranchOperations/LabelPrintRequests` |
| Branch Operations | `/branch-operations/request-voucher` | `BranchOperations/VoucherRequests` |
| Ecommerce | `/ecommerce/orders` | `Ecommerce/Orders` |
| Auth | `/login`, `/auth/login-basic` | `Auth/Login` |
| Auth | `/auth/register-basic` | `Auth/Register` |
| Auth | `/auth/forgot-password-basic` | `Auth/ForgotPassword` |
| Monitoring | Semua route `monitoring.*` | `Monitoring/Report` |
| Monitoring | `/monitoring` | `Monitoring/Index` |
| Dashboard | `/dashboard` | `Dashboard/Index` |
| Master Data | `/master-data/vouchers/{key}/codes` | `MasterData/VoucherCodes` |
| Master Data | `/master-data/vouchers/{key}/print-codes` | `MasterData/VoucherPrint` |

Blade view Settings, Loyalty, Management, Report Export, Branch Operations, Ecommerce, Auth, seluruh Reports, seluruh Master Data, seluruh Monitoring, dan Dashboard sudah dihapus karena tidak dipakai lagi.

## 3. Sisa halaman Blade

Tidak ada halaman bisnis yang masih memakai Blade. Yang tersisa hanya:

- `resources/views/app.blade.php` sebagai root Inertia.
- Root view auth memakai `resources/views/app.blade.php` yang sama.
- Layout/partials lama, error page, dan pagination vendor yang tidak menjadi halaman aplikasi.

## 4. Pola migrasi per modul

1. Pindahkan validasi ke Form Request.
2. Pindahkan query ke Query Object atau Action.
3. Ubah controller menjadi `Inertia::render`.
4. Buat halaman Vue dengan layout dan komponen tabel/form yang dipakai ulang.
5. Tambahkan test Inertia dan test aksi tulis.
6. Hapus Blade view modul setelah test lulus.
7. Update `docs/route-inventory.md` bila route berubah.

## 5. Urutan migrasi yang dieksekusi

1. Auth.
2. Settings, Loyalty, Management, Report Export.
3. Branch Operations dan Ecommerce.
4. Reports.
5. Master Data.
6. Monitoring.
7. Dashboard.

## 6. Validasi akhir

| Pemeriksaan | Hasil |
|---|---|
| Full test suite | 50 lulus, 892 assertion |
| `composer lint` | Lulus |
| `composer analyse` | 0 error |
| `npm run typecheck` | Lulus |
| `npm run build:vite` | Berhasil |
| `route:cache` | Berhasil |
| `view:cache` | Berhasil |
| Smoke test Monitoring | 56 route, 54x200, 2x302, tanpa 500 |

## 7. Catatan

- Notifikasi tetap berupa endpoint JSON karena dipakai navbar dan polling, bukan halaman.
- Route `report-exports/{id}/download` tetap mengembalikan file, bukan halaman Inertia.
- `app.blade.php` sekarang hanya root Inertia; shell aplikasi sepenuhnya `resources/js/layouts/AppLayout.vue`.
- Monitoring memakai `Monitoring/Report` generik untuk tabel, filter, dan ringkasan. Export/custom chart per laporan perlu ditambahkan bila dibutuhkan.
- Dashboard memakai subset query yang ditampilkan di `Dashboard/Index`; schema introspection di-cache per request agar tidak menembak database puluhan kali.
- Prefix `master-data.` dan `monitoring.` sudah dianggap Inertia di `app/Support/Navigation.php`.
