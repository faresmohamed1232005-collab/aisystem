<?php

namespace App\Http\Controllers;

use App\Models\BranchModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
