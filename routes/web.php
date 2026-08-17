<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BranchReportController;
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
use App\Http\Controllers\SetupSyncController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InsuredPatientController;
use App\Http\Controllers\InsuranceClaimController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\DiagnosticsController;
use App\Support\Runtime;


// ===== إعداد أول تشغيل (الديسكتوب) — بدون auth/guest، متاح قبل التسجيل =====
Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
Route::get('/setup/sync', [SetupSyncController::class, 'show'])->name('setup.sync.show');
Route::post('/setup/sync/step', [SetupSyncController::class, 'step'])->name('setup.sync.step');
// يؤكّد اكتمال أول مزامنة (كل الجداول completed) ويضبط علَم الدخول.
Route::post('/setup/sync/complete', [SetupSyncController::class, 'complete'])->name('setup.sync.complete');
// مخرج إعادة ضبط محلي (offline، بدون سيرفر ولا دخول) لفكّ القفل عند إعداد أول خاطئ.
Route::post('/setup/reset-connection', [SetupController::class, 'resetConnection'])->name('setup.reset-connection');

// ===== مركز التشخيص — للمستخدم المسجّل، أو ضيف تطبيق سطح المكتب فقط =====
Route::middleware('diagnostics.access')->group(function () {
    Route::get('/settings/diagnostics', [DiagnosticsController::class, 'page'])->name('diagnostics.page');
    Route::get('/settings/diagnostics/status', [DiagnosticsController::class, 'status'])->name('diagnostics.status');
    Route::post('/settings/diagnostics/repair', [DiagnosticsController::class, 'repair'])->middleware('throttle:3,5')->name('diagnostics.repair');
    Route::post('/settings/diagnostics/reconfigure', [DiagnosticsController::class, 'reconfigure'])->middleware('throttle:3,5')->name('diagnostics.reconfigure');
    Route::post('/settings/diagnostics/factory-reset', [DiagnosticsController::class, 'factoryReset'])->middleware('throttle:2,10')->name('diagnostics.factory-reset');
});
Route::middleware('auth')->prefix('/settings/diagnostics/actions')->name('diagnostics.actions.')->group(function () {
    Route::post('/sync', [DiagnosticsController::class, 'sync'])->name('sync');
    Route::post('/backup', [DiagnosticsController::class, 'backup'])->name('backup');
    Route::post('/retry', [DiagnosticsController::class, 'retry'])->middleware('throttle:5,1')->name('retry');
    Route::post('/disconnect', [DiagnosticsController::class, 'disconnect'])->middleware('throttle:3,5')->name('disconnect');
});


// ===== تحدّي المصادقة الثنائية (بين كلمة المرور والدخول) — بلا auth/guest =====
Route::get('/2fa', [LoginController::class, 'showTwoFactor'])->name('login.2fa');
Route::post('/2fa', [LoginController::class, 'verifyTwoFactor'])->name('login.2fa.verify');


// ===== Guest =====
// الصفحة الرئيسية (لاندينج بيدج) — متاحة للجميع
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    // Desktop تطبيق تشغيل وليس موقعاً تسويقياً؛ بعد الإعداد يبدأ من تسجيل الدخول.
    if (Runtime::isDesktop()) {
        return redirect()->route('login');
    }

    return view('landing');
})->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::middleware('web.only')->group(function () {
        Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
    });
});




// ===== Auth =====
Route::middleware('auth')->group(function () {

    Route::get('/purchases/report', [PurchaseReportController::class, 'index'])->name('purchases.report');
    Route::get('/sales/{sale}/print', [SaleController::class, 'printInvoice'])->name('sales.print');
    Route::get('/purchases/{invoice}/print', [PurchaseReportController::class, 'printInvoice'])->name('purchases.print');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== Cross-branch central dashboard (Phase 4) =====
    Route::get('/reports/branches', [BranchReportController::class, 'index'])
        ->middleware('can:view_reports')->name('reports.branches');

    // ===== Products =====
    // ملاحظة: راوت البحث والـ index باسم products.* معرّفان في مجموعة "مخزن اليوزر" أدناه
    // (ProductsController). نستثني index هنا لتفادي تكرار اسم الراوت الذي يُفشِل route:cache.
    Route::resource('products', ProductController::class)->except(['index']);

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
    Route::post('/update/check', [\App\Http\Controllers\UpdateController::class, 'check'])->name('update.check');
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
        Route::resource('contracts', ContractController::class)->except(['create', 'edit']);

        // المرضى المؤمّن عليهم
        Route::get('/insured-patients/search', [InsuredPatientController::class, 'search'])->name('insured-patients.search');
        Route::resource('insured-patients', InsuredPatientController::class)->only(['index', 'store', 'update', 'destroy']);

        // مطالبات التأمين
        Route::patch('/insurance-claims/{insuranceClaim}/status', [InsuranceClaimController::class, 'updateStatus'])->name('insurance-claims.status');
        Route::resource('insurance-claims', InsuranceClaimController::class)->except(['edit', 'update']);
    });

    // ===== أمان الحساب (Phase 3C) — المصادقة الثنائية (المالك) =====
    Route::get('/security', [\App\Http\Controllers\SecurityController::class, 'show'])->name('security.index');
    Route::post('/security/2fa/enable', [\App\Http\Controllers\SecurityController::class, 'enable'])->name('security.2fa.enable');
    Route::post('/security/2fa/confirm', [\App\Http\Controllers\SecurityController::class, 'confirm'])->name('security.2fa.confirm');
    Route::post('/security/2fa/disable', [\App\Http\Controllers\SecurityController::class, 'disable'])->name('security.2fa.disable');

    // ===== سجل التدقيق (Phase 3B) — قراءة فقط =====
    Route::get('/audit-logs', [\App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('can:view_audit')->name('audit-logs.index');

    // ===== الفروع والتحويلات (Phase 2ب) =====
    // إدارة الفروع (صلاحية: manage_branches)
    Route::middleware('can:manage_branches')->group(function () {
        // اختيار «الفرع النشط» الذي يعمل عليه المالك على الموقع (قبل الـ resource wildcard).
        Route::post('branches/switch', [BranchController::class, 'switch'])->name('branches.switch');
        Route::resource('branches', BranchController::class)->only(['index', 'create', 'store', 'show', 'update']);
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
        // اعتماد/إرسال التحويل (المصدر): draft → approved → sent (خصم المخزون).
        Route::post('/stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
        Route::post('/stock-transfers/{stockTransfer}/send', [StockTransferController::class, 'send'])->name('stock-transfers.send');
        // حذف مسودة (المصدر): draft فقط.
        Route::delete('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->name('stock-transfers.destroy');
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
    Route::get('/products/search', [ProductsController::class, 'search'])->name('products.search.slash');
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


Route::get('/products/inventory/print', [App\Http\Controllers\ProductsController::class, 'inventoryPrint'])
    ->name('products.inventory.print')
    ->middleware('auth');
    
    
 Route::get('/dashboard/export', [App\Http\Controllers\DashboardController::class, 'export'])
    ->name('dashboard.export')
    ->middleware('auth');
    
    Route::patch('/dashboard/pharmacies/{pharmacy}/toggle', [App\Http\Controllers\DashboardController::class, 'togglePharmacy'])
    ->name('dashboard.pharmacies.toggle')
    ->middleware('auth');
    
    Route::get('/super/sales-report', [SuperAdminController::class, 'salesReport'])
    ->name('super.admin.sales-report');