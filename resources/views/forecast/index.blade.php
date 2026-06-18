@extends('layouts.app')

@section('title', 'توقعات الطلب')

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- ═══════ Header ═══════ --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-lg shadow-purple-200">
                <i class="fas fa-robot text-lg"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">توقعات الطلب وإعادة الشراء</h2>
                <p class="text-sm text-gray-500 mt-0.5">تحليل آخر {{ $days }} يوم ({{ $dateFrom->format('d/m/Y') }} - {{ $dateTo->format('d/m/Y') }})</p>
            </div>
        </div>
    </div>

    @if(count($recommendations) === 0)
        <div class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
            <i class="fas fa-chart-line text-4xl mb-3"></i>
            <p>لا توجد بيانات مبيعات كافية خلال آخر {{ $days }} يوم</p>
        </div>
    @else

        {{-- ═══════ كروت الأولوية (Top 3) ═══════ --}}
        <div class="mb-6">
            <h3 class="text-sm font-bold text-gray-500 mb-3 flex items-center gap-2">
                <i class="fas fa-star text-purple-500"></i>
                الأكثر مبيعاً — اشتري منها أكتر
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach(array_slice($recommendations, 0, 3) as $i => $rec)
                    <div class="relative bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl shadow-lg shadow-purple-200/60 p-5 text-white overflow-hidden">

                        {{-- رقم الترتيب --}}
                        <div class="absolute top-3 left-3 w-8 h-8 rounded-full bg-white/15 flex items-center justify-center text-sm font-bold">
                            #{{ $i + 1 }}
                        </div>

                        {{-- زخرفة دائرية --}}
                        <div class="absolute -left-8 -bottom-8 w-32 h-32 rounded-full bg-white/5"></div>

                        <div class="relative">
                            <div class="text-xs font-semibold text-purple-200 mb-1">
                                <i class="fas fa-fire"></i> الأكثر طلباً
                            </div>
                            <h4 class="text-lg font-bold mb-3 truncate">{{ $rec['name'] }}</h4>

                            <div class="grid grid-cols-2 gap-2 text-xs mb-4">
                                <div class="bg-white/10 rounded-lg p-2">
                                    <div class="text-purple-200">إجمالي المباع</div>
                                    <div class="font-bold text-base">{{ $rec['total_qty_sold'] }}</div>
                                </div>
                                <div class="bg-white/10 rounded-lg p-2">
                                    <div class="text-purple-200">معدل يومي</div>
                                    <div class="font-bold text-base">{{ $rec['daily_avg'] }}</div>
                                </div>
                            </div>

                    

                            @if($rec['days_coverage'] !== null)
                                <div class="mt-2 text-xs text-purple-200">
                                    <i class="fas fa-box-open"></i>
                                    مخزونك الحالي يكفي لـ {{ $rec['days_coverage'] }} يوم
                                    @if($rec['urgent'])
                                        <span class="bg-red-500 text-white px-2 py-0.5 rounded-full mr-1">اشتري الآن</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ═══════ الجدول الكامل ═══════ --}}
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                <i class="fas fa-list-ul text-purple-500"></i>
                <h3 class="text-sm font-bold text-gray-700">كل المنتجات (آخر {{ $days }} يوم)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-purple-50 text-purple-700 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-right">#</th>
                            <th class="px-4 py-3 text-right">المنتج</th>
                            <th class="px-4 py-3 text-center">إجمالي المباع</th>
                            <th class="px-4 py-3 text-center">إجمالي الإيراد</th>
                            <th class="px-4 py-3 text-center">معدل البيع اليومي</th>
                            <th class="px-4 py-3 text-center">المخزون الحالي</th>
                            <th class="px-4 py-3 text-center">أيام التغطية</th>
                            <th class="px-4 py-3 text-center">الكمية المقترحة للشراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recommendations as $i => $rec)
                            <tr class="hover:bg-purple-50/50 transition {{ $rec['urgent'] ? 'bg-red-50' : '' }}">
                                <td class="px-4 py-3 text-gray-400 font-semibold">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-700">
                                    {{ $rec['name'] }}
                                    @if($i < 3)
                                        <span class="mr-1 bg-purple-100 text-purple-600 text-[10px] px-2 py-0.5 rounded-full">
                                            <i class="fas fa-star"></i> الأكثر طلباً
                                        </span>
                                    @endif
                                    @if($rec['urgent'])
                                        <span class="mr-1 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full">عاجل</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">{{ $rec['total_qty_sold'] }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($rec['total_revenue'], 2) }} ج.م</td>
                                <td class="px-4 py-3 text-center">{{ $rec['daily_avg'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $rec['current_stock'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($rec['days_coverage'] !== null)
                                        <span class="{{ $rec['days_coverage'] <= 7 ? 'text-red-600 font-bold' : ($rec['days_coverage'] <= 15 ? 'text-orange-500 font-bold' : 'text-green-600') }}">
                                            {{ $rec['days_coverage'] }} يوم
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($rec['suggested_reorder'] > 0)
                                        <span class="bg-purple-100 text-purple-700 font-bold px-3 py-1 rounded-lg">
                                            {{ $rec['suggested_reorder'] }} {{ $rec['major_units'] > 1 ? 'علبة' : 'وحدة' }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">كافٍ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection