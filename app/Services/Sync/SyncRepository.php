<?php

namespace App\Services\Sync;

use App\Support\Settings;
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
     * @return array{accepted:int, rejected:int, errors:array, accepted_uuids:array}
     */
    public function applyBatch(string $table, array $rows, bool $markSynced = false): array
    {
        $models    = config('sync.models', []);
        $relations = config('sync.relations', []);

        if (!isset($models[$table])) {
            return ['accepted' => 0, 'rejected' => count($rows), 'errors' => ["جدول غير مدعوم: {$table}"], 'accepted_uuids' => []];
        }

        $fkMap         = $relations[$table] ?? [];
        $accepted      = 0;
        $rejected      = 0;
        $errors        = [];
        $acceptedUuids = []; // نرجّع الـ uuid المقبولة ليعلّمها الفرع synced (المرفوض يُعاد)

        foreach ($rows as $row) {
            try {
                $this->applyRow($table, $row, $fkMap, $markSynced);
                $accepted++;
                if (!empty($row['uuid'])) {
                    $acceptedUuids[] = $row['uuid'];
                }
            } catch (\Throwable $e) {
                $rejected++;
                $errors[] = ($row['uuid'] ?? '?') . ': ' . $e->getMessage();
            }
        }

        return ['accepted' => $accepted, 'rejected' => $rejected, 'errors' => $errors, 'accepted_uuids' => $acceptedUuids];
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

        // preserve_id (users): نحفظ نفس id السيرفر على الفرع ليتطابق Auth::id() وكل
        // أعمدة user_id تلقائياً. لغير هذه الجداول لا نثق في id المحلي — الربط عبر uuid.
        $preserveId = in_array($table, config('sync.preserve_id', []), true);
        $incomingId = $row['id'] ?? null;
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
            // preserve_id: أدرج بنفس id السيرفر (users على الفرع فقط). قد توجد في قاعدة
            // Desktop القديمة عينة مستخدم بنفس id؛ نستبدلها فقط إن لم ترتبط بها بيانات تشغيلية.
            if ($preserveId && $incomingId !== null) {
                $conflictingUuid = DB::table($table)->where('id', $incomingId)->value('uuid');
                if ($conflictingUuid !== null && $conflictingUuid !== $uuid) {
                    if ($table === 'users' && $this->isVerifiedOwner($uuid)) {
                        // UUID هو الهوية العابرة للأجهزة. نُبقي id المحلي حتى تظل كل
                        // علاقات بيانات العمل سليمة، ونحدّث صفه بهوية وبيانات المالك الموثقة.
                        $row['uuid'] = $uuid;
                        DB::table($table)->where('id', $incomingId)->update($row);
                        $newId = $incomingId;
                    } elseif ($table !== 'users' || $this->userHasOperationalData((int) $incomingId)) {
                        throw new \RuntimeException("تعارض id محفوظ: {$table}#{$incomingId}");
                    } else {
                        DB::table($table)->where('id', $incomingId)->update($row);
                        $newId = $incomingId;
                    }
                } else {
                    $row['id'] = $incomingId;
                    DB::table($table)->insert($row);
                    $newId = $incomingId;
                }
            } else {
                $newId = DB::table($table)->insertGetId($row);
            }
            $this->uuidToId[$table][$uuid] = $newId;
        }
    }

    private function isVerifiedOwner(string $uuid): bool
    {
        $ownerUuid = (string) Settings::get('branch.owner_uuid', '');

        return $ownerUuid !== '' && hash_equals($ownerUuid, $uuid);
    }

    /** هل المستخدم المحلي المتعارض مرتبط بأي بيانات يجب عدم لمسها؟ */
    private function userHasOperationalData(int $userId): bool
    {
        $tables = array_values(array_unique(array_merge(
            config('sync.push', []),
            config('sync.bidirectional', []),
            config('sync.pull_owner_scoped', []),
            ['sub_users']
        )));

        foreach ($tables as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $column = $table === 'sub_users' ? 'owner_id' : 'user_id';
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $userId)->exists()) {
                return true;
            }
        }

        return false;
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
