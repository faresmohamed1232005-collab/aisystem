<?php

namespace App\Services\Sync;

use App\Support\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SyncPullService — جانب الفرع: يسحب تحديثات الكتالوج/المراجع من السيرفر (master).
 *
 * الخطوات:
 *   1. اقرأ cursor كل جدول قابل للسحب من sync_state (آخر updated_at استُلم).
 *   2. اطلب من /api/sync/pull الصفوف المتغيّرة بعد الـ cursor.
 *   3. طبّقها محلياً عبر SyncRepository (يترجم <fk>_uuid → id محلي ويعمل upsert بالـ uuid).
 *   4. حدّث cursor الجدول، وكرّر إن كان هناك المزيد (more=true).
 *
 * القاعدة: السيرفر master للكتالوج → last-write-wins (الصف القادم يحدّث المحلي).
 */
class SyncPullService
{
    /** أقصى عدد دورات لكل جدول (حماية من حلقة لا نهائية). */
    private const MAX_ROUNDS = 50;

    public function __construct(private SyncRepository $repo) {}

    /**
     * @return array{success:bool, pulled:int, message:string, detail?:array}
     */
    public function run(): array
    {
        $serverUrl = rtrim((string) config('sync.server_url'), '/');
        $token     = (string) config('sync.token');

        if ($serverUrl === '' || $token === '') {
            return ['success' => false, 'pulled' => 0, 'message' => 'إعدادات المزامنة ناقصة (server_url / token).'];
        }

        // ما يسحبه الفرع = pull (الكتالوج/الماستر) + pull_scoped (التحويلات/اللقطات).
        $pullable    = array_merge(config('sync.pull', []), array_keys(config('sync.pull_scoped', [])));
        $totalPulled = 0;
        $detail      = [];

        foreach ($pullable as $table) {
            try {
                $pulled = $this->pullTable($serverUrl, $token, $table);
                $totalPulled += $pulled;
                $detail[$table] = $pulled;
            } catch (\Throwable $e) {
                Log::warning("Sync pull failed for {$table}", ['error' => $e->getMessage()]);
                return ['success' => false, 'pulled' => $totalPulled, 'message' => "تعذّر سحب {$table}: " . $e->getMessage(), 'detail' => $detail];
            }
        }

        return [
            'success' => true,
            'pulled'  => $totalPulled,
            'message' => $totalPulled > 0 ? "تم تنزيل {$totalPulled} تحديث من السيرفر." : 'لا تحديثات جديدة من السيرفر.',
            'detail'  => $detail,
        ];
    }

    /**
     * اسحب جدولاً واحداً على دفعات حتى يفرغ. يرجّع عدد الصفوف المطبّقة.
     */
    private function pullTable(string $serverUrl, string $token, string $table): int
    {
        $applied = 0;

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            // cursor مركّب (keyset): {ts, uuid} — يمنع تخطّي صفوف بنفس الثانية.
            $state  = DB::table('sync_state')->where('table_name', $table)->first();
            $cursor = $state && $state->last_pulled_at !== null
                ? ['ts' => $state->last_pulled_at, 'uuid' => $state->last_pulled_uuid]
                : null;

            $response = Http::withHeaders(['X-Sync-Token' => $token])
                ->timeout(120)
                ->acceptJson()
                ->post($serverUrl . '/api/sync/pull', [
                    'cursors'   => [$table => $cursor],
                    'branch_id' => Branch::id(), // ليخصّص السيرفر حسابات هذا الفرع
                ]);

            if (!$response->successful() || !$response->json('success')) {
                throw new \RuntimeException($response->json('message', 'استجابة غير متوقعة من السيرفر'));
            }

            $payload = $response->json("tables.{$table}", ['rows' => [], 'cursor' => $cursor, 'more' => false]);
            $rows    = $payload['rows'] ?? [];

            if (!empty($rows)) {
                // طبّق الصفوف محلياً (upsert + ترجمة FK). markSynced=true لمنع إعادة رفعها.
                DB::transaction(function () use ($table, $rows) {
                    $this->repo->applyBatch($table, $rows, true);
                });
                $applied += count($rows);
            }

            // حدّث cursor الجدول (ts + uuid).
            $newCursor = $payload['cursor'] ?? $cursor;
            if (is_array($newCursor) && !empty($newCursor['ts'])) {
                DB::table('sync_state')->updateOrInsert(
                    ['table_name' => $table],
                    [
                        'last_pulled_at'   => $newCursor['ts'],
                        'last_pulled_uuid' => $newCursor['uuid'] ?? null,
                        'last_pull_run_at' => Carbon::now(),
                        'updated_at'       => Carbon::now(),
                    ]
                );
            }

            if (empty($payload['more'])) {
                break; // لا مزيد من الدفعات لهذا الجدول
            }
        }

        return $applied;
    }
}
