@extends('layouts.app')
@section('title', 'تقارير الفروع')

@section('content')

{{-- ===== فلتر الفترة ===== --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <div class="flex items-center gap-2 mb-3">
        <i class="fas fa-chart-pie text-emerald-500"></i>
        <h2 class="font-bold text-gray-800">الرؤية المركزية للفروع</h2>
        <span class="text-xs text-gray-400 mr-auto">الفترة: {{ $periodLabel }}</span>
    </div>
    <form method="GET" action="{{ route('reports.branches') }}" class="flex flex-wrap items-center gap-3">
        @foreach(['today'=>'اليوم','week'=>'هذا الأسبوع','month'=>'هذا الشهر','last10'=>'آخر 10 أيام','custom'=>'تاريخ مخصص'] as $val=>$label)
        <button type="submit" name="period" value="{{ $val }}"
                class="text-sm px-4 py-2 rounded-xl border transition font-medium
                {{ $period===$val ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-emerald-50 hover:border-emerald-300' }}">
            {{ $label }}
        </button>
        @endforeach
        @if($period==='custom')
        <div class="flex items-center gap-2 mr-auto">
            <input type="date" name="from" value="{{ request('from',$dateFrom->format('Y-m-d')) }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-400">
            <span class="text-gray-400 text-sm">إلى</span>
            <input type="date" name="to" value="{{ request('to',$dateTo->format('Y-m-d')) }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-emerald-400">
            <button type="submit" name="period" value="custom"
                    class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-emerald-700 transition">
                <i class="fas fa-filter ml-1"></i> تطبيق
            </button>
        </div>
        @endif
    </form>
</div>

{{-- ===== كروت إجمالية للسلسلة ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="text-xs text-gray-400 mb-1">إجمالي الإيراد</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($totals['revenue'],2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="text-xs text-gray-400 mb-1">صافي الربح</div>
        <div class="text-2xl font-bold text-green-600">{{ number_format($totals['profit'],2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="text-xs text-gray-400 mb-1">عدد الفواتير</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($totals['invoices']) }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="text-xs text-gray-400 mb-1">قيمة المخزون (تكلفة)</div>
        <div class="text-2xl font-bold text-gray-800">{{ number_format($totals['inv_value'],2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="text-xs text-gray-400 mb-1">أصناف تحت الحد</div>
        <div class="text-2xl font-bold {{ $totals['low_stock']>0?'text-red-500':'text-gray-800' }}">{{ number_format($totals['low_stock']) }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    {{-- ===== شارت مقارنة الفروع ===== --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-column text-emerald-500 ml-1"></i> مقارنة الفروع (إيراد وربح)</h3>
        @if($branches->isEmpty())
            <p class="text-center text-gray-400 py-12">لا توجد بيانات في هذه الفترة</p>
        @else
            <canvas id="branchChart" height="120"></canvas>
        @endif
    </div>

    {{-- ===== لوحة التنبيهات ===== --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-bell text-amber-500 ml-1"></i> التنبيهات
            <span class="text-xs bg-amber-100 text-amber-700 rounded-full px-2 py-0.5">{{ count($alerts) }}</span>
        </h3>
        @forelse($alerts as $a)
        <div class="flex items-start gap-2 p-2.5 mb-2 rounded-xl text-sm
            {{ $a['level']==='danger' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' }}">
            <i class="fas {{ $a['icon'] }} mt-0.5"></i>
            <div class="flex-1">
                {{ $a['message'] }}
                @if($a['url'])
                <a href="{{ $a['url'] }}" class="block text-xs underline mt-0.5 opacity-80">عرض التفاصيل</a>
                @endif
            </div>
        </div>
        @empty
        <p class="text-center text-gray-400 py-8"><i class="fas fa-check-circle text-green-400 text-2xl block mb-2"></i>لا توجد تنبيهات</p>
        @endforelse
    </div>
</div>

{{-- ===== جدول مقارنة الفروع ===== --}}
<div class="bg-white rounded-2xl shadow-sm p-5 mb-6 overflow-x-auto">
    <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-code-branch text-indigo-500 ml-1"></i> تفصيل الفروع</h3>
    <table class="w-full text-sm min-w-[720px]">
        <thead>
            <tr class="text-gray-400 text-right border-b border-gray-100">
                <th class="pb-3 font-medium">الفرع</th>
                <th class="pb-3 font-medium">الإيراد</th>
                <th class="pb-3 font-medium">الربح</th>
                <th class="pb-3 font-medium">الهامش</th>
                <th class="pb-3 font-medium">الفواتير</th>
                <th class="pb-3 font-medium">وحدات المخزون</th>
                <th class="pb-3 font-medium">قيمة المخزون</th>
                <th class="pb-3 font-medium">تحت الحد</th>
            </tr>
        </thead>
        <tbody>
            @forelse($branches as $b)
            <tr class="border-b border-gray-50 hover:bg-gray-50">
                <td class="py-3">
                    <span class="font-medium text-gray-800">{{ $b['name'] }}</span>
                    <span class="text-xs text-gray-400 mr-1">{{ $b['code'] }}</span>
                    @if($b['status']==='stopped')<span class="text-xs bg-gray-100 text-gray-500 rounded px-1.5">متوقف</span>@endif
                </td>
                <td class="py-3 font-semibold text-gray-800">{{ number_format($b['revenue'],2) }}</td>
                <td class="py-3 text-green-600">{{ number_format($b['profit'],2) }}</td>
                <td class="py-3 text-gray-500">{{ number_format($b['margin'],1) }}%</td>
                <td class="py-3 text-gray-500">{{ number_format($b['invoices']) }}</td>
                <td class="py-3 text-gray-500">{{ number_format($b['inv_units']) }}</td>
                <td class="py-3 text-gray-500">{{ number_format($b['inv_value'],2) }}</td>
                <td class="py-3">
                    @if($b['low_stock']>0)
                    <span class="text-red-500 font-medium">{{ $b['low_stock'] }}</span>
                    @else <span class="text-gray-300">0</span> @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="py-8 text-center text-gray-400">لا توجد فروع أو بيانات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- ===== ملخّص التحويلات ===== --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-right-left text-blue-500 ml-1"></i> تحويلات المخزون</h3>
        <div class="grid grid-cols-2 gap-3">
            @php $tLabels = ['draft'=>'مسودّة','sent'=>'مُرسَلة','received'=>'مستلمة','rejected'=>'مرفوضة'];
                 $tColors = ['draft'=>'gray','sent'=>'amber','received'=>'green','rejected'=>'red']; @endphp
            @foreach($transferCounts as $status=>$count)
            <div class="rounded-xl p-3 bg-{{ $tColors[$status] }}-50">
                <div class="text-xs text-gray-500">{{ $tLabels[$status] ?? $status }}</div>
                <div class="text-xl font-bold text-{{ $tColors[$status] }}-600">{{ $count }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ===== ملخّص التأمين ===== --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4"><i class="fas fa-file-invoice-dollar text-purple-500 ml-1"></i> التأمين والمطالبات</h3>

        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="rounded-xl p-3 bg-purple-50">
                <div class="text-xs text-gray-500">مبيعات التأمين (الفترة)</div>
                <div class="text-lg font-bold text-purple-600">{{ number_format($insuranceSales->covered ?? 0,2) }}</div>
                <div class="text-xs text-gray-400">{{ (int)($insuranceSales->invoices ?? 0) }} فاتورة</div>
            </div>
            <div class="rounded-xl p-3 bg-amber-50">
                <div class="text-xs text-gray-500">مطالبات مُرسَلة (مديونية)</div>
                <div class="text-lg font-bold text-amber-600">{{ number_format(optional($claimsByStatus->get('submitted'))->amount ?? 0,2) }}</div>
                <div class="text-xs text-gray-400">{{ optional($claimsByStatus->get('submitted'))->c ?? 0 }} مطالبة</div>
            </div>
        </div>

        @if($contractDebt->isNotEmpty())
        <div class="text-xs text-gray-400 mb-2">مديونية الجهات (مطالبات غير مسدّدة)</div>
        <div class="space-y-1.5">
            @foreach($contractDebt as $c)
            <div class="flex items-center justify-between text-sm py-1 border-b border-gray-50">
                <span class="text-gray-700">{{ $c->contract_name }}</span>
                <span class="font-semibold text-amber-600">{{ number_format($c->debt,2) }} ج.م</span>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-center text-gray-300 text-sm py-4">لا توجد مطالبات غير مسدّدة</p>
        @endif
    </div>
</div>

@endsection

@section('scripts')
@if($branches->isNotEmpty())
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const bLabels  = @json($chartLabels);
const bRevenue = @json($chartRevenue);
const bProfit  = @json($chartProfit);

new Chart(document.getElementById('branchChart'), {
    type: 'bar',
    data: {
        labels: bLabels,
        datasets: [
            { label: 'الإيراد', data: bRevenue, backgroundColor: '#6366f1', borderRadius: 6 },
            { label: 'الربح',   data: bProfit,  backgroundColor: '#10b981', borderRadius: 6 },
        ],
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } },
    },
});
</script>
@endif
@endsection
