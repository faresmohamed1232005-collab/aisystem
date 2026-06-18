<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReportController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // ===== تحديد الفترة =====
        $period = $request->get('period', 'today');
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($period, $request);

        // ===== إجمالي الفترة المختارة =====
        $salesQuery = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ]);

        $totalRevenue  = (clone $salesQuery)->sum('total');
        $totalDiscount = (clone $salesQuery)->sum('discount');
        $totalCount    = (clone $salesQuery)->count();

        // ===== حساب التكلفة والربح =====
        $profitData = SaleItem::whereHas('sale', function ($q) use ($userId, $dateFrom, $dateTo) {
                $q->where('user_id', $userId)
                  ->whereBetween('created_at', [
                      $dateFrom->copy()->startOfDay(),
                      $dateTo->copy()->endOfDay(),
                  ]);
            })
            ->selectRaw('
                SUM(quantity * price)                          as total_revenue,
                SUM(quantity * COALESCE(cost_price, 0))        as total_cost,
                SUM(quantity * (price - COALESCE(cost_price, 0))) as total_profit
            ')
            ->first();

        $totalCost    = $profitData->total_cost   ?? 0;
        $totalProfit  = $profitData->total_profit ?? 0;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        // ===== مقارنة مع الفترة السابقة =====
        $prevDiff    = $dateTo->diffInDays($dateFrom);
        $prevFrom    = $dateFrom->copy()->subDays($prevDiff + 1);
        $prevTo      = $dateFrom->copy()->subDay();
        $prevRevenue = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                $prevFrom->startOfDay(),
                $prevTo->endOfDay(),
            ])
            ->sum('total');

        $revenueChange = $prevRevenue > 0
            ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100
            : ($totalRevenue > 0 ? 100 : 0);

        // ===== رسم بياني: مبيعات يومية =====
        $dailyChart = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ])
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $chartLabels  = [];
        $chartRevenue = [];
        $chartCount   = [];
        $current = $dateFrom->copy()->startOfDay();
        while ($current <= $dateTo->copy()->endOfDay()) {
            $dayKey         = $current->format('Y-m-d');
            $chartLabels[]  = $current->format('d/m');
            $chartRevenue[] = round($dailyChart->get($dayKey)?->revenue ?? 0, 2);
            $chartCount[]   = $dailyChart->get($dayKey)?->count ?? 0;
            $current->addDay();
        }

        // ===== أعلى 10 أدوية مبيعاً =====
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($userId, $dateFrom, $dateTo) {
                $q->where('user_id', $userId)
                  ->whereBetween('created_at', [
                      $dateFrom->copy()->startOfDay(),
                      $dateTo->copy()->endOfDay(),
                  ]);
            })
            ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
            ->selectRaw('
                COALESCE(drugs.name_ar, drugs.name_en) as name,
                drugs.category,
                sale_items.cost_price,
                SUM(sale_items.quantity)                                          as total_qty,
                SUM(sale_items.subtotal)                                          as total_revenue,
                SUM(sale_items.quantity * COALESCE(sale_items.cost_price, 0))     as total_cost,
                SUM(sale_items.quantity * (sale_items.price - COALESCE(sale_items.cost_price, 0))) as total_profit
            ')
            ->groupBy('drugs.id', 'drugs.name_ar', 'drugs.name_en', 'drugs.category', 'sale_items.cost_price')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ===== مبيعات حسب الفئة =====
        $categoryChart = SaleItem::whereHas('sale', function ($q) use ($userId, $dateFrom, $dateTo) {
                $q->where('user_id', $userId)
                  ->whereBetween('created_at', [
                      $dateFrom->copy()->startOfDay(),
                      $dateTo->copy()->endOfDay(),
                  ]);
            })
            ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
            ->selectRaw('COALESCE(drugs.category, "أخرى") as category, SUM(sale_items.subtotal) as revenue')
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->get();

        // ===== طرق الدفع =====
        $paymentChart = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ])
            ->selectRaw('payment_method, card_type, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('payment_method', 'card_type')
            ->get();

        // ===== آخر 10 فواتير — مع تحميل العلاقات الكاملة =====
        $recentSales = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                $dateFrom->copy()->startOfDay(),
                $dateTo->copy()->endOfDay(),
            ])
            ->with(['items.drug', 'customer', 'user'])   // ✅ drug بدل product + user للصيدلية
            ->latest()
            ->limit(10)
            ->get();

        return view('sales.report', compact(
            'period', 'periodLabel', 'dateFrom', 'dateTo',
            'totalRevenue', 'totalDiscount', 'totalCount',
            'totalCost', 'totalProfit', 'profitMargin',
            'revenueChange', 'prevRevenue',
            'chartLabels', 'chartRevenue', 'chartCount',
            'topProducts', 'categoryChart', 'paymentChart',
            'recentSales'
        ));
    }

    // ===== صفحة الطباعة =====
    public function print(Sale $sale)
    {
        // ✅ تحميل بيانات الصيدلية من اليوزر المالك للفاتورة
        $sale->load(['items.drug', 'customer', 'user']);

        return view('sales.print', compact('sale'));
    }

    private function resolvePeriod($period, $request): array
    {
        return match ($period) {
            'today'  => [Carbon::today(), Carbon::today(), 'اليوم'],
            'week'   => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'هذا الأسبوع'],
            'month'  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
            'last10' => [Carbon::today()->subDays(9), Carbon::today(), 'آخر 10 أيام'],
            'custom' => [
                Carbon::parse($request->get('from', now()->subDays(7)->format('Y-m-d'))),
                Carbon::parse($request->get('to',   now()->format('Y-m-d'))),
                'تاريخ مخصص',
            ],
            default  => [Carbon::today(), Carbon::today(), 'اليوم'],
        };
    }
}