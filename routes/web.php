<?php

use App\Http\Controllers\AI\AssistantController;
// Controllers - Auth & Dashboard
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\BranchOperations\StoreLabelPrintRequestController;
use App\Http\Controllers\BranchOperations\StoreVoucherRequestController;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\Ecommerce\EcommerceOrderController;
use App\Http\Controllers\Ecommerce\EcommercePaymentOperationsController;
use App\Http\Controllers\Loyalty\CustomerLoyaltyPointController;
use App\Http\Controllers\Loyalty\CustomerLoyaltyPointMutationController;
// Master Data
use App\Http\Controllers\Management\BackofficeRoleController;
use App\Http\Controllers\Management\LabelPrintRequestApprovalController;
use App\Http\Controllers\Management\RolePermissionController;
use App\Http\Controllers\Management\UserRoleController;
use App\Http\Controllers\MasterData\BrandController;
use App\Http\Controllers\MasterData\CourierController;
use App\Http\Controllers\MasterData\CustomerController;
use App\Http\Controllers\MasterData\DiscountController;
use App\Http\Controllers\MasterData\EcommerceBannerController;
use App\Http\Controllers\MasterData\ItemConditionController;
use App\Http\Controllers\MasterData\LocationstoreController;
use App\Http\Controllers\MasterData\LocationwarehouseController;
use App\Http\Controllers\MasterData\MembershipBenefitController;
use App\Http\Controllers\MasterData\MembershipController;
use App\Http\Controllers\MasterData\PartnerController;
use App\Http\Controllers\MasterData\PaymentMethodController;
use App\Http\Controllers\MasterData\ProductCategoryController;
use App\Http\Controllers\MasterData\ProductCategoryGroupController;
use App\Http\Controllers\MasterData\ProductController;
use App\Http\Controllers\MasterData\ProductDiscountController;
use App\Http\Controllers\MasterData\ProductPriceController;
use App\Http\Controllers\MasterData\ProductSpecificationController;
use App\Http\Controllers\MasterData\PromoController;
use App\Http\Controllers\MasterData\PromoSetupController;
use App\Http\Controllers\MasterData\RfidTagController;
use App\Http\Controllers\MasterData\StoreController;
use App\Http\Controllers\MasterData\SupplierController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\MasterData\UserManageController;
use App\Http\Controllers\MasterData\VoucherController;
use App\Http\Controllers\MasterData\WarehouseController;
// Monitoring
use App\Http\Controllers\Monitoring\AnomalyItemMonitoringController;
use App\Http\Controllers\Monitoring\AuditWmsOpnameController;
use App\Http\Controllers\Monitoring\BarcodeRequestReportController;
use App\Http\Controllers\Monitoring\CashierShiftReportController;
use App\Http\Controllers\Monitoring\CustomerLoyaltyPointsReportController;
use App\Http\Controllers\Monitoring\CustomerMembershipMonitoringController;
use App\Http\Controllers\Monitoring\EcommerceOrderFulfillmentController;
use App\Http\Controllers\Monitoring\EcommercePaymentController;
use App\Http\Controllers\Monitoring\EmployeeShiftAttendanceController;
use App\Http\Controllers\Monitoring\ExpiringStockMonitoringController;
use App\Http\Controllers\Monitoring\MonitoringCommandCenterController;
use App\Http\Controllers\Monitoring\MonitoringDataCustomerController;
use App\Http\Controllers\Monitoring\MonitoringDiskonController;
use App\Http\Controllers\Monitoring\MonitoringVoucherRequestController;
use App\Http\Controllers\Monitoring\MutasiStockWmsController;
use App\Http\Controllers\Monitoring\OmsetCabangController;
use App\Http\Controllers\Monitoring\OmsetKotorSalesController;
use App\Http\Controllers\Monitoring\OrderHandledController;
use App\Http\Controllers\Monitoring\PaymentIncomeRealtimeController;
use App\Http\Controllers\Monitoring\PhysicalStockAccumulationController;
use App\Http\Controllers\Monitoring\ProductAgingMonitoringController;
use App\Http\Controllers\Monitoring\ProductSlowmovingController;
use App\Http\Controllers\Monitoring\ProdukTerlarisController;
use App\Http\Controllers\Monitoring\PromoUsageController;
use App\Http\Controllers\Monitoring\RedeemPointReportController;
use App\Http\Controllers\Monitoring\ReferralScoreReportController;
use App\Http\Controllers\Monitoring\SlowDeadstockController;
use App\Http\Controllers\Monitoring\StokPercabangController;
use App\Http\Controllers\Monitoring\StokWmsController;
use App\Http\Controllers\Monitoring\StoreInboundController;
use App\Http\Controllers\Monitoring\StoreOrderRequestController;
use App\Http\Controllers\Monitoring\StoreOutboundController;
use App\Http\Controllers\Monitoring\StorePaymentTransactionController;
use App\Http\Controllers\Monitoring\StoreReturnRequestController;
use App\Http\Controllers\Monitoring\StoreSlowstockController;
use App\Http\Controllers\Monitoring\StoreStockBarrierController;
use App\Http\Controllers\Monitoring\StoreStockOpnameReportController;
use App\Http\Controllers\Monitoring\SupplierInsightController;
use App\Http\Controllers\Monitoring\TrackingBarangController;
use App\Http\Controllers\Monitoring\UserMonitoringController;
use App\Http\Controllers\Monitoring\VoidCancelCustomerReportController;
use App\Http\Controllers\Monitoring\VoucherRejectReportController;
use App\Http\Controllers\Monitoring\WarehouseInboundController;
use App\Http\Controllers\Monitoring\WarehouseOutboundController;
use App\Http\Controllers\Monitoring\WarehouseStockBarrierController;
// Reports
use App\Http\Controllers\NotificationStatusController;
use App\Http\Controllers\Operations\CustomerDemographicsOperationsController;
use App\Http\Controllers\Operations\CustomerVisitOperationsController;
use App\Http\Controllers\Operations\InboundController;
use App\Http\Controllers\Operations\OperationalStockController;
use App\Http\Controllers\Operations\OutboundController;
use App\Http\Controllers\Operations\StoreInboundOperationsController;
use App\Http\Controllers\Operations\StoreOrderApprovalController;
use App\Http\Controllers\Operations\StoreOutboundOperationsController;
use App\Http\Controllers\Operations\StoreReturnApprovalController as OperationsStoreReturnApprovalController;
use App\Http\Controllers\Operations\StoreStockOperationsController;
use App\Http\Controllers\OperationsHubController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\Reports\BusinessOverviewController;
use App\Http\Controllers\Reports\CrmController;
use App\Http\Controllers\Reports\CustomerPreferenceController;
use App\Http\Controllers\Reports\HistoryCustomerController;
use App\Http\Controllers\Reports\OrderCabangController;
// Settings
use App\Http\Controllers\Reports\StoreReturnApprovalController;
use App\Http\Controllers\Reports\SupplierProductController;
use App\Http\Controllers\Reports\VoucherReportController;
use App\Http\Controllers\ReportsHubController;
// Management
use App\Http\Controllers\Settings\AffiliatePointsInviteeController;
use App\Http\Controllers\Settings\AffiliatePointsInviterController;
use App\Http\Controllers\Settings\CustomerLoyaltyPointTransactionRewardController;
use App\Http\Controllers\Settings\PointSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('operations.index')
        : redirect()->route('login');
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/auth/login-basic', [AuthController::class, 'showLogin'])->name('auth-login-basic');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/auth/register-basic', [AuthController::class, 'showRegister'])->name('auth-register-basic');

    Route::get('/auth/forgot-password-basic', [AuthController::class, 'showForgotPassword'])
        ->name('auth-forgot-password-basic');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/language', [LocaleController::class, 'update'])->name('language.update');

/*
|--------------------------------------------------------------------------
| APP
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('report-exports', [ReportExportController::class, 'index'])->name('report-exports.index');
    Route::get('report-exports/{id}', [ReportExportController::class, 'show'])->whereUuid('id')->name('report-exports.show');
    Route::get('report-exports/{id}/download', [ReportExportController::class, 'download'])->whereUuid('id')->name('report-exports.download');
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/operations', OperationsHubController::class)->name('operations.index');
    Route::get('/operations/stock', OperationalStockController::class)->name('operations.stock.index');
    Route::get('/operations/inbound', InboundController::class)->name('operations.inbound.index');
    Route::get('/operations/outbound', OutboundController::class)->name('operations.outbound.index');
    Route::get('/operations/customers', CustomerVisitOperationsController::class)->name('operations.customers.index');
    Route::get('/operations/customers/demographics', CustomerDemographicsOperationsController::class)
        ->name('operations.customers.demographics');
    Route::get('/operations/customers/demographics/export', [CustomerDemographicsOperationsController::class, 'export'])
        ->name('operations.customers.demographics.export');
    Route::post('/operations/customers/demographics/birthday-greeting', [CustomerDemographicsOperationsController::class, 'sendBirthdayGreeting'])
        ->name('operations.customers.demographics.birthday-greeting');
    Route::get('/operations/store-stock', StoreStockOperationsController::class)->name('operations.store-stock.index');
    Route::get('/operations/store-inbound', StoreInboundOperationsController::class)->name('operations.store-inbound.index');
    Route::get('/operations/store-outbound', StoreOutboundOperationsController::class)->name('operations.store-outbound.index');
    Route::prefix('operations/store-orders')->name('operations.store-orders.')->group(function () {
        Route::get('/', [StoreOrderApprovalController::class, 'index'])->name('index');
        Route::get('{order}', [StoreOrderApprovalController::class, 'show'])
            ->whereUuid('order')
            ->name('show');
        Route::patch('{order}/approve', [StoreOrderApprovalController::class, 'approve'])
            ->whereUuid('order')
            ->name('approve');
        Route::patch('{order}/reject', [StoreOrderApprovalController::class, 'reject'])
            ->whereUuid('order')
            ->name('reject');
    });
    Route::prefix('operations/store-returns')->name('operations.store-returns.')->group(function () {
        Route::get('/', [OperationsStoreReturnApprovalController::class, 'index'])->name('index');
        Route::get('{return}', [OperationsStoreReturnApprovalController::class, 'show'])
            ->whereUuid('return')
            ->name('show');
        Route::patch('{return}/approve', [OperationsStoreReturnApprovalController::class, 'approve'])
            ->whereUuid('return')
            ->name('approve');
        Route::patch('{return}/reject', [OperationsStoreReturnApprovalController::class, 'reject'])
            ->whereUuid('return')
            ->name('reject');
    });
    Route::get('/reports', ReportsHubController::class)->name('reports.index');

    Route::get('/dashboard', [Analytics::class, 'index'])->name('dashboard-analytics');

    Route::prefix('ai-assistant')->name('ai-assistant.')->middleware('permission:view ai-analytics|view ai-assistant')->group(function () {
        Route::get('/', [AssistantController::class, 'index'])->name('index');
        Route::get('/state', [AssistantController::class, 'state'])->name('state');
        Route::post('/chat', [AssistantController::class, 'chat'])->name('chat')->middleware('throttle:ai-assistant');
        Route::post('/reset', [AssistantController::class, 'reset'])->name('reset');
    });

    /*
    |--------------------------------------------------------------------------
    | REALTIME NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/summary', [NotificationStatusController::class, 'summary'])
            ->name('summary');
        Route::get('/store-return-status', [NotificationStatusController::class, 'storeReturnStatus'])
            ->name('store-return-status');
        Route::post('/mark-read', [NotificationStatusController::class, 'markRead'])
            ->name('mark-read');
        Route::get('/open/{key}', [NotificationStatusController::class, 'open'])
            ->name('open');
    });

    /*
    |--------------------------------------------------------------------------
    | SETTINGS
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('app/points', [PointSettingController::class, 'edit'])->name('points.edit');
        Route::post('app/points', [PointSettingController::class, 'update'])->name('points.update');

        Route::get(
            'app/customer-loyalty-point-transaction-reward',
            [CustomerLoyaltyPointTransactionRewardController::class, 'edit']
        )->name('app.customer-loyalty-point-transaction-reward.edit');

        Route::post(
            'app/customer-loyalty-point-transaction-reward',
            [CustomerLoyaltyPointTransactionRewardController::class, 'update']
        )->name('app.customer-loyalty-point-transaction-reward.update');

        Route::get(
            'app/affiliate-points/inviter',
            [AffiliatePointsInviterController::class, 'edit']
        )->name('app.affiliate-points.inviter.edit');

        Route::post(
            'app/affiliate-points/inviter',
            [AffiliatePointsInviterController::class, 'update']
        )->name('app.affiliate-points.inviter.update');

        Route::get(
            'app/affiliate-points/invitee',
            [AffiliatePointsInviteeController::class, 'edit']
        )->name('app.affiliate-points.invitee.edit');

        Route::post(
            'app/affiliate-points/invitee',
            [AffiliatePointsInviteeController::class, 'update']
        )->name('app.affiliate-points.invitee.update');
    });

    /*
    |--------------------------------------------------------------------------
    | ECOMMERCE
    |--------------------------------------------------------------------------
    */
    Route::prefix('ecommerce')->name('ecommerce.')->group(function () {
        Route::get('/orders', [EcommerceOrderController::class, 'index'])->name('orders.index');
        Route::put('/orders/{id}', [EcommerceOrderController::class, 'update'])->name('orders.update');
        Route::get('/orders/{id}', [EcommerceOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{id}/stock', [EcommerceOrderController::class, 'stock'])->name('orders.stock');
        Route::get('/payments', EcommercePaymentOperationsController::class)->name('payments.index');
    });

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */
    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');

        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');

        Route::prefix('locations-warehouse')->name('locations-warehouse.')->group(function () {
            Route::get('/', [LocationwarehouseController::class, 'index'])->name('index');
            Route::get('/tree', [LocationwarehouseController::class, 'treeData'])->name('tree');
            Route::post('/', [LocationwarehouseController::class, 'store'])->name('store');
            Route::post('/generate', [LocationwarehouseController::class, 'generate'])->name('generate');
            Route::post('/quick-add', [LocationwarehouseController::class, 'quickAdd'])->name('quick-add');
            Route::put('/warehouse/{warehouse}/name', [LocationwarehouseController::class, 'updateWarehouseName'])->name('update-name');
            Route::put('/{location}', [LocationwarehouseController::class, 'update'])->name('update');
            Route::delete('/{location}', [LocationwarehouseController::class, 'destroy'])->name('destroy');
            Route::get('/{location}/qr', [LocationwarehouseController::class, 'qr'])->name('qr');
        });

        Route::prefix('locations-store')->name('locations-store.')->group(function () {
            Route::get('/', [LocationstoreController::class, 'index'])->name('index');
            Route::get('/tree', [LocationstoreController::class, 'treeData'])->name('tree');
            Route::post('/', [LocationstoreController::class, 'store'])->name('store');
            Route::post('/generate', [LocationstoreController::class, 'generate'])->name('generate');
            Route::post('/quick-add', [LocationstoreController::class, 'quickAdd'])->name('quick-add');
            Route::put('/store/{store}/name', [LocationstoreController::class, 'updateStoreName'])->name('update-name');
            Route::put('/{location}', [LocationstoreController::class, 'update'])->name('update');
            Route::delete('/{location}', [LocationstoreController::class, 'destroy'])->name('destroy');
            Route::get('/{location}/qr', [LocationstoreController::class, 'qr'])->name('qr');
        });

        Route::get('units', [UnitController::class, 'index'])->name('units.index');
        Route::post('units', [UnitController::class, 'store'])->name('units.store');
        Route::put('units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])->name('units.destroy');

        Route::get('product-categories', [ProductCategoryController::class, 'index'])->name('product-categories.index');
        Route::post('product-categories', [ProductCategoryController::class, 'store'])->name('product-categories.store');
        Route::put('product-categories/{product_category}', [ProductCategoryController::class, 'update'])->name('product-categories.update');
        Route::delete('product-categories/{product_category}', [ProductCategoryController::class, 'destroy'])->name('product-categories.destroy');

        Route::get('product-category-groups', [ProductCategoryGroupController::class, 'index'])->name('product-category-groups.index');
        Route::post('product-category-groups', [ProductCategoryGroupController::class, 'store'])->name('product-category-groups.store');
        Route::put('product-category-groups/{product_category_group}', [ProductCategoryGroupController::class, 'update'])->name('product-category-groups.update');
        Route::delete('product-category-groups/{product_category_group}', [ProductCategoryGroupController::class, 'destroy'])->name('product-category-groups.destroy');

        Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
        Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
        Route::put('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');

        Route::get('couriers', [CourierController::class, 'index'])->name('couriers.index');
        Route::post('couriers', [CourierController::class, 'store'])->name('couriers.store');
        Route::put('couriers/{courier}', [CourierController::class, 'update'])->name('couriers.update');
        Route::delete('couriers/{courier}', [CourierController::class, 'destroy'])->name('couriers.destroy');

        Route::get('rfid-tags', [RfidTagController::class, 'index'])->name('rfid-tags.index');
        Route::post('rfid-tags', [RfidTagController::class, 'store'])->name('rfid-tags.store');
        Route::put('rfid-tags/{rfid_tag}', [RfidTagController::class, 'update'])->name('rfid-tags.update');
        Route::delete('rfid-tags/{rfid_tag}', [RfidTagController::class, 'destroy'])->name('rfid-tags.destroy');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/regions/regencies', [CustomerController::class, 'regencies'])->name('customers.regencies');
        Route::get('customers/regions/districts', [CustomerController::class, 'districts'])->name('customers.districts');
        Route::get('customers/regions/villages', [CustomerController::class, 'villages'])->name('customers.villages');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
        Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

        Route::resource('product-specifications', ProductSpecificationController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['product-specifications' => 'productSpecification']);

        Route::get('product-prices', [ProductPriceController::class, 'index'])->name('product-prices.index');
        Route::post('product-prices', [ProductPriceController::class, 'store'])->name('product-prices.store');
        Route::put('product-prices/{product_price}', [ProductPriceController::class, 'update'])->name('product-prices.update');
        Route::delete('product-prices/{product_price}', [ProductPriceController::class, 'destroy'])->name('product-prices.destroy');
        Route::post('product-prices/check-duplicate', [ProductPriceController::class, 'checkDuplicate'])->name('product-prices.check-duplicate');
        Route::get('api/product-prices/{id}', [ProductPriceController::class, 'getUsedTypes']);

        Route::get('products-diskons', [ProductDiscountController::class, 'index'])->name('products-diskons.index');
        Route::post('products-diskons', [ProductDiscountController::class, 'store'])->name('products-diskons.store');
        Route::put('products-diskons', [ProductDiscountController::class, 'update'])->name('products-diskons.update');
        Route::delete('products-diskons', [ProductDiscountController::class, 'destroy'])->name('products-diskons.destroy');

        Route::get('promo-setup', [PromoSetupController::class, 'index'])->name('promo-setup.index');
        Route::post('promo-setup', [PromoSetupController::class, 'store'])->name('promo-setup.store');

        Route::get('promos', [PromoController::class, 'index'])->name('promos.index');
        Route::post('promos', [PromoController::class, 'store'])->name('promos.store');
        Route::put('promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
        Route::delete('promos/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');

        Route::get('memberships', [MembershipController::class, 'index'])->name('memberships.index');
        Route::post('memberships', [MembershipController::class, 'store'])->name('memberships.store');
        Route::put('memberships/{membership}', [MembershipController::class, 'update'])->name('memberships.update');
        Route::delete('memberships/{membership}', [MembershipController::class, 'destroy'])->name('memberships.destroy');

        Route::get('membership-benefits', [MembershipBenefitController::class, 'index'])->name('membership-benefits.index');
        Route::post('membership-benefits', [MembershipBenefitController::class, 'store'])->name('membership-benefits.store');
        Route::put('membership-benefits/{membership_benefit}', [MembershipBenefitController::class, 'update'])->name('membership-benefits.update');
        Route::delete('membership-benefits/{membership_benefit}', [MembershipBenefitController::class, 'destroy'])->name('membership-benefits.destroy');

        Route::get('item-conditions', [ItemConditionController::class, 'index'])->name('item-conditions.index');
        Route::post('item-conditions', [ItemConditionController::class, 'store'])->name('item-conditions.store');
        Route::put('item-conditions/{item_condition}', [ItemConditionController::class, 'update'])->name('item-conditions.update');
        Route::delete('item-conditions/{item_condition}', [ItemConditionController::class, 'destroy'])->name('item-conditions.destroy');

        Route::get('payment-methods', [PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('payment-methods', [PaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::put('payment-methods/{payment_method}', [PaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::delete('payment-methods/{payment_method}', [PaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
        Route::post('vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('vouchers/{key}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::delete('vouchers/{key}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::post('vouchers/{key}/print-codes', [VoucherController::class, 'printUniqueCodes'])->name('vouchers.print-codes');
        Route::get('vouchers/{key}/codes', [VoucherController::class, 'voucherCodes'])->name('vouchers.codes');
        Route::patch('vouchers/{key}/codes/{voucherCodeId}/status', [VoucherController::class, 'updateVoucherCodeStatus'])->name('vouchers.codes.status');

        Route::get('diskons', [DiscountController::class, 'index'])->name('diskons.index');
        Route::post('diskons', [DiscountController::class, 'store'])->name('diskons.store');
        Route::put('diskons/{discount}', [DiscountController::class, 'update'])->whereUuid('discount')->name('diskons.update');
        Route::delete('diskons/{discount}', [DiscountController::class, 'destroy'])->whereUuid('discount')->name('diskons.destroy');

        Route::permanentRedirect('discounts', 'diskons');

        Route::get('ecommerce-banners', [EcommerceBannerController::class, 'index'])->name('ecommerce-banners.index');
        Route::post('ecommerce-banners', [EcommerceBannerController::class, 'store'])->name('ecommerce-banners.store');
        Route::put('ecommerce-banners/{ecommerce_banner}', [EcommerceBannerController::class, 'update'])->whereUuid('ecommerce_banner')->name('ecommerce-banners.update');
        Route::delete('ecommerce-banners/{ecommerce_banner}', [EcommerceBannerController::class, 'destroy'])->whereUuid('ecommerce_banner')->name('ecommerce-banners.destroy');

        Route::get('users', [UserManageController::class, 'index'])->name('users.index');
        Route::post('users', [UserManageController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserManageController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManageController::class, 'destroy'])->name('users.destroy');
        Route::patch('users/{user}/toggle-active', [UserManageController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('partners', [PartnerController::class, 'store'])->name('partners.store');
        Route::put('partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
        Route::delete('partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | LOYALTY
    |--------------------------------------------------------------------------
    */
    Route::prefix('loyalty')->name('loyalty.')->group(function () {
        Route::get('customer-loyalty-points', [CustomerLoyaltyPointController::class, 'index'])->name('customer-loyalty-points.index');
        Route::get('customer-loyalty-point-mutations', [CustomerLoyaltyPointMutationController::class, 'index'])->name('customer-loyalty-point-mutations.index');
        Route::get('customer-visits', fn () => redirect()->route('operations.customers.index'))->name('customer-visits.index');
    });

    /*
    |--------------------------------------------------------------------------
    | BRANCH OPERATIONS
    |--------------------------------------------------------------------------
    */
    Route::prefix('branch-operations')->name('branch-operations.')->group(function () {
        Route::get('request-label', [StoreLabelPrintRequestController::class, 'index'])
            ->name('request-label.index');

        Route::redirect('request-print-barcode', '/branch-operations/request-label', 301)
            ->name('request-print-barcode.index');

        Route::get('request-voucher', [StoreVoucherRequestController::class, 'index'])
            ->name('request-voucher.index');

        Route::put('request-voucher/{id}/approve', [StoreVoucherRequestController::class, 'approve'])
            ->name('request-voucher.approve');

        Route::put('request-voucher/{id}/reject', [StoreVoucherRequestController::class, 'reject'])
            ->name('request-voucher.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('management')->name('management.')->group(function () {
        Route::resource('roles-backoffice', BackofficeRoleController::class)->except(['show']);

        Route::get('user-roles', [UserRoleController::class, 'index'])->name('user-roles.index');
        Route::post('user-roles/{user}/sync', [UserRoleController::class, 'sync'])->name('user-roles.sync');

        Route::get('role-permissions', [RolePermissionController::class, 'index'])
            ->name('role-permissions.index');

        Route::put('role-permissions', [RolePermissionController::class, 'update'])
            ->name('role-permissions.update');

        Route::get('request-labels', [LabelPrintRequestApprovalController::class, 'index'])
            ->name('request-labels.index');
        Route::put('request-labels/{id}/approve', [LabelPrintRequestApprovalController::class, 'approve'])
            ->name('request-labels.approve');
        Route::put('request-labels/{id}/reject', [LabelPrintRequestApprovalController::class, 'reject'])
            ->name('request-labels.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | MONITORING
    |--------------------------------------------------------------------------
    */
    Route::prefix('monitoring')->name('monitoring.')->group(function () {
        Route::get('/', [MonitoringCommandCenterController::class, 'index'])
            ->name('command-center.index');
        Route::get('warehouse-outbound/export', [WarehouseOutboundController::class, 'export'])
            ->name('warehouse-outbound.export');
        Route::get('warehouse-outbound', [WarehouseOutboundController::class, 'index'])
            ->name('warehouse-outbound.index');
        Route::get('quality-control', [MonitoringCommandCenterController::class, 'qualityControl'])
            ->name('quality-control.index');
        Route::get('payment-reconciliation', [MonitoringCommandCenterController::class, 'paymentReconciliation'])
            ->name('payment-reconciliation.index');
        Route::get('fulfillment-control', [MonitoringCommandCenterController::class, 'fulfillmentControl'])
            ->name('fulfillment-control.index');

        Route::prefix('order-handled')
            ->name('order-handled.')
            ->group(function () {
                Route::get('/', [OrderHandledController::class, 'index'])
                    ->name('index');

                Route::get('/store-orders', [OrderHandledController::class, 'storeOrders'])
                    ->name('store-orders');

                Route::get('/store-returns', [OrderHandledController::class, 'storeReturns'])
                    ->name('store-returns');
            });

        Route::get('audit-center', [MonitoringCommandCenterController::class, 'auditCenter'])
            ->name('audit-center.index');
        Route::get('data-quality', [MonitoringCommandCenterController::class, 'dataQuality'])
            ->name('data-quality.index');
        Route::get('supplier-insight', [SupplierInsightController::class, 'index'])
            ->name('supplier-insight.index');

        Route::get('stok-percabang', [StokPercabangController::class, 'index'])
            ->name('stok-percabang');

        Route::get('stok-percabang/export', [StokPercabangController::class, 'export'])
            ->name('stok-percabang.export');

        Route::get('stok-percabang/reservations', [StokPercabangController::class, 'reservationDetails'])
            ->name('stok-percabang.reservations');

        Route::get('stok-wms', [StokWmsController::class, 'index'])
            ->name('stok-wms');

        Route::get('stok-wms/export', [StokWmsController::class, 'export'])
            ->name('stok-wms.export');

        Route::get('stok-wms/reservations', [StokWmsController::class, 'reservationDetails'])
            ->name('stok-wms.reservations');

        Route::get('mutasi-stock-wms', [MutasiStockWmsController::class, 'index'])
            ->name('mutasi-stock-wms');

        Route::get('mutasi-stock-wms/export', [MutasiStockWmsController::class, 'export'])
            ->name('mutasi-stock-wms.export');

        Route::get('warehouse-inbound', [WarehouseInboundController::class, 'index'])
            ->name('warehouse-inbound');

        Route::get('warehouse-inbound/export', [WarehouseInboundController::class, 'export'])
            ->name('warehouse-inbound.export');

        Route::get('store-inbound', [StoreInboundController::class, 'index'])
            ->name('store-inbound.index');

        Route::get('store-outbound', [StoreOutboundController::class, 'index'])
            ->name('store-outbound.index');

        Route::get('stok-barier-cabang', [StoreStockBarrierController::class, 'index'])
            ->name('stok-barier-cabang.index');

        Route::post('stok-barier-cabang', [StoreStockBarrierController::class, 'store'])
            ->name('stok-barier-cabang.store');

        Route::put('stok-barier-cabang/{barrier}', [StoreStockBarrierController::class, 'update'])
            ->name('stok-barier-cabang.update');

        Route::delete('stok-barier-cabang/{barrier}', [StoreStockBarrierController::class, 'destroy'])
            ->name('stok-barier-cabang.destroy');

        Route::resource('stok-barier-gudang', WarehouseStockBarrierController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        Route::get('audit-wms-opnames', [AuditWmsOpnameController::class, 'index'])
            ->name('audit-wms-opnames.index');

        Route::get('audit-wms-opnames/export', [AuditWmsOpnameController::class, 'export'])
            ->name('audit-wms-opnames.export');

        Route::get('audit-wms-opnames/detail', [AuditWmsOpnameController::class, 'detail'])
            ->name('audit-wms-opnames.detail');

        Route::get('store-stock-opnames', [StoreStockOpnameReportController::class, 'index'])
            ->name('store-stock-opnames.index');

        Route::get('store-stock-opnames/export', [StoreStockOpnameReportController::class, 'export'])
            ->name('store-stock-opnames.export');

        Route::get('store-stock-opnames/{sessionItemId}/rfid-detail', [StoreStockOpnameReportController::class, 'rfidDetail'])
            ->name('store-stock-opnames.rfid-detail');

        Route::get('slow-deadstock', [SlowDeadstockController::class, 'index'])
            ->name('slow-deadstock.index');

        Route::get('slow-deadstock/export-slow', [SlowDeadstockController::class, 'exportSlow'])
            ->name('slow-deadstock.export-slow');

        Route::get('slow-deadstock/export-dead', [SlowDeadstockController::class, 'exportDead'])
            ->name('slow-deadstock.export-dead');

        Route::get('store-payment-transactions', [StorePaymentTransactionController::class, 'index'])
            ->name('store-payment-transactions.index');

        Route::get('store-payment-transactions/export', [StorePaymentTransactionController::class, 'export'])
            ->name('store-payment-transactions.export');

        Route::prefix('payment-income-realtime')
            ->name('payment-income-realtime.')
            ->group(function () {
                Route::get('/', [PaymentIncomeRealtimeController::class, 'index'])
                    ->name('index');

                Route::get('/export', [PaymentIncomeRealtimeController::class, 'export'])
                    ->name('export');
            });

        Route::get('ecommerce-payment', [EcommercePaymentController::class, 'index'])
            ->name('ecommerce-payment.index');

        Route::get('ecommerce-payment/export', [EcommercePaymentController::class, 'export'])
            ->name('ecommerce-payment.export');

        Route::get('ecommerce-payment/{order}/products', [EcommercePaymentController::class, 'products'])
            ->name('ecommerce-payment.products');

        Route::prefix('ecommerce-order-fulfillment')
            ->name('ecommerce-order-fulfillment.')
            ->group(function () {
                Route::get('/', [EcommerceOrderFulfillmentController::class, 'index'])
                    ->name('index');

                Route::get('/export', [EcommerceOrderFulfillmentController::class, 'export'])
                    ->name('export');
            });

        Route::get('store-order-requests', [StoreOrderRequestController::class, 'index'])
            ->name('store-order-requests.index');

        Route::get('store-order-requests/export', [StoreOrderRequestController::class, 'export'])
            ->name('store-order-requests.export');

        Route::get('store-return-requests', [StoreReturnRequestController::class, 'index'])
            ->name('store-return-requests.index');

        Route::get('store-return-requests/export', [StoreReturnRequestController::class, 'export'])
            ->name('store-return-requests.export');

        Route::get('store-slowstock', [StoreSlowstockController::class, 'index'])
            ->name('store-slowstock.index');

        Route::get('store-slowstock/export-slow', [StoreSlowstockController::class, 'exportSlow'])
            ->name('store-slowstock.export-slow');

        Route::get('store-slowstock/export-dead', [StoreSlowstockController::class, 'exportDead'])
            ->name('store-slowstock.export-dead');

        Route::get('diskon', [MonitoringDiskonController::class, 'index'])
            ->name('diskon.index');

        Route::get('diskon/export', [MonitoringDiskonController::class, 'export'])
            ->name('diskon.export');

        Route::get('anomali-item', [AnomalyItemMonitoringController::class, 'index'])
            ->name('anomali-item.index');
        Route::get('anomali-item/{item}/location-history', [AnomalyItemMonitoringController::class, 'locationHistory'])
            ->whereUuid('item')
            ->name('anomali-item.location-history');
        Route::get('anomali-item/{item}/location-history-json', [AnomalyItemMonitoringController::class, 'locationHistoryJson'])
            ->whereUuid('item')
            ->name('anomali-item.location-history-json');

        Route::prefix('data-customer')->name('data-customer.')->group(function () {
            Route::get('/', [MonitoringDataCustomerController::class, 'index'])
                ->name('index');

            Route::get('/export', [MonitoringDataCustomerController::class, 'export'])
                ->name('export');

            Route::get('/regencies', [MonitoringDataCustomerController::class, 'regencies'])
                ->name('regencies');

            Route::get('/districts', [MonitoringDataCustomerController::class, 'districts'])
                ->name('districts');

            Route::get('/villages', [MonitoringDataCustomerController::class, 'villages'])
                ->name('villages');

            Route::get('/postal-code', [MonitoringDataCustomerController::class, 'postalCode'])
                ->name('postal-code');
        });

        Route::get('promo-usage', [PromoUsageController::class, 'index'])
            ->name('promo-usage.index');

        Route::get('promo-usage/export', [PromoUsageController::class, 'export'])
            ->name('promo-usage.export');

        Route::get('user-monitoring', [UserMonitoringController::class, 'index'])
            ->name('user-monitoring.index');

        Route::get('user-monitoring/export', [UserMonitoringController::class, 'export'])
            ->name('user-monitoring.export');

        Route::get('tracking-barang', [TrackingBarangController::class, 'index'])
            ->name('tracking-barang.index');

        Route::get('tracking-barang/export', [TrackingBarangController::class, 'export'])
            ->name('tracking-barang.export');

        Route::get('tracking-barang/{product}/qr/{item}', [TrackingBarangController::class, 'downloadQr'])
            ->name('tracking-barang.qr-download');

        Route::get('tracking-barang/{product}', [TrackingBarangController::class, 'detail'])
            ->name('tracking-barang.detail');

        Route::get('product-aging', [ProductAgingMonitoringController::class, 'index'])
            ->name('product-aging.index');

        Route::get('product-aging/export', [ProductAgingMonitoringController::class, 'export'])
            ->name('product-aging.export');

        Route::get('product-aging/{productId}/detail', [ProductAgingMonitoringController::class, 'detail'])
            ->name('product-aging.detail');

        Route::get('stok-mendekati-kedaluwarsa', [ExpiringStockMonitoringController::class, 'index'])
            ->name('stok-mendekati-kedaluwarsa.index');

        Route::prefix('cashier-shift-report')->name('cashier-shift-report.')->group(function () {
            Route::get('/', [CashierShiftReportController::class, 'index'])
                ->name('index');

            Route::get('/export', [CashierShiftReportController::class, 'export'])
                ->name('export');

            Route::get('/{cashierSession}/detail', [CashierShiftReportController::class, 'detail'])
                ->name('detail');

            Route::get('/{cashierSession}/detail-export', [CashierShiftReportController::class, 'detailExport'])
                ->name('detail-export');

            Route::get('/transaction/{paymentId}/items', [CashierShiftReportController::class, 'transactionItems'])
                ->name('transaction-items');
        });

        Route::get('referral-score-report', [ReferralScoreReportController::class, 'index'])
            ->name('referral-score-report.index');

        Route::get('referral-score-report/export', [ReferralScoreReportController::class, 'export'])
            ->name('referral-score-report.export');

        Route::get('customer-loyalty-points-report', [CustomerLoyaltyPointsReportController::class, 'index'])
            ->name('customer-loyalty-points-report.index');

        Route::get('customer-loyalty-points-report/export', [CustomerLoyaltyPointsReportController::class, 'export'])
            ->name('customer-loyalty-points-report.export');

        Route::get('customer-membership', [CustomerMembershipMonitoringController::class, 'index'])
            ->name('customer-membership.index');

        Route::get('barcode-request-report', [BarcodeRequestReportController::class, 'index'])
            ->name('barcode-request-report.index');

        Route::get('barcode-request-report/export', [BarcodeRequestReportController::class, 'export'])
            ->name('barcode-request-report.export');

        Route::get('void-cancel-customer', [VoidCancelCustomerReportController::class, 'index'])
            ->name('void-cancel-customer.index');

        Route::get('void-cancel-customer/export', [VoidCancelCustomerReportController::class, 'export'])
            ->name('void-cancel-customer.export');

        Route::get('omset-kotor', [OmsetKotorSalesController::class, 'index'])
            ->name('omset-kotor.index');

        Route::get('omset-kotor/export', [OmsetKotorSalesController::class, 'export'])
            ->name('omset-kotor.export');

        Route::get('omset-kotor/{product}/detail', [OmsetKotorSalesController::class, 'detail'])
            ->name('omset-kotor.detail');

        Route::get('omset-cabang', [OmsetCabangController::class, 'index'])
            ->name('omset-cabang.index');

        Route::get('omset-cabang/export', [OmsetCabangController::class, 'export'])
            ->name('omset-cabang.export');

        Route::get('produk-terlaris', [ProdukTerlarisController::class, 'index'])
            ->name('produk-terlaris.index');

        Route::get('produk-terlaris/export', [ProdukTerlarisController::class, 'export'])
            ->name('produk-terlaris.export');

        Route::get('voucher-reject-report', [VoucherRejectReportController::class, 'index'])
            ->name('voucher-reject-report.index');

        Route::get('voucher-reject-report/export', [VoucherRejectReportController::class, 'export'])
            ->name('voucher-reject-report.export');

        Route::prefix('request-voucher')->name('request-voucher.')->group(function () {
            Route::get('/', [MonitoringVoucherRequestController::class, 'index'])
                ->name('index');
            Route::get('/export', [MonitoringVoucherRequestController::class, 'export'])
                ->name('export');
        });

        Route::get('redeem-point-report', [RedeemPointReportController::class, 'index'])
            ->name('redeem-point-report.index');

        Route::get('redeem-point-report/export', [RedeemPointReportController::class, 'export'])
            ->name('redeem-point-report.export');

        Route::prefix('physical-stocks')->name('physical-stocks.')->group(function () {
            Route::get('/', [PhysicalStockAccumulationController::class, 'index'])
                ->name('index');

            Route::get('/export', [PhysicalStockAccumulationController::class, 'export'])
                ->name('export');
        });

        Route::prefix('product-slowmoving')->name('product-slowmoving.')->group(function () {
            Route::get('/', [ProductSlowmovingController::class, 'index'])
                ->name('index');

            Route::get('/export-slow', [ProductSlowmovingController::class, 'exportSlow'])
                ->name('export-slow');

            Route::get('/export-dead', [ProductSlowmovingController::class, 'exportDead'])
                ->name('export-dead');
        });

        Route::prefix('employee-shift-attendance')->name('employee-shift-attendance.')->group(function () {
            Route::get('/', [EmployeeShiftAttendanceController::class, 'index'])
                ->name('index');

            Route::get('/export', [EmployeeShiftAttendanceController::class, 'export'])
                ->name('export');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('business-overview', [BusinessOverviewController::class, 'index'])
            ->name('business-overview.index');

        Route::get('crm', [CrmController::class, 'index'])->name('crm.index');

        Route::get('crm/customer-profiles', [CrmController::class, 'customerProfiles'])
            ->name('crm.customer-profiles');

        Route::get('crm/demographics', [CrmController::class, 'demographics'])
            ->name('crm.demographics');

        Route::get('crm/demographics/export', [CrmController::class, 'exportDemographics'])
            ->name('crm.demographics.export');

        Route::post('crm/demographics/birthday-greeting', [CrmController::class, 'sendBirthdayGreeting'])
            ->name('crm.demographics.birthday-greeting');

        Route::get('crm/demographics/regions/regencies', [CrmController::class, 'demographicRegencies'])
            ->name('crm.demographics.regencies');

        Route::get('crm/demographics/regions/districts', [CrmController::class, 'demographicDistricts'])
            ->name('crm.demographics.districts');

        Route::get('crm/demographics/regions/villages', [CrmController::class, 'demographicVillages'])
            ->name('crm.demographics.villages');

        Route::get('crm/customer-preference', [CustomerPreferenceController::class, 'index'])
            ->name('crm.customer-preference.index');

        Route::get('crm/customer-preference/export', [CustomerPreferenceController::class, 'export'])
            ->name('crm.customer-preference.export');

        Route::get('crm/customers/{customerId}', [CrmController::class, 'customerProfile'])
            ->name('crm.customer-profile');

        Route::get('crm/customers/{customerId}/export', [CrmController::class, 'exportCustomerProfile'])
            ->name('crm.customer-profile.export');

        Route::get('crm/export', [CrmController::class, 'export'])
            ->name('crm.export');

        Route::prefix('supplier-products')
            ->name('supplier-products.')
            ->group(function () {
                Route::get('/', [SupplierProductController::class, 'index'])
                    ->name('index');

                Route::get('/export', [SupplierProductController::class, 'export'])
                    ->name('export');
            });

        Route::get('voucher', [VoucherReportController::class, 'index'])
            ->name('voucher.index');

        Route::get('voucher/{id}/detail', [VoucherReportController::class, 'detail'])
            ->name('voucher.detail');

        Route::get('voucher-export', [VoucherReportController::class, 'export'])
            ->name('voucher.export');

        Route::prefix('order-cabang')->name('order-cabang.')->group(function () {
            Route::get('/', [OrderCabangController::class, 'index'])->name('index');
            Route::post('/', [OrderCabangController::class, 'store'])->name('store');

            Route::get('stock', [OrderCabangController::class, 'stockByWarehouse'])->name('stock');

            Route::put('{order}', [OrderCabangController::class, 'update'])
                ->whereUuid('order')
                ->name('update');

            Route::delete('{order}', [OrderCabangController::class, 'destroy'])
                ->whereUuid('order')
                ->name('destroy');

            Route::patch('{order}/request', [OrderCabangController::class, 'requestSubmit'])
                ->whereUuid('order')
                ->name('request');

            Route::patch('{order}/approve', [OrderCabangController::class, 'approve'])
                ->whereUuid('order')
                ->name('approve');

            Route::patch('{order}/reject', [OrderCabangController::class, 'reject'])
                ->whereUuid('order')
                ->name('reject');

            Route::patch('{order}/receive', [OrderCabangController::class, 'receive'])
                ->whereUuid('order')
                ->name('receive');

            Route::get('{order}', [OrderCabangController::class, 'show'])
                ->whereUuid('order')
                ->name('show');
        });

        Route::prefix('retur-cabang')->name('retur-cabang.')->group(function () {
            Route::get('/', [StoreReturnApprovalController::class, 'index'])->name('index');

            Route::get('{return}', [StoreReturnApprovalController::class, 'show'])
                ->whereUuid('return')
                ->name('show');

            Route::patch('{return}/approve', [StoreReturnApprovalController::class, 'approve'])
                ->whereUuid('return')
                ->name('approve');

            Route::patch('{return}/reject', [StoreReturnApprovalController::class, 'reject'])
                ->whereUuid('return')
                ->name('reject');
        });

        Route::get('history-customer', [HistoryCustomerController::class, 'index'])
            ->name('history-customer.index');

        Route::get('history-customer/ecom/{customerId}', [HistoryCustomerController::class, 'ecomDetail'])
            ->name('history-customer.ecom.detail');
    });

    Route::permanentRedirect('/reports/monitoring/stok-percabang', '/monitoring/stok-percabang');
});
