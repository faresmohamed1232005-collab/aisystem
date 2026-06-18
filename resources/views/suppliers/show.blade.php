@extends('layouts.app')
@section('title', 'ملف المورد')
@section('content')
<div class="space-y-6">

    {{-- ── Header ── --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('suppliers.index') }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-right text-lg"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $supplier->name }}</h2>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="font-mono text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-lg">{{ $supplier->code }}</span>
                    @if($supplier->company)
                    <span class="text-xs text-gray-400">{{ $supplier->company }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}"
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-file-invoice"></i> فاتورة شراء جديدة
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif

    {{-- ── كروت الملخص المالي ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- إجمالي المشتريات --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border-r-4 border-blue-400">
            <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shopping-cart text-blue-500 text-xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">إجمالي المشتريات</div>
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalPurchased, 2) }}</div>
                <div class="text-xs text-gray-400">ج.م</div>
            </div>
        </div>

        {{-- إجمالي المدفوع --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border-r-4 border-green-400">
            <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">إجمالي المدفوع</div>
                <div class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 2) }}</div>
                <div class="text-xs text-gray-400">ج.م</div>
            </div>
        </div>

        {{-- الرصيد (موجب = عليك / سالب = لصالحك / صفر = مسدد) --}}
        @php
            $isCredit  = $totalDebt < 0;   // رصيد لصالحك
            $isEven    = $totalDebt == 0;   // مسدد بالكامل
            $isOwed    = $totalDebt > 0;    // عليك فلوس

            $borderColor = $isOwed   ? 'border-red-400'
                         : ($isCredit ? 'border-blue-400' : 'border-green-400');
            $bgColor     = $isOwed   ? 'bg-red-50'
                         : ($isCredit ? 'bg-blue-50'  : 'bg-green-50');
            $iconColor   = $isOwed   ? 'text-red-500'
                         : ($isCredit ? 'text-blue-500' : 'text-green-500');
            $icon        = $isOwed   ? 'fa-hourglass-half'
                         : ($isCredit ? 'fa-hand-holding-usd' : 'fa-check-circle');
            $label       = $isOwed   ? 'المتبقي عليك'
                         : ($isCredit ? 'رصيد لصالحك'   : 'الحساب');
            $textColor   = $isOwed   ? 'text-red-500'
                         : ($isCredit ? 'text-blue-600'  : 'text-green-600');
        @endphp

        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 border-r-4 {{ $borderColor }}">
            <div class="w-12 h-12 {{ $bgColor }} rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas {{ $icon }} {{ $iconColor }} text-xl"></i>
            </div>
            <div>
                <div class="text-xs text-gray-400 mb-0.5">{{ $label }}</div>
                @if($isEven)
                    <div class="text-xl font-bold {{ $textColor }}">مسدد بالكامل ✓</div>
                @else
                    <div class="text-2xl font-bold {{ $textColor }}">{{ number_format(abs($totalDebt), 2) }}</div>
                    <div class="text-xs text-gray-400">ج.م</div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── بيانات المورد ── --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><div class="text-gray-400 text-xs mb-1">التليفون</div><div class="font-semibold">{{ $supplier->phone ?? '—' }}</div></div>
        <div><div class="text-gray-400 text-xs mb-1">البريد</div><div class="font-semibold">{{ $supplier->email ?? '—' }}</div></div>
        <div><div class="text-gray-400 text-xs mb-1">الرقم الضريبي</div><div class="font-semibold">{{ $supplier->tax_number ?? '—' }}</div></div>
        <div><div class="text-gray-400 text-xs mb-1">العنوان</div><div class="font-semibold">{{ $supplier->address ?? '—' }}</div></div>
    </div>

    {{-- ── فواتير الشراء ── --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b flex items-center gap-2">
            <i class="fas fa-file-invoice text-green-500"></i>
            <h3 class="font-bold text-gray-700">فواتير الشراء</h3>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">رقم الفاتورة</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                    <th class="px-4 py-3 font-medium">الأصناف</th>
                    <th class="px-4 py-3 font-medium">الإجمالي</th>
                    <th class="px-4 py-3 font-medium">المدفوع</th>
                    <th class="px-4 py-3 font-medium">المتبقي</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($supplier->purchaseInvoices as $invoice)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">{{ $invoice->invoice_number }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ $invoice->invoice_date?->format('Y-m-d') ?? $invoice->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="toggleItems('inv-{{ $invoice->id }}')"
                                class="text-indigo-500 text-xs hover:underline">
                            {{ $invoice->items->count() }} صنف <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="inv-{{ $invoice->id }}" class="hidden mt-2 space-y-1">
                            @foreach($invoice->items as $item)
                            <div class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-lg flex justify-between">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 font-semibold">{{ number_format($invoice->net_total, 2) }} ج.م</td>
                    <td class="px-4 py-3 text-green-600">{{ number_format($invoice->paid, 2) }} ج.م</td>
                    <td class="px-4 py-3 font-bold {{ $invoice->remaining > 0 ? 'text-red-500' : 'text-green-600' }}">
                        {{ number_format($invoice->remaining, 2) }} ج.م
                    </td>
                    <td class="px-4 py-3">
                        @if($invoice->payment_status === 'paid')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">✅ مسدد</span>
                        @elseif($invoice->payment_status === 'partial')
                            <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">⏳ جزئي</span>
                        @else
                            <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">🔴 غير مسدد</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($invoice->remaining > 0)
                        <button onclick="openPayModal({{ $invoice->id }}, {{ $invoice->remaining }}, '{{ $invoice->invoice_number }}')"
                                class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition font-semibold">
                            <i class="fas fa-money-bill ml-1"></i> سداد
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-10 text-gray-300">
                    <i class="fas fa-file-invoice text-3xl block mb-2"></i>مفيش فواتير
                </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── مرتجعات الشراء ── --}}
    @if($purchaseReturns->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-undo text-orange-500"></i>
                <h3 class="font-bold text-gray-700">مرتجعات الشراء</h3>
                <span class="text-xs bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-semibold">
                    {{ $purchaseReturns->count() }} مرتجع
                </span>
            </div>
            <div class="text-sm text-gray-500">
                إجمالي المرتجعات:
                <span class="font-bold text-orange-600">{{ number_format($purchaseReturns->sum('total'), 2) }} ج.م</span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">رقم المرتجع</th>
                    <th class="px-4 py-3 font-medium">الفاتورة الأصلية</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                    <th class="px-4 py-3 font-medium">الأصناف المرتجعة</th>
                    <th class="px-4 py-3 font-medium">قيمة المرتجع</th>
                    <th class="px-4 py-3 font-medium">طريقة الاسترداد</th>
                    <th class="px-4 py-3 font-medium">السبب</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($purchaseReturns as $return)
                <tr class="hover:bg-orange-50/40 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-lg">
                            {{ $return->return_number }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($return->purchaseInvoice)
                        <div class="flex flex-col gap-0.5">
                            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-lg inline-block w-fit">
                                {{ $return->purchaseInvoice->invoice_number }}
                            </span>
                            <span class="text-xs text-gray-400">
                                باقي على الفاتورة:
                                <span class="font-bold {{ $return->purchaseInvoice->remaining > 0 ? 'text-red-500' : 'text-green-600' }}">
                                    {{ number_format($return->purchaseInvoice->remaining, 2) }} ج.م
                                </span>
                            </span>
                        </div>
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">
                        {{ $return->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-4 py-3">
                        <button onclick="toggleItems('ret-{{ $return->id }}')"
                                class="text-orange-500 text-xs hover:underline">
                            {{ $return->items->count() }} صنف <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="ret-{{ $return->id }}" class="hidden mt-2 space-y-1">
                            @foreach($return->items as $item)
                            <div class="text-xs bg-orange-50 text-orange-700 px-2 py-1 rounded-lg flex justify-between">
                                <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                            </div>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-4 py-3 font-bold text-orange-600">
                        {{ number_format($return->total, 2) }} ج.م
                    </td>
                    <td class="px-4 py-3">
                        @if($return->refund_method === 'balance')
                            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">💳 خصم من الرصيد</span>
                        @elseif($return->refund_method === 'cash')
                            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">💵 رد نقدي</span>
                        @else
                            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded-full">🚫 بدون استرداد</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $return->reason ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

{{-- ── Modal السداد ── --}}
<div id="pay-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 shadow-2xl w-full max-w-sm mx-4">
        <h3 class="font-bold text-gray-800 mb-1">سداد للمورد</h3>
        <p id="pay-invoice-label" class="text-xs text-gray-400 mb-4"></p>
        <form id="pay-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المبلغ (ج.م)</label>
                <input type="number" name="amount" id="pay-amount" required min="0.01" step="0.01"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                <div class="text-xs text-gray-400 mt-1">المتبقي: <span id="pay-remaining" class="font-bold text-red-500"></span> ج.م</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الدفع</label>
                <select name="payment_method" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
                    <option value="cash">💵 كاش</option>
                    <option value="card">💳 بطاقة</option>
                    <option value="transfer">🏦 تحويل بنكي</option>
                    <option value="wallet">📱 محفظة</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <input type="text" name="notes"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                       placeholder="اختياري">
            </div>
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition">
                    <i class="fas fa-check ml-1"></i> تسجيل الدفع
                </button>
                <button type="button" onclick="closePayModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-600 py-3 rounded-xl transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleItems(id) {
    document.getElementById(id).classList.toggle('hidden');
}

function openPayModal(invoiceId, remaining, invoiceNum) {
    document.getElementById('pay-form').action = `/suppliers/pay-invoice/${invoiceId}`;
    document.getElementById('pay-amount').value = parseFloat(remaining).toFixed(2);
    document.getElementById('pay-amount').max   = remaining;
    document.getElementById('pay-remaining').textContent = parseFloat(remaining).toFixed(2);
    document.getElementById('pay-invoice-label').textContent = 'فاتورة: ' + invoiceNum;
    document.getElementById('pay-modal').classList.remove('hidden');
}

function closePayModal() {
    document.getElementById('pay-modal').classList.add('hidden');
}
</script>
@endsection