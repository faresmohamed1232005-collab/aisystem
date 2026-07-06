<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use App\Models\UserDrugInventory;
use App\Models\Drug;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
class ForecastController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $days = 90;

        $dateFrom = Carbon::today()->subDays($days - 1)->startOfDay();
        $dateTo   = Carbon::today()->endOfDay();

        // ===== أكثر المنتجات مبيعاً خلال آخر 90 يوم =====
        $topProducts = SaleItem::whereHas('sale', function ($q) use ($userId, $dateFrom, $dateTo) {
                $q->where('user_id', $userId)
                  ->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
            ->selectRaw('
                drugs.id as drug_id,
                COALESCE(drugs.name_ar, drugs.name_en) as name,
                drugs.major_units,
                SUM(sale_items.quantity * COALESCE(sale_items.qty_factor, 1)) as total_qty_sold,
                SUM(sale_items.subtotal) as total_revenue
            ')
            ->groupBy('drugs.id', 'drugs.name_ar', 'drugs.name_en', 'drugs.major_units')
            ->orderByDesc('total_qty_sold')
            ->limit(15)
            ->get();

        // ===== حساب المخزون الحالي + معدل البيع اليومي + اقتراح إعادة الطلب =====
        $recommendations = [];

        foreach ($topProducts as $product) {

            // إجمالي المخزون الحالي عبر كل الباتشات (بوحدة العلبة)
            $currentStock = UserDrugInventory::where('user_id', $userId)
                ->currentBranch()
                ->where('drug_id', $product->drug_id)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            // معدل البيع اليومي (بوحدة العلبة)
            $dailyAvg = $product->total_qty_sold / $days;

            // أيام التغطية المتبقية بالمخزون الحالي
            $daysCoverage = $dailyAvg > 0
                ? round($currentStock / $dailyAvg, 1)
                : null;

            // الكمية المقترحة لإعادة الطلب لتغطية 30 يوم قادمة
            $targetCoverageDays = 30;
            $neededForTarget = $dailyAvg * $targetCoverageDays;
            $suggestedReorder = max(0, ceil($neededForTarget - $currentStock));

            $recommendations[] = [
                'drug_id'          => $product->drug_id,
                'name'             => $product->name,
                'major_units'      => $product->major_units,
                'total_qty_sold'   => round($product->total_qty_sold, 2),
                'total_revenue'    => round($product->total_revenue, 2),
                'daily_avg'        => round($dailyAvg, 2),
                'current_stock'    => round($currentStock, 2),
                'days_coverage'    => $daysCoverage,
                'suggested_reorder'=> $suggestedReorder,
                'urgent'           => $daysCoverage !== null && $daysCoverage <= 7,
            ];
        }

        return view('forecast.index', [
            'recommendations' => $recommendations,
            'dateFrom'        => $dateFrom,
            'dateTo'          => $dateTo,
            'days'            => $days,
        ]);
    }
}