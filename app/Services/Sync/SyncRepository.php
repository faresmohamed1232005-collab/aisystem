<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\DB;

/**
 * SyncRepository — منطق تطبيق دفعات المزامنة على السيرفر (جانب الاستقبال/Push).
 *
 * المسؤوليات:
 *   - ترجمة المفاتيح الأجنبية من uuid (كما أرسلها الفرع) إلى id محلي على السيرفر.
 *   - upsert الصف حسب uuid (إدراج جديد أو تحديث موجود) بدل الاعتماد على id المحلي.
 *   - احترام الحذف الناعم (deleted_at) القادم من الفرع.
 *
 * مبدأ التعارض (السيرفر master للمراجع، append-only للمعاملات):
 *   المعاملات (sales/purchases...) لها uuid فريد من المصدر فلا تتعارض. المراجع
 *   (drugs/suppliers...) يفوز فيها الأحدث (updated_at) — مبسّط هنا بـ last-write-wins.
 */
class SyncRepository
{
    /** كاش لترجمة uuid → id لكل جدول خلال معالجة الدفعة. */
    private array $uuidToId = [];

    /**
     * طبّق دفعة صفوف لجدول واحد. يرجّع ملخصاً (مقبول/مرفوض).
     *
     * @param  string  $table  اسم الجدول
     * @param  array   $rows        صفوف (مصفوفات ترابطية) قادمة من الفرع/السيرفر، تحوي uuid
     * @param  bool    $markSynced  علّم الصف كمتزامن (synced_at = updated_at) — يُستخدم عند الـ
     *                              pull على الفرع لمنع إعادة رفع ما سُحب (echo loop).
     * @return array{accepted:int, rejected:int, errors:array}
     */
    public function applyBatch(string $table, array $rows, bool $markSynced = false): array
    {
        $models    = config('sync.models', []);
        $relations = config('sync.relations', []);

        if (!isset($models[$table])) {
            return ['accepted' => 0, 'rejected' => count($rows), 'errors' => ["جدول غير مدعوم: {$table}"]];
        }

        $fkMap    = $relations[$table] ?? [];
        $accepted = 0;
        $rejected = 0;
        $errors   = [];

        foreach ($rows as $row) {
            try {
                $this->applyRow($table, $row, $fkMap, $markSynced);
                $accepted++;
            } catch (\Throwable $e) {
                $rejected++;
                $errors[] = ($row['uuid'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected, 'errors' => $errors];
    }

    /**
     * طبّق صفاً واحداً: ترجمة الـ FK ثم upsert حسب uuid.
     */
    private function applyRow(string $table, array $row, array $fkMap, bool $markSynced = false): void
    {
        $uuid = $row['uuid'] ?? null;
        if (empty($uuid)) {
            throw new \RuntimeException('صف بدون uuid — مرفوض');
        }

        // لا نثق في id المحلي القادم من الفرع — نزيله، الربط يتم عبر uuid فقط.
        unset($row['id']);

        // ترجمة كل FK من uuid (كما أرسله الفرع) إلى id محلي على السيرفر.
        foreach ($fkMap as $fkColumn => $referencedTable) {
            $uuidKey = $fkColumn . '_uuid'; // الفرع يرسل القيمة المرجعية كـ <fk>_uuid
            if (array_key_exists($uuidKey, $row)) {
                $refUuid = $row[$uuidKey];
                unset($row[$uuidKey]);
                $row[$fkColumn] = $refUuid !== null
                    ? $this->resolveId($referencedTable, $refUuid)
                    : null;
            }
        }

        // عند الـ pull على الفرع: علّم الصف كمتزامن حتى لا يُعاد رفعه (echo loop).
        // synced_at = updated_at يجعل scope pendingSync يتجاهله.
        if ($markSynced && array_key_exists('updated_at', $row)) {
            $row['synced_at'] = $row['updated_at'];
        }

        // upsert حسب uuid
        $existingId = $this->resolveId($table, $uuid, false);
        if ($existingId !== null) {
            DB::table($table)->where('id', $existingId)->update($row);
        } else {
            $newId = DB::table($table)->insertGetId($row);
            $this->uuidToId[$table][$uuid] = $newId;
        }
    }

    /**
     * ترجمة uuid → id محلي على السيرفر (مع كاش). يرمي استثناءً إن لم يوجد و$required.
     */
    private function resolveId(string $table, string $uuid, bool $required = true): ?int
    {
        if (isset($this->uuidToId[$table][$uuid])) {
            return $this->uuidToId[$table][$uuid];
        }

        $id = DB::table($table)->where('uuid', $uuid)->value('id');
        if ($id !== null) {
            $this->uuidToId[$table][$uuid] = $id;
            return $id;
        }

        if ($required) {
            throw new \RuntimeException("مرجع غير موجود: {$table}#{$uuid} (لم يصل بعد؟)");
        }
        return null;
    }
}
