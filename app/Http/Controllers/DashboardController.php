<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use App\Models\UserDrugInventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\PurchaseInvoice;
use App\Models\PendingOrder;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $userId = $user->id;

        // ========== الإحصائيات الأساسية ==========
        $totalProducts = UserDrugInventory::where('user_id', $userId)->currentBranch()->count();

        $lowStock = UserDrugInventory::where('user_id', $userId)
            ->currentBranch()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= min_quantity')
            ->count();

        $expiringSoon = UserDrugInventory::where('user_id', $userId)
            ->currentBranch()
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>=', today())
            ->count();

        $todaySales = Sale::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->sum('total');

        // ========== مقارنة مبيعات اليوم بالأمس ==========
        $yesterdaySales = Sale::where('user_id', $userId)
            ->whereDate('created_at', today()->subDay())
            ->sum('total');

        $todayCount     = Sale::where('user_id', $userId)->whereDate('created_at', today())->count();
        $yesterdayCount = Sale::where('user_id', $userId)->whereDate('created_at', today()->subDay())->count();

        // ========== مقارنة هذا الأسبوع بالأسبوع الماضي ==========
        $thisWeekSales = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('total');

        $lastWeekSales = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->sum('total');

        $thisWeekCount = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $lastWeekCount = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->count();

        // ========== رسم بياني مبيعات آخر 7 أيام ==========
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $day = today()->subDays($i);
            $amt = Sale::where('user_id', $userId)->whereDate('created_at', $day)->sum('total');
            $last7Days->push([
                'label' => $day->format('D d/m'),
                'total' => round($amt, 2),
            ]);
        }
        $chartDayLabels = $last7Days->pluck('label');
        $chartDayTotals = $last7Days->pluck('total');

        // ========== أعلى 5 منتجات مبيعاً ==========
        $topProducts = SaleItem::whereHas('sale', fn($q) => $q->where('user_id', $userId))
            ->select(
                'drug_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->groupBy('drug_id')
            ->orderByDesc('total_qty')
            ->with('drug:id,name_ar,name_en,category')
            ->limit(5)
            ->get();

        // ========== أعلى 5 فئات مبيعاً ==========
        $topCategories = SaleItem::whereHas('sale', fn($q) => $q->where('user_id', $userId))
            ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
            ->select(
                DB::raw('COALESCE(drugs.category, "أخرى") as category'),
                DB::raw('SUM(sale_items.quantity) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('category')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // ========== آخر 5 فواتير مبيعات ==========
        $recentSales = Sale::where('user_id', $userId)
            ->with(['customer', 'items'])
            ->latest()
            ->limit(5)
            ->get();

        // ========== آخر 5 فواتير مشتريات ==========
        $recentPurchases = PurchaseInvoice::where('user_id', $userId)
            ->with(['supplier', 'items'])
            ->latest()
            ->limit(5)
            ->get();

        // ========== التوصيل: هذا الأسبوع ==========
        $deliveryThisWeek = PendingOrder::where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->select('delivery_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('delivery_type')
            ->pluck('cnt', 'delivery_type');

        $deliveryCountThisWeek = $deliveryThisWeek->get('delivery', 0);
        $storeCountThisWeek    = $deliveryThisWeek->get('store', 0);

        // ========== التوصيل: الأسبوع الماضي ==========
        $deliveryLastWeek = PendingOrder::where('user_id', $userId)
            ->whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->select('delivery_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('delivery_type')
            ->pluck('cnt', 'delivery_type');

        $deliveryCountLastWeek = $deliveryLastWeek->get('delivery', 0);
        $storeCountLastWeek    = $deliveryLastWeek->get('store', 0);

        // ========== إجمالي التوصيل كل الوقت ==========
        $allDeliveryStats = PendingOrder::where('user_id', $userId)
            ->select('delivery_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('delivery_type')
            ->pluck('cnt', 'delivery_type');

        $totalDeliveryOrders = $allDeliveryStats->get('delivery', 0);
        $totalStoreOrders    = $allDeliveryStats->get('store', 0);

        // ========== إشعارات ==========
        $this->generateNotifications($userId);
        $notifications = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->latest()
            ->take(5)
            ->get();

        // ========== إجماليات إضافية ==========
        $totalCustomers = \App\Models\Customer::where('user_id', $userId)->count();
        $totalSuppliers = \App\Models\Supplier::where('user_id', $userId)->count();
        $pendingOrders  = PendingOrder::where('user_id', $userId)->where('status', 'pending')->count();
        $totalDebt      = \App\Models\Customer::where('user_id', $userId)->sum('balance');
        $monthSales     = Sale::where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        return view('dashboard', compact(
            'totalProducts', 'lowStock', 'expiringSoon', 'todaySales', 'notifications',
            'yesterdaySales', 'todayCount', 'yesterdayCount',
            'thisWeekSales', 'lastWeekSales', 'thisWeekCount', 'lastWeekCount',
            'chartDayLabels', 'chartDayTotals',
            'topProducts', 'topCategories',
            'recentSales', 'recentPurchases',
            'deliveryCountThisWeek', 'storeCountThisWeek',
            'deliveryCountLastWeek', 'storeCountLastWeek',
            'totalDeliveryOrders', 'totalStoreOrders',
            'totalCustomers', 'totalSuppliers', 'pendingOrders', 'totalDebt', 'monthSales'
        ));
    }

    private function generateNotifications($userId): void
    {
        // منتجات قاربت الانتهاء
        $expiring = UserDrugInventory::where('user_id', $userId)
            ->currentBranch()
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>=', today())
            ->with('drug')
            ->get();

        foreach ($expiring as $inv) {
            $name = $inv->drug?->name_ar ?? $inv->drug?->name_en ?? 'دواء';
            Notification::firstOrCreate(
                ['user_id' => $userId, 'title' => 'قرب انتهاء صلاحية: ' . $name, 'is_read' => false],
                [
                    'message' => 'المنتج ' . $name . ' سينتهي في ' . $inv->expiry_date->format('Y-m-d'),
                    'type'    => 'warning',
                ]
            );
        }

        // مخزون منخفض
        $lowStockItems = UserDrugInventory::where('user_id', $userId)
            ->currentBranch()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= min_quantity')
            ->with('drug')
            ->get();

        foreach ($lowStockItems as $inv) {
            $name = $inv->drug?->name_ar ?? $inv->drug?->name_en ?? 'دواء';
            Notification::firstOrCreate(
                ['user_id' => $userId, 'title' => 'نقص في المخزون: ' . $name, 'is_read' => false],
                [
                    'message' => 'الكمية المتبقية من ' . $name . ' هي ' . $inv->quantity . ' فقط',
                    'type'    => 'danger',
                ]
            );
        }
    }
}