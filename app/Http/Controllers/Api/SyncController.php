<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sync\SyncPullQuery;
use App\Services\Sync\SyncRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        // حماية (whitelist): السيرفر يقبل فقط ما يملكه الفرع = push + bidirectional.
        // أي جدول خارج القائمة (الكتالوج/الحسابات/الفروع/اللقطات) يُتجاهل — server-master.
        $writable = array_merge(config('sync.push', []), config('sync.bidirectional', []));

        $summary = [];

        try {
            DB::transaction(function () use ($order, $tables, $repo, $writable, &$summary) {
                // نعالج بترتيب التبعيات (الأب قبل الابن) لا بترتيب وصول المفاتيح.
                foreach ($order as $table) {
                    if (!in_array($table, $writable, true)) {
                        continue; // خارج whitelist — يُتجاهل عند الـ push (السيرفر master)
                    }
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

        // أعد بناء لقطات مخزون هذا الفرع للأدوية المتأثرة (server-maintained) ليراها بقية الفروع.
        if (! empty($tables['user_drug_inventory']) && is_array($tables['user_drug_inventory'])) {
            try {
                $this->rebuildSnapshots($branchId, $tables['user_drug_inventory']);
            } catch (\Throwable $e) {
                Log::warning('Snapshot rebuild failed', ['branch' => $branchId, 'error' => $e->getMessage()]);
            }
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

    /** تحقّق مركزي من مالك الفرع دون تسجيل أو تعديل الفرع. */
    public function verifyOwner(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|string|max:64',
            'owner_login' => 'required|string|max:255',
            'owner_password' => 'required|string',
        ]);

        $branch = Schema::hasTable('branches')
            ? DB::table('branches')->where('branch_id', $data['branch_id'])->first()
            : null;
        if (! $branch || $branch->user_id === null) {
            return response()->json(['success' => false, 'message' => 'الفرع غير موجود.'], 404);
        }

        $login = trim($data['owner_login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $owner = \App\Models\User::whereKey($branch->user_id)->where($field, $login)->first();
        if (! $owner || ! \Illuminate\Support\Facades\Hash::check($data['owner_password'], $owner->password)) {
            return response()->json(['success' => false, 'message' => 'بيانات مالك الصيدلية غير صحيحة.'], 401);
        }
        if (isset($owner->is_approved) && ! $owner->is_approved) {
            return response()->json(['success' => false, 'message' => 'حساب الصيدلية غير مفعّل.'], 403);
        }

        return response()->json([
            'success' => true,
            'branch_id' => $branch->branch_id,
            'owner_uuid' => $owner->uuid,
        ]);
    }

    /**
     * Register: تسجيل فرع جديد عند أول تشغيل. يستقبل كود الفرع (والاسم اختياري)،
     * يولّد branch_id ثابت ويحفظه في جدول branches، ثم يعيده للجهاز ليخزّنه دائماً.
     *
     * idempotent: إعادة التسجيل بنفس code (إعادة تثبيت على نفس الفرع) تُعيد نفس
     * branch_id بدل توليد واحد جديد — فتبقى هوية الفرع ثابتة عبر إعادات التثبيت.
     *
     * يتحقق من بيانات مالك الصيدلية (email/username + password) ليربط الفرع بالـ tenant
     * الصحيح — فيُرسل السيرفر لهذا الفرع لاحقاً حساب تلك الصيدلية وموظفيها فقط.
     *
     * الحمولة: { "code":"A", "name":"فرع المعادي", "owner_login":"..", "owner_password":".." }
     * الردّ:   { "success": true, "branch_id": "br_...", "branch_code": "A" }
     */
    public function register(Request $request)
    {
        // لو السيرفر قديم بلا جدول branches (لم تُشغّل المهاجرة بعد).
        if (! Schema::hasTable('branches')) {
            return response()->json(['success' => false, 'message' => 'جدول الفروع غير مهيّأ على السيرفر.'], 503);
        }

        $data = $request->validate([
            'code'           => 'required|string|max:16',
            'name'           => 'sometimes|nullable|string|max:255',
            'owner_login'    => 'required|string|max:255',
            'owner_password' => 'required|string',
        ]);

        // تحقّق من مالك الصيدلية (email أو username) — يربط الفرع بالـ tenant.
        $login = trim($data['owner_login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $owner = \App\Models\User::where($field, $login)->first();

        if (! $owner || ! \Illuminate\Support\Facades\Hash::check($data['owner_password'], $owner->password)) {
            return response()->json(['success' => false, 'message' => 'بيانات دخول الصيدلية غير صحيحة.'], 401);
        }
        if (isset($owner->is_approved) && ! $owner->is_approved) {
            return response()->json(['success' => false, 'message' => 'حساب الصيدلية غير مفعّل بعد.'], 403);
        }

        $code = strtoupper(trim($data['code']));
        $now  = now();

        $existing = DB::table('branches')->where('code', $code)->first();

        // امنع اختطاف كود فرع مسجّل لصيدلية أخرى.
        if ($existing && $existing->user_id !== null && (int) $existing->user_id !== (int) $owner->id) {
            return response()->json(['success' => false, 'message' => 'كود الفرع مستخدم لصيدلية أخرى.'], 409);
        }

        if ($existing) {
            DB::table('branches')->where('id', $existing->id)->update([
                'name'         => $data['name'] ?? $existing->name,
                'user_id'      => $owner->id,
                'last_seen_at' => $now,
                'updated_at'   => $now,
            ]);
            $branchId = $existing->branch_id;
        } else {
            $branchId = 'br_' . Str::ulid();
            DB::table('branches')->insert([
                'branch_id'     => $branchId,
                'code'          => $code,
                'name'          => $data['name'] ?? null,
                'user_id'       => $owner->id,
                'registered_at' => $now,
                'last_seen_at'  => $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        Log::info('Branch registered', ['code' => $code, 'branch_id' => $branchId, 'owner' => $owner->id, 'new' => ! $existing]);

        return response()->json([
            'success'     => true,
            'branch_id'   => $branchId,
            'branch_code' => $code,
            'owner_uuid'  => $owner->uuid,
        ]);
    }

    /**
     * Pull: يرجّع للفرع ما تغيّر في الجداول القابلة للسحب منذ cursor لكل جدول.
     *
     * الـ cursor مركّب (keyset): { ts: updated_at, uuid: uuid } لآخر صف مستلَم. هذا
     * يمنع تخطّي صفوف تتشارك نفس الثانية (updated_at بدقّة ثانية) عند تجاوز حجم الدفعة —
     * كان الاعتماد على updated_at وحده يفقدها بصمت (مهم لجدول drugs الكبير).
     *
     * الحمولة المتوقّعة:
     * { "cursors": { "drugs": {"ts":"2026-06-21 12:00:00","uuid":"01H..."}|null, "ads": null } }
     *
     * الردّ:
     * { "success": true, "tables": { "drugs": { rows:[...], cursor:{ts,uuid}, more:bool }, ... } }
     *
     * نُترجم كل FK إلى <fk>_uuid حتى يطبّقها الفرع عبر uuid (نفس آلية push معكوسة).
     * نُرسل حتى batch_size صف لكل جدول؛ الفرع يكرّر السحب حتى يفرغ (cursor يتقدّم).
     */
    public function pull(Request $request, SyncPullQuery $pullQueries)
    {
        $data = $request->validate([
            'cursors' => 'sometimes|array',
            'until' => 'sometimes|array',
            'branch_id' => 'sometimes|nullable|string|max:64',
        ]);
        $cursors = $data['cursors'] ?? [];
        $until = $data['until'] ?? [];
        $relations = config('sync.relations', []);
        $preserveId = config('sync.preserve_id', []);
        $limit = (int) config('sync.batch_size', 200);
        $branchId = $data['branch_id'] ?? null;
        $ownerId = $pullQueries->ownerId($branchId);

        if ($branchId && Schema::hasTable('branches')) {
            DB::table('branches')->where('branch_id', $branchId)->update(['last_seen_at' => now()]);
        }

        $out = [];

        foreach ($pullQueries->tables() as $table) {
            $cursor = $cursors[$table] ?? null;
            $sinceTs = is_array($cursor) ? ($cursor['ts'] ?? null) : $cursor;
            $sinceUuid = is_array($cursor) ? ($cursor['uuid'] ?? null) : null;
            $untilCursor = $until[$table] ?? null;
            $untilTs = is_array($untilCursor) ? ($untilCursor['ts'] ?? null) : null;
            $untilUuid = is_array($untilCursor) ? ($untilCursor['uuid'] ?? null) : null;

            $query = $pullQueries->scoped($table, $branchId, $ownerId)
                ->when($sinceTs !== null, fn ($q) => $this->applyAfterCursor($q, $sinceTs, $sinceUuid))
                ->when($untilTs !== null, fn ($q) => $this->applyUntilCursor($q, $untilTs, $untilUuid))
                ->orderBy('updated_at')
                ->orderBy('uuid')
                ->limit($limit + 1);

            $rows = $query->get();
            $more = $rows->count() > $limit;
            $rows = $rows->take($limit);
            $mapped = [];
            $newCursor = $cursor;

            foreach ($rows as $row) {
                $arr = (array) $row;
                unset($arr['synced_at']);
                if (! in_array($table, $preserveId, true)) {
                    unset($arr['id']);
                }

                foreach ($relations[$table] ?? [] as $fkColumn => $referencedTable) {
                    $localFk = $arr[$fkColumn] ?? null;
                    unset($arr[$fkColumn]);
                    $arr[$fkColumn . '_uuid'] = $localFk !== null
                        ? DB::table($referencedTable)->where('id', $localFk)->value('uuid')
                        : null;
                }

                $mapped[] = $arr;
                $newCursor = ['ts' => $row->updated_at, 'uuid' => $row->uuid];
            }

            $out[$table] = ['rows' => $mapped, 'cursor' => $newCursor, 'more' => $more];
        }

        return response()->json(['success' => true, 'tables' => $out]);
    }

    public function manifest(Request $request, SyncPullQuery $pullQueries)
    {
        $data = $request->validate(['branch_id' => 'sometimes|nullable|string|max:64']);
        $branchId = $data['branch_id'] ?? null;
        $ownerId = $pullQueries->ownerId($branchId);
        return DB::transaction(function () use ($pullQueries, $branchId, $ownerId) {
            $generatedAt = now()->utc();
            $tables = [];

            foreach ($pullQueries->tables() as $table) {
                $query = $pullQueries->scoped($table, $branchId, $ownerId);
                $total = (clone $query)->count();
                $last = (clone $query)->orderByDesc('updated_at')->orderByDesc('uuid')->first(['updated_at', 'uuid']);
                $tables[$table] = [
                    'total' => $total,
                    'until' => $last ? ['ts' => $last->updated_at, 'uuid' => $last->uuid] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'version' => 1,
                'manifest_id' => (string) Str::ulid(),
                'generated_at' => $generatedAt->toIso8601String(),
                'tables' => $tables,
            ]);
        });
    }

    private function applyAfterCursor($query, string $ts, ?string $uuid): void
    {
        $query->where(function ($where) use ($ts, $uuid) {
            $where->where('updated_at', '>', $ts);
            if ($uuid !== null) {
                $where->orWhere(fn ($same) => $same->where('updated_at', $ts)->where('uuid', '>', $uuid));
            }
        });
    }

    private function applyUntilCursor($query, string $ts, ?string $uuid): void
    {
        $query->where(function ($where) use ($ts, $uuid) {
            $where->where('updated_at', '<', $ts);
            if ($uuid !== null) {
                $where->orWhere(fn ($same) => $same->where('updated_at', $ts)->where('uuid', '<=', $uuid));
            } else {
                $where->orWhere('updated_at', $ts);
            }
        });
    }

    /**
     * يعيد بناء لقطات مخزون فرع للأدوية المتأثرة بدفعة الـ push (server-maintained).
     *
     * لكل دواء في الدفعة: يجمّع كميته الحالية بهذا الفرع من user_drug_inventory ويحدّث
     * (أو يُنشئ) صف branch_inventory_snapshots. يُضبط synced_at=null + updated_at=now
     * حتى تسحبه بقية فروع المالك (pull_scoped: owner_other_branches). branch_id للّقطة
     * = 'server' (السيرفر منشئها).
     */
    private function rebuildSnapshots(string $branchId, array $invRows): void
    {
        $ownerUserId = DB::table('branches')->where('branch_id', $branchId)->value('user_id');
        if (! $ownerUserId) {
            return; // فرع غير مسجّل — لا مالك نعرفه
        }

        $drugUuids = array_values(array_unique(array_filter(array_column($invRows, 'drug_id_uuid'))));
        if (empty($drugUuids)) {
            return;
        }

        $drugIdByUuid = DB::table('drugs')->whereIn('uuid', $drugUuids)->pluck('id', 'uuid');
        $serverBranch = (string) (config('sync.server_branch_id') ?: 'server');
        $now          = now();

        foreach ($drugIdByUuid as $drugId) {
            $qty = (float) DB::table('user_drug_inventory')
                ->where('user_id', $ownerUserId)
                ->where('branch_id', $branchId)
                ->where('drug_id', $drugId)
                ->sum('quantity');

            $existing = DB::table('branch_inventory_snapshots')
                ->where('user_id', $ownerUserId)
                ->where('snapshot_branch_id', $branchId)
                ->where('drug_id', $drugId)
                ->first();

            if ($existing) {
                DB::table('branch_inventory_snapshots')->where('id', $existing->id)->update([
                    'quantity'   => $qty,
                    'updated_at' => $now,
                    'synced_at'  => null,
                    'deleted_at' => null,
                ]);
            } else {
                DB::table('branch_inventory_snapshots')->insert([
                    'uuid'               => (string) Str::ulid(),
                    'user_id'            => $ownerUserId,
                    'snapshot_branch_id' => $branchId,
                    'drug_id'            => $drugId,
                    'quantity'           => $qty,
                    'branch_id'          => $serverBranch,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                    'synced_at'          => null,
                ]);
            }
        }
    }
}
