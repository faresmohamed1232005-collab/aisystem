<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — المزامنة بين الفروع والسيرفر المركزي
|--------------------------------------------------------------------------
|
| محمية بـ middleware sync.auth (مفتاح X-Sync-Token المشترك).
|
*/

Route::middleware('sync.auth')->prefix('sync')->group(function () {
    // تسجيل فرع جديد عند أول تشغيل (يعيد branch_id ثابت من السيرفر).
    Route::post('/register', [SyncController::class, 'register'])->name('sync.register');

    // تحقق من مالك فرع قائم دون تسجيل أو تعديل الهوية.
    Route::post('/verify-owner', [SyncController::class, 'verifyOwner'])->middleware('throttle:5,1')->name('sync.verify-owner');

    // استقبال دفعة تغييرات من فرع (Push من الفرع → السيرفر).
    Route::post('/push', [SyncController::class, 'push'])->name('sync.push');

    // لقطة ثابتة لأحجام وحدود المزامنة الأولى.
    Route::post('/manifest', [SyncController::class, 'manifest'])->name('sync.manifest');

    // إرسال تحديثات الكتالوج/المراجع للفرع (Pull من السيرفر → الفرع).
    Route::post('/pull', [SyncController::class, 'pull'])->name('sync.pull');
});
