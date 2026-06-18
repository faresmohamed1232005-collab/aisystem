<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeTransaction;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /* ══════════════════════════════════════════════════
       INDEX — قائمة الموظفين مع الفلتر الشهري
    ══════════════════════════════════════════════════ */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $month  = (int) $request->input('month', now()->month);
        $year   = (int) $request->input('year',  now()->year);

        $employees = Employee::where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // أضف بيانات كل موظف للشهر المختار
        $employees->each(function (Employee $emp) use ($month, $year) {
            $txns = $emp->monthTransactions($month, $year);

            $emp->month_bonus     = $txns->where('type', 'bonus')->sum('amount');
            $emp->month_deduction = $txns->where('type', 'deduction')->sum('amount');
            $emp->month_advance   = $txns->where('type', 'advance')->sum('amount');
            $emp->month_absence   = $txns->where('type', 'absence')->sum('amount');
            $emp->month_absence_days = $txns->where('type', 'absence')->sum('absence_days');
            $emp->net_salary      = $emp->base_salary
                                  + $emp->month_bonus
                                  - $emp->month_deduction
                                  - $emp->month_advance
                                  - $emp->month_absence;
            $emp->salary_paid     = $emp->salaryPaid($month, $year);
        });

        // إحصائيات الشهر
        $totalBaseSalaries = $employees->sum('base_salary');
        $totalNetSalaries  = $employees->sum('net_salary');
        $totalPaid         = $employees->where('salary_paid', true)->count();
        $totalEmployees    = $employees->count();

        // قائمة السنوات للفلتر
        $years = collect(range(now()->year, now()->year - 3))->values();

        return view('employees.index', compact(
            'employees', 'month', 'year', 'years',
            'totalBaseSalaries', 'totalNetSalaries',
            'totalPaid', 'totalEmployees'
        ));
    }

    /* ══════════════════════════════════════════════════
       STORE — إضافة موظف جديد
    ══════════════════════════════════════════════════ */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'job_title'   => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'base_salary' => 'required|numeric|min:1',
            'hired_at'    => 'required|date',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $data['user_id'] = Auth::id();
        $data['status']  = 'active';

        Employee::create($data);

        return redirect()->route('employees.index')
            ->with('success', 'تم إضافة الموظف بنجاح ✅');
    }

    /* ══════════════════════════════════════════════════
       DESTROY — حذف موظف
    ══════════════════════════════════════════════════ */
    public function destroy(Employee $employee)
    {
        abort_if($employee->user_id !== Auth::id(), 403);
        $employee->update(['status' => 'inactive']); // soft delete منطقي
        return redirect()->route('employees.index')
            ->with('success', 'تم إيقاف الموظف بنجاح');
    }

    /* ══════════════════════════════════════════════════
       ADD TRANSACTION — إضافة إجراء (خصم/بونص/سلفة/غياب)
    ══════════════════════════════════════════════════ */
    public function addTransaction(Request $request, Employee $employee)
    {
        abort_if($employee->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'type'         => 'required|in:bonus,deduction,advance,absence',
            'amount'       => 'required_unless:type,absence|nullable|numeric|min:0',
            'absence_days' => 'required_if:type,absence|nullable|integer|min:1|max:31',
            'month'        => 'required|integer|between:1,12',
            'year'         => 'required|integer|min:2020',
            'notes'        => 'nullable|string|max:500',
        ]);

        // لو غياب → احسب المبلغ تلقائياً (راتب يومي × أيام الغياب)
        if ($data['type'] === 'absence') {
            $dailyRate    = $employee->base_salary / 30;
            $data['amount'] = round($dailyRate * $data['absence_days'], 2);
        }

        EmployeeTransaction::create([
            'employee_id'  => $employee->id,
            'user_id'      => Auth::id(),
            'type'         => $data['type'],
            'amount'       => $data['amount'],
            'absence_days' => $data['absence_days'] ?? 0,
            'month'        => $data['month'],
            'year'         => $data['year'],
            'notes'        => $data['notes'] ?? null,
        ]);

        return redirect()->route('employees.index', [
            'month' => $data['month'],
            'year'  => $data['year'],
        ])->with('success', 'تم إضافة الإجراء بنجاح ✅');
    }

    /* ══════════════════════════════════════════════════
       PAY SALARY — صرف الراتب وتسجيله في المصروفات
    ══════════════════════════════════════════════════ */
    public function paySalary(Request $request, Employee $employee)
    {
        abort_if($employee->user_id !== Auth::id(), 403);

        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year',  now()->year);

        // لا تصرف مرتين
        if ($employee->salaryPaid($month, $year)) {
            return redirect()->route('employees.index', compact('month', 'year'))
                ->with('error', 'تم صرف الراتب بالفعل لهذا الشهر!');
        }

        $net = $employee->netSalary($month, $year);

        DB::transaction(function () use ($employee, $month, $year, $net) {
            // 1. سجّل في المصروفات
            $monthName = \Carbon\Carbon::create()->month($month)->locale('ar')->isoFormat('MMMM');
            $expense = Expense::create([
                'user_id'      => Auth::id(),
                'title'        => "راتب {$employee->name} - {$monthName} {$year}",
                'category'     => 'رواتب',
                'amount'       => $net,
                'expense_date' => \Carbon\Carbon::create($year, $month)->endOfMonth()->toDateString(),
                'notes'        => "صرف تلقائي من نظام الموظفين",
            ]);

            // 2. سجّل معاملة الراتب
            EmployeeTransaction::create([
                'employee_id' => $employee->id,
                'user_id'     => Auth::id(),
                'type'        => 'salary',
                'amount'      => $net,
                'month'       => $month,
                'year'        => $year,
                'expense_id'  => $expense->id,
                'notes'       => "صافي الراتب بعد الخصومات والإضافات",
            ]);
        });

        return redirect()->route('employees.index', compact('month', 'year'))
            ->with('success', "✅ تم صرف راتب {$employee->name} بمبلغ " . number_format($net, 2) . " جنيه وتسجيله في المصروفات");
    }

    /* ══════════════════════════════════════════════════
       DELETE TRANSACTION — حذف إجراء
    ══════════════════════════════════════════════════ */
    public function deleteTransaction(EmployeeTransaction $transaction)
    {
        abort_if($transaction->user_id !== Auth::id(), 403);
        abort_if($transaction->type === 'salary', 403, 'لا يمكن حذف سجل راتب مدفوع');

        $month = $transaction->month;
        $year  = $transaction->year;
        $transaction->delete();

        return redirect()->route('employees.index', compact('month', 'year'))
            ->with('success', 'تم حذف الإجراء بنجاح');
    }
}