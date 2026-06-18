<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q', '');
        $customers = Customer::where('user_id', Auth::id())
            ->when($q, fn($query) => $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%");
            }))
            ->withCount('sales')
            ->latest()
            ->paginate(15);

        return view('customers.index', compact('customers', 'q'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب',
        ]);

        $data['user_id'] = Auth::id();
        $data['code'] = Customer::generateCode(Auth::id());

        Customer::create($data);

        return redirect()->route('customers.index')
            ->with('success', 'تم إضافة العميل بنجاح!');
    }

    public function show(Customer $customer)
    {
        abort_if($customer->user_id !== Auth::id(), 403);

        $customer->load([
            'sales' => function ($q) {
                $q->with('items.drug')->latest(); 
            },
            'payments'
        ]);

        $totalBought = $customer->sales->sum('total');
        $totalPaid = $customer->sales->sum('paid') + $customer->payments->sum('amount');
        $totalDebt = $customer->sales
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining');

        return view('customers.show', compact(
            'customer',
            'totalBought',
            'totalPaid',
            'totalDebt'
        ));
    }

    public function edit(Customer $customer)
    {
        abort_if($customer->user_id !== Auth::id(), 403);
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        abort_if($customer->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $customer->update($data);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'تم تحديث بيانات العميل!');
    }

    public function destroy(Customer $customer)
    {
        abort_if($customer->user_id !== Auth::id(), 403);
        $customer->delete();
        return redirect()->route('customers.index')
            ->with('success', 'تم حذف العميل!');
    }

    // بحث AJAX للعملاء في صفحة البيع
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $customers = Customer::where('user_id', Auth::id())
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%$q%")
                    ->orWhere('code', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%");
            })
            ->select('id', 'code', 'name', 'phone', 'address', 'balance', 'credit_limit')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    // سداد فاتورة آجلة
    public function paySale(Request $request, \App\Models\Sale $sale)
    {
        abort_if($sale->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $sale->remaining,
            'payment_method' => 'required|in:cash,card,wallet',
            'notes' => 'nullable|string',
        ], [
            'amount.required' => 'المبلغ مطلوب',
            'amount.max' => 'المبلغ أكبر من المتبقي',
        ]);

        \App\Models\SalePayment::create([
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'user_id' => Auth::id(),
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'notes' => $data['notes'] ?? null,
        ]);

        // تحديث الفاتورة
        $newPaid = $sale->paid + $data['amount'];
        $newRemaining = max(0, $sale->remaining - $data['amount']);
        $newStatus = $newRemaining <= 0 ? 'paid' : 'partial';

        $sale->update([
            'paid' => $newPaid,
            'remaining' => $newRemaining,
            'payment_status' => $newStatus,
        ]);

        // تحديث رصيد العميل
        if ($sale->customer) {
            $sale->customer->decrement('balance', $data['amount']);
        }

        return back()->with('success', 'تم تسجيل الدفع بنجاح!');
    }
}