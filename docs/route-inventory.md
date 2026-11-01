# Inventaris Route

Dokumen ini dihasilkan dari `php artisan route:list --json` pada Laravel 13.39.0. Hanya route aplikasi yang memakai controller `App\Http\Controllers` yang dicatat. Route framework (Ignition, Sanctum, redirect legacy) tidak dimasukkan.

Total route aplikasi: **307**

## Ringkasan per modul

| Modul | Route | GET | Non-GET | Auth | Ber-permission |
|---|---:|---:|---:|---:|---:|
| Master Data | 114 | 35 | 79 | 114 | 98 |
| Monitoring | 111 | 105 | 6 | 111 | 22 |
| Reports | 31 | 22 | 9 | 31 | 2 |
| Management | 13 | 6 | 7 | 13 | 0 |
| Settings | 8 | 4 | 4 | 8 | 0 |
| Auth | 6 | 3 | 3 | 0 | 0 |
| Branch Operations | 4 | 2 | 2 | 4 | 0 |
| Ecommerce | 4 | 3 | 1 | 4 | 0 |
| Notifications | 4 | 3 | 1 | 4 | 0 |
| AI Analytics | 3 | 1 | 2 | 3 | 0 |
| AI Assistant | 2 | 1 | 1 | 2 | 2 |
| Loyalty | 3 | 3 | 0 | 3 | 3 |
| Report Export | 3 | 3 | 0 | 3 | 0 |
| Dashboard | 1 | 1 | 0 | 1 | 0 |
| **Total** | **307** | **192** | **115** | **301** | **127** |

## Daftar route per modul

### Master Data

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/master-data/api/product-prices/{id}` | `master-data.` | `ProductPriceController@getUsedTypes` | auth | - |
| GET | `/master-data/brands` | `master-data.brands.index` | `BrandController@index` | auth | view master-data-brands |
| POST | `/master-data/brands` | `master-data.brands.store` | `BrandController@store` | auth | create master-data-brands |
| DELETE | `/master-data/brands/{brand}` | `master-data.brands.destroy` | `BrandController@destroy` | auth | delete master-data-brands |
| PUT | `/master-data/brands/{brand}` | `master-data.brands.update` | `BrandController@update` | auth | update master-data-brands |
| GET | `/master-data/couriers` | `master-data.couriers.index` | `CourierController@index` | auth | view master-data-couriers |
| POST | `/master-data/couriers` | `master-data.couriers.store` | `CourierController@store` | auth | create master-data-couriers |
| DELETE | `/master-data/couriers/{courier}` | `master-data.couriers.destroy` | `CourierController@destroy` | auth | delete master-data-couriers |
| PUT | `/master-data/couriers/{courier}` | `master-data.couriers.update` | `CourierController@update` | auth | update master-data-couriers |
| GET | `/master-data/customers` | `master-data.customers.index` | `CustomerController@index` | auth | view master-customer |
| POST | `/master-data/customers` | `master-data.customers.store` | `CustomerController@store` | auth | create master-customer |
| GET | `/master-data/customers/regions/districts` | `master-data.customers.districts` | `CustomerController@districts` | auth | view master-customer |
| GET | `/master-data/customers/regions/regencies` | `master-data.customers.regencies` | `CustomerController@regencies` | auth | view master-customer |
| GET | `/master-data/customers/regions/villages` | `master-data.customers.villages` | `CustomerController@villages` | auth | view master-customer |
| DELETE | `/master-data/customers/{customer}` | `master-data.customers.destroy` | `CustomerController@destroy` | auth | - |
| PUT | `/master-data/customers/{customer}` | `master-data.customers.update` | `CustomerController@update` | auth | update master-customer |
| GET | `/master-data/diskons` | `master-data.diskons.index` | `DiscountController@index` | auth | view master-data-discounts |
| POST | `/master-data/diskons` | `master-data.diskons.store` | `DiscountController@store` | auth | create master-data-discounts |
| DELETE | `/master-data/diskons/{discount}` | `master-data.diskons.destroy` | `DiscountController@destroy` | auth | delete master-data-discounts |
| PUT | `/master-data/diskons/{discount}` | `master-data.diskons.update` | `DiscountController@update` | auth | update master-data-discounts |
| GET | `/master-data/ecommerce-banners` | `master-data.ecommerce-banners.index` | `EcommerceBannerController@index` | auth | view master-data-ecommerce-banners |
| POST | `/master-data/ecommerce-banners` | `master-data.ecommerce-banners.store` | `EcommerceBannerController@store` | auth | create master-data-ecommerce-banners |
| DELETE | `/master-data/ecommerce-banners/{ecommerce_banner}` | `master-data.ecommerce-banners.destroy` | `EcommerceBannerController@destroy` | auth | delete master-data-ecommerce-banners |
| PUT | `/master-data/ecommerce-banners/{ecommerce_banner}` | `master-data.ecommerce-banners.update` | `EcommerceBannerController@update` | auth | update master-data-ecommerce-banners |
| GET | `/master-data/item-conditions` | `master-data.item-conditions.index` | `ItemConditionController@index` | auth | view master-data-item-conditions |
| POST | `/master-data/item-conditions` | `master-data.item-conditions.store` | `ItemConditionController@store` | auth | create master-data-item-conditions |
| DELETE | `/master-data/item-conditions/{item_condition}` | `master-data.item-conditions.destroy` | `ItemConditionController@destroy` | auth | delete master-data-item-conditions |
| PUT | `/master-data/item-conditions/{item_condition}` | `master-data.item-conditions.update` | `ItemConditionController@update` | auth | update master-data-item-conditions |
| GET | `/master-data/locations-store` | `master-data.locations-store.index` | `LocationstoreController@index` | auth | view master-data-locations-store |
| POST | `/master-data/locations-store` | `master-data.locations-store.store` | `LocationstoreController@store` | auth | create master-data-locations-store |
| GET | `/master-data/locations-store/tree` | `master-data.locations-store.tree` | `LocationstoreController@treeData` | auth | view master-data-locations-store |
| DELETE | `/master-data/locations-store/{location}` | `master-data.locations-store.destroy` | `LocationstoreController@destroy` | auth | delete master-data-locations-store |
| PUT | `/master-data/locations-store/{location}` | `master-data.locations-store.update` | `LocationstoreController@update` | auth | update master-data-locations-store |
| GET | `/master-data/locations-store/{location}/qr` | `master-data.locations-store.qr` | `LocationstoreController@qr` | auth | view master-data-locations-store |
| GET | `/master-data/locations-warehouse` | `master-data.locations-warehouse.index` | `LocationwarehouseController@index` | auth | view master-data-locations-warehouse |
| POST | `/master-data/locations-warehouse` | `master-data.locations-warehouse.store` | `LocationwarehouseController@store` | auth | create master-data-locations-warehouse |
| GET | `/master-data/locations-warehouse/tree` | `master-data.locations-warehouse.tree` | `LocationwarehouseController@treeData` | auth | view master-data-locations-warehouse |
| DELETE | `/master-data/locations-warehouse/{location}` | `master-data.locations-warehouse.destroy` | `LocationwarehouseController@destroy` | auth | delete master-data-locations-warehouse |
| PUT | `/master-data/locations-warehouse/{location}` | `master-data.locations-warehouse.update` | `LocationwarehouseController@update` | auth | update master-data-locations-warehouse |
| GET | `/master-data/locations-warehouse/{location}/qr` | `master-data.locations-warehouse.qr` | `LocationwarehouseController@qr` | auth | view master-data-locations-warehouse |
| GET | `/master-data/membership-benefits` | `master-data.membership-benefits.index` | `MembershipBenefitController@index` | auth | view master-data-membership-benefits |
| POST | `/master-data/membership-benefits` | `master-data.membership-benefits.store` | `MembershipBenefitController@store` | auth | create master-data-membership-benefits |
| DELETE | `/master-data/membership-benefits/{membership_benefit}` | `master-data.membership-benefits.destroy` | `MembershipBenefitController@destroy` | auth | delete master-data-membership-benefits |
| PUT | `/master-data/membership-benefits/{membership_benefit}` | `master-data.membership-benefits.update` | `MembershipBenefitController@update` | auth | update master-data-membership-benefits |
| GET | `/master-data/memberships` | `master-data.memberships.index` | `MembershipController@index` | auth | view master-data-memberships |
| POST | `/master-data/memberships` | `master-data.memberships.store` | `MembershipController@store` | auth | create master-data-memberships |
| DELETE | `/master-data/memberships/{membership}` | `master-data.memberships.destroy` | `MembershipController@destroy` | auth | delete master-data-memberships |
| PUT | `/master-data/memberships/{membership}` | `master-data.memberships.update` | `MembershipController@update` | auth | update master-data-memberships |
| GET | `/master-data/partners` | `master-data.partners.index` | `PartnerController@index` | auth | - |
| POST | `/master-data/partners` | `master-data.partners.store` | `PartnerController@store` | auth | - |
| DELETE | `/master-data/partners/{partner}` | `master-data.partners.destroy` | `PartnerController@destroy` | auth | - |
| PUT | `/master-data/partners/{partner}` | `master-data.partners.update` | `PartnerController@update` | auth | - |
| GET | `/master-data/payment-methods` | `master-data.payment-methods.index` | `PaymentMethodController@index` | auth | view master-data-payment-methods |
| POST | `/master-data/payment-methods` | `master-data.payment-methods.store` | `PaymentMethodController@store` | auth | create master-data-payment-methods |
| DELETE | `/master-data/payment-methods/{payment_method}` | `master-data.payment-methods.destroy` | `PaymentMethodController@destroy` | auth | delete master-data-payment-methods |
| PUT | `/master-data/payment-methods/{payment_method}` | `master-data.payment-methods.update` | `PaymentMethodController@update` | auth | update master-data-payment-methods |
| GET | `/master-data/product-categories` | `master-data.product-categories.index` | `ProductCategoryController@index` | auth | view master-data-product-categories |
| POST | `/master-data/product-categories` | `master-data.product-categories.store` | `ProductCategoryController@store` | auth | create master-data-product-categories |
| DELETE | `/master-data/product-categories/{product_category}` | `master-data.product-categories.destroy` | `ProductCategoryController@destroy` | auth | delete master-data-product-categories |
| PUT | `/master-data/product-categories/{product_category}` | `master-data.product-categories.update` | `ProductCategoryController@update` | auth | update master-data-product-categories |
| GET | `/master-data/product-prices` | `master-data.product-prices.index` | `ProductPriceController@index` | auth | view master-data-product-prices |
| POST | `/master-data/product-prices` | `master-data.product-prices.store` | `ProductPriceController@store` | auth | create master-data-product-prices |
| POST | `/master-data/product-prices/check-duplicate` | `master-data.product-prices.check-duplicate` | `ProductPriceController@checkDuplicate` | auth | create master-data-product-prices, update master-data-product-prices |
| DELETE | `/master-data/product-prices/{product_price}` | `master-data.product-prices.destroy` | `ProductPriceController@destroy` | auth | delete master-data-product-prices |
| PUT | `/master-data/product-prices/{product_price}` | `master-data.product-prices.update` | `ProductPriceController@update` | auth | update master-data-product-prices |
| GET | `/master-data/product-specifications` | `master-data.product-specifications.index` | `ProductSpecificationController@index` | auth | view master-data-product-specifications |
| POST | `/master-data/product-specifications` | `master-data.product-specifications.store` | `ProductSpecificationController@store` | auth | create master-data-product-specifications |
| DELETE | `/master-data/product-specifications/{productSpecification}` | `master-data.product-specifications.destroy` | `ProductSpecificationController@destroy` | auth | delete master-data-product-specifications |
| PUT|PATCH | `/master-data/product-specifications/{productSpecification}` | `master-data.product-specifications.update` | `ProductSpecificationController@update` | auth | update master-data-product-specifications |
| GET | `/master-data/products` | `master-data.products.index` | `ProductController@index` | auth | view master-data-products |
| POST | `/master-data/products` | `master-data.products.store` | `ProductController@store` | auth | create master-data-products |
| DELETE | `/master-data/products-diskons` | `master-data.products-diskons.destroy` | `ProductDiscountController@destroy` | auth | delete master-data-product-discounts |
| GET | `/master-data/products-diskons` | `master-data.products-diskons.index` | `ProductDiscountController@index` | auth | view master-data-product-discounts |
| POST | `/master-data/products-diskons` | `master-data.products-diskons.store` | `ProductDiscountController@store` | auth | create master-data-product-discounts |
| PUT | `/master-data/products-diskons` | `master-data.products-diskons.update` | `ProductDiscountController@update` | auth | update master-data-product-discounts |
| DELETE | `/master-data/products/{product}` | `master-data.products.destroy` | `ProductController@destroy` | auth | delete master-data-products |
| PUT | `/master-data/products/{product}` | `master-data.products.update` | `ProductController@update` | auth | update master-data-products |
| GET | `/master-data/promos` | `master-data.promos.index` | `PromoController@index` | auth | view master-data-promos |
| POST | `/master-data/promos` | `master-data.promos.store` | `PromoController@store` | auth | create master-data-promos |
| DELETE | `/master-data/promos/{promo}` | `master-data.promos.destroy` | `PromoController@destroy` | auth | delete master-data-promos |
| PUT | `/master-data/promos/{promo}` | `master-data.promos.update` | `PromoController@update` | auth | update master-data-promos |
| GET | `/master-data/rfid-tags` | `master-data.rfid-tags.index` | `RfidTagController@index` | auth | - |
| POST | `/master-data/rfid-tags` | `master-data.rfid-tags.store` | `RfidTagController@store` | auth | - |
| DELETE | `/master-data/rfid-tags/{rfid_tag}` | `master-data.rfid-tags.destroy` | `RfidTagController@destroy` | auth | - |
| PUT | `/master-data/rfid-tags/{rfid_tag}` | `master-data.rfid-tags.update` | `RfidTagController@update` | auth | - |
| GET | `/master-data/stores` | `master-data.stores.index` | `StoreController@index` | auth | view master-data-stores |
| POST | `/master-data/stores` | `master-data.stores.store` | `StoreController@store` | auth | create master-data-stores |
| DELETE | `/master-data/stores/{store}` | `master-data.stores.destroy` | `StoreController@destroy` | auth | delete master-data-stores |
| PUT | `/master-data/stores/{store}` | `master-data.stores.update` | `StoreController@update` | auth | update master-data-stores |
| GET | `/master-data/suppliers` | `master-data.suppliers.index` | `SupplierController@index` | auth | view master-data-suppliers |
| POST | `/master-data/suppliers` | `master-data.suppliers.store` | `SupplierController@store` | auth | create master-data-suppliers |
| DELETE | `/master-data/suppliers/{supplier}` | `master-data.suppliers.destroy` | `SupplierController@destroy` | auth | delete master-data-suppliers |
| PUT | `/master-data/suppliers/{supplier}` | `master-data.suppliers.update` | `SupplierController@update` | auth | update master-data-suppliers |
| GET | `/master-data/suppliers/{supplier}/edit` | `master-data.suppliers.edit` | `SupplierController@edit` | auth | - |
| GET | `/master-data/units` | `master-data.units.index` | `UnitController@index` | auth | view master-data-units |
| POST | `/master-data/units` | `master-data.units.store` | `UnitController@store` | auth | create master-data-units |
| DELETE | `/master-data/units/{unit}` | `master-data.units.destroy` | `UnitController@destroy` | auth | delete master-data-units |
| PUT | `/master-data/units/{unit}` | `master-data.units.update` | `UnitController@update` | auth | update master-data-units |
| GET | `/master-data/users` | `master-data.users.index` | `UserManageController@index` | auth | - |
| POST | `/master-data/users` | `master-data.users.store` | `UserManageController@store` | auth | - |
| DELETE | `/master-data/users/{user}` | `master-data.users.destroy` | `UserManageController@destroy` | auth | - |
| PUT | `/master-data/users/{user}` | `master-data.users.update` | `UserManageController@update` | auth | - |
| PATCH | `/master-data/users/{user}/toggle-active` | `master-data.users.toggle-active` | `UserManageController@toggleActive` | auth | - |
| GET | `/master-data/vouchers` | `master-data.vouchers.index` | `VoucherController@index` | auth | view master-data-vouchers |
| POST | `/master-data/vouchers` | `master-data.vouchers.store` | `VoucherController@store` | auth | create master-data-vouchers |
| DELETE | `/master-data/vouchers/{key}` | `master-data.vouchers.destroy` | `VoucherController@destroy` | auth | delete master-data-vouchers |
| PUT | `/master-data/vouchers/{key}` | `master-data.vouchers.update` | `VoucherController@update` | auth | update master-data-vouchers |
| GET | `/master-data/vouchers/{key}/codes` | `master-data.vouchers.codes` | `VoucherController@voucherCodes` | auth | view master-data-vouchers |
| PATCH | `/master-data/vouchers/{key}/codes/{voucherCodeId}/status` | `master-data.vouchers.codes.status` | `VoucherController@updateVoucherCodeStatus` | auth | update master-data-vouchers |
| POST | `/master-data/vouchers/{key}/print-codes` | `master-data.vouchers.print-codes` | `VoucherController@printUniqueCodes` | auth | update master-data-vouchers |
| GET | `/master-data/warehouses` | `master-data.warehouses.index` | `WarehouseController@index` | auth | view master-data-warehouses |
| POST | `/master-data/warehouses` | `master-data.warehouses.store` | `WarehouseController@store` | auth | create master-data-warehouses |
| DELETE | `/master-data/warehouses/{warehouse}` | `master-data.warehouses.destroy` | `WarehouseController@destroy` | auth | delete master-data-warehouses |
| PUT | `/master-data/warehouses/{warehouse}` | `master-data.warehouses.update` | `WarehouseController@update` | auth | update master-data-warehouses |

### Monitoring

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/monitoring` | `monitoring.command-center.index` | `MonitoringCommandCenterController@index` | auth | - |
| GET | `/monitoring/anomali-item` | `monitoring.anomali-item.index` | `AnomalyItemMonitoringController@index` | auth | - |
| GET | `/monitoring/anomali-item/{item}/location-history` | `monitoring.anomali-item.location-history` | `AnomalyItemMonitoringController@locationHistory` | auth | - |
| GET | `/monitoring/anomali-item/{item}/location-history-json` | `monitoring.anomali-item.location-history-json` | `AnomalyItemMonitoringController@locationHistoryJson` | auth | - |
| GET | `/monitoring/audit-center` | `monitoring.audit-center.index` | `MonitoringCommandCenterController@auditCenter` | auth | - |
| GET | `/monitoring/audit-wms-opnames` | `monitoring.audit-wms-opnames.index` | `AuditWmsOpnameController@index` | auth | - |
| GET | `/monitoring/audit-wms-opnames/detail` | `monitoring.audit-wms-opnames.detail` | `AuditWmsOpnameController@detail` | auth | - |
| GET | `/monitoring/audit-wms-opnames/export` | `monitoring.audit-wms-opnames.export` | `AuditWmsOpnameController@export` | auth | - |
| GET | `/monitoring/barcode-request-report` | `monitoring.barcode-request-report.index` | `BarcodeRequestReportController@index` | auth | - |
| GET | `/monitoring/barcode-request-report/export` | `monitoring.barcode-request-report.export` | `BarcodeRequestReportController@export` | auth | - |
| GET | `/monitoring/cashier-shift-report` | `monitoring.cashier-shift-report.index` | `CashierShiftReportController@index` | auth | - |
| GET | `/monitoring/cashier-shift-report/export` | `monitoring.cashier-shift-report.export` | `CashierShiftReportController@export` | auth | - |
| GET | `/monitoring/cashier-shift-report/transaction/{paymentId}/items` | `monitoring.cashier-shift-report.transaction-items` | `CashierShiftReportController@transactionItems` | auth | - |
| GET | `/monitoring/cashier-shift-report/{cashierSession}/detail` | `monitoring.cashier-shift-report.detail` | `CashierShiftReportController@detail` | auth | - |
| GET | `/monitoring/cashier-shift-report/{cashierSession}/detail-export` | `monitoring.cashier-shift-report.detail-export` | `CashierShiftReportController@detailExport` | auth | - |
| GET | `/monitoring/customer-loyalty-points-report` | `monitoring.customer-loyalty-points-report.index` | `CustomerLoyaltyPointsReportController@index` | auth | - |
| GET | `/monitoring/customer-loyalty-points-report/export` | `monitoring.customer-loyalty-points-report.export` | `CustomerLoyaltyPointsReportController@export` | auth | - |
| GET | `/monitoring/customer-membership` | `monitoring.customer-membership.index` | `CustomerMembershipMonitoringController@index` | auth | - |
| GET | `/monitoring/data-customer` | `monitoring.data-customer.index` | `MonitoringDataCustomerController@index` | auth | - |
| GET | `/monitoring/data-customer/districts` | `monitoring.data-customer.districts` | `MonitoringDataCustomerController@districts` | auth | - |
| GET | `/monitoring/data-customer/export` | `monitoring.data-customer.export` | `MonitoringDataCustomerController@export` | auth | - |
| GET | `/monitoring/data-customer/postal-code` | `monitoring.data-customer.postal-code` | `MonitoringDataCustomerController@postalCode` | auth | - |
| GET | `/monitoring/data-customer/regencies` | `monitoring.data-customer.regencies` | `MonitoringDataCustomerController@regencies` | auth | - |
| GET | `/monitoring/data-customer/villages` | `monitoring.data-customer.villages` | `MonitoringDataCustomerController@villages` | auth | - |
| GET | `/monitoring/data-quality` | `monitoring.data-quality.index` | `MonitoringCommandCenterController@dataQuality` | auth | - |
| GET | `/monitoring/diskon` | `monitoring.diskon.index` | `MonitoringDiskonController@index` | auth | - |
| GET | `/monitoring/diskon/export` | `monitoring.diskon.export` | `MonitoringDiskonController@export` | auth | - |
| GET | `/monitoring/ecommerce-order-fulfillment` | `monitoring.ecommerce-order-fulfillment.index` | `EcommerceOrderFulfillmentController@index` | auth | - |
| GET | `/monitoring/ecommerce-order-fulfillment/export` | `monitoring.ecommerce-order-fulfillment.export` | `EcommerceOrderFulfillmentController@export` | auth | - |
| GET | `/monitoring/ecommerce-payment` | `monitoring.ecommerce-payment.index` | `EcommercePaymentController@index` | auth | - |
| GET | `/monitoring/ecommerce-payment/export` | `monitoring.ecommerce-payment.export` | `EcommercePaymentController@export` | auth | - |
| GET | `/monitoring/ecommerce-payment/{order}/products` | `monitoring.ecommerce-payment.products` | `EcommercePaymentController@products` | auth | - |
| GET | `/monitoring/employee-shift-attendance` | `monitoring.employee-shift-attendance.index` | `EmployeeShiftAttendanceController@index` | auth | - |
| GET | `/monitoring/employee-shift-attendance/export` | `monitoring.employee-shift-attendance.export` | `EmployeeShiftAttendanceController@export` | auth | - |
| GET | `/monitoring/fulfillment-control` | `monitoring.fulfillment-control.index` | `MonitoringCommandCenterController@fulfillmentControl` | auth | - |
| GET | `/monitoring/mutasi-stock-wms` | `monitoring.mutasi-stock-wms` | `MutasiStockWmsController@index` | auth | - |
| GET | `/monitoring/mutasi-stock-wms/export` | `monitoring.mutasi-stock-wms.export` | `MutasiStockWmsController@export` | auth | - |
| GET | `/monitoring/omset-cabang` | `monitoring.omset-cabang.index` | `OmsetCabangController@index` | auth | view monitoring-omset-cabang |
| GET | `/monitoring/omset-cabang/export` | `monitoring.omset-cabang.export` | `OmsetCabangController@export` | auth | view monitoring-omset-cabang |
| GET | `/monitoring/omset-kotor` | `monitoring.omset-kotor.index` | `OmsetKotorSalesController@index` | auth | view monitoring-omset-kotor |
| GET | `/monitoring/omset-kotor/export` | `monitoring.omset-kotor.export` | `OmsetKotorSalesController@export` | auth | view monitoring-omset-kotor |
| GET | `/monitoring/omset-kotor/{product}/detail` | `monitoring.omset-kotor.detail` | `OmsetKotorSalesController@detail` | auth | view monitoring-omset-kotor |
| GET | `/monitoring/payment-income-realtime` | `monitoring.payment-income-realtime.index` | `PaymentIncomeRealtimeController@index` | auth | - |
| GET | `/monitoring/payment-income-realtime/export` | `monitoring.payment-income-realtime.export` | `PaymentIncomeRealtimeController@export` | auth | - |
| GET | `/monitoring/payment-reconciliation` | `monitoring.payment-reconciliation.index` | `MonitoringCommandCenterController@paymentReconciliation` | auth | - |
| GET | `/monitoring/physical-stocks` | `monitoring.physical-stocks.index` | `PhysicalStockAccumulationController@index` | auth | - |
| GET | `/monitoring/physical-stocks/export` | `monitoring.physical-stocks.export` | `PhysicalStockAccumulationController@export` | auth | - |
| GET | `/monitoring/product-aging` | `monitoring.product-aging.index` | `ProductAgingMonitoringController@index` | auth | - |
| GET | `/monitoring/product-aging/export` | `monitoring.product-aging.export` | `ProductAgingMonitoringController@export` | auth | - |
| GET | `/monitoring/product-aging/{productId}/detail` | `monitoring.product-aging.detail` | `ProductAgingMonitoringController@detail` | auth | - |
| GET | `/monitoring/product-slowmoving` | `monitoring.product-slowmoving.index` | `ProductSlowmovingController@index` | auth | view monitoring-product-slowmoving |
| GET | `/monitoring/product-slowmoving/export-dead` | `monitoring.product-slowmoving.export-dead` | `ProductSlowmovingController@exportDead` | auth | view monitoring-product-slowmoving |
| GET | `/monitoring/product-slowmoving/export-slow` | `monitoring.product-slowmoving.export-slow` | `ProductSlowmovingController@exportSlow` | auth | view monitoring-product-slowmoving |
| GET | `/monitoring/produk-terlaris` | `monitoring.produk-terlaris.index` | `ProdukTerlarisController@index` | auth | - |
| GET | `/monitoring/produk-terlaris/export` | `monitoring.produk-terlaris.export` | `ProdukTerlarisController@export` | auth | - |
| GET | `/monitoring/promo-usage` | `monitoring.promo-usage.index` | `PromoUsageController@index` | auth | - |
| GET | `/monitoring/promo-usage/export` | `monitoring.promo-usage.export` | `PromoUsageController@export` | auth | - |
| GET | `/monitoring/quality-control` | `monitoring.quality-control.index` | `MonitoringCommandCenterController@qualityControl` | auth | - |
| GET | `/monitoring/redeem-point-report` | `monitoring.redeem-point-report.index` | `RedeemPointReportController@index` | auth | - |
| GET | `/monitoring/redeem-point-report/export` | `monitoring.redeem-point-report.export` | `RedeemPointReportController@export` | auth | - |
| GET | `/monitoring/referral-score-report` | `monitoring.referral-score-report.index` | `ReferralScoreReportController@index` | auth | - |
| GET | `/monitoring/referral-score-report/export` | `monitoring.referral-score-report.export` | `ReferralScoreReportController@export` | auth | - |
| GET | `/monitoring/request-voucher` | `monitoring.request-voucher.index` | `MonitoringVoucherRequestController@index` | auth | - |
| GET | `/monitoring/request-voucher/export` | `monitoring.request-voucher.export` | `MonitoringVoucherRequestController@export` | auth | - |
| GET | `/monitoring/slow-deadstock` | `monitoring.slow-deadstock.index` | `SlowDeadstockController@index` | auth | view monitoring-slow-deadstock |
| GET | `/monitoring/slow-deadstock/export-dead` | `monitoring.slow-deadstock.export-dead` | `SlowDeadstockController@exportDead` | auth | view monitoring-slow-deadstock |
| GET | `/monitoring/slow-deadstock/export-slow` | `monitoring.slow-deadstock.export-slow` | `SlowDeadstockController@exportSlow` | auth | view monitoring-slow-deadstock |
| GET | `/monitoring/stok-barier-cabang` | `monitoring.stok-barier-cabang.index` | `StoreStockBarrierController@index` | auth | - |
| POST | `/monitoring/stok-barier-cabang` | `monitoring.stok-barier-cabang.store` | `StoreStockBarrierController@store` | auth | - |
| DELETE | `/monitoring/stok-barier-cabang/{barrier}` | `monitoring.stok-barier-cabang.destroy` | `StoreStockBarrierController@destroy` | auth | - |
| PUT | `/monitoring/stok-barier-cabang/{barrier}` | `monitoring.stok-barier-cabang.update` | `StoreStockBarrierController@update` | auth | - |
| GET | `/monitoring/stok-barier-gudang` | `monitoring.stok-barier-gudang.index` | `WarehouseStockBarrierController@index` | auth | - |
| POST | `/monitoring/stok-barier-gudang` | `monitoring.stok-barier-gudang.store` | `WarehouseStockBarrierController@store` | auth | - |
| DELETE | `/monitoring/stok-barier-gudang/{stok_barier_gudang}` | `monitoring.stok-barier-gudang.destroy` | `WarehouseStockBarrierController@destroy` | auth | - |
| PUT|PATCH | `/monitoring/stok-barier-gudang/{stok_barier_gudang}` | `monitoring.stok-barier-gudang.update` | `WarehouseStockBarrierController@update` | auth | - |
| GET | `/monitoring/stok-mendekati-kedaluwarsa` | `monitoring.stok-mendekati-kedaluwarsa.index` | `ExpiringStockMonitoringController@index` | auth | - |
| GET | `/monitoring/stok-percabang` | `monitoring.stok-percabang` | `StokPercabangController@index` | auth | - |
| GET | `/monitoring/stok-percabang/export` | `monitoring.stok-percabang.export` | `StokPercabangController@export` | auth | - |
| GET | `/monitoring/stok-percabang/reservations` | `monitoring.stok-percabang.reservations` | `StokPercabangController@reservationDetails` | auth | - |
| GET | `/monitoring/stok-wms` | `monitoring.stok-wms` | `StokWmsController@index` | auth | - |
| GET | `/monitoring/stok-wms/export` | `monitoring.stok-wms.export` | `StokWmsController@export` | auth | - |
| GET | `/monitoring/stok-wms/reservations` | `monitoring.stok-wms.reservations` | `StokWmsController@reservationDetails` | auth | - |
| GET | `/monitoring/store-inbound` | `monitoring.store-inbound.index` | `StoreInboundController@index` | auth | - |
| GET | `/monitoring/store-order-requests` | `monitoring.store-order-requests.index` | `StoreOrderRequestController@index` | auth | view monitoring-store-order-requests |
| GET | `/monitoring/store-order-requests/export` | `monitoring.store-order-requests.export` | `StoreOrderRequestController@export` | auth | view monitoring-store-order-requests |
| GET | `/monitoring/store-outbound` | `monitoring.store-outbound.index` | `StoreOutboundController@index` | auth | - |
| GET | `/monitoring/store-payment-transactions` | `monitoring.store-payment-transactions.index` | `StorePaymentTransactionController@index` | auth | view monitoring-store-payment-transactions |
| GET | `/monitoring/store-payment-transactions/export` | `monitoring.store-payment-transactions.export` | `StorePaymentTransactionController@export` | auth | view monitoring-store-payment-transactions |
| GET | `/monitoring/store-return-requests` | `monitoring.store-return-requests.index` | `StoreReturnRequestController@index` | auth | - |
| GET | `/monitoring/store-return-requests/export` | `monitoring.store-return-requests.export` | `StoreReturnRequestController@export` | auth | - |
| GET | `/monitoring/store-slowstock` | `monitoring.store-slowstock.index` | `StoreSlowstockController@index` | auth | view monitoring-store-slowstock |
| GET | `/monitoring/store-slowstock/export-dead` | `monitoring.store-slowstock.export-dead` | `StoreSlowstockController@exportDead` | auth | view monitoring-store-slowstock |
| GET | `/monitoring/store-slowstock/export-slow` | `monitoring.store-slowstock.export-slow` | `StoreSlowstockController@exportSlow` | auth | view monitoring-store-slowstock |
| GET | `/monitoring/store-stock-opnames` | `monitoring.store-stock-opnames.index` | `StoreStockOpnameReportController@index` | auth | - |
| GET | `/monitoring/store-stock-opnames/export` | `monitoring.store-stock-opnames.export` | `StoreStockOpnameReportController@export` | auth | - |
| GET | `/monitoring/store-stock-opnames/{sessionItemId}/rfid-detail` | `monitoring.store-stock-opnames.rfid-detail` | `StoreStockOpnameReportController@rfidDetail` | auth | - |
| GET | `/monitoring/supplier-insight` | `monitoring.supplier-insight.index` | `SupplierInsightController@index` | auth | - |
| GET | `/monitoring/tracking-barang` | `monitoring.tracking-barang.index` | `TrackingBarangController@index` | auth | view monitoring-tracking-barang |
| GET | `/monitoring/tracking-barang/export` | `monitoring.tracking-barang.export` | `TrackingBarangController@export` | auth | view monitoring-tracking-barang |
| GET | `/monitoring/tracking-barang/{product}` | `monitoring.tracking-barang.detail` | `TrackingBarangController@detail` | auth | view monitoring-tracking-barang |
| GET | `/monitoring/tracking-barang/{product}/qr/{item}` | `monitoring.tracking-barang.qr-download` | `TrackingBarangController@downloadQr` | auth | view monitoring-tracking-barang |
| GET | `/monitoring/user-monitoring` | `monitoring.user-monitoring.index` | `UserMonitoringController@index` | auth | - |
| GET | `/monitoring/user-monitoring/export` | `monitoring.user-monitoring.export` | `UserMonitoringController@export` | auth | - |
| GET | `/monitoring/void-cancel-customer` | `monitoring.void-cancel-customer.index` | `VoidCancelCustomerReportController@index` | auth | - |
| GET | `/monitoring/void-cancel-customer/export` | `monitoring.void-cancel-customer.export` | `VoidCancelCustomerReportController@export` | auth | - |
| GET | `/monitoring/voucher-reject-report` | `monitoring.voucher-reject-report.index` | `VoucherRejectReportController@index` | auth | - |
| GET | `/monitoring/voucher-reject-report/export` | `monitoring.voucher-reject-report.export` | `VoucherRejectReportController@export` | auth | - |
| GET | `/monitoring/warehouse-inbound` | `monitoring.warehouse-inbound` | `WarehouseInboundController@index` | auth | - |
| GET | `/monitoring/warehouse-inbound/export` | `monitoring.warehouse-inbound.export` | `WarehouseInboundController@export` | auth | - |
| GET | `/monitoring/warehouse-outbound` | `monitoring.warehouse-outbound.index` | `WarehouseOutboundController@index` | auth | - |
| GET | `/monitoring/warehouse-outbound/export` | `monitoring.warehouse-outbound.export` | `WarehouseOutboundController@export` | auth | - |

### Reports

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/reports/crm` | `reports.crm.index` | `CrmController@index` | auth | - |
| GET | `/reports/crm/customer-profiles` | `reports.crm.customer-profiles` | `CrmController@customerProfiles` | auth | - |
| GET | `/reports/crm/customers/{customerId}` | `reports.crm.customer-profile` | `CrmController@customerProfile` | auth | - |
| GET | `/reports/crm/customers/{customerId}/export` | `reports.crm.customer-profile.export` | `CrmController@exportCustomerProfile` | auth | - |
| GET | `/reports/crm/demographics` | `reports.crm.demographics` | `CrmController@demographics` | auth | - |
| GET | `/reports/crm/demographics/export` | `reports.crm.demographics.export` | `CrmController@exportDemographics` | auth | - |
| GET | `/reports/crm/demographics/regions/districts` | `reports.crm.demographics.districts` | `CrmController@demographicDistricts` | auth | - |
| GET | `/reports/crm/demographics/regions/regencies` | `reports.crm.demographics.regencies` | `CrmController@demographicRegencies` | auth | - |
| GET | `/reports/crm/demographics/regions/villages` | `reports.crm.demographics.villages` | `CrmController@demographicVillages` | auth | - |
| GET | `/reports/crm/export` | `reports.crm.export` | `CrmController@export` | auth | - |
| GET | `/reports/history-customer` | `reports.history-customer.index` | `HistoryCustomerController@index` | auth | - |
| GET | `/reports/history-customer/ecom/{customerId}` | `reports.history-customer.ecom.detail` | `HistoryCustomerController@ecomDetail` | auth | - |
| GET | `/reports/order-cabang` | `reports.order-cabang.index` | `OrderCabangController@index` | auth | - |
| POST | `/reports/order-cabang` | `reports.order-cabang.store` | `OrderCabangController@store` | auth | - |
| GET | `/reports/order-cabang/stock` | `reports.order-cabang.stock` | `OrderCabangController@stockByWarehouse` | auth | - |
| DELETE | `/reports/order-cabang/{order}` | `reports.order-cabang.destroy` | `OrderCabangController@destroy` | auth | - |
| GET | `/reports/order-cabang/{order}` | `reports.order-cabang.show` | `OrderCabangController@show` | auth | - |
| PUT | `/reports/order-cabang/{order}` | `reports.order-cabang.update` | `OrderCabangController@update` | auth | - |
| PATCH | `/reports/order-cabang/{order}/approve` | `reports.order-cabang.approve` | `OrderCabangController@approve` | auth | - |
| PATCH | `/reports/order-cabang/{order}/receive` | `reports.order-cabang.receive` | `OrderCabangController@receive` | auth | - |
| PATCH | `/reports/order-cabang/{order}/reject` | `reports.order-cabang.reject` | `OrderCabangController@reject` | auth | - |
| PATCH | `/reports/order-cabang/{order}/request` | `reports.order-cabang.request` | `OrderCabangController@requestSubmit` | auth | - |
| GET | `/reports/retur-cabang` | `reports.retur-cabang.index` | `StoreReturnApprovalController@index` | auth | - |
| GET | `/reports/retur-cabang/{return}` | `reports.retur-cabang.show` | `StoreReturnApprovalController@show` | auth | - |
| PATCH | `/reports/retur-cabang/{return}/approve` | `reports.retur-cabang.approve` | `StoreReturnApprovalController@approve` | auth | - |
| PATCH | `/reports/retur-cabang/{return}/reject` | `reports.retur-cabang.reject` | `StoreReturnApprovalController@reject` | auth | - |
| GET | `/reports/supplier-products` | `reports.supplier-products.index` | `SupplierProductController@index` | auth | view supplier-product |
| GET | `/reports/supplier-products/export` | `reports.supplier-products.export` | `SupplierProductController@export` | auth | view supplier-product |
| GET | `/reports/voucher` | `reports.voucher.index` | `VoucherReportController@index` | auth | - |
| GET | `/reports/voucher-export` | `reports.voucher.export` | `VoucherReportController@export` | auth | - |
| GET | `/reports/voucher/{id}/detail` | `reports.voucher.detail` | `VoucherReportController@detail` | auth | - |

### Management

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/management/request-labels` | `management.request-labels.index` | `LabelPrintRequestApprovalController@index` | auth | - |
| PUT | `/management/request-labels/{id}/approve` | `management.request-labels.approve` | `LabelPrintRequestApprovalController@approve` | auth | - |
| PUT | `/management/request-labels/{id}/reject` | `management.request-labels.reject` | `LabelPrintRequestApprovalController@reject` | auth | - |
| GET | `/management/role-permissions` | `management.role-permissions.index` | `RolePermissionController@index` | auth | - |
| PUT | `/management/role-permissions` | `management.role-permissions.update` | `RolePermissionController@update` | auth | - |
| GET | `/management/roles-backoffice` | `management.roles-backoffice.index` | `BackofficeRoleController@index` | auth | - |
| POST | `/management/roles-backoffice` | `management.roles-backoffice.store` | `BackofficeRoleController@store` | auth | - |
| GET | `/management/roles-backoffice/create` | `management.roles-backoffice.create` | `BackofficeRoleController@create` | auth | - |
| DELETE | `/management/roles-backoffice/{roles_backoffice}` | `management.roles-backoffice.destroy` | `BackofficeRoleController@destroy` | auth | - |
| PUT|PATCH | `/management/roles-backoffice/{roles_backoffice}` | `management.roles-backoffice.update` | `BackofficeRoleController@update` | auth | - |
| GET | `/management/roles-backoffice/{roles_backoffice}/edit` | `management.roles-backoffice.edit` | `BackofficeRoleController@edit` | auth | - |
| GET | `/management/user-roles` | `management.user-roles.index` | `UserRoleController@index` | auth | - |
| POST | `/management/user-roles/{user}/sync` | `management.user-roles.sync` | `UserRoleController@sync` | auth | - |

### Settings

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/settings/app/affiliate-points/invitee` | `settings.app.affiliate-points.invitee.edit` | `AffiliatePointsInviteeController@edit` | auth | - |
| POST | `/settings/app/affiliate-points/invitee` | `settings.app.affiliate-points.invitee.update` | `AffiliatePointsInviteeController@update` | auth | - |
| GET | `/settings/app/affiliate-points/inviter` | `settings.app.affiliate-points.inviter.edit` | `AffiliatePointsInviterController@edit` | auth | - |
| POST | `/settings/app/affiliate-points/inviter` | `settings.app.affiliate-points.inviter.update` | `AffiliatePointsInviterController@update` | auth | - |
| GET | `/settings/app/customer-loyalty-point-transaction-reward` | `settings.app.customer-loyalty-point-transaction-reward.edit` | `CustomerLoyaltyPointTransactionRewardController@edit` | auth | - |
| POST | `/settings/app/customer-loyalty-point-transaction-reward` | `settings.app.customer-loyalty-point-transaction-reward.update` | `CustomerLoyaltyPointTransactionRewardController@update` | auth | - |
| GET | `/settings/app/points` | `settings.points.edit` | `PointSettingController@edit` | auth | - |
| POST | `/settings/app/points` | `settings.points.update` | `PointSettingController@update` | auth | - |

### Auth

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/auth/login-basic` | `auth-login-basic` | `AuthController@showLogin` | guest | - |
| GET | `/auth/register-basic` | `auth-register-basic` | `AuthController@showRegister` | guest | - |
| GET | `/login` | `login` | `AuthController@showLogin` | guest | - |
| POST | `/login` | `login.attempt` | `AuthController@login` | guest | - |
| POST | `/logout` | `logout` | `AuthController@logout` | public | - |
| POST | `/register` | `register.attempt` | `AuthController@register` | guest | - |

### Branch Operations

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/branch-operations/request-label` | `branch-operations.request-label.index` | `StoreLabelPrintRequestController@index` | auth | - |
| GET | `/branch-operations/request-voucher` | `branch-operations.request-voucher.index` | `StoreVoucherRequestController@index` | auth | - |
| PUT | `/branch-operations/request-voucher/{id}/approve` | `branch-operations.request-voucher.approve` | `StoreVoucherRequestController@approve` | auth | - |
| PUT | `/branch-operations/request-voucher/{id}/reject` | `branch-operations.request-voucher.reject` | `StoreVoucherRequestController@reject` | auth | - |

### Ecommerce

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/ecommerce/orders` | `ecommerce.orders.index` | `EcommerceOrderController@index` | auth | - |
| GET | `/ecommerce/orders/{id}` | `ecommerce.orders.show` | `EcommerceOrderController@show` | auth | - |
| PUT | `/ecommerce/orders/{id}` | `ecommerce.orders.update` | `EcommerceOrderController@update` | auth | - |
| GET | `/ecommerce/orders/{id}/stock` | `ecommerce.orders.stock` | `EcommerceOrderController@stock` | auth | - |

### Notifications

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| POST | `/notifications/mark-read` | `notifications.mark-read` | `NotificationStatusController@markRead` | auth | - |
| GET | `/notifications/open/{key}` | `notifications.open` | `NotificationStatusController@open` | auth | - |
| GET | `/notifications/store-return-status` | `notifications.store-return-status` | `NotificationStatusController@storeReturnStatus` | auth | - |
| GET | `/notifications/summary` | `notifications.summary` | `NotificationStatusController@summary` | auth | - |

### AI Analytics

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/ai-analytics` | `ai-analytics.index` | `AIAnalyticsController@index` | auth | - |
| PATCH | `/ai-analytics/recommendations/{recommendation}/review` | `ai-analytics.recommendations.review` | `AIAnalyticsController@review` | auth | - |
| POST | `/ai-analytics/run` | `ai-analytics.run` | `AIAnalyticsController@run` | auth | - |

### AI Assistant

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/ai-assistant` | `ai-assistant.index` | `AIAssistantController@index` | auth | view ai-analytics |
| POST | `/ai-assistant/ask` | `ai-assistant.ask` | `AIAssistantController@ask` | auth | view ai-analytics |

### Loyalty

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/loyalty/customer-loyalty-point-mutations` | `loyalty.customer-loyalty-point-mutations.index` | `CustomerLoyaltyPointMutationController@index` | auth | view loyalty-customer-loyalty-point-mutations |
| GET | `/loyalty/customer-loyalty-points` | `loyalty.customer-loyalty-points.index` | `CustomerLoyaltyPointController@index` | auth | view loyalty-customer-loyalty-points |
| GET | `/loyalty/customer-visits` | `loyalty.customer-visits.index` | `CustomerVisitController@index` | auth | view loyalty-customer-visits |

### Report Export

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/report-exports` | `report-exports.index` | `ReportExportController@index` | auth | - |
| GET | `/report-exports/{id}` | `report-exports.show` | `ReportExportController@show` | auth | - |
| GET | `/report-exports/{id}/download` | `report-exports.download` | `ReportExportController@download` | auth | - |

### Dashboard

| Method | URI | Name | Controller | Auth | Permission |
|---|---|---|---|---|---|
| GET | `/dashboard` | `dashboard-analytics` | `Analytics@index` | auth | - |
