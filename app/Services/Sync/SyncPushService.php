<?php

namespace App\Services\Sync;

use App\Support\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SyncPushService — جانب الفرع: يدفع التغييرات المحلية إلى السيرفر المركزي.
 *
 * الخطوات:
 *   1. لكل جدول (بترتيب التبعيات): اجمع الصفوف pendingSync (synced_at < updated_at أو null).
 *   2. ترجم كل FK من id محلي → uuid الصف المرجعي (حتى يفهمه السيرفر).
 *   3. أرسل الدفعة إلى /api/sync/push مع X-Sync-Token.
 *   4. عند النجاح: علّم الصفوف المرفوعة بـ synced_at = now().
 */
class SyncPushService
{
    /** كاش ترجمة id → uuid لكل جدول مرجعي خلال هذه الدفعة. */
    private array $idToUuid = [];

    /**
     * نفّذ دورة Push كاملة. يرجّع ملخّصاً للعرض/السجل.
     *
     * @return array{success:bool, pushed:int, message:string, detail?:array}
     */
    public function run(): array
    {
        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token     = (string) config('sync.token');

        if ($serverUrl === '' || $token === '') {
            return ['success' => false, 'pushed' => 0, 'message' => 'إعدادات المزامنة ناقصة (server_url / token).'];
        }

        $models    = config('sync.models', []);
        $relations = config('sync.relations', []);
        $batchSize = (int) config('sync.batch_size', 200);

        // اجمع الدفعة لكل جدول مع ترجمة الـ FK إلى uuid.
        $payloadTables = [];
        $idsToMark     = []; // table => [local ids] لتعليمها synced بعد النجاح
        $totalRows     = 0;

        foreach ($models as $table => $modelClass) {
            $rows = $modelClass::query()
                ->withTrashed()          // ندفع المحذوف ناعماً أيضاً (deleted_at)
                ->pendingSync()
                ->limit($batchSize)
                ->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $fkMap   = $relations[$table] ?? [];
            $outRows = [];
            foreach ($rows as $model) {
                $attrs = $model->getAttributes();
                $idsToMark[$table][] = $attrs['id'];

                // ترجمة الـ FK: id محلي → <fk>_uuid
                foreach ($fkMap as $fkColumn => $referencedTable) {
                    $localFk = $attrs[$fkColumn] ?? null;
                    unset($attrs[$fkColumn]); // لا نرسل id المحلي للمرجع
                    $attrs[$fkColumn . '_uuid'] = $localFk !== null
                        ? $this->uuidFor($referencedTable, $localFk)
                        : null;
                }

                // synced_at حقل محلي بحت — لا يُرسَل للسيرفر.
                unset($attrs['synced_at']);
                $outRows[] = $attrs;
            }

            $payloadTables[$table] = $outRows;
            $totalRows += count($outRows);
        }

        if ($totalRows === 0) {
            return ['success' => true, 'pushed' => 0, 'message' => 'لا توجد تغييرات للرفع — كل شيء متزامن.'];
        }

        // أرسل الدفعة للسيرفر.
        try {
            $response = Http::withHeaders(['X-Sync-Token' => $token])
                ->timeout(120)
                ->acceptJson()
                ->post($serverUrl . '/api/sync/push', [
                    'branch_id' => Branch::id(),
                    'tables'    => $payloadTables,
                ]);
        } catch (\Throwable $e) {
            Log::warning('Sync push connection failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'pushed' => 0, 'message' => 'تعذّر الاتصال بالسيرفر: ' . $e->getMessage()];
        }

        if (!$response->successful() || !$response->json('success')) {
            $msg = $response->json('message', 'استجابة غير متوقعة من السيرفر');
            return ['success' => false, 'pushed' => 0, 'message' => $msg, 'detail' => $response->json('detail')];
        }

        // نجاح: علّم الصفوف المرفوعة بـ synced_at = now() (لكل جدول دفعةً واحدة).
        $now = Carbon::now();
        foreach ($idsToMark as $table => $ids) {
            DB::table($table)->whereIn('id', $ids)->update(['synced_at' => $now]);
        }

        return [
            'success' => true,
            'pushed'  => (int) $response->json('accepted', $totalRows),
            'message' => 'تمت مزامنة ' . $response->json('accepted', $totalRows) . ' صف بنجاح.',
            'detail'  => $response->json('detail'),
        ];
    }

    /**
     * ترجمة id محلي → uuid للصف المرجعي (مع كاش). يبحث ضمن المحذوف أيضاً.
     */
    private function uuidFor(string $table, $localId): ?string
    {
        if (isset($this->idToUuid[$table][$localId])) {
            return $this->idToUuid[$table][$localId];
        }
        $uuid = DB::table($table)->where('id', $localId)->value('uuid');
        if ($uuid !== null) {
            $this->idToUuid[$table][$localId] = $uuid;
        }
        return $uuid;
    }
}
