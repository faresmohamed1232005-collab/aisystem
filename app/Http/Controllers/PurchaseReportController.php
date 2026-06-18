<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // ===== الفترة =====
        $period = $request->get('period', 'month');
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($period, $request);

        $from = $dateFrom->copy()->startOfDay();
        $to   = $dateTo->copy()->endOfDay();

        // ===== استعلام أساسي للفواتير =====
        $baseQuery = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [$from, $to]);

        // ===== بطاقات الإحصائيات =====
        $totalSpent    = (clone $baseQuery)->sum('net_total');
        $totalDiscount = (clone $baseQuery)->sum('discount');
        $totalTax      = (clone $baseQuery)->sum('tax');
        $totalCount    = (clone $baseQuery)->count();
        $totalDeferred = (clone $baseQuery)
            ->where('payment_status', '!=', 'paid')
            ->sum('remaining');

        // ===== ✅ إجمالي المرتجعات في نفس الفترة =====
        $totalReturns = PurchaseReturn::where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->sum('total');

        $totalReturnsCount = PurchaseReturn::where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // ===== ✅ صافي الإنفاق بعد المرتجعات =====
        $netSpentAfterReturns = max(0, $totalSpent - $totalReturns);

        // ===== مقارنة بالفترة السابقة =====
        $diff     = $dateTo->diffInDays($dateFrom);
        $prevFrom = $dateFrom->copy()->subDays($diff + 1);
        $prevTo   = $dateFrom->copy()->subDay();

        $prevSpent = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [
                $prevFrom->startOfDay(),
                $prevTo->endOfDay()
            ])
            ->sum('net_total');

        $prevReturns = PurchaseReturn::where('user_id', $userId)
            ->whereBetween('created_at', [
                $prevFrom->startOfDay(),
                $prevTo->endOfDay()
            ])
            ->sum('total');

        $prevNetSpent = max(0, $prevSpent - $prevReturns);

        $spentChange = $prevNetSpent > 0
            ? (($netSpentAfterReturns - $prevNetSpent) / $prevNetSpent) * 100
            : ($netSpentAfterReturns > 0 ? 100 : 0);

        // ===== رسم بياني يومي (فواتير + مرتجعات) =====
        $dailyChart = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [$from, $to])
            ->selectRaw('DATE(purchase_invoices.created_at) as day, SUM(net_total) as total, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // ✅ مرتجعات يومية
        $dailyReturnsChart = PurchaseReturn::where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels = $chartSpent = $chartCount = $chartReturns = $chartNet = [];
        $cur = $dateFrom->copy()->startOfDay();

        while ($cur <= $dateTo->copy()->endOfDay()) {
            $k               = $cur->format('Y-m-d');
            $daySpent        = round($dailyChart->get($k)?->total ?? 0, 2);
            $dayReturns      = round($dailyReturnsChart->get($k)?->total ?? 0, 2);
            $chartLabels[]   = $cur->format('d/m');
            $chartSpent[]    = $daySpent;
            $chartCount[]    = $dailyChart->get($k)?->count ?? 0;
            $chartReturns[]  = $dayReturns;                          // ✅ مرتجعات يومية
            $chartNet[]      = round($daySpent - $dayReturns, 2);    // ✅ صافي يومي
            $cur->addDay();
        }

        // ===== أعلى 10 موردين =====
        $topSuppliers = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [$from, $to])
            ->whereNotNull('purchase_invoices.supplier_id')
            ->join('suppliers', 'purchase_invoices.supplier_id', '=', 'suppliers.id')
            ->selectRaw('
                suppliers.id,
                suppliers.name,
                suppliers.code,
                suppliers.balance,
                COUNT(purchase_invoices.id) as invoices_count,
                SUM(purchase_invoices.net_total) as total_spent,
                SUM(purchase_invoices.paid) as total_paid,
                SUM(purchase_invoices.remaining) as total_remaining
            ')
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.code', 'suppliers.balance')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->get();

        // ✅ أضف قيمة المرتجعات لكل مورد
        foreach ($topSuppliers as $sup) {
            $sup->total_returns = PurchaseReturn::where('user_id', $userId)
                ->where('supplier_id', $sup->id)
                ->whereBetween('created_at', [$from, $to])
                ->sum('total');
            $sup->net_spent = max(0, $sup->total_spent - $sup->total_returns);
        }

        // ===== أعلى المنتجات =====
        $topProducts = PurchaseInvoiceItem::whereHas('purchaseInvoice', function ($q) use ($userId, $from, $to) {
            $q->where('purchase_invoices.user_id', $userId)
              ->whereBetween('purchase_invoices.created_at', [$from, $to]);
        })
        ->selectRaw('
            product_name,
            category,
            SUM(quantity) as total_qty,
            SUM(subtotal) as total_cost,
            AVG(purchase_price) as avg_purchase_price,
            AVG(selling_price) as avg_selling_price,
            COUNT(*) as times_purchased
        ')
        ->groupBy('product_name', 'category')
        ->orderByDesc('total_cost')
        ->limit(10)
        ->get();

        // ===== ✅ آخر المرتجعات =====
        $recentReturns = PurchaseReturn::where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->with(['purchaseInvoice', 'supplier', 'items'])
            ->latest()
            ->limit(5)
            ->get();

        // ===== موردين بأعلى ديون =====
        $debtSuppliers = Supplier::where('user_id', $userId)
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->limit(5)
            ->get();

        // ===== حسب الفئة =====
        $categoryChart = PurchaseInvoiceItem::whereHas('purchaseInvoice', function ($q) use ($userId, $from, $to) {
            $q->where('purchase_invoices.user_id', $userId)
              ->whereBetween('purchase_invoices.created_at', [$from, $to]);
        })
        ->selectRaw('COALESCE(category, "أخرى") as category, SUM(subtotal) as total')
        ->groupBy('category')
        ->orderByDesc('total')
        ->get();

        // ===== طرق الدفع =====
        $paymentChart = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [$from, $to])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(net_total) as total')
            ->groupBy('payment_method')
            ->get();

        // ===== آخر فواتير =====
        $recentInvoices = PurchaseInvoice::where('purchase_invoices.user_id', $userId)
            ->whereBetween('purchase_invoices.created_at', [$from, $to])
            ->with(['supplier', 'items'])
            ->latest()
            ->limit(10)
            ->get();

        // ===== إجمالي الديون =====
        $totalAllDebt = Supplier::where('user_id', $userId)->sum('balance');

        return view('purchases.report', compact(
            'period',
            'periodLabel',
            'dateFrom',
            'dateTo',
            'totalSpent',
            'totalDiscount',
            'totalTax',
            'totalCount',
            'totalDeferred',
            'totalReturns',           // ✅ إجمالي المرتجعات
            'totalReturnsCount',      // ✅ عدد المرتجعات
            'netSpentAfterReturns',   // ✅ صافي الإنفاق بعد المرتجعات
            'spentChange',
            'prevSpent',
            'chartLabels',
            'chartSpent',
            'chartCount',
            'chartReturns',           // ✅ مرتجعات يومية للرسم البياني
            'chartNet',               // ✅ صافي يومي للرسم البياني
            'topSuppliers',
            'topProducts',
            'recentReturns',          // ✅ آخر المرتجعات
            'debtSuppliers',
            'categoryChart',
            'paymentChart',
            'recentInvoices',
            'totalAllDebt'
        ));
    }

    private function resolvePeriod($period, $request): array
    {
        return match ($period) {
            'today'  => [Carbon::today(), Carbon::today(), 'اليوم'],
            'week'   => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'هذا الأسبوع'],
            'month'  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
            'last10' => [Carbon::today()->subDays(9), Carbon::today(), 'آخر 10 أيام'],
            'custom' => [
                Carbon::parse($request->get('from', now()->subDays(30)->format('Y-m-d'))),
                Carbon::parse($request->get('to', now()->format('Y-m-d'))),
                'تاريخ مخصص',
            ],
            default  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
        };
    }

    public function printInvoice(PurchaseInvoice $invoice)
    {
        $invoice->load('items.product', 'supplier');
        return view('purchases.print', compact('invoice'));
    }
}