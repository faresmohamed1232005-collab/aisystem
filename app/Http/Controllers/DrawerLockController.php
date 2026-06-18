<?php

namespace App\Http\Controllers;

use App\Models\DrawerLock;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SubUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DrawerLockController extends Controller
{
    public function index()
    {
        $locks = DrawerLock::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        if (session()->has('sub_user')) {
            $currentUserName  = session('sub_user.name');
            $currentUserEmail = session('sub_user.email');
        } else {
            $currentUserName  = Auth::user()->name;
            $currentUserEmail = Auth::user()->email;
        }

        return view('drawer-lock.index', compact('locks', 'currentUserName', 'currentUserEmail'));
    }

    /* ═══════════════════════════════════════════════════
       GET /drawer-lock/expected
       ✅ بيطرح مرتجعات الكاش من المبلغ المتوقع
       ═══════════════════════════════════════════════════ */
    public function expectedAmount()
    {
        $lastLock = DrawerLock::where('user_id', Auth::id())
            ->latest()
            ->first();

        $salesQuery   = Sale::where('user_id', Auth::id())->where('payment_method', 'cash');
        $returnsQuery = SaleReturn::where('user_id', Auth::id())->where('refund_method', 'cash');

        if ($lastLock) {
            // مبيعات + مرتجعات بعد آخر تقفيل بس
            $salesQuery->where('created_at',   '>', $lastLock->created_at);
            $returnsQuery->where('created_at', '>', $lastLock->created_at);
        } else {
            // لو مفيش تقفيل خالص → مبيعات اليوم كله
            $salesQuery->whereDate('created_at',   today());
            $returnsQuery->whereDate('created_at', today());
        }

        $cashSales   = (float) $salesQuery->sum('paid');
        $cashReturns = (float) $returnsQuery->sum('total'); // ✅ الكاش المرتجع للعملاء

        $amount = max(0, $cashSales - $cashReturns);

        return response()->json([
            'amount'          => $amount,
            'cash_sales'      => $cashSales,    // للعرض التفصيلي في الـ tooltip
            'cash_returns'    => $cashReturns,  // للعرض التفصيلي
            'last_lock_time'  => $lastLock ? $lastLock->created_at->format('H:i') : null,
        ]);
    }

    /* ═══════════════════════════════════════════════════
       POST /drawer-lock
       ═══════════════════════════════════════════════════ */
    public function store(Request $request)
    {
        $request->validate([
            'locked_by_name'  => 'required|string',
            'locked_by_email' => 'required|email',
            'seller_name'     => 'required|string|max:100',
            'cash_amount'     => 'required|numeric|min:0',
            'expected_amount' => 'required|numeric|min:0',
            'opening_amount'  => 'nullable|numeric|min:0',
            'password'        => 'required|string',
            'notes'           => 'nullable|string|max:500',
        ], [
            'seller_name.required'     => 'اسم البائع مطلوب',
            'cash_amount.required'     => 'المبلغ الفعلي مطلوب',
            'expected_amount.required' => 'المبلغ المتوقع مطلوب',
            'password.required'        => 'كلمة المرور مطلوبة للتأكيد',
        ]);

        // ── التحقق من كلمة المرور ──
        $passwordOk = false;

        if (session()->has('sub_user')) {
            $subUser = SubUser::where('id', session('sub_user.id'))
                ->where('owner_id', Auth::id())
                ->where('is_active', true)
                ->first();

            if ($subUser && Hash::check($request->password, $subUser->password)) {
                $passwordOk = true;
            }
        } else {
            if (Hash::check($request->password, Auth::user()->password)) {
                $passwordOk = true;
            }
        }

        if (!$passwordOk) {
            return response()->json([
                'success' => false,
                'errors'  => ['password' => ['كلمة المرور غير صحيحة ❌']]
            ], 422);
        }

        $diff    = $request->cash_amount - $request->expected_amount;
        $opening = $request->opening_amount ?? 0;

        $lock = DrawerLock::create([
            'user_id'         => Auth::id(),
            'locked_by_name'  => $request->locked_by_name,
            'locked_by_email' => $request->locked_by_email,
            'seller_name'     => $request->seller_name,
            'cash_amount'     => $request->cash_amount,
            'expected_amount' => $request->expected_amount,
            'opening_amount'  => $opening,
            'difference'      => $diff,
            'notes'           => $request->notes,
        ]);

        $row = view('drawer-lock._row', [
            'lock'  => $lock,
            'index' => '#',
        ])->render();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل تقفيل الدرج بنجاح ✅',
            'row'     => $row,
        ]);
    }

    /* ═══════════════════════════════════════════════════
       DELETE /drawer-lock/{drawerLock}
       ═══════════════════════════════════════════════════ */
    public function destroy(DrawerLock $drawerLock)
    {
        if ($drawerLock->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $drawerLock->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العملية بنجاح',
        ]);
    }
}