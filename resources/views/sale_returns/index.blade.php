@extends('layouts.app')
@section('title', 'مرتجعات المبيعات')

@section('content')
<div class="space-y-6" dir="rtl">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">مرتجعات المبيعات</h2>
            <span class="bg-orange-100 text-orange-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $returns->total() }} مرتجع
            </span>
        </div>
        <a href="{{ route('sale-returns.create') }}"
           class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            مرتجع جديد
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm flex items-center gap-2">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $returns->total() }}</div>
            <div class="text-xs text-gray-400 mt-1">إجمالي المرتجعات</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-orange-500">{{ number_format($totalValue, 0) }}</div>
            <div class="text-xs text-gray-400 mt-1">قيمة المرتجعات (ج.م)</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $returns->where('refund_method','cash')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">رد نقدي</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $returns->where('refund_method','balance')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">رصيد عميل</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <input type="text" name="q" value="{{ $q }}"
                    placeholder="ابحث برقم المرتجع أو رقم الفاتورة..."
                    class="w-full border border-gray-200 rounded-xl pr-4 pl-4 py-2.5 text-sm focus:outline-none focus:border-orange-400 text-right">
            </div>
            @foreach(['all'=>'الكل','today'=>'اليوم','week'=>'هذا الأسبوع','month'=>'هذا الشهر'] as $val=>$label)
            <button type="submit" name="period" value="{{ $val }}"
                class="text-sm px-4 py-2.5 rounded-xl border transition font-medium
                {{ ($period??'all')===$val ? 'bg-orange-500 text-white border-orange-500' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-orange-50 hover:border-orange-300' }}">
                {{ $label }}
            </button>
            @endforeach
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">رقم المرتجع</th>
                    <th class="px-4 py-3 font-medium">فاتورة البيع</th>
                    <th class="px-4 py-3 font-medium">العميل</th>
                    <th class="px-4 py-3 font-medium">الأصناف</th>
                    <th class="px-4 py-3 font-medium">القيمة</th>
                    <th class="px-4 py-3 font-medium">طريقة الاسترداد</th>
                    <th class="px-4 py-3 font-medium">السبب</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($returns as $ret)
                @php
                    $refundBadge = match($ret->refund_method){
                        'cash'    => ['رد نقدي',       'bg-green-100 text-green-700'],
                        'balance' => ['رصيد العميل',   'bg-blue-100 text-blue-700'],
                        default   => ['بدون رد',       'bg-gray-100 text-gray-500'],
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('sale-returns.show', $ret) }}"
                           class="font-mono text-xs bg-orange-50 text-orange-700 hover:bg-orange-100 px-2 py-1 rounded-lg transition">
                            {{ $ret->return_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('sales.show', $ret->sale) }}"
                           class="font-mono text-xs bg-gray-100 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 px-2 py-1 rounded-lg transition">
                            {{ $ret->sale->invoice_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700 font-medium text-sm">
                        {{ $ret->customer?->name ?? 'عميل عام' }}
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                            class="text-orange-600 text-xs hover:underline font-semibold">
                            {{ $ret->items->count() }} صنف
                        </button>
                        <div class="hidden mt-2 space-y-1">
                            @foreach($ret->items as $item)
                            <div class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded-lg flex justify-between">
                                <span>{{ $item->product?->name ?? 'منتج' }} × {{ $item->quantity }}</span>
                                <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 font-bold text-orange-600">
                        {{ number_format($ret->total, 2) }} ج.م
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $refundBadge[1] }}">{{ $refundBadge[0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $ret->reason ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $ret->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('sale-returns.print', $ret) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs bg-orange-50 text-orange-700 hover:bg-orange-100 px-3 py-1.5 rounded-lg transition font-semibold">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                طباعة
                            </a>
                            <form action="{{ route('sale-returns.destroy', $ret) }}" method="POST"
                                  onsubmit="return confirm('هل أنت متأكد؟ سيتم عكس تأثير المرتجع على المخزن والرصيد')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition font-semibold">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-14 text-gray-300">
                        <div class="text-4xl mb-2">↩️</div>
                        لا توجد مرتجعات بعد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($returns->hasPages())
        <div class="p-4 border-t">{{ $returns->links() }}</div>
        @endif
    </div>
</div>
@endsection
