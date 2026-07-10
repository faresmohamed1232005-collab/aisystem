<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * محلّل فترة التقارير (Report period resolver) — Phase 4.
 *
 * استخراج منطق resolvePeriod() المكرّر في تقارير المبيعات لمكان واحد قابل لإعادة
 * الاستخدام. يرجّع [البداية, النهاية, التسمية] بنفس الدلالة السابقة تماماً.
 */
class ReportPeriod
{
    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    public static function resolve(string $period, Request $request): array
    {
        return match ($period) {
            'today'  => [Carbon::today(), Carbon::today(), 'اليوم'],
            'week'   => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'هذا الأسبوع'],
            'month'  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
            'last10' => [Carbon::today()->subDays(9), Carbon::today(), 'آخر 10 أيام'],
            'custom' => [
                Carbon::parse($request->get('from', now()->subDays(7)->format('Y-m-d'))),
                Carbon::parse($request->get('to',   now()->format('Y-m-d'))),
                'تاريخ مخصص',
            ],
            default  => [Carbon::today(), Carbon::today(), 'اليوم'],
        };
    }
}
