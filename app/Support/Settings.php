<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settings — مخزن إعدادات دائم key/value في جدول app_settings (SQLite على الفرع).
 *
 * السبب: هوية الفرع وإعدادات المزامنة (branch id/code، رابط السيرفر، التوكن) كانت
 * تُقرأ من env المخبوز في الـ .exe (نسخة لكل فرع) أو من الكاش (يُمسح فتتغيّر الهوية
 * وتبوّظ المزامنة). هذا المخزن **دائم ومستقل عن env والكاش** — يُكتب مرة عند «شاشة
 * إعداد أول تشغيل» ويبقى ثابتاً، فتكفي نسخة .exe واحدة لكل الفروع.
 *
 * memoized per-process (static) لأن Branch::id() يُستدعى كثيراً (عند كل إنشاء صف
 * قابل للمزامنة) — لا نضرب قاعدة البيانات في كل مرة.
 */
class Settings
{
    /** @var array<string,?string>|null كاش داخل العملية؛ null = لم يُحمّل بعد. */
    private static ?array $cache = null;

    /** هل جدول app_settings موجود؟ (قد يُستدعى قبل تشغيل المهاجرات على فرع جديد.) */
    private static function tableReady(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (\Throwable $e) {
            return false; // قاعدة غير جاهزة بعد
        }
    }

    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        if (! self::tableReady()) {
            return self::$cache = [];
        }
        return self::$cache = DB::table('app_settings')->pluck('value', 'key')->all();
    }

    public static function get(string $key, $default = null)
    {
        $all = self::load();
        $val = $all[$key] ?? null;
        return ($val === null || $val === '') ? $default : $val;
    }

    public static function set(string $key, ?string $value): void
    {
        if (! self::tableReady()) {
            return; // لا نفشل بصمت أثناء boot مبكر جداً
        }
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
        // حدّث الكاش الداخلي مباشرة.
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function forget(string $key): void
    {
        if (self::tableReady()) {
            DB::table('app_settings')->where('key', $key)->delete();
        }
        if (self::$cache !== null) {
            unset(self::$cache[$key]);
        }
    }

    /** أعِد تحميل الكاش الداخلي (بعد كتابة خارجية مثلاً). */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
