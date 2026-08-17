<?php

namespace App\Http\Controllers;

use App\Models\BranchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * إدارة الفروع (Phase 2ب) — رؤية المالك المركزية.
 *
 * الفروع تُنشأ تلقائياً عند تسجيل كل جهاز (POST /api/sync/register). هنا يحرّر المالك
 * بياناتها الوصفية وأعلام صلاحياتها، ويرى ملخّص مخزون كل فرع من لقطات المخزون.
 * السيرفر master لهذا الجدول (على الفرع تُقرأ فقط عملياً — يعاد سحبها من السيرفر).
 */
class BranchController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::id();
        $q     = $request->get('q', '');

        $branches = BranchModel::where('user_id', $owner)
            ->when($q, fn($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")->orWhere('code', 'like', "%$q%");
            }))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('branches.index', compact('branches', 'q'));
    }

    public function create()
    {
        return view('branches.create');
    }

    /**
     * يختار المالك «الفرع النشط» الذي يعمل عليه على الموقع؛ يُخزَّن في الجلسة فيُوسَم به
     * ما يُنشئه (شراء/مخزون) ويُقيَّد به العرض. تمرير فارغ = العودة للمركز (بدون فرع محدّد).
     */
    public function switch(Request $request)
    {
        $data = $request->validate(['branch_id' => 'nullable|string|max:32']);

        if (! empty($data['branch_id'])) {
            $branch = BranchModel::where('branch_id', $data['branch_id'])->first();
            abort_if(! $branch || $branch->user_id !== Auth::id(), 403);
            session([\App\Support\ActiveBranch::SESSION_KEY => $branch->branch_id]);
            $msg = 'أنت الآن تعمل على فرع «' . ($branch->name ?: $branch->code) . '».';
        } else {
            session()->forget(\App\Support\ActiveBranch::SESSION_KEY);
            $msg = 'رجعت للعرض المركزي (بدون فرع محدّد).';
        }

        return back()->with('success', $msg);
    }

    /**
     * إنشاء فرع مركزياً على السيرفر (قبل تثبيت أي جهاز). يولّد branch_id ثابت ويربطه
     * بالمالك، فيمكن للمالك ضبط مخزونه وفواتيره ثم توصيل أجهزة الفرع بالكود لاحقاً.
     *
     * code فريد عالمياً (بادئة الفواتير + مُعرّف الفرع البشري) — نمنع تكراره لأي مالك.
     * الإنشاء عبر Eloquent فيختم Syncable الـ uuid تلقائياً (شرط سحب الفرع للأجهزة).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:16', Rule::unique('branches', 'code')],
            'name'        => ['nullable', 'string', 'max:255'],
            'type'        => ['required', 'in:pharmacy,warehouse,office'],
            'governorate' => ['nullable', 'string', 'max:255'],
            'address'     => ['nullable', 'string', 'max:500'],
            'phone'       => ['nullable', 'string', 'max:30'],
        ], [
            'code.unique' => 'كود الفرع مستخدم بالفعل. اختر كوداً آخر.',
        ]);

        $branch = BranchModel::create([
            'branch_id'     => 'br_' . Str::ulid(),
            'code'          => strtoupper(trim($data['code'])),
            'name'          => $data['name'] ?? null,
            'type'          => $data['type'],
            'governorate'   => $data['governorate'] ?? null,
            'address'       => $data['address'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'user_id'       => Auth::id(),
            'status'        => 'active',
            'registered_at' => now(),
        ]);

        return redirect()->route('branches.show', $branch)
            ->with('success', 'تم إنشاء الفرع «' . ($branch->name ?: $branch->code) . '». اضبط مخزونه وفواتيره ثم وصّل أجهزته بالكود.');
    }

    public function show(BranchModel $branch)
    {
        abort_if($branch->user_id !== Auth::id(), 403);

        // ملخّص مخزون الفرع من اللقطات (يبنيها السيرفر عند كل push مخزون من الفرع).
        $items = DB::table('branch_inventory_snapshots as s')
            ->join('drugs as d', 'd.id', '=', 's.drug_id')
            ->where('s.user_id', Auth::id())
            ->where('s.snapshot_branch_id', $branch->branch_id)
            ->where('s.quantity', '>', 0)
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.quantity')
            ->limit(200)
            ->get([DB::raw('COALESCE(d.name_ar, d.name_en) as name'), 's.quantity', 's.drug_id']);

        $inventoryStats = [
            'drugs' => $items->count(),
            'units' => (float) $items->sum('quantity'),
        ];

        return view('branches.show', compact('branch', 'items', 'inventoryStats'));
    }

    public function update(Request $request, BranchModel $branch)
    {
        abort_if($branch->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'name'               => 'nullable|string|max:255',
            'type'               => 'required|in:pharmacy,warehouse,office',
            'governorate'        => 'nullable|string|max:255',
            'address'            => 'nullable|string|max:500',
            'phone'              => 'nullable|string|max:30',
            'status'             => 'required|in:active,stopped',
            'allow_transfer_out' => 'nullable|boolean',
            'allow_transfer_in'  => 'nullable|boolean',
            'allow_pricing'      => 'nullable|boolean',
            'allow_stocktake'    => 'nullable|boolean',
        ]);

        foreach (['allow_transfer_out', 'allow_transfer_in', 'allow_pricing', 'allow_stocktake'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $branch->update($data);

        return redirect()->route('branches.index')
            ->with('success', 'تم تحديث بيانات الفرع!');
    }
}
