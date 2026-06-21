<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * طبقة توافق SQL بين MySQL (السيرفر) و SQLite (الفرع/الـ desktop).
 *
 * بعض دوال MySQL ليست متطابقة في SQLite (YEAR, DATE_FORMAT, SUBSTRING...).
 * هذا الـ helper يُرجِع تعبير SQL الصحيح حسب نوع الاتصال الحالي، فيعمل نفس الكود
 * على القاعدتين دون تفرّع في كل Controller.
 *
 * الاستخدام في الاستعلامات:
 *   ->selectRaw(Sql::year('expense_date') . ' as y')
 *   ->orderByRaw(Sql::castInt(Sql::substr('code', 5)))
 */
class Sql
{
    /** هل الاتصال الحالي SQLite؟ */
    public static function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    /** استخراج السنة من عمود تاريخ. MySQL: YEAR(col) — SQLite: strftime('%Y', col). */
    public static function year(string $column): string
    {
        return self::isSqlite()
            ? "CAST(strftime('%Y', {$column}) AS INTEGER)"
            : "YEAR({$column})";
    }

    /** استخراج الشهر من عمود تاريخ. */
    public static function month(string $column): string
    {
        return self::isSqlite()
            ? "CAST(strftime('%m', {$column}) AS INTEGER)"
            : "MONTH({$column})";
    }

    /** التاريخ فقط (بدون وقت) من عمود datetime. MySQL: DATE(col) — SQLite: date(col). */
    public static function date(string $column): string
    {
        return self::isSqlite()
            ? "date({$column})"
            : "DATE({$column})";
    }

    /**
     * استخراج جزء من نص. MySQL: SUBSTRING(col, start[, len]) — SQLite: substr(col, start[, len]).
     * (الدالتان متطابقتان في التوقيع، لكن نوحّد التسمية لتفادي اختلافات.)
     */
    public static function substr(string $column, int $start, ?int $length = null): string
    {
        $fn = self::isSqlite() ? 'substr' : 'SUBSTRING';
        return $length === null
            ? "{$fn}({$column}, {$start})"
            : "{$fn}({$column}, {$start}, {$length})";
    }

    /** تحويل تعبير إلى عدد صحيح للترتيب الرقمي. متوافق مع الاثنين. */
    public static function castInt(string $expr): string
    {
        return "CAST({$expr} AS " . (self::isSqlite() ? 'INTEGER' : 'UNSIGNED') . ')';
    }

    /**
     * تنسيق تاريخ بصيغة. يغطّي الأنماط الشائعة (شهر/سنة).
     * pattern: 'Y-m' أو 'Y-m-d' ...
     */
    public static function dateFormat(string $column, string $pattern): string
    {
        if (self::isSqlite()) {
            $map = ['Y' => '%Y', 'm' => '%m', 'd' => '%d', 'H' => '%H', 'i' => '%M'];
            $sqliteFmt = strtr($pattern, $map);
            return "strftime('" . $sqliteFmt . "', {$column})";
        }
        $map = ['Y' => '%Y', 'm' => '%m', 'd' => '%d', 'H' => '%H', 'i' => '%i'];
        $mysqlFmt = strtr($pattern, $map);
        return "DATE_FORMAT({$column}, '" . $mysqlFmt . "')";
    }
}
