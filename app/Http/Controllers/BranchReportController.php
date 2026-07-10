<?php

namespace App\Http\Controllers;

use App\Models\BranchModel;
use App\Models\Contract;
use App\Models\InsuranceClaim;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockTransfer;
use App\Models\UserDrugInventory;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * الداشبورد المركزية المقارنة cross-branch (Phase 4).
 *
 * كل التقارير الأخرى single-tenant (تفلتر بـ user_id + تقيّد المخزون بـ Branch::id()).
 * هنا المالك يرى **كل فروعه** مرة واحدة: نجمّع بيانات المالك بالكامل بُعدها branch_id
 * (وسم الفرع المُنشئ على كل صف) — بدون أي تقييد بالفرع الحالي. على السيرفر ده يرى كل
 * الفروع (كلها مدفوعة إليه)؛ على فرع مفرد يرى فرعه فقط (سلوك مقبول).
 */
class BranchReportController extends Controller
{
    /** الوسم المستخدم للصفوف بلا branch_id (بيانات أُنشئت قبل التوسيم متعدّد الفروع). */
    private const UNTAGGED = '__untagged__';

    public function index(Request $request)
    {
        $owner = Auth::id();

        $period = $request->get('period', 'month');
        [$dateFrom, $dateTo, $periodLabel] = ReportPeriod::resolve($period, $request);
        $from = $dateFrom->copy()->startOfDay();
        $to   = $dateTo->copy()->endOfDay();

        // ===== خريطة الفروع: branch_id → اسم/كود =====
        $branchModels = BranchModel::where('user_id', $owner)->orderBy('code')->get();
        $branchMap = $branchModels->keyBy('branch_id');

        // ===== مبيعات لكل فرع (الفترة) =====
        $salesByBranch = Sale::where('user_id', $owner)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('branch_id, SUM(total) as revenue, SUM(discount) as discount, COUNT(*) as invoices')
            ->groupBy('branch_id')
            ->get()
            ->keyBy(fn($r) => $r->branch_id ?? self::UNTAGGED);

        // ===== ربح لكل فرع (الفترة) — نفس صيغة SalesReportController =====
        $profitByBranch = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.user_id', $owner)
            ->whereBetween('sales.created_at', [$from, $to])
            ->selectRaw('sales.branch_id as branch_id,
                SUM(sale_items.quantity * (sale_items.price - COALESCE(sale_items.cost_price, 0))) as profit')
            ->groupBy('sales.branch_id')
            ->get()
            ->keyBy(fn($r) => $r->branch_id ?? self::UNTAGGED);

        // ===== المخزون لكل فرع (لحظي — مش مقيّد بالفترة) من user_drug_inventory =====
        // على السيرفر كل صفوف كل الفروع محفوظة (بُعدها branch_id + min_quantity + cost).
        $inventoryByBranch = UserDrugInventory::where('user_id', $owner)
            ->selectRaw('branch_id,
                COUNT(*) as items,
                SUM(quantity) as units,
                SUM(quantity * COALESCE(cost_price, 0)) as value,
                SUM(CASE WHEN quantity > 0 AND quantity <= min_quantity THEN 1 ELSE 0 END) as low_stock')
            ->groupBy('branch_id')
            ->get()
            ->keyBy(fn($r) => $r->branch_id ?? self::UNTAGGED);

        // ===== دمج الكل في صف لكل فرع =====
        // مجموعة المفاتيح = كل فرع مسجّل + أي branch_id ظهر في البيانات (بما فيه غير الموسوم).
        $keys = collect($branchMap->keys())
            ->merge($salesByBranch->keys())
            ->merge($profitByBranch->keys())
            ->merge($inventoryByBranch->keys())
            ->unique()
            ->values();

        $branches = $keys->map(function ($key) use ($branchMap, $salesByBranch, $profitByBranch, $inventoryByBranch) {
            $model  = $key === self::UNTAGGED ? null : $branchMap->get($key);
            $sale   = $salesByBranch->get($key);
            $prof   = $profitByBranch->get($key);
            $inv    = $inventoryByBranch->get($key);

            $revenue = (float) ($sale->revenue ?? 0);
            $profit  = (float) ($prof->profit ?? 0);

            return [
                'branch_id' => $key === self::UNTAGGED ? null : $key,
                'name'      => $model->name ?? ($key === self::UNTAGGED ? 'غير محدّد / الرئيسي' : $key),
                'code'      => $model->code ?? '—',
                'status'    => $model->status ?? null,
                'revenue'   => $revenue,
                'discount'  => (float) ($sale->discount ?? 0),
                'invoices'  => (int) ($sale->invoices ?? 0),
                'profit'    => $profit,
                'margin'    => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                'inv_items' => (int) ($inv->items ?? 0),
                'inv_units' => (float) ($inv->units ?? 0),
                'inv_value' => (float) ($inv->value ?? 0),
                'low_stock' => (int) ($inv->low_stock ?? 0),
            ];
        })
        ->sortByDesc('revenue')
        ->values();

        // ===== إجماليات السلسلة =====
        $totals = [
            'revenue'   => $branches->sum('revenue'),
            'profit'    => $branches->sum('profit'),
            'invoices'  => $branches->sum('invoices'),
            'inv_value' => $branches->sum('inv_value'),
            'low_stock' => $branches->sum('low_stock'),
            'branches'  => $branchModels->where('status', '!=', 'stopped')->count()
                            ?: $branches->count(),
        ];

        // ===== بيانات الشارت (أعلى الفروع إيراداً) =====
        $chartBranches = $branches->take(12);
        $chartLabels   = $chartBranches->pluck('name');
        $chartRevenue  = $chartBranches->map(fn($b) => round($b['revenue'], 2));
        $chartProfit   = $chartBranches->map(fn($b) => round($b['profit'], 2));

        // ===== التحويلات: أعداد حسب الحالة =====
        $transfersByStatus = StockTransfer::where('user_id', $owner)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');
        $transferCounts = collect(StockTransfer::STATUSES)
            ->mapWithKeys(fn($s) => [$s => (int) ($transfersByStatus[$s] ?? 0)]);

        // ===== التأمين: مطالبات حسب الحالة + مديونية لكل عقد + مبيعات تأمين =====
        $claimsByStatus = InsuranceClaim::where('user_id', $owner)
            ->selectRaw('status, COUNT(*) as c, SUM(amount) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // مديونية = مطالبات مُرسَلة (submitted) لسه ما اتسدّدتش، مجمّعة لكل جهة.
        $contractDebt = InsuranceClaim::join('contracts', 'insurance_claims.contract_id', '=', 'contracts.id')
            ->where('insurance_claims.user_id', $owner)
            ->where('insurance_claims.status', 'submitted')
            ->selectRaw('contracts.name as contract_name, SUM(insurance_claims.amount) as debt, COUNT(*) as claims')
            ->groupBy('contracts.id', 'contracts.name')
            ->orderByDesc('debt')
            ->get();

        $insuranceSales = Sale::where('user_id', $owner)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('contract_id')
            ->selectRaw('COUNT(*) as invoices, SUM(covered_amount) as covered, SUM(patient_amount) as patient')
            ->first();

        // ===== التنبيهات =====
        $alerts = $this->buildAlerts($owner, $branches, $branchMap);

        return view('reports.branches', compact(
            'period', 'periodLabel', 'dateFrom', 'dateTo',
            'branches', 'totals',
            'chartLabels', 'chartRevenue', 'chartProfit',
            'transferCounts', 'claimsByStatus', 'contractDebt', 'insuranceSales',
            'alerts'
        ));
    }

    /**
     * لوحة التنبيهات: نقص مخزون لكل فرع، عقود قرب تنتهي، تحويلات عالقة > 48 ساعة.
     */
    private function buildAlerts(int $owner, $branches, $branchMap): array
    {
        $alerts = [];

        // نقص مخزون: فروع عندها أصناف تحت حد التنبيه.
        foreach ($branches as $b) {
            if ($b['low_stock'] > 0) {
                $alerts[] = [
                    'type'    => 'low_stock',
                    'level'   => 'warning',
                    'icon'    => 'fa-triangle-exclamation',
                    'message' => "فرع «{$b['name']}» به {$b['low_stock']} صنف تحت حد التنبيه",
                    'url'     => null,
                ];
            }
        }

        // عقود قرب تنتهي (خلال 30 يوم) وحالتها active.
        $expiring = Contract::where('user_id', $owner)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', today())
            ->whereDate('end_date', '<=', today()->addDays(30))
            ->orderBy('end_date')
            ->get(['id', 'name', 'end_date']);
        foreach ($expiring as $c) {
            $days = (int) today()->diffInDays($c->end_date, false);
            $alerts[] = [
                'type'    => 'contract_expiring',
                'level'   => 'danger',
                'icon'    => 'fa-file-contract',
                'message' => "عقد «{$c->name}» ينتهي خلال {$days} يوم",
                'url'     => route('contracts.show', $c->id),
            ];
        }

        // تحويلات عالقة: أُرسلت ولم تُستلم لأكثر من 48 ساعة.
        $stuck = StockTransfer::where('user_id', $owner)
            ->where('status', 'sent')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', now()->subHours(48))
            ->orderBy('sent_at')
            ->get(['id', 'transfer_number', 'to_branch_id', 'sent_at']);
        foreach ($stuck as $t) {
            $toName = $branchMap->get($t->to_branch_id)->name ?? $t->to_branch_id;
            $hours  = (int) $t->sent_at->diffInHours(now());
            $alerts[] = [
                'type'    => 'transfer_stuck',
                'level'   => 'danger',
                'icon'    => 'fa-truck-arrow-right',
                'message' => "تحويل {$t->transfer_number} إلى «{$toName}» لم يُستلم منذ {$hours} ساعة",
                'url'     => route('stock-transfers.show', $t->id),
            ];
        }

        return $alerts;
    }
}
