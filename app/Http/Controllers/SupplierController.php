<?php
namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q', '');
        $suppliers = Supplier::where('user_id', Auth::id())
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%")
                    ->orWhere('company', 'like', "%$q%");
            }))
            ->withCount('purchaseInvoices')
            ->latest()->paginate(15);

        return view('suppliers.index', compact('suppliers', 'q'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'nullable|email',
            'address'    => 'nullable|string',
            'company'    => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
        ], ['name.required' => 'اسم المورد مطلوب']);

        $data['user_id'] = Auth::id();
        $data['code']    = Supplier::generateCode(Auth::id());
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'تم إضافة المورد بنجاح!');
    }

    public function show(Supplier $supplier)
    {
        abort_if($supplier->user_id !== Auth::id(), 403);

        $supplier->load([
            'purchaseInvoices' => fn($q) => $q->with('items')->latest(),
        ]);

        // ══════════════════════════════════════════════════════
        //  الإحصائيات المالية
        //
        //  - totalPurchased : مجموع كل الفواتير
        //  - totalPaid      : مجموع ما دُفع فعلاً (نقدي) من الفواتير
        //  - totalReturns   : مجموع المرتجعات (balance method فقط)
        //
        //  - totalDebt = supplier->balance  ← مصدر الحقيقة الوحيد
        //    لأنه يتحدث في كل عملية:
        //    • شراء مؤجل   → balance += remaining
        //    • سداد نقدي   → balance -= amount
        //    • مرتجع(خصم) → balance -= total
        //    ويمكن أن يكون سالباً = رصيد لصالحك
        // ══════════════════════════════════════════════════════
        $totalPurchased = round($supplier->purchaseInvoices->sum('net_total'), 2);
        $totalPaid      = round($supplier->purchaseInvoices->sum('paid'), 2);
        $totalDebt      = $supplier->balance; // موجب = عليك | سالب = لصالحك

        // ── مرتجعات الشراء ──
        $purchaseReturns = PurchaseReturn::where('supplier_id', $supplier->id)
            ->where('user_id', Auth::id())
            ->with(['purchaseInvoice', 'items'])
            ->latest()
            ->get();

        return view('suppliers.show', compact(
            'supplier',
            'totalPurchased',
            'totalPaid',
            'totalDebt',
            'purchaseReturns'
        ));
    }

    public function edit(Supplier $supplier)
    {
        abort_if($supplier->user_id !== Auth::id(), 403);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_if($supplier->user_id !== Auth::id(), 403);
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'email'      => 'nullable|email',
            'address'    => 'nullable|string',
            'company'    => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
        ]);
        $supplier->update($data);
        return redirect()->route('suppliers.show', $supplier)->with('success', 'تم تحديث بيانات المورد!');
    }

    public function destroy(Supplier $supplier)
    {
        abort_if($supplier->user_id !== Auth::id(), 403);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'تم حذف المورد!');
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $suppliers = Supplier::where('user_id', Auth::id())
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%")
                    ->orWhere('company', 'like', "%$q%");
            })
            ->select('id', 'code', 'name', 'phone', 'company', 'balance')
            ->limit(10)->get();

        return response()->json($suppliers);
    }

    public function payInvoice(Request $request, PurchaseInvoice $invoice)
    {
        abort_if($invoice->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . $invoice->remaining,
            'payment_method' => 'required|in:cash,card,wallet,transfer',
            'notes'          => 'nullable|string',
        ]);

        PurchasePayment::create([
            'purchase_invoice_id' => $invoice->id,
            'supplier_id'         => $invoice->supplier_id,
            'user_id'             => Auth::id(),
            'amount'              => $data['amount'],
            'payment_method'      => $data['payment_method'],
            'notes'               => $data['notes'] ?? null,
        ]);

        $newPaid      = $invoice->paid + $data['amount'];
        $newRemaining = max(0, $invoice->remaining - $data['amount']);

        $invoice->update([
            'paid'           => $newPaid,
            'remaining'      => $newRemaining,
            'payment_status' => $newRemaining <= 0 ? 'paid' : 'partial',
        ]);

        if ($invoice->supplier) {
            $invoice->supplier->decrement('balance', $data['amount']);
        }

        return back()->with('success', 'تم تسجيل الدفع بنجاح!');
    }
}