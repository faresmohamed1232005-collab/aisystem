<?php

namespace App\Http\Controllers;

use App\Models\BranchModel;
use App\Models\Drug;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\UserDrugInventory;
use App\Support\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * تحويلات المخزون بين فروع المالك الواحد (Phase 2ب).
 *
 * دورة الحياة: المصدر ينشئ (status=draft، بلا خصم) → يعتمد (approved) → يرسل (sent،
 * خصم مخزون المصدر) → المزامنة تنقله للوجهة → الوجهة تستلم (إضافة مخزونها) أو ترفض.
 * كل جهاز يعدّل مخزون فرعه فقط (branch-scoped).
 */
class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $me    = Branch::id();
        $owner = Auth::id();
        $tab   = $request->get('tab', 'outgoing');

        $outgoing = StockTransfer::where('user_id', $owner)
            ->where('from_branch_id', $me)
            ->withCount('items')
            ->latest()
            ->paginate(15, ['*'], 'out')
            ->withQueryString();

        $incoming = StockTransfer::where('user_id', $owner)
            ->where('to_branch_id', $me)
            ->withCount('items')
            ->latest()
            ->paginate(15, ['*'], 'in')
            ->withQueryString();

        $branchNames = BranchModel::where('user_id', $owner)->pluck('name', 'branch_id');

        return view('stock-transfers.index', compact('outgoing', 'incoming', 'branchNames', 'tab', 'me'));
    }

    public function create()
    {
        $me    = Branch::id();
        $owner = Auth::id();

        $destinations = BranchModel::where('user_id', $owner)
            ->where('branch_id', '!=', $me)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['branch_id', 'code', 'name']);

        return view('stock-transfers.create', compact('destinations'));
    }

    /** بحث AJAX: باتشات دواء في مخزون الفرع الحالي (لانتقاء ما يُحوَّل). */
    public function drugBatches(Request $request)
    {
        $request->validate(['drug_id' => 'required|integer']);

        $batches = UserDrugInventory::where('user_id', Auth::id())
            ->currentBranch()
            ->where('drug_id', $request->drug_id)
            ->where('quantity', '>', 0)
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->get(['id', 'drug_id', 'expiry_date', 'quantity', 'cost_price', 'custom_price']);

        return response()->json($batches);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'to_branch_id'          => 'required|string|exists:branches,branch_id',
            'items'                 => 'required|array|min:1',
            'items.*.inventory_id'  => 'required|integer',
            'items.*.quantity'      => 'required|numeric|min:0.0001',
            'notes'                 => 'nullable|string|max:500',
        ], [
            'items.required'          => 'أضف صنفاً واحداً على الأقل للتحويل',
            'to_branch_id.required'   => 'اختر الفرع الوجهة',
            'to_branch_id.exists'     => 'الفرع الوجهة غير معروف',
        ]);

        $me    = Branch::id();
        $owner = Auth::id();

        if ($data['to_branch_id'] === $me) {
            return back()->withInput()->withErrors(['to_branch_id' => 'لا يمكن التحويل لنفس الفرع']);
        }

        // الوجهة لازم تكون فرعاً لنفس المالك.
        $dest = BranchModel::where('user_id', $owner)->where('branch_id', $data['to_branch_id'])->first();
        if (! $dest) {
            return back()->withInput()->withErrors(['to_branch_id' => 'الفرع الوجهة غير تابع لك']);
        }

        try {
            // إنشاء كمسودة (draft) بلا خصم مخزون — الخصم يتم عند الإرسال (send).
            // نتحقق من التوفّر هنا كتحذير فقط لتحسين تجربة المستخدم؛ التحقق الصارم يتكرر في send.
            $transfer = DB::transaction(function () use ($data, $me, $owner) {
                $transfer = StockTransfer::create([
                    'user_id'         => $owner,
                    'from_branch_id'  => $me,
                    'to_branch_id'    => $data['to_branch_id'],
                    'transfer_number' => StockTransfer::generateNumber($owner, Branch::code()),
                    'status'          => 'draft',
                    'notes'           => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $line) {
                    $inv = UserDrugInventory::where('user_id', $owner)
                        ->currentBranch()
                        ->where('id', $line['inventory_id'])
                        ->first();

                    if (! $inv) {
                        throw new \RuntimeException('صنف غير موجود في مخزون فرعك');
                    }

                    $qty = (float) $line['quantity'];
                    if ($qty > (float) $inv->quantity) {
                        $name = optional(Drug::find($inv->drug_id))->name_ar ?? 'الصنف';
                        throw new \RuntimeException("الكمية المطلوبة من \"{$name}\" أكبر من المتاح ({$inv->quantity})");
                    }

                    // نسجّل الباتش المصدر (دواء+صلاحية+تكلفة) دون خصم — الخصم عند الإرسال.
                    StockTransferItem::create([
                        'user_id'     => $owner,
                        'transfer_id' => $transfer->id,
                        'drug_id'     => $inv->drug_id,
                        'expiry_date' => $inv->expiry_date,
                        'quantity'    => $qty,
                        'cost_price'  => $inv->cost_price,
                    ]);
                }

                return $transfer;
            });
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('stock-transfers.index')
            ->with('success', 'تم إنشاء التحويل كمسودة. اعتمده ثم أرسله.');
    }

    /** اعتماد التحويل (المصدر فقط): draft → approved. */
    public function approve(StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->from_branch_id !== Branch::id(), 403, 'الاعتماد من فرع المصدر فقط');
        abort_if($stockTransfer->status !== 'draft', 422, 'لا يمكن اعتماد هذا التحويل');

        $stockTransfer->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'تم اعتماد التحويل. يمكنك الآن إرساله.');
    }

    /** إرسال التحويل (المصدر فقط): approved → sent + خصم مخزون المصدر (نقطة الخصم الفعلية). */
    public function send(StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->from_branch_id !== Branch::id(), 403, 'الإرسال من فرع المصدر فقط');
        abort_if($stockTransfer->status !== 'approved', 422, 'لا يمكن إرسال هذا التحويل');

        $owner = Auth::id();

        try {
            DB::transaction(function () use ($stockTransfer, $owner) {
                foreach ($stockTransfer->items as $item) {
                    // نختار باتش المصدر المطابق (نفس الدواء+الصلاحية) بقفل للتحديث.
                    // ملاحظة: expiry_date مُحوَّل لكائن Carbon في الـ item، لذا نقارن التاريخ فقط
                    // (whereDate) لتفادي عدم تطابق '2027-01-01' مع '2027-01-01 00:00:00'.
                    $inv = UserDrugInventory::where('user_id', $owner)
                        ->currentBranch()
                        ->where('drug_id', $item->drug_id)
                        ->when(
                            $item->expiry_date !== null,
                            fn ($q) => $q->whereDate('expiry_date', $item->expiry_date),
                            fn ($q) => $q->whereNull('expiry_date')
                        )
                        ->lockForUpdate()
                        ->first();

                    $qty = (float) $item->quantity;
                    if (! $inv || $qty > (float) $inv->quantity) {
                        $name = optional(Drug::find($item->drug_id))->name_ar ?? 'الصنف';
                        $available = $inv ? $inv->quantity : 0;
                        throw new \RuntimeException("الكمية المطلوبة من \"{$name}\" أكبر من المتاح ({$available})");
                    }

                    $inv->quantity = (float) $inv->quantity - $qty;
                    $inv->save();
                }

                $stockTransfer->update([
                    'status'  => 'sent',
                    'sent_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('stock-transfers.show', $stockTransfer)
                ->withErrors(['send' => $e->getMessage()]);
        }

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'تم إرسال التحويل وخصم مخزون فرعك!');
    }

    public function show(StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        $stockTransfer->load('items.drug:id,name_ar,name_en');

        $branchNames = BranchModel::where('user_id', Auth::id())->pluck('name', 'branch_id');

        return view('stock-transfers.show', [
            'transfer'    => $stockTransfer,
            'branchNames' => $branchNames,
            'me'          => Branch::id(),
        ]);
    }

    /** شاشة استلام تحويل وارد (الوجهة فقط). */
    public function receiveForm(StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->to_branch_id !== Branch::id(), 403, 'هذا التحويل ليس موجّهاً لفرعك');
        abort_if($stockTransfer->status !== 'sent', 422, 'لا يمكن استلام هذا التحويل');

        $stockTransfer->load('items.drug:id,name_ar,name_en');

        return view('stock-transfers.receive', ['transfer' => $stockTransfer]);
    }

    public function confirmReceive(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->to_branch_id !== Branch::id(), 403);
        abort_if($stockTransfer->status !== 'sent', 422, 'لا يمكن استلام هذا التحويل');

        $data = $request->validate([
            'items'                => 'required|array',
            'items.*.received'     => 'required|numeric|min:0',
            'items.*.damaged'      => 'nullable|numeric|min:0',
            'notes'                => 'nullable|string|max:500',
        ]);

        $owner = Auth::id();
        $me    = Branch::id();

        DB::transaction(function () use ($stockTransfer, $data, $owner, $me) {
            foreach ($stockTransfer->items as $item) {
                $line     = $data['items'][$item->id] ?? null;
                $received = (float) ($line['received'] ?? 0);
                $damaged  = (float) ($line['damaged'] ?? 0);

                if ($received > 0) {
                    // أضف لمخزون فرع الوجهة (نفس الباتش: دواء+صلاحية+تكلفة).
                    $inv = UserDrugInventory::firstOrNew([
                        'user_id'     => $owner,
                        'branch_id'   => $me,
                        'drug_id'     => $item->drug_id,
                        'expiry_date' => $item->expiry_date,
                    ]);
                    $inv->quantity = (float) ($inv->quantity ?? 0) + $received;
                    if ($item->cost_price !== null) {
                        $inv->cost_price = $item->cost_price;
                    }
                    $inv->save();
                }

                $item->received_quantity = $received;
                $item->damaged_quantity  = $damaged;
                $item->save();
            }

            $stockTransfer->status      = 'received';
            $stockTransfer->received_at = now();
            if (! empty($data['notes'])) {
                $stockTransfer->notes = trim(($stockTransfer->notes ? $stockTransfer->notes . ' | ' : '') . 'استلام: ' . $data['notes']);
            }
            $stockTransfer->save();
        });

        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'تم استلام التحويل وإضافته لمخزون فرعك!');
    }

    public function reject(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->to_branch_id !== Branch::id(), 403);
        abort_if($stockTransfer->status !== 'sent', 422, 'لا يمكن رفض هذا التحويل');

        $data = $request->validate(['reject_reason' => 'required|string|max:500']);

        $stockTransfer->update([
            'status'        => 'rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        // ملاحظة: إرجاع مخزون المصدر يتم من جهة فرع المصدر (يرى التحويل مرفوضاً) — Phase لاحقة.
        return redirect()->route('stock-transfers.show', $stockTransfer)
            ->with('success', 'تم رفض التحويل.');
    }

    /** حذف مسودة تحويل (المصدر فقط، على حالة draft — قبل الاعتماد/الإرسال). */
    public function destroy(StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->user_id !== Auth::id(), 403);
        abort_if($stockTransfer->from_branch_id !== Branch::id(), 403, 'الحذف من فرع المصدر فقط');
        abort_if($stockTransfer->status !== 'draft', 422, 'لا يمكن حذف تحويل بعد اعتماده');

        $stockTransfer->delete(); // soft delete (Syncable)

        return redirect()->route('stock-transfers.index')
            ->with('success', 'تم حذف المسودة.');
    }

    /** اقتراح فرع بديل: فروع أخرى لديها مخزون من دواء (من لقطات المخزون). */
    public function alternatives(Request $request)
    {
        $request->validate(['drug_id' => 'required|integer']);
        $me    = Branch::id();
        $owner = Auth::id();

        $rows = DB::table('branch_inventory_snapshots as s')
            ->leftJoin('branches as b', 'b.branch_id', '=', 's.snapshot_branch_id')
            ->where('s.user_id', $owner)
            ->where('s.snapshot_branch_id', '!=', $me)
            ->where('s.drug_id', $request->drug_id)
            ->where('s.quantity', '>', 0)
            ->whereNull('s.deleted_at')
            ->orderByDesc('s.quantity')
            ->get([
                's.snapshot_branch_id',
                'b.name as branch_name',
                'b.code as branch_code',
                's.quantity',
            ]);

        return response()->json($rows);
    }
}
