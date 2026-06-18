@extends('layouts.app')
@section('title', 'مرتجعات المشتريات')

@section('content')
<div class="space-y-6" dir="rtl">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">مرتجعات المشتريات</h2>
            <span class="bg-blue-100 text-blue-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $returns->total() }} مرتجع
            </span>
        </div>
        <a href="{{ route('purchase-returns.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            مرتجع شراء جديد
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm flex items-center gap-2">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
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
            <div class="text-2xl font-bold text-blue-600">{{ number_format($totalValue, 0) }}</div>
            <div class="text-xs text-gray-400 mt-1">قيمة المرتجعات (ج.م)</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $returns->where('refund_method','cash')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">رد نقدي من المورد</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $returns->where('refund_method','balance')->count() }}</div>
            <div class="text-xs text-gray-400 mt-1">خصم من رصيد المورد</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-48">
                <input type="text" name="q" value="{{ $q }}"
                    placeholder="ابحث برقم المرتجع أو رقم الفاتورة أو المورد..."
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right">
            </div>
            @foreach(['all'=>'الكل','today'=>'اليوم','week'=>'هذا الأسبوع','month'=>'هذا الشهر'] as $val=>$label)
            <button type="submit" name="period" value="{{ $val }}"
                class="text-sm px-4 py-2.5 rounded-xl border transition font-medium
                {{ ($period??'all')===$val ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-blue-50 hover:border-blue-300' }}">
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
                    <th class="px-4 py-3 font-medium">فاتورة الشراء</th>
                    <th class="px-4 py-3 font-medium">المورد</th>
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
                        'cash'    => ['رد نقدي من المورد',    'bg-green-100 text-green-700'],
                        'balance' => ['خصم من رصيد المورد',  'bg-purple-100 text-purple-700'],
                        default   => ['بدون رد',              'bg-gray-100 text-gray-500'],
                    };
                @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('purchase-returns.show', $ret) }}"
                           class="font-mono text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 px-2 py-1 rounded-lg transition">
                            {{ $ret->return_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">
                            {{ $ret->purchaseInvoice->invoice_number }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-700 font-medium text-sm">
                        {{ $ret->supplier?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                            class="text-blue-600 text-xs hover:underline font-semibold">
                            {{ $ret->items->count() }} صنف
                        </button>
                        <div class="hidden mt-2 space-y-1">
                            @foreach($ret->items as $item)
                            <div class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-lg flex justify-between">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 font-bold text-blue-600">{{ number_format($ret->total, 2) }} ج.م</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $refundBadge[1] }}">{{ $refundBadge[0] }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $ret->reason ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $ret->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('purchase-returns.print', $ret) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition font-semibold">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                                طباعة
                            </a>
                            <form action="{{ route('purchase-returns.destroy', $ret) }}" method="POST"
                                  onsubmit="return confirm('سيتم عكس تأثير المرتجع على المخزن ورصيد المورد. متأكد؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 text-xs bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg transition font-semibold">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
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
                        لا توجد مرتجعات مشتريات بعد
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
