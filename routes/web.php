<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PendingOrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\PurchaseImportController;
use App\Http\Controllers\PurchaseReportController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\SubUserController;
use App\Http\Controllers\DrawerLockController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ForecastController;
use App\Http\Controllers\MarketPriceCheckController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InsuredPatientController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StockTransferController;




// ===== إعداد أول تشغيل (الديسكتوب) — بدون auth/guest، متاح قبل التسجيل =====
Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');


// ===== Guest =====
Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});




Route::get('/purchases/report', [PurchaseReportController::class, 'index'])->name('purchases.report');
Route::get('/sales/{sale}/print', [SaleController::class, 'printInvoice'])->name('sales.print');
Route::get('/purchases/{invoice}/print', [PurchaseReportController::class, 'printInvoice'])->name('purchases.print');

// ===== Auth =====
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== Products =====
    // search لازم قبل resource دايماً
    Route::get('/products-search', [ProductController::class, 'search'])->name('products.search');
    Route::resource('products', ProductController::class);

    // ===== Sales =====
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
    Route::get('/sales/report', [SalesReportController::class, 'index'])->middleware('can:view_reports')->name('sales.report');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

    // ===== Customers =====
    // ⚠️ الـ 3 routes دول لازم يكونوا قبل resource
    Route::get('/customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::post('/customers/pay-sale/{sale}', [CustomerController::class, 'paySale'])->name('customers.pay-sale');
    Route::resource('customers', CustomerController::class);
    Route::get('/pending-orders', [PendingOrderController::class, 'index'])->name('pending.index');
    Route::post('/pending-orders', [PendingOrderController::class, 'store'])->name('pending.store');
    Route::post('/pending-orders/{pendingOrder}/confirm', [PendingOrderController::class, 'confirm'])->name('pending.confirm');
    Route::delete('/pending-orders/{pendingOrder}', [PendingOrderController::class, 'destroy'])->name('pending.destroy');
    Route::get('/pending-orders/count', [PendingOrderController::class, 'count'])->name('pending.count');
    // ===== Notifications =====
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.count');

    // ===== المزامنة مع السيرفر (تشغيل يدوي/دوري من الواجهة) =====
    Route::post('/sync/run', [\App\Http\Controllers\SyncWebController::class, 'run'])->name('sync.run');
    Route::get('/sync/status', [\App\Http\Controllers\SyncWebController::class, 'status'])->name('sync.status');

    // ===== تحديث التطبيق (فحص/تنزيل/تثبيت بإذن المستخدم) =====
    Route::get('/update/status', [\App\Http\Controllers\UpdateController::class, 'status'])->name('update.status');
    Route::post('/update/download', [\App\Http\Controllers\UpdateController::class, 'download'])->name('update.download');
    Route::post('/update/install', [\App\Http\Controllers\UpdateController::class, 'install'])->name('update.install');




    // الموردين
    Route::get('/suppliers/search', [SupplierController::class, 'search'])->name('suppliers.search');
    Route::resource('suppliers', SupplierController::class);
    Route::post('/suppliers/pay-invoice/{invoice}', [SupplierController::class, 'payInvoice'])->name('suppliers.payInvoice');

    // فواتير الشراء
    Route::get('/purchases', [PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create', [PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseInvoiceController::class, 'store'])->name('purchases.store');

    // استيراد فاتورة شراء من صورة (لازم قبل الـ wildcard تحت)
    Route::get('/purchases/import', [PurchaseImportController::class, 'index'])->name('purchases.import');
    Route::post('/purchases/import/extract', [PurchaseImportController::class, 'extract'])->name('purchases.import.extract');

    Route::get('/purchases/{purchaseInvoice}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');

    // ===== التأمين والتعاقدات ===== (صلاحية: manage_contracts)
    Route::middleware('can:manage_contracts')->group(function () {
        // التعاقدات + قواعد التأمين/التسعير
        Route::post('/contracts/{contract}/insurance-rule', [ContractController::class, 'saveInsuranceRule'])->name('contracts.insurance-rule');
        Route::post('/contracts/{contract}/pricing-rules', [ContractController::class, 'addPricingRule'])->name('contracts.pricing-rules.store');
        Route::delete('/contracts/{contract}/pricing-rules/{pricingRule}', [ContractController::class, 'deletePricingRule'])->name('contracts.pricing-rules.destroy');
        Route::resource('contracts', ContractController::class);

        // المرضى المؤمّن عليهم
        Route::get('/insured-patients/search', [InsuredPatientController::class, 'search'])->name('insured-patients.search');
        Route::resource('insured-patients', InsuredPatientController::class)->except(['show']);

        // مطالبات التأمين
        Route::patch('/insurance-claims/{insuranceClaim}/status', [InsuranceClaimController::class, 'updateStatus'])->name('insurance-claims.status');
        Route::resource('insurance-claims', InsuranceClaimController::class)->except(['edit', 'update']);
    });

    // ===== الفروع والتحويلات (Phase 2ب) =====
    // إدارة الفروع (صلاحية: manage_branches)
    Route::middleware('can:manage_branches')->group(function () {
        Route::resource('branches', BranchController::class)->only(['index', 'show', 'edit', 'update']);
    });

    // تحويلات المخزون — AJAX قبل الـ wildcard دايماً
    // القراءة (قائمة/تفصيل/بحث باتشات/بدائل) متاحة لأي فاعل معنيّ؛ الإنشاء والاستلام مقيّدان.
    Route::get('/stock-transfers/drug-batches', [StockTransferController::class, 'drugBatches'])->name('stock-transfers.drug-batches');
    Route::get('/stock-transfers/alternatives', [StockTransferController::class, 'alternatives'])->name('stock-transfers.alternatives');
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
    // ⚠️ create/store قبل الـ wildcard {stockTransfer} حتى لا يبتلعهما.
    Route::middleware('can:create_transfers')->group(function () {
        Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
        Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    });
    Route::middleware('can:receive_transfers')->group(function () {
        Route::get('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receiveForm'])->name('stock-transfers.receive');
        Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'confirmReceive'])->name('stock-transfers.receive.confirm');
        Route::post('/stock-transfers/{stockTransfer}/reject', [StockTransferController::class, 'reject'])->name('stock-transfers.reject');
    });
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');

});


// ─── مرتجعات المبيعات ───────────────────────────────────────
Route::middleware('auth')->prefix('sale-returns')->name('sale-returns.')->group(function () {
    Route::get('/', [SaleReturnController::class, 'index'])->name('index');
    Route::get('/create', [SaleReturnController::class, 'create'])->name('create');
    Route::post('/', [SaleReturnController::class, 'store'])->name('store');
    Route::get('/fetch-sale', [SaleReturnController::class, 'fetchSale'])->name('fetch-sale');
    Route::get('/{saleReturn}', [SaleReturnController::class, 'show'])->name('show');
    Route::delete('/{saleReturn}', [SaleReturnController::class, 'destroy'])->name('destroy');
    Route::get('/{saleReturn}/print', [SaleReturnController::class, 'print'])->name('print');
});

// ─── مرتجعات المشتريات ─────────────────────────────────────
Route::middleware('auth')->prefix('purchase-returns')->name('purchase-returns.')->group(function () {
    Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseReturnController::class, 'create'])->name('create');
    Route::post('/', [PurchaseReturnController::class, 'store'])->name('store');
    Route::get('/fetch-invoice', [PurchaseReturnController::class, 'fetchInvoice'])->name('fetch-invoice');
    Route::get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
    Route::delete('/{purchaseReturn}', [PurchaseReturnController::class, 'destroy'])->name('destroy');
    Route::get('/{purchaseReturn}/print', [PurchaseReturnController::class, 'print'])->name('print');
});


// ── صلاحيات المستخدمين (المالك فقط) — كانت خارج auth (ثغرة اتصلحت في Phase 3) ──
Route::middleware(['auth', 'can:manage_sub_users'])->prefix('sub-users')->name('sub-users.')->group(function () {
    Route::get('/', [SubUserController::class, 'index'])->name('index');
    Route::post('/', [SubUserController::class, 'store'])->name('store');
    Route::put('/{subUser}', [SubUserController::class, 'update'])->name('update');
    Route::delete('/{subUser}', [SubUserController::class, 'destroy'])->name('destroy');
    Route::patch('/{subUser}/toggle', [SubUserController::class, 'toggleActive'])->name('toggle');
});

Route::middleware('auth')->group(function () {
    Route::prefix('drawer-lock')->name('drawer-lock.')->group(function () {
        Route::get('/', [DrawerLockController::class, 'index'])->name('index');
        Route::post('/', [DrawerLockController::class, 'store'])->name('store');
        Route::get('/expected', [DrawerLockController::class, 'expectedAmount'])->name('expected');
    });
    Route::delete('drawer-lock/{drawerLock}', [DrawerLockController::class, 'destroy'])->name('drawer-lock.destroy');

    // مخزن اليوزر — القراءة/البحث لأي فاعل مسجّل؛ الكتابة تحتاج manage_stock.
    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
    Route::get('/products/catalog', [ProductsController::class, 'catalog'])->name('products.catalog');
    Route::get('/products/search', [ProductsController::class, 'search'])->name('products.search');
    Route::get('/products/barcode', [ProductsController::class, 'findByBarcode'])->name('products.barcode');
    Route::get('/products-search', [ProductsController::class, 'search'])->name('products.search');

    Route::middleware('can:manage_stock')->group(function () {
        Route::post('/products/add-to-inventory', [ProductsController::class, 'addToInventory'])->name('products.add-to-inventory');
        Route::put('/products/inventory/{drugId}', [ProductsController::class, 'updateInventory'])->name('products.update-inventory');
    });
});


Route::prefix('super-admin')->middleware(['auth', 'super.admin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'index'])->name('super.admin.index');
    Route::patch('/users/{user}/toggle', [SuperAdminController::class, 'toggleApprove'])->name('super.admin.toggle');
    Route::delete('/users/{user}', [SuperAdminController::class, 'destroy'])->name('super.admin.destroy');
});


Route::middleware(['auth'])->group(function () {
    Route::post('/ai-assistant/chat', [AiAssistantController::class, 'chat'])
        ->name('ai-assistant.chat');
});


Route::get('/super-admin/pharmacy/{user}', [SuperAdminController::class, 'pharmacyReport'])
    ->name('super.admin.pharmacy');



// Ads — Super Admin
Route::post('/super/ads', [SuperAdminController::class, 'adsStore'])->name('super.ads.store');
Route::patch('/super/ads/{ad}/toggle', [SuperAdminController::class, 'adsToggle'])->name('super.ads.toggle');
Route::delete('/super/ads/{ad}', [SuperAdminController::class, 'adsDestroy'])->name('super.ads.destroy');



// routes/web.php
Route::patch(
    'super/users/{user}/reset-password',
    [SuperAdminController::class, 'resetPassword']
)->name('super.admin.resetPassword');


// ── المصروفات والموظفون ── (صلاحية: manage_employees)
Route::middleware(['auth', 'can:manage_employees'])->group(function () {
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('/employees', [App\Http\Controllers\EmployeeController::class, 'index'])->name('employees.index');
    Route::post('/employees', [App\Http\Controllers\EmployeeController::class, 'store'])->name('employees.store');
    Route::delete('/employees/{employee}', [App\Http\Controllers\EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::post('/employees/{employee}/transaction', [App\Http\Controllers\EmployeeController::class, 'addTransaction'])->name('employees.transaction.store');
    Route::post('/employees/{employee}/pay', [App\Http\Controllers\EmployeeController::class, 'paySalary'])->name('employees.pay');
    Route::delete('/employees/transaction/{transaction}', [App\Http\Controllers\EmployeeController::class, 'deleteTransaction'])->name('employees.transaction.delete');
});



Route::middleware(['auth', 'can:view_reports'])->group(function () {
    Route::get('/forecast', [ForecastController::class, 'index'])->name('forecast.index');
});


Route::get('/cron/check-market-prices', [MarketPriceCheckController::class, 'run']);


