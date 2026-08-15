<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * UuidBackfiller — يملأ عمود uuid (وطوابع الوقت الناقصة) لكل صف قابل للمزامنة بـ uuid فارغ.
 *
 * الحاجة: بعض الجداول (خصوصاً drugs عبر DrugsSeeder) تُملأ عبر إدراج خام
 * (DB::table()->insert) يتخطّى Eloquent فلا يعمل هوك Syncable::creating الذي يولّد الـ uuid.
 * النتيجة صفوف بـ uuid=NULL على الماستر، يرفضها الفرع عند السحب («صف بدون uuid») فتتوقّف
 * المزامنة. هذا الـ backfill يعالج البيانات الموجودة، وهو idempotent (يلمس فقط uuid IS NULL).
 *
 * كذلك يملأ created_at/updated_at الناقصة لأن الـ pull cursor (keyset {updated_at, uuid})
 * ينكسر لو كان updated_at فارغاً.
 */
class UuidBackfiller
{
    /**
     * @param  callable|null  $progress  تُستدعى (string $table, int $filled) لكل جدول.
     * @return array<string,int>  عدد الصفوف التي مُلئت لكل جدول.
     */
    public static function run(?callable $progress = null): array
    {
        $tables = array_keys(config('sync.models', []));
        $result = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            self::backfillTimestamps($table);
            $filled = self::backfillUuids($table);
            $result[$table] = $filled;

            if ($progress) {
                $progress($table, $filled);
            }
        }

        return $result;
    }

    /** طوابع الوقت الناقصة تكسر الـ pull cursor — نملؤها قبل السحب. */
    private static function backfillTimestamps(string $table): void
    {
        $now = now();

        if (Schema::hasColumn($table, 'created_at')) {
            DB::table($table)->whereNull('created_at')->update(['created_at' => $now]);
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            DB::table($table)->whereNull('updated_at')->update([
                'updated_at' => Schema::hasColumn($table, 'created_at') ? DB::raw('created_at') : $now,
            ]);
        }
    }

    /**
     * توليد ULID لكل صف بدون uuid. نجمع كل دفعة في UPDATE واحد (CASE id WHEN ... THEN ...)
     * — أسرع بكثير من UPDATE لكل صف على الجداول الكبيرة (drugs ~25k). نفس أسلوب migration
     * 2026_06_21_000001.
     */
    private static function backfillUuids(string $table): int
    {
        $filled = 0;

        DB::table($table)->whereNull('uuid')->orderBy('id')->chunkById(1000, function ($rows) use ($table, &$filled) {
            $ids   = [];
            $cases = [];
            $bind  = [];
            foreach ($rows as $row) {
                $ids[]   = $row->id;
                $cases[] = 'WHEN ? THEN ?';
                $bind[]  = $row->id;                     // لمطابقة WHEN
                $bind[]  = (string) Str::ulid();         // قيمة uuid
            }
            if (empty($ids)) {
                return;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $caseSql      = implode(' ', $cases);
            $params       = array_merge($bind, $ids);    // قيم CASE أولاً ثم قيم IN
            DB::update(
                "UPDATE {$table} SET uuid = CASE id {$caseSql} END WHERE id IN ({$placeholders})",
                $params
            );
            $filled += count($ids);
        });

        return $filled;
    }
}
