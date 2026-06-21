<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| جدولة مهام الفرع (المزامنة + النسخ الاحتياطي)
|--------------------------------------------------------------------------
|
| تعمل فقط عندما تكون المزامنة مُفعّلة (بيئة الفرع). تحترم الـ backoff داخلياً.
| على الفرع: يشغّل المجدوِل خدمة الخلفية (NativePHP queue/scheduler).
|
*/
if (config('sync.enabled')) {
    // دورة مزامنة كل دقيقة (تحترم backoff داخلياً، فلا تُغرق السيرفر).
    Schedule::command('sync:run')
        ->everyMinute()
        ->withoutOverlapping()
        ->runInBackground();

    // نسخة احتياطية محلية يومياً (3:17 فجراً — وقت هادئ).
    Schedule::command('sync:backup')
        ->dailyAt('03:17')
        ->withoutOverlapping();
}
