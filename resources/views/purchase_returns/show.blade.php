{{-- ============================================================
     purchase_returns/show.blade.php
     ============================================================ --}}
@extends('layouts.app')
@section('title', 'تفاصيل مرتجع شراء')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto" dir="rtl">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase-returns.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
            </a>
            <h2 class="text-xl font-bold text-gray-800">مرتجع: {{ $purchaseReturn->return_number }}</h2>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchase-returns.print', $purchaseReturn) }}" target="_blank"
               class="bg-blue-50 text-blue-700 hover:bg-blue-100 px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
                طباعة
            </a>
            <form action="{{ route('purchase-returns.destroy', $purchaseReturn) }}" method="POST"
                  onsubmit="return confirm('سيتم عكس تأثير المرتجع على المخزن ورصيد المورد. متأكد؟')">
                @csrf @method('DELETE')
                <button class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                    حذف
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-xs text-gray-400 mb-1">رقم المرتجع</div>
                <div class="font-bold text-blue-600 font-mono">{{ $purchaseReturn->return_number }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">فاتورة الشراء</div>
                <div class="font-bold font-mono text-gray-700">{{ $purchaseReturn->purchaseInvoice->invoice_number }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">المورد</div>
                <div class="font-bold">{{ $purchaseReturn->supplier?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">تاريخ المرتجع</div>
                <div class="font-bold">{{ $purchaseReturn->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">إجمالي المرتجع</div>
                <div class="font-bold text-2xl text-blue-600">{{ number_format($purchaseReturn->total, 2) }} ج.م</div>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">طريقة الاسترداد</div>
                @php
                    $badge = match($purchaseReturn->refund_method){
                        'cash'    => ['رد نقدي من المورد',   'bg-green-100 text-green-700'],
                        'balance' => ['خصم من رصيد المورد', 'bg-purple-100 text-purple-700'],
                        default   => ['بدون رد',             'bg-gray-100 text-gray-500'],
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $badge[1] }}">{{ $badge[0] }}</span>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-1">السبب</div>
                <div class="font-medium">{{ $purchaseReturn->reason ?? '—' }}</div>
            </div>
            @if($purchaseReturn->notes)
            <div>
                <div class="text-xs text-gray-400 mb-1">ملاحظات</div>
                <div class="text-sm text-gray-600">{{ $purchaseReturn->notes }}</div>
            </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-bold text-gray-700">الأصناف المرتجعة</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-right font-medium">المنتج</th>
                    <th class="px-4 py-3 text-center font-medium">الكمية</th>
                    <th class="px-4 py-3 text-center font-medium">سعر الشراء</th>
                    <th class="px-4 py-3 text-center font-medium">الإجمالي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($purchaseReturn->items as $item)
                <tr>
                    <td class="px-4 py-3 font-semibold text-gray-800">
                        {{ $item->product_name }}
                        @if($item->product?->category)
                        <div class="text-xs text-indigo-400">{{ $item->product->category }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center font-bold">{{ $item->quantity }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($item->purchase_price, 2) }} ج.م</td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600">{{ number_format($item->subtotal, 2) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-blue-200">
                <tr>
                    <td colspan="3" class="px-4 py-3 text-left font-bold text-gray-700">الإجمالي</td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600 text-base">{{ number_format($purchaseReturn->total, 2) }} ج.م</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
