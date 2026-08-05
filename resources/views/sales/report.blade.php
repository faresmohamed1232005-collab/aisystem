@extends('layouts.app')
@section('title', 'تقارير المبيعات')

@section('content')

@php
    $isSubUser = session()->has('sub_user');
    $blurClass = $isSubUser ? 'profit-blur' : '';
@endphp

{{-- ===== فلتر الفترة ===== --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('sales.report') }}" class="flex flex-wrap items-center gap-3">
        @foreach(['today'=>'اليوم','week'=>'هذا الأسبوع','month'=>'هذا الشهر','last10'=>'آخر 10 أيام','custom'=>'تاريخ مخصص'] as $val=>$label)
        <button type="submit" name="period" value="{{ $val }}"
                class="text-sm px-4 py-2 rounded-xl border transition font-medium
                {{ $period===$val ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-indigo-50 hover:border-indigo-300' }}">
            {{ $label }}
        </button>
        @endforeach
        @if($period==='custom')
        <div class="flex items-center gap-2 mr-auto">
            <input type="date" name="from" value="{{ request('from',$dateFrom->format('Y-m-d')) }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
            <span class="text-gray-400 text-sm">إلى</span>
            <input type="date" name="to" value="{{ request('to',$dateTo->format('Y-m-d')) }}"
                   class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
            <button type="submit" name="period" value="custom"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">
                <i class="fas fa-filter ml-1"></i> تطبيق
            </button>
        </div>
        @endif
    </form>
</div>

{{-- ===== بطاقات الإحصائيات ===== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- إجمالي الإيرادات --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs text-gray-400 mb-1">إجمالي الإيرادات</div>
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalRevenue,2) }}</div>
                <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
            </div>
            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-1 text-xs {{ $revenueChange>=0?'text-green-600':'text-red-500' }}">
            <i class="fas fa-arrow-{{ $revenueChange>=0?'up':'down' }}"></i>
            {{ number_format(abs($revenueChange),1) }}% مقارنة بالفترة السابقة
        </div>
    </div>

    {{-- صافي الأرباح --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs text-gray-400 mb-1">صافي الأرباح</div>
                <div class="text-2xl font-bold {{ $totalProfit>=0?'text-green-600':'text-red-500' }} {{ $blurClass }}">
                    {{ number_format($totalProfit,2) }}
                </div>
                <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
            </div>
            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-400">
            هامش الربح:
            <span class="font-semibold text-gray-600 {{ $blurClass }}">
                {{ number_format($profitMargin,1) }}%
            </span>
        </div>
    </div>

    {{-- عدد الفواتير --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs text-gray-400 mb-1">عدد الفواتير</div>
                <div class="text-2xl font-bold text-gray-800">{{ $totalCount }}</div>
                <div class="text-xs text-gray-400 mt-0.5">فاتورة</div>
            </div>
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-400">متوسط الفاتورة: <span class="font-semibold text-gray-600">{{ $totalCount>0?number_format($totalRevenue/$totalCount,2):'0.00' }} ج.م</span></div>
    </div>

    {{-- إجمالي الخصومات + التكلفة --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-xs text-gray-400 mb-1">إجمالي الخصومات</div>
                <div class="text-2xl font-bold text-orange-500">{{ number_format($totalDiscount,2) }}</div>
                <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
            </div>
            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center text-orange-500">
                <i class="fas fa-tags"></i>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-400">
            التكلفة الإجمالية:
            <span class="font-semibold text-gray-600 {{ $blurClass }}">
                {{ number_format($totalCost,2) }} ج.م
            </span>
        </div>
    </div>

</div>

{{-- ===== الرسوم البيانية ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-area text-indigo-500"></i> المبيعات اليومية — {{ $periodLabel }}
        </h3>
        <canvas id="lineChart" height="100"></canvas>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie text-indigo-500"></i> المبيعات حسب الفئة
        </h3>
        @if($categoryChart->isEmpty())
            <div class="flex items-center justify-center h-48 text-gray-300 text-sm">
                <div class="text-center"><i class="fas fa-chart-pie text-4xl block mb-2"></i>لا توجد بيانات</div>
            </div>
        @else
            <canvas id="pieChart" height="200"></canvas>
        @endif
    </div>
</div>

{{-- ===== طرق الدفع + أعلى المنتجات ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-credit-card text-indigo-500"></i> طرق الدفع
        </h3>
        @php
            $payIcons  = ['cash'=>'💵','card'=>'💳','insurance'=>'🏥','deferred'=>'📋'];
            $payLabels = ['cash'=>'كاش','card'=>'بطاقة','insurance'=>'تأمين','deferred'=>'آجل'];
            $cardLabels= ['visa'=>'فيزا','instapay'=>'إنستاباي','wallet'=>'محفظة'];
        @endphp
        @forelse($paymentChart as $pm)
        @php
            $pct=$totalRevenue>0?($pm->revenue/$totalRevenue)*100:0;
            $methodLabel=$payLabels[$pm->payment_method]??$pm->payment_method;
            $methodIcon=$payIcons[$pm->payment_method]??'💰';
            $cardDetail='';
            if($pm->payment_method==='card'&&!empty($pm->card_type))
                $cardDetail=' — '.($cardLabels[$pm->card_type]??$pm->card_type);
            $barColor=match($pm->payment_method){'cash'=>'bg-green-500','card'=>'bg-blue-500','insurance'=>'bg-purple-500','deferred'=>'bg-red-400',default=>'bg-indigo-500'};
        @endphp
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-700 font-medium">{{ $methodIcon }} {{ $methodLabel }}{{ $cardDetail }}</span>
                <span class="text-gray-500">{{ $pm->count }} فاتورة — {{ number_format($pm->revenue,0) }} ج.م</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full {{ $barColor }} rounded-full" style="width:{{ $pct }}%"></div>
            </div>
            <div class="text-xs text-gray-400 mt-0.5 text-left">{{ number_format($pct,1) }}%</div>
        </div>
        @empty
        <div class="text-center text-gray-300 py-8 text-sm">لا توجد بيانات</div>
        @endforelse
    </div>

    {{-- أعلى المنتجات --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-trophy text-yellow-500"></i> أعلى المنتجات مبيعاً
        </h3>
        @if($topProducts->isEmpty())
            <div class="text-center text-gray-300 py-8 text-sm"><i class="fas fa-box-open text-3xl block mb-2"></i>لا توجد بيانات</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-right">
                        <th class="px-3 py-2 rounded-r-lg font-medium">#</th>
                        <th class="px-3 py-2 font-medium">المنتج</th>
                        <th class="px-3 py-2 font-medium">الكمية</th>
                        <th class="px-3 py-2 font-medium">الإيراد</th>
                        <th class="px-3 py-2 font-medium">التكلفة</th>
                        <th class="px-3 py-2 font-medium text-green-600 rounded-l-lg">الربح</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($topProducts as $i=>$p)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-3 py-2.5">
                            @if($i===0)🥇@elseif($i===1)🥈@elseif($i===2)🥉
                            @else<span class="text-gray-400 text-xs">{{ $i+1 }}</span>@endif
                        </td>
                        <td class="px-3 py-2.5">
                            <div class="font-semibold text-gray-800">{{ $p->name }}</div>
                            @if($p->category)<div class="text-xs text-indigo-400">{{ $p->category }}</div>@endif
                        </td>
                        <td class="px-3 py-2.5 text-gray-600">{{ $p->total_qty }} وحدة</td>
                        <td class="px-3 py-2.5 font-semibold text-gray-800">{{ number_format($p->total_revenue,2) }} ج.م</td>
                        <td class="px-3 py-2.5 text-gray-500 {{ $blurClass }}">
                            {{ number_format($p->total_cost,2) }} ج.م
                        </td>
                        <td class="px-3 py-2.5">
                            @php $pMargin=$p->total_revenue>0?($p->total_profit/$p->total_revenue)*100:0; @endphp
                            <span class="font-bold {{ $p->total_profit>=0?'text-green-600':'text-red-500' }} {{ $blurClass }}">
                                {{ number_format($p->total_profit,2) }} ج.م
                            </span>
                            <div class="text-xs text-gray-400 {{ $blurClass }}">{{ number_format($pMargin,1) }}%</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- ===== آخر الفواتير ===== --}}
<div class="bg-white rounded-2xl shadow-sm p-5">
    <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
        <i class="fas fa-list text-indigo-500"></i>
        آخر الفواتير
        <span class="text-xs text-gray-400 font-normal mr-1">({{ $periodLabel }})</span>
    </h3>

    @if($recentSales->isEmpty())
        <div class="text-center text-gray-300 py-10 text-sm">
            <i class="fas fa-receipt text-4xl block mb-2"></i>
            لا توجد فواتير في هذه الفترة
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-right">
                    <th class="px-4 py-2 rounded-r-lg font-medium">رقم الفاتورة</th>
                    <th class="px-4 py-2 font-medium">التاريخ والوقت</th>
                    <th class="px-4 py-2 font-medium">العميل</th>
                    <th class="px-4 py-2 font-medium">المنتجات والوحدات</th>
                    <th class="px-4 py-2 font-medium">الخصم</th>
                    <th class="px-4 py-2 font-medium">طريقة الدفع</th>
                    <th class="px-4 py-2 font-medium">الحالة</th>
                    <th class="px-4 py-2 font-medium">الإجمالي</th>
                    <th class="px-4 py-2 font-medium rounded-l-lg"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($recentSales as $sale)
                @php
                    $pIcon  = $payIcons[$sale->payment_method]  ?? '💰';
                    $pLabel = $payLabels[$sale->payment_method] ?? $sale->payment_method;
                    $pDetail= '';
                    if($sale->payment_method==='card' && $sale->card_type)
                        $pDetail=' / '.($cardLabels[$sale->card_type] ?? $sale->card_type);
                    $pClass = match($sale->payment_method){
                        'cash'      => 'bg-green-100 text-green-700',
                        'card'      => 'bg-blue-100 text-blue-700',
                        'insurance' => 'bg-purple-100 text-purple-700',
                        'deferred'  => 'bg-red-100 text-red-700',
                        default     => 'bg-gray-100 text-gray-600',
                    };
                    $statusLabel = match($sale->payment_status ?? 'paid'){
                        'paid'     => ['✅ مسدد',  'bg-green-100 text-green-700'],
                        'partial'  => ['⏳ جزئي',  'bg-orange-100 text-orange-700'],
                        'deferred' => ['🔴 آجل',   'bg-red-100 text-red-700'],
                        default    => ['—',         'bg-gray-100 text-gray-500'],
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded-lg text-gray-600">
                            {{ $sale->invoice_number }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        <div>{{ $sale->created_at->format('Y-m-d') }}</div>
                        <div class="text-gray-400">{{ $sale->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600">
                        @if($sale->customer)
                            <div class="font-semibold text-indigo-600">{{ $sale->customer->name }}</div>
                            <div class="text-gray-400 font-mono">{{ $sale->customer->code }}</div>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @foreach($sale->items->take(3) as $item)
                            @php
                            $itemName = $item->drug->name_ar ?? $item->drug->name_en ?? $item->name ?? '—';
                                $unitKey  = $item->unit_key ?? null;
                                $unitName = $item->unit_name ?? null;

                                if (!$unitKey && $item->qty_factor) {
                                    $factor = (float) $item->qty_factor;
                                    if ($factor >= 0.99) {
                                        $unitKey  = 'pack';
                                        $unitName = $drug->unit_name ?? 'علبة';
                                    } elseif ($factor >= 0.05) {
                                        $unitKey  = 'sub';
                                        $unitName = $drug->sub_unit_name ?? 'شريط';
                                    } else {
                                        $unitKey  = 'smallest';
                                        $unitName = $drug->smallest_unit_name ?? 'حبة';
                                    }
                                }

                                $unitKey  = $unitKey  ?? 'pack';
                                $unitName = $unitName ?? ($drug->unit_name ?? 'علبة');

                                $uc = match($unitKey) {
                                    'sub'     => 'bg-orange-50 text-orange-700 border border-orange-200',
                                    'smallest'=> 'bg-green-50 text-green-700 border border-green-200',
                                    default   => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
                                };
                                $icon = match($unitKey) {
                                    'sub'     => 'fa-layer-group',
                                    'smallest'=> 'fa-circle',
                                    default   => 'fa-box',
                                };
                                $iconSize = $unitKey === 'smallest' ? '7px' : '9px';
                            @endphp
                            <span class="text-xs {{ $uc }} px-2 py-0.5 rounded-full flex items-center gap-1">
                                <i class="fas {{ $icon }}" style="font-size:{{ $iconSize }}"></i>
                                <span class="font-semibold">{{ $item->name }}</span>
                                <span class="font-bold text-gray-600">×{{ $item->quantity }}</span>
                                <span class="opacity-75 text-xs">{{ $unitName }}</span>
                            </span>
                            @endforeach
                            @if($sale->items->count() > 3)
                                <span class="text-xs text-gray-400 self-center">+{{ $sale->items->count()-3 }} أخرى</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-orange-500 text-xs">
                        {{ $sale->discount>0 ? number_format($sale->discount,2).' ج.م' : '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $pClass }}">
                            {{ $pIcon }} {{ $pLabel }}{{ $pDetail }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $statusLabel[1] }}">
                            {{ $statusLabel[0] }}
                        </span>
                        @if(isset($sale->remaining) && $sale->remaining > 0)
                            <div class="text-xs text-red-500 mt-0.5">متبقي: {{ number_format($sale->remaining,2) }} ج.م</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-bold text-indigo-600">
                        {{ number_format($sale->total,2) }} ج.م
                    </td>
                    <td class="px-4 py-3">
                        <x-print-dropdown
                            :receipt-url="route('sales.print', ['sale' => $sale, 'format' => 'receipt'])"
                            :a4-url="route('sales.print', ['sale' => $sale, 'format' => 'a4'])" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection

@section('scripts')
<style>
.profit-blur {
    filter: blur(6px);
    user-select: none;
    pointer-events: none;
    transition: filter 0.3s;
}
</style>
<script>
document.addEventListener('chart-ready', function () {
const chartLabels  = @json($chartLabels);
const chartRevenue = @json($chartRevenue);
const chartCount   = @json($chartCount);
const catLabels    = @json($categoryChart->pluck('category'));
const catRevenue   = @json($categoryChart->pluck('revenue')->map(fn($v)=>round($v,2)));
const pieColors    = ['#6366f1','#8b5cf6','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899','#84cc16','#f97316','#3b82f6','#a855f7','#14b8a6','#eab308'];

new Chart(document.getElementById('lineChart'),{
    type:'line',
    data:{labels:chartLabels,datasets:[
        {label:'الإيرادات (ج.م)',data:chartRevenue,borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,0.08)',borderWidth:2.5,pointBackgroundColor:'#6366f1',pointRadius:4,pointHoverRadius:6,fill:true,tension:0.4,yAxisID:'y'},
        {label:'عدد الفواتير',data:chartCount,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.05)',borderWidth:2,pointBackgroundColor:'#10b981',pointRadius:3,pointHoverRadius:5,fill:false,tension:0.4,yAxisID:'y1'}
    ]},
    options:{responsive:true,interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'top',labels:{font:{family:'Cairo'},boxWidth:12}},
            tooltip:{callbacks:{label:ctx=>ctx.datasetIndex===0?` ${ctx.parsed.y.toFixed(2)} ج.م`:` ${ctx.parsed.y} فاتورة`}}},
        scales:{
            x:{grid:{display:false},ticks:{font:{family:'Cairo',size:11}}},
            y:{position:'right',grid:{color:'rgba(0,0,0,0.04)'},ticks:{font:{family:'Cairo',size:11},callback:v=>v+' ج.م'}},
            y1:{position:'left',grid:{display:false},ticks:{font:{family:'Cairo',size:11},stepSize:1}}
        }
    }
});

@if(!$categoryChart->isEmpty())
new Chart(document.getElementById('pieChart'),{
    type:'doughnut',
    data:{labels:catLabels,datasets:[{data:catRevenue,backgroundColor:pieColors.slice(0,catLabels.length),borderWidth:2,borderColor:'#fff',hoverOffset:6}]},
    options:{responsive:true,plugins:{
        legend:{position:'bottom',labels:{font:{family:'Cairo',size:11},boxWidth:12,padding:10}},
        tooltip:{callbacks:{label:ctx=>` ${ctx.label}: ${ctx.parsed.toFixed(2)} ج.م`}}
    },cutout:'60%'}
});
@endif
});
</script>
@endsection