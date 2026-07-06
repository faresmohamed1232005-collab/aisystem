<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        // حماية: السيرفر master للجداول pull-only (الكتالوج/الحسابات: drugs, ads,
        // users, sub_users). لا نسمح لفرع بالكتابة فوقها عبر push حتى لو أرسلها.
        $pullOnly = array_diff(config('sync.pull', []), config('sync.push', []));

        $summary = [];

        try {
            DB::transaction(function () use ($order, $tables, $repo, $pullOnly, &$summary) {
                // نعالج بترتيب التبعيات (الأب قبل الابن) لا بترتيب وصول المفاتيح.
                foreach ($order as $table) {
                    if (in_array($table, $pullOnly, true)) {
                        continue; // pull-only — يُتجاهل عند الـ push (السيرفر master)
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
    public function pull(Request $request)
    {
        $data    = $request->validate([
            'cursors'   => 'sometimes|array',
            'branch_id' => 'sometimes|nullable|string|max:64',
        ]);
        $cursors = $data['cursors'] ?? [];

        $pullable   = config('sync.pull', []);
        $relations  = config('sync.relations', []);
        $preserveId = config('sync.preserve_id', []);
        $limit      = (int) config('sync.batch_size', 200);

        // حسابات الفرع مُخصَّصة للـ tenant المالك: نحلّ user_id من branch_id.
        $ownerUserId = null;
        if (! empty($data['branch_id']) && Schema::hasTable('branches')) {
            $ownerUserId = DB::table('branches')->where('branch_id', $data['branch_id'])->value('user_id');
            DB::table('branches')->where('branch_id', $data['branch_id'])->update(['last_seen_at' => now()]);
        }

        $out = [];

        foreach ($pullable as $table) {
            $cursor   = $cursors[$table] ?? null;
            $sinceTs  = is_array($cursor) ? ($cursor['ts'] ?? null) : $cursor; // توافق مع cursor نصّي قديم
            $sinceId  = is_array($cursor) ? ($cursor['uuid'] ?? null) : null;

            // keyset: updated_at > ts  OR  (updated_at = ts AND uuid > lastUuid)
            $query = DB::table($table)
                ->when($sinceTs !== null, function ($q) use ($sinceTs, $sinceId) {
                    $q->where(function ($w) use ($sinceTs, $sinceId) {
                        $w->where('updated_at', '>', $sinceTs);
                        if ($sinceId !== null) {
                            $w->orWhere(function ($e) use ($sinceTs, $sinceId) {
                                $e->where('updated_at', '=', $sinceTs)
                                  ->where('uuid', '>', $sinceId);
                            });
                        }
                    });
                });

            // تخصيص الحسابات للفرع: فقط الصيدلية المالكة وموظفيها (لا حسابات tenants أخرى).
            if ($table === 'users') {
                $query->where('id', $ownerUserId ?? -1);
            } elseif ($table === 'sub_users') {
                $query->where('owner_id', $ownerUserId ?? -1);
            } elseif (in_array($table, config('sync.pull_owner_scoped', []), true)) {
                // بيانات ماستر خاصة بالمالك (تعاقدات/تأمين): تُسحب لفروع نفس المالك فقط.
                $query->where('user_id', $ownerUserId ?? -1);
            }

            $query->orderBy('updated_at')
                ->orderBy('uuid') // uuid (ULID) فريد وثابت عبر الطرفين → tiebreaker مستقر
                ->limit($limit);

            $rows   = $query->get();
            $fkMap  = $relations[$table] ?? [];
            $keepId = in_array($table, $preserveId, true); // users: نرسل id ليُحفظ على الفرع
            $mapped = [];
            $newCursor = $cursor;

            foreach ($rows as $row) {
                $arr = (array) $row;
                unset($arr['synced_at']); // synced_at حقل محلي بحت
                if (! $keepId) {
                    unset($arr['id']); // id السيرفر لا معنى له للفرع (الربط عبر uuid)
                }

                // ترجمة الـ FK: id (على السيرفر) → <fk>_uuid
                foreach ($fkMap as $fkColumn => $referencedTable) {
                    $localFk = $arr[$fkColumn] ?? null;
                    unset($arr[$fkColumn]);
                    $arr[$fkColumn . '_uuid'] = $localFk !== null
                        ? DB::table($referencedTable)->where('id', $localFk)->value('uuid')
                        : null;
                }

                $mapped[]  = $arr;
                // آخر صف يحدّد الـ cursor الجديد (الصفوف مرتّبة تصاعدياً بـ updated_at,uuid).
                $newCursor = ['ts' => $row->updated_at, 'uuid' => $row->uuid];
            }

            $out[$table] = [
                'rows'   => $mapped,
                'cursor' => $newCursor,
                'more'   => $rows->count() === $limit, // هل توجد دفعات أخرى؟
            ];
        }

        return response()->json(['success' => true, 'tables' => $out]);
    }
}
