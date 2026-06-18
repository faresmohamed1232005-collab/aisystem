@extends('layouts.app')
@section('title', 'تقارير المشتريات')

@section('styles')
    <style>
        .stat-card {
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.10);
        }

        .rank-bar {
            transition: width 1s cubic-bezier(.4, 0, .2, 1);
        }
    </style>
@endsection

@section('content')

    {{-- ===== فلتر الفترة ===== --}}
    <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('purchases.report') }}" class="flex flex-wrap items-center gap-3">
            @foreach ([
            'today' => 'اليوم',
            'week' => 'هذا الأسبوع',
            'month' => 'هذا الشهر',
            'last10' => 'آخر 10 أيام',
            'custom' => 'تاريخ مخصص',
        ] as $val => $label)
                <button type="submit" name="period" value="{{ $val }}"
                    class="text-sm px-4 py-2 rounded-xl border transition font-medium
                    {{ $period === $val
                        ? 'bg-green-600 text-white border-green-600'
                        : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-green-50 hover:border-green-300' }}">
                    {{ $label }}
                </button>
            @endforeach

            @if ($period === 'custom')
                <div class="flex items-center gap-2 mr-auto">
                    <input type="date" name="from" value="{{ request('from', $dateFrom->format('Y-m-d')) }}"
                        class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                    <span class="text-gray-400 text-sm">إلى</span>
                    <input type="date" name="to" value="{{ request('to', $dateTo->format('Y-m-d')) }}"
                        class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-green-400">
                    <button type="submit" name="period" value="custom"
                        class="bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
                        <i class="fas fa-filter ml-1"></i> تطبيق
                    </button>
                </div>
            @endif
        </form>
    </div>

    {{-- ===== بطاقات الإحصائيات ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- إجمالي الإنفاق --}}
        <div class="stat-card bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-gray-400 mb-1">إجمالي المشتريات</div>
                    <div class="text-2xl font-bold text-gray-800">{{ number_format($totalSpent, 2) }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
                </div>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1 text-xs {{ $spentChange <= 0 ? 'text-green-600' : 'text-red-500' }}">
                <i class="fas fa-arrow-{{ $spentChange <= 0 ? 'down' : 'up' }}"></i>
                {{ number_format(abs($spentChange), 1) }}% مقارنة بالفترة السابقة
            </div>
        </div>

        {{-- عدد الفواتير --}}
        <div class="stat-card bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-gray-400 mb-1">عدد الفواتير</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $totalCount }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">فاتورة</div>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600">
                    <i class="fas fa-file-invoice"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400">
                متوسط الفاتورة:
                <span class="font-semibold text-gray-600">
                    {{ $totalCount > 0 ? number_format($totalSpent / $totalCount, 2) : '0.00' }} ج.م
                </span>
            </div>
        </div>

        {{-- الديون للموردين --}}
        <div class="stat-card bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-gray-400 mb-1">ديون للموردين</div>
                    <div class="text-2xl font-bold {{ $totalAllDebt > 0 ? 'text-red-500' : 'text-green-600' }}">
                        {{ number_format($totalAllDebt, 2) }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
                </div>
                <div
                    class="w-10 h-10 {{ $totalAllDebt > 0 ? 'bg-red-100' : 'bg-green-100' }} rounded-xl flex items-center justify-center {{ $totalAllDebt > 0 ? 'text-red-500' : 'text-green-600' }}">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400">
                متبقي هذه الفترة:
                <span class="font-semibold text-red-500">{{ number_format($totalDeferred, 2) }} ج.م</span>
            </div>
        </div>

        {{-- الخصومات --}}
        <div class="stat-card bg-white rounded-2xl shadow-sm p-5">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-xs text-gray-400 mb-1">خصومات حصلنا عليها</div>
                    <div class="text-2xl font-bold text-orange-500">{{ number_format($totalDiscount, 2) }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">ج.م</div>
                </div>
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center text-orange-500">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
            <div class="mt-3 text-xs text-gray-400">
                ضرائب مدفوعة:
                <span class="font-semibold text-gray-600">{{ number_format($totalTax, 2) }} ج.م</span>
            </div>
        </div>
    </div>

    {{-- ===== الرسم البياني اليومي + الفئات ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-area text-green-500"></i>
                المشتريات اليومية — {{ $periodLabel }}
            </h3>
            <canvas id="lineChart" height="100"></canvas>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-green-500"></i>
                المشتريات حسب الفئة
            </h3>
            @if ($categoryChart->isEmpty())
                <div class="flex items-center justify-center h-48 text-gray-300 text-sm">
                    <div class="text-center"><i class="fas fa-chart-pie text-4xl block mb-2"></i>لا توجد بيانات</div>
                </div>
            @else
                <canvas id="pieChart" height="210"></canvas>
            @endif
        </div>
    </div>

    {{-- ===== الموردين + الديون ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- أعلى موردين --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-5 flex items-center gap-2">
                <i class="fas fa-truck text-green-500"></i>
                أعلى الموردين — {{ $periodLabel }}
            </h3>
            @if ($topSuppliers->isEmpty())
                <div class="text-center text-gray-300 py-8 text-sm">
                    <i class="fas fa-truck text-3xl block mb-2"></i>لا توجد بيانات
                </div>
            @else
                @php $maxSpent = $topSuppliers->first()->total_spent; @endphp
                <div class="space-y-4">
                    @foreach ($topSuppliers as $i => $sup)
                        @php
                            $pct = $maxSpent > 0 ? ($sup->total_spent / $maxSpent) * 100 : 0;
                            $medals = ['🥇', '🥈', '🥉'];
                            $barColors = [
                                'bg-yellow-400',
                                'bg-gray-400',
                                'bg-orange-400',
                                'bg-green-500',
                                'bg-blue-500',
                                'bg-indigo-500',
                                'bg-purple-500',
                                'bg-pink-500',
                                'bg-teal-500',
                                'bg-cyan-500',
                            ];
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ $medals[$i] ?? $i + 1 }}</span>
                                    <a href="{{ route('suppliers.show', $sup->id) }}"
                                        class="font-semibold text-sm text-gray-800 hover:text-green-600 transition">
                                        {{ $sup->name }}
                                    </a>
                                    <span
                                        class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">{{ $sup->code }}</span>
                                    <span class="text-xs text-gray-400">{{ $sup->invoices_count }} فاتورة</span>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-sm text-gray-800">{{ number_format($sup->total_spent, 2) }}
                                        ج.م</span>
                                    @if ($sup->total_remaining > 0)
                                        <span class="text-xs text-red-500 mr-2">متبقي:
                                            {{ number_format($sup->total_remaining, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $barColors[$i] ?? 'bg-green-500' }} rounded-full rank-bar"
                                    style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- موردين بديون --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle text-red-400"></i>
                ديون مستحقة للموردين
            </h3>
            @if ($debtSuppliers->isEmpty())
                <div class="flex items-center justify-center h-32 text-gray-300 text-sm">
                    <div class="text-center">
                        <i class="fas fa-check-circle text-3xl text-green-400 block mb-2"></i>
                        <span class="text-green-500 font-semibold">ممتاز! لا يوجد ديون</span>
                    </div>
                </div>
            @else
                @php $maxDebt = $debtSuppliers->first()->balance; @endphp
                <div class="space-y-3">
                    @foreach ($debtSuppliers as $sup)
                        @php $pct = $maxDebt > 0 ? ($sup->balance / $maxDebt) * 100 : 0; @endphp
                        <div class="bg-red-50 rounded-xl p-3">
                            <div class="flex items-center justify-between mb-1.5">
                                <a href="{{ route('suppliers.show', $sup) }}"
                                    class="font-semibold text-sm text-gray-800 hover:text-red-600 transition truncate max-w-28">
                                    {{ $sup->name }}
                                </a>
                                <span class="font-bold text-sm text-red-600">{{ number_format($sup->balance, 2) }}
                                    ج.م</span>
                            </div>
                            <div class="h-2 bg-red-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500 rounded-full rank-bar" style="width: {{ $pct }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-3 border-t flex justify-between text-sm">
                    <span class="text-gray-500">الإجمالي</span>
                    <span class="font-bold text-red-600">{{ number_format($totalAllDebt, 2) }} ج.م</span>
                </div>
            @endif
        </div>
    </div>

    {{-- ===== أعلى المنتجات + طرق الدفع ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- طرق الدفع --}}
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-credit-card text-green-500"></i>
                طرق الدفع
            </h3>
            @php
                $payIcons = ['cash' => '💵', 'card' => '💳', 'transfer' => '🏦', 'deferred' => '📋'];
                $payLabels = ['cash' => 'كاش', 'card' => 'بطاقة', 'transfer' => 'تحويل', 'deferred' => 'آجل'];
                $payBarColors = [
                    'cash' => 'bg-green-500',
                    'card' => 'bg-blue-500',
                    'transfer' => 'bg-purple-500',
                    'deferred' => 'bg-red-400',
                ];
            @endphp
            @forelse($paymentChart as $pm)
                @php
                    $pct = $totalSpent > 0 ? ($pm->total / $totalSpent) * 100 : 0;
                @endphp
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">
                            {{ $payIcons[$pm->payment_method] ?? '💰' }}
                            {{ $payLabels[$pm->payment_method] ?? $pm->payment_method }}
                        </span>
                        <span class="text-gray-500">{{ $pm->count }} — {{ number_format($pm->total, 0) }} ج.م</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $payBarColors[$pm->payment_method] ?? 'bg-green-500' }} rounded-full rank-bar"
                            style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5 text-left">{{ number_format($pct, 1) }}%</div>
                </div>
            @empty
                <div class="text-center text-gray-300 py-8 text-sm">لا توجد بيانات</div>
            @endforelse
        </div>

        {{-- أعلى المنتجات شراءً --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-500"></i>
                أعلى المنتجات شراءً — {{ $periodLabel }}
            </h3>
            @if ($topProducts->isEmpty())
                <div class="text-center text-gray-300 py-8 text-sm">
                    <i class="fas fa-box-open text-3xl block mb-2"></i>لا توجد بيانات
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-right">
                                <th class="px-3 py-2 rounded-r-lg font-medium">#</th>
                                <th class="px-3 py-2 font-medium">المنتج</th>
                                <th class="px-3 py-2 font-medium">الكمية</th>
                                <th class="px-3 py-2 font-medium">مرات الشراء</th>
                                <th class="px-3 py-2 font-medium">متوسط سعر الشراء</th>
                                <th class="px-3 py-2 font-medium text-green-600 rounded-l-lg">إجمالي التكلفة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($topProducts as $i => $p)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-3 py-2.5">
                                        @if ($i === 0)
                                            🥇
                                        @elseif($i === 1)
                                            🥈
                                        @elseif($i === 2)
                                            🥉
                                        @else
                                            <span class="text-gray-400 text-xs">{{ $i + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="font-semibold text-gray-800">{{ $p->product_name }}</div>
                                        @if ($p->category)
                                            <div class="text-xs text-green-500">{{ $p->category }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-600 font-semibold">{{ number_format($p->total_qty) }}
                                        وحدة</td>
                                    <td class="px-3 py-2.5">
                                        <span
                                            class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-semibold">
                                            {{ $p->times_purchased }} مرة
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-500">{{ number_format($p->avg_purchase_price, 2) }}
                                        ج.م</td>
                                    <td class="px-3 py-2.5 font-bold text-green-700">
                                        {{ number_format($p->total_cost, 2) }} ج.م</td>
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
            <i class="fas fa-list text-green-500"></i>
            آخر فواتير الشراء
            <span class="text-xs text-gray-400 font-normal mr-1">({{ $periodLabel }})</span>
        </h3>

        @if ($recentInvoices->isEmpty())
            <div class="text-center text-gray-300 py-10 text-sm">
                <i class="fas fa-file-invoice text-4xl block mb-2"></i>
                لا توجد فواتير في هذه الفترة
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-right">
                            <th class="px-4 py-2 rounded-r-lg font-medium">رقم الفاتورة</th>
                            <th class="px-4 py-2 font-medium">التاريخ</th>
                            <th class="px-4 py-2 font-medium">المورد</th>
                            <th class="px-4 py-2 font-medium">الأصناف</th>
                            <th class="px-4 py-2 font-medium">طريقة الدفع</th>
                            <th class="px-4 py-2 font-medium">الحالة</th>
                            <th class="px-4 py-2 font-medium rounded-l-lg">الإجمالي</th>
                            <th class="px-4 py-2 font-medium rounded-l-lg"></th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($recentInvoices as $inv)
                            @php
                                $statusMap = [
                                    'paid' => ['✅ مسدد', 'bg-green-100 text-green-700'],
                                    'partial' => ['⏳ جزئي', 'bg-orange-100 text-orange-700'],
                                    'unpaid' => ['🔴 غير مسدد', 'bg-red-100 text-red-700'],
                                    'deferred' => ['🔴 آجل', 'bg-red-100 text-red-700'],
                                ];
                                [$sLabel, $sClass] = $statusMap[$inv->payment_status] ?? [
                                    '—',
                                    'bg-gray-100 text-gray-500',
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3">
                                    <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">
                                        {{ $inv->invoice_number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    <div>
                                        {{ optional($inv->invoice_date)->format('Y-m-d') ?? $inv->created_at->format('Y-m-d') }}
                                    </div>
                                    <div class="text-gray-400">{{ $inv->created_at->format('H:i') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($inv->supplier)
                                        <div class="font-semibold text-green-700 text-xs">{{ $inv->supplier->name }}</div>
                                        <div class="font-mono text-xs text-gray-400">{{ $inv->supplier->code }}</div>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($inv->items->take(2) as $item)
                                            <span class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full">
                                                {{ $item->product_name }} ×{{ $item->quantity }}
                                            </span>
                                        @endforeach
                                        @if ($inv->items->count() > 2)
                                            <span class="text-xs text-gray-400">+{{ $inv->items->count() - 2 }}
                                                أخرى</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                        {{ $payIcons[$inv->payment_method] ?? '💰' }}
                                        {{ $payLabels[$inv->payment_method] ?? $inv->payment_method }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="text-xs px-2 py-1 rounded-full {{ $sClass }}">{{ $sLabel }}</span>
                                    @if ($inv->remaining > 0)
                                        <div class="text-xs text-red-500 mt-0.5">متبقي:
                                            {{ number_format($inv->remaining, 2) }} ج.م</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-bold text-green-700">
                                    {{ number_format($inv->net_total, 2) }} ج.م
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('purchases.print', $inv) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 text-xs bg-green-50 text-green-700
              hover:bg-green-100 px-3 py-1.5 rounded-lg transition font-semibold">
                                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path
                                                d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                                            <rect x="6" y="14" width="12" height="8" rx="1" />
                                        </svg>
                                        طباعة
                                    </a>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const chartLabels = @json($chartLabels);
        const chartSpent = @json($chartSpent);
        const chartCount = @json($chartCount);
        const catLabels = @json($categoryChart->pluck('category'));
        const catTotals = @json($categoryChart->pluck('total')->map(fn($v) => round($v, 2)));

        const pieColors = [
            '#10b981', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#3b82f6',
            '#a855f7', '#14b8a6', '#eab308',
        ];

        // ===== الرسم البياني اليومي =====
        new Chart(document.getElementById('lineChart'), {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                        label: 'المشتريات (ج.م)',
                        data: chartSpent,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y',
                    },
                    {
                        label: 'عدد الفواتير',
                        data: chartCount,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.05)',
                        borderWidth: 2,
                        pointBackgroundColor: '#6366f1',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Cairo'
                            },
                            boxWidth: 12
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.datasetIndex === 0 ?
                                ` ${ctx.parsed.y.toFixed(2)} ج.م` :
                                ` ${ctx.parsed.y} فاتورة`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Cairo',
                                size: 11
                            }
                        }
                    },
                    y: {
                        position: 'right',
                        grid: {
                            color: 'rgba(0,0,0,0.04)'
                        },
                        ticks: {
                            font: {
                                family: 'Cairo',
                                size: 11
                            },
                            callback: v => v + ' ج.م'
                        }
                    },
                    y1: {
                        position: 'left',
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Cairo',
                                size: 11
                            },
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // ===== الرسم الدائري =====
        @if (!$categoryChart->isEmpty())
            new Chart(document.getElementById('pieChart'), {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catTotals,
                        backgroundColor: pieColors.slice(0, catLabels.length),
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: {
                                    family: 'Cairo',
                                    size: 11
                                },
                                boxWidth: 12,
                                padding: 10
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.label}: ${ctx.parsed.toFixed(2)} ج.م`
                            }
                        }
                    },
                    cutout: '60%',
                }
            });
        @endif

        // ===== animate bars on load =====
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.rank-bar').forEach(bar => {
                const w = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => bar.style.width = w, 100);
            });
        });
    </script>
@endsection
