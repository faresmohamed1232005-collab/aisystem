<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync API (جانب السيرفر/master).
 *
 * push: يستقبل دفعة تغييرات من فرع، يطبّقها بترتيب التبعيات، ويردّ بملخّص.
 *       الدفعة الكاملة داخل معاملة واحدة: إما تُطبّق كلها أو تُلغى (سلامة المخزون).
 */
class SyncController extends Controller
{
    /**
     * الحمولة المتوقّعة:
     * {
     *   "branch_id": "br_...",
     *   "tables": {
     *     "drugs":  [ {row...}, ... ],
     *     "sales":  [ {row..., customer_id_uuid: "..."} , ... ],
     *     ...
     *   }
     * }
     */
    public function push(Request $request, SyncRepository $repo)
    {
        $data   = $request->validate([
            'branch_id' => 'required|string|max:64',
            'tables'    => 'required|array',
        ]);

        $branchId = $data['branch_id'];
        $tables   = $data['tables'];
        $order    = array_keys(config('sync.models', [])); // ترتيب التبعيات الصحيح

        $summary = [];

        try {
            DB::transaction(function () use ($order, $tables, $repo, &$summary) {
                // نعالج بترتيب التبعيات (الأب قبل الابن) لا بترتيب وصول المفاتيح.
                foreach ($order as $table) {
                    if (empty($tables[$table]) || !is_array($tables[$table])) {
                        continue;
                    }
                    $summary[$table] = $repo->applyBatch($table, $tables[$table]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Sync push failed', ['branch' => $branchId, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'فشل تطبيق الدفعة: ' . $e->getMessage(),
            ], 422);
        }

        $totalAccepted = array_sum(array_column($summary, 'accepted'));
        $totalRejected = array_sum(array_column($summary, 'rejected'));

        Log::info('Sync push applied', [
            'branch'   => $branchId,
            'accepted' => $totalAccepted,
            'rejected' => $totalRejected,
        ]);

        return response()->json([
            'success'  => true,
            'accepted' => $totalAccepted,
            'rejected' => $totalRejected,
            'detail'   => $summary,
        ]);
    }

    /**
     * Pull: يرجّع للفرع ما تغيّر في الجداول القابلة للسحب منذ cursor لكل جدول.
     *
     * الحمولة المتوقّعة:
     * { "cursors": { "drugs": "2026-06-21 12:00:00"|null, "ads": null } }
     *
     * الردّ:
     * { "success": true, "tables": { "drugs": { rows:[...], cursor:"..." }, ... } }
     *
     * نُترجم كل FK إلى <fk>_uuid حتى يطبّقها الفرع عبر uuid (نفس آلية push معكوسة).
     * نُرسل حتى batch_size صف لكل جدول؛ الفرع يكرّر السحب حتى يفرغ (cursor يتقدّم).
     */
    public function pull(Request $request)
    {
        $data    = $request->validate(['cursors' => 'sometimes|array']);
        $cursors = $data['cursors'] ?? [];

        $pullable  = config('sync.pull', []);
        $relations = config('sync.relations', []);
        $limit     = (int) config('sync.batch_size', 200);

        $out = [];

        foreach ($pullable as $table) {
            $since = $cursors[$table] ?? null;

            $query = DB::table($table)
                ->when($since, fn ($q) => $q->where('updated_at', '>', $since))
                ->orderBy('updated_at')
                ->orderBy('id')
                ->limit($limit);

            $rows   = $query->get();
            $fkMap  = $relations[$table] ?? [];
            $mapped = [];
            $maxTs  = $since;

            foreach ($rows as $row) {
                $arr = (array) $row;
                unset($arr['id'], $arr['synced_at']); // id محلي للسيرفر + synced_at لا معنى لهما للفرع

                // ترجمة الـ FK: id (على السيرفر) → <fk>_uuid
                foreach ($fkMap as $fkColumn => $referencedTable) {
                    $localFk = $arr[$fkColumn] ?? null;
                    unset($arr[$fkColumn]);
                    $arr[$fkColumn . '_uuid'] = $localFk !== null
                        ? DB::table($referencedTable)->where('id', $localFk)->value('uuid')
                        : null;
                }

                $mapped[] = $arr;
                if ($row->updated_at !== null && (string) $row->updated_at > (string) $maxTs) {
                    $maxTs = $row->updated_at;
                }
            }

            $out[$table] = [
                'rows'   => $mapped,
                'cursor' => $maxTs,
                'more'   => $rows->count() === $limit, // هل توجد دفعات أخرى؟
            ];
        }

        return response()->json(['success' => true, 'tables' => $out]);
    }
}
