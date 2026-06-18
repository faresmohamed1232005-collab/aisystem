@extends('layouts.app')
@section('title', 'ملف العميل')

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.index') }}" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-arrow-right text-lg"></i>
                </a>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $customer->name }}</h2>
                    <span class="font-mono text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-lg">
                        {{ $customer->code }}
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('customers.edit', $customer) }}"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                    <i class="fas fa-edit"></i> تعديل
                </a>
            </div>
        </div>

        {{-- بطاقات الإحصائيات --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $customer->sales->count() }}</div>
                <div class="text-xs text-gray-400 mt-1">فاتورة</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ number_format($totalBought, 0) }}</div>
                <div class="text-xs text-gray-400 mt-1">إجمالي المشتريات (ج.م)</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 0) }}</div>
                <div class="text-xs text-gray-400 mt-1">إجمالي المدفوع (ج.م)</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold {{ $totalDebt > 0 ? 'text-red-500' : 'text-green-600' }}">
                    {{ number_format($totalDebt, 0) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">الرصيد المديون (ج.م)</div>
            </div>
        </div>

        {{-- معلومات العميل --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <div class="text-gray-400 text-xs mb-1">التليفون</div>
                <div class="font-semibold text-gray-700">{{ $customer->phone ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-400 text-xs mb-1">البريد</div>
                <div class="font-semibold text-gray-700">{{ $customer->email ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-400 text-xs mb-1">حد الآجل</div>
                <div class="font-semibold text-gray-700">{{ number_format($customer->credit_limit, 2) }} ج.م</div>
            </div>
            <div>
                <div class="text-gray-400 text-xs mb-1">العنوان</div>
                <div class="font-semibold text-gray-700">{{ $customer->address ?? '—' }}</div>
            </div>
        </div>

        {{-- فواتير العميل --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b flex items-center gap-2">
                <i class="fas fa-receipt text-indigo-500"></i>
                <h3 class="font-bold text-gray-700">الفواتير</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-4 py-3 font-medium">رقم الفاتورة</th>
                        <th class="px-4 py-3 font-medium">التاريخ</th>
                        <th class="px-4 py-3 font-medium">المنتجات</th>
                        <th class="px-4 py-3 font-medium">الإجمالي</th>
                        <th class="px-4 py-3 font-medium">المدفوع</th>
                        <th class="px-4 py-3 font-medium">المتبقي</th>
                        <th class="px-4 py-3 font-medium">الحالة</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($customer->sales as $sale)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-lg">
                                    {{ $sale->invoice_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $sale->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                <button onclick="toggleItems({{ $sale->id }})"
                                    class="text-indigo-500 text-xs hover:underline">
                                    {{ $sale->items->count() }} منتج
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </button>
                                {{-- تفاصيل المنتجات --}}
                                <div id="items-{{ $sale->id }}" class="hidden mt-2 space-y-1">
                                    @foreach ($sale->items as $item)
                                        <div
                                            class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-lg flex justify-between">
                                            {{ $item->drug->name_ar ?? ($item->drug->name_en ?? 'محذوف') }} ×
                                            {{ $item->quantity }}</span>
                                            <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ number_format($sale->total, 2) }} ج.م</td>
                            <td class="px-4 py-3 text-green-600">{{ number_format($sale->paid, 2) }} ج.م</td>
                            <td class="px-4 py-3 font-bold {{ $sale->remaining > 0 ? 'text-red-500' : 'text-green-600' }}">
                                {{ number_format($sale->remaining, 2) }} ج.م
                            </td>
                            <td class="px-4 py-3">
                                @if ($sale->payment_status === 'paid')
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">✅ مسدد</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">⏳ جزئي</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">🔴 آجل</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($sale->remaining > 0)
                                    <button
                                        onclick="openPayModal({{ $sale->id }}, {{ $sale->remaining }}, '{{ $sale->invoice_number }}')"
                                        class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition font-semibold">
                                        <i class="fas fa-money-bill-wave ml-1"></i> سداد
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-gray-300">
                                <i class="fas fa-receipt text-3xl block mb-2"></i>
                                مفيش فواتير
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== Modal السداد ===== --}}
    <div id="pay-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 shadow-2xl w-full max-w-sm mx-4">
            <h3 class="font-bold text-gray-800 mb-1">سداد فاتورة</h3>
            <p id="pay-invoice-label" class="text-xs text-gray-400 mb-4"></p>

            @if (session('success'))
                <div class="bg-green-100 text-green-700 text-sm rounded-xl p-3 mb-4">{{ session('success') }}</div>
            @endif

            <form id="pay-form" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المبلغ (ج.م)</label>
                    <input type="number" name="amount" id="pay-amount" required min="0.01" step="0.01"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                    <div class="text-xs text-gray-400 mt-1">المتبقي: <span id="pay-remaining"
                            class="font-bold text-red-500"></span> ج.م</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الدفع</label>
                    <select name="payment_method"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
                        <option value="cash">💵 كاش</option>
                        <option value="card">💳 بطاقة</option>
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
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition">
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
        function toggleItems(saleId) {
            const el = document.getElementById('items-' + saleId);
            el.classList.toggle('hidden');
        }

        function openPayModal(saleId, remaining, invoiceNum) {
            document.getElementById('pay-form').action = `/customers/pay-sale/${saleId}`;
            document.getElementById('pay-amount').value = remaining;
            document.getElementById('pay-amount').max = remaining;
            document.getElementById('pay-remaining').textContent = parseFloat(remaining).toFixed(2);
            document.getElementById('pay-invoice-label').textContent = 'فاتورة: ' + invoiceNum;
            document.getElementById('pay-modal').classList.remove('hidden');
        }

        function closePayModal() {
            document.getElementById('pay-modal').classList.add('hidden');
        }
    </script>
@endsection
