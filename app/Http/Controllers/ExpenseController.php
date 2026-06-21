<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * عرض صفحة المصروفات مع الفلتر والتقرير
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $month = $request->input('month', now()->month);
        $year  = $request->input('year',  now()->year);

        // ── جلب المصروفات المفلترة ──
        $expenses = Expense::where('user_id', $userId)
            ->forMonth($month, $year)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        // ── إجمالي الشهر ──
        $totalMonth = $expenses->sum('amount');

        // ── إجمالي السنة ──
        $totalYear = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->sum('amount');

        // ── تقرير حسب الفئة ──
        $byCategory = $expenses
            ->groupBy('category')
            ->map(fn($g) => $g->sum('amount'))
            ->sortDesc();

        // ── إجمالي كل الأوقات ──
        $totalAll = Expense::where('user_id', $userId)->sum('amount');

        // ── قائمة السنوات المتاحة (للفلتر) ──
        $years = Expense::where('user_id', $userId)
            ->selectRaw(\App\Support\Sql::year('expense_date') . ' as yr')
            ->distinct()
            ->orderByDesc('yr')
            ->pluck('yr')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $categories = Expense::$categories;

        return view('expenses.index', compact(
            'expenses', 'totalMonth', 'totalYear',
            'totalAll', 'byCategory', 'categories',
            'month', 'year', 'years'
        ));
    }

    /**
     * تخزين مصروف جديد
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $data['user_id'] = Auth::id();

        Expense::create($data);

        return redirect()->route('expenses.index')
            ->with('success', 'تم إضافة المصروف بنجاح ✅');
    }

    /**
     * حذف مصروف
     */
    public function destroy(Expense $expense)
    {
        // تأكد إن المصروف ده تبع اليوزر ده
        if ($expense->user_id !== Auth::id()) {
            abort(403);
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'تم حذف المصروف بنجاح 🗑️');
    }
}