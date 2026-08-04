<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Ad;
use Illuminate\Support\Facades\Storage;
class SuperAdminController extends Controller
{
    public function index(Request $request)
    {
        // ── جلب المدن حسب المحافظة المختارة ──
        $cities = $request->gov
            ? User::where('governorate', $request->gov)
                ->distinct()->orderBy('city')->pluck('city')
            : collect();

        $users = User::query()
            ->when(
                $request->search,
                fn($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('pharmacy_name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%")
            )
            ->when($request->status === 'approved', fn($q) => $q->where('is_approved', true))
            ->when($request->status === 'unapproved', fn($q) => $q->where('is_approved', false))
            ->when($request->gov, fn($q) => $q->where('governorate', $request->gov))
            ->when($request->city, fn($q) => $q->where('city', $request->city))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $govs = User::distinct()->orderBy('governorate')->pluck('governorate');

        // ── بيانات المدن لكل محافظة (JSON لـ JS) ──
        $govCities = User::select('governorate', 'city')
            ->distinct()
            ->orderBy('city')
            ->get()
            ->groupBy('governorate')
            ->map(fn($rows) => $rows->pluck('city')->values());

        $stats = [
            'total' => User::count(),
            'approved' => User::where('is_approved', true)->count(),
            'pending' => User::where('is_approved', false)->count(),
            'today' => User::whereDate('created_at', today())->count(),
        ];

        $ads = Ad::latest()->get(); // أضف هذا السطر

        return view('super-admin.index', compact(
            'users',
            'govs',
            'cities',
            'govCities',
            'stats',
            'ads'
        ));
    }

    public function pharmacyReport(User $user, Request $request)
    {
        $period = $request->get('period', 'month');
        [$dateFrom, $dateTo, $periodLabel] = $this->resolvePeriod($period, $request);

        $dateRange = [
            $dateFrom->copy()->startOfDay(),
            $dateTo->copy()->endOfDay(),
        ];

        // ── إجماليات ──
        $salesBase = Sale::where('user_id', $user->id)->whereBetween('created_at', $dateRange);

        $totalRevenue = (clone $salesBase)->sum('total');
        $totalDiscount = (clone $salesBase)->sum('discount');
        $totalCount = (clone $salesBase)->count();

        $profitData = SaleItem::whereHas(
            'sale',
            fn($q) =>
            $q->where('user_id', $user->id)->whereBetween('created_at', $dateRange)
        )
            ->selectRaw('
            SUM(quantity * price)                                           as total_revenue,
            SUM(quantity * COALESCE(cost_price,0))                          as total_cost,
            SUM(quantity * (price - COALESCE(cost_price,0)))                as total_profit
        ')
            ->first();

        $totalCost = $profitData->total_cost ?? 0;
        $totalProfit = $profitData->total_profit ?? 0;
        $profitMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;

        // ── رسم بياني يومي ──
        $dailyChart = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', $dateRange)
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue, COUNT(*) as cnt')
            ->groupBy('day')->orderBy('day')->get()->keyBy('day');

        $chartLabels = $chartRevenue = $chartCount = [];
        $cur = $dateFrom->copy()->startOfDay();
        while ($cur <= $dateTo->copy()->endOfDay()) {
            $k = $cur->format('Y-m-d');
            $chartLabels[] = $cur->format('d/m');
            $chartRevenue[] = round($dailyChart->get($k)?->revenue ?? 0, 2);
            $chartCount[] = $dailyChart->get($k)?->cnt ?? 0;
            $cur->addDay();
        }

        // ── كل الأدوية المباعة (paginated 15) ──
        $allDrugs = SaleItem::whereHas(
            'sale',
            fn($q) =>
            $q->where('user_id', $user->id)->whereBetween('created_at', $dateRange)
        )
            ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
            ->selectRaw('
            drugs.id                                                            as drug_id,
            COALESCE(drugs.name_ar, drugs.name_en)                              as name,
            drugs.category,
            SUM(sale_items.quantity)                                            as total_qty,
            SUM(sale_items.subtotal)                                            as total_revenue,
            SUM(sale_items.quantity * COALESCE(sale_items.cost_price,0))        as total_cost,
            SUM(sale_items.quantity * (sale_items.price - COALESCE(sale_items.cost_price,0))) as total_profit,
            AVG(sale_items.price)                                               as avg_price
        ')
            ->groupBy('drugs.id', 'drugs.name_ar', 'drugs.name_en', 'drugs.category')
            ->orderByDesc('total_revenue')
            ->paginate(15)
            ->withQueryString();

        // ── طرق الدفع ──
        $paymentChart = Sale::where('user_id', $user->id)
            ->whereBetween('created_at', $dateRange)
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('payment_method')->get();

        return view('super-admin.pharmacy-report', compact(
            'user',
            'period',
            'periodLabel',
            'dateFrom',
            'dateTo',
            'totalRevenue',
            'totalDiscount',
            'totalCount',
            'totalCost',
            'totalProfit',
            'profitMargin',
            'chartLabels',
            'chartRevenue',
            'chartCount',
            'allDrugs',
            'paymentChart'
        ));
    }
    public function toggleApprove(User $user)
    {
        $user->update(['is_approved' => !$user->is_approved]);
        $label = $user->is_approved ? 'تمت الموافقة على' : 'تم إيقاف';
        return back()->with('success', "{$label} {$user->name}");
    }

    public function destroy(User $user)
    {
        if ($user->email === config('superadmin.email'))
            return back()->with('error', 'مينفعش تحذف حساب الأدمن الرئيسي.');
        $user->delete();
        return back()->with('success', "تم حذف {$user->name} بنجاح.");
    }

    private function resolvePeriod($period, $request): array
    {
        return match ($period) {
            'today' => [Carbon::today(), Carbon::today(), 'اليوم'],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'هذا الأسبوع'],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
            'last10' => [Carbon::today()->subDays(9), Carbon::today(), 'آخر 10 أيام'],
            'custom' => [
                Carbon::parse($request->get('from', now()->subDays(7)->format('Y-m-d'))),
                Carbon::parse($request->get('to', now()->format('Y-m-d'))),
                'تاريخ مخصص',
            ],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'هذا الشهر'],
        };
    }



    public function adsStore(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
            'title' => 'nullable|string|max:100',
        ]);

        $path = $request->file('image')->store('ads', 'public');

        Ad::create([
            'title' => $request->title,
            'image_path' => $path,
            'is_active' => true,
        ]);

        return back()->with('success', 'تم رفع الإعلان بنجاح ✅');
    }

    public function adsToggle(Ad $ad)
    {
        $ad->update(['is_active' => !$ad->is_active]);
        return back()->with('success', $ad->is_active ? 'تم تفعيل الإعلان' : 'تم إيقاف الإعلان');
    }

    public function adsDestroy(Ad $ad)
    {
        Storage::disk('public')->delete($ad->image_path);
        $ad->delete();
        return back()->with('success', 'تم حذف الإعلان');
    }

    public function resetPassword(Request $request, User $user)
{
    $request->validate([
        'new_password' => 'required|string|min:6',
    ]);

    $user->update([
        'password' => \Hash::make($request->new_password),
    ]);

    return back()->with('success', "✅ تم تغيير باسورد {$user->name} بنجاح");
}


public function salesReport(Request $request)
{
    $users = User::query()
        ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
            ->orWhere('email', 'like', "%{$request->search}%")
            ->orWhere('pharmacy_name', 'like', "%{$request->search}%")
            ->orWhere('phone', 'like', "%{$request->search}%"))
        ->when($request->status === 'approved', fn($q) => $q->where('is_approved', true))
        ->when($request->status === 'unapproved', fn($q) => $q->where('is_approved', false))
        ->when($request->gov, fn($q) => $q->where('governorate', $request->gov))
        ->when($request->city, fn($q) => $q->where('city', $request->city))
        ->get();

    $userIds = $users->pluck('id');

    // ── تفصيل كل دواء + الكمية المباعة، مجمّع على كل الصيدليات المطابقة للفلتر ──
    $drugs = SaleItem::whereHas('sale', fn($q) => $q->whereIn('user_id', $userIds))
        ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
        ->join('drugs', 'sale_items.drug_id', '=', 'drugs.id')
        ->selectRaw('
            drugs.id                                                     as drug_id,
            COALESCE(drugs.name_ar, drugs.name_en)                       as name,
            drugs.category,
            SUM(sale_items.quantity)                                     as total_qty,
            SUM(sale_items.subtotal)                                     as total_revenue,
            AVG(sale_items.price)                                        as avg_price,
            COUNT(DISTINCT sales.user_id)                                as pharmacies_count
        ')
        ->groupBy('drugs.id', 'drugs.name_ar', 'drugs.name_en', 'drugs.category')
        ->orderByDesc('total_qty')
        ->get();

    $grandQty     = $drugs->sum('total_qty');
    $grandRevenue = $drugs->sum('total_revenue');
    $filtersLabel = $this->buildFilterLabel($request);

    return view('super-admin.sales-report-print', compact(
        'drugs', 'grandQty', 'grandRevenue', 'filtersLabel'
    ));
}

private function buildFilterLabel(Request $request): string
{
    $parts = [];
    if ($request->gov)     $parts[] = "المحافظة: {$request->gov}";
    if ($request->city)     $parts[] = "المركز: {$request->city}";
    if ($request->status)   $parts[] = $request->status === 'approved' ? 'موافق عليهم' : 'في الانتظار';
    if ($request->search)   $parts[] = "بحث: {$request->search}";

    return $parts ? implode(' — ', $parts) : 'كل الصيدليات';
}
}