@extends('layouts.app')
@section('title', 'الطلبات المعلقة')

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-800">الطلبات المعلقة</h2>
                <span class="bg-orange-100 text-orange-700 text-sm px-3 py-1 rounded-full font-bold">
                    {{ $orders->count() }} طلب
                </span>
                @if ($confirmedToday > 0)
                    <span class="bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                        ✅ {{ $confirmedToday }} مؤكد اليوم
                    </span>
                @endif
            </div>
            <a href="{{ route('sales.create') }}"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition flex items-center gap-2">
                <i class="fas fa-plus"></i> طلب جديد
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600">
                @foreach ($errors->all() as $e)
                    <div>• {{ $e }}</div>
                @endforeach
            </div>
        @endif

        {{-- قائمة الطلبات --}}
        @forelse($orders as $order)
            <div
                class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100
                {{ $order->delivery_type === 'delivery' ? 'border-r-4 border-r-indigo-400' : 'border-r-4 border-r-gray-200' }}">

                {{-- Header الطلب --}}
                <div class="p-4 flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0
                            {{ $order->delivery_type === 'delivery' ? 'bg-indigo-100' : 'bg-gray-100' }}">
                            {{ $order->delivery_type === 'delivery' ? '🚚' : '🏪' }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-gray-800">{{ $order->order_number }}</span>
                                <span
                                    class="text-xs {{ $order->delivery_type === 'delivery' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }} px-2 py-0.5 rounded-full font-semibold">
                                    {{ $order->delivery_type === 'delivery' ? '🚚 توصيل منزلي' : '🏪 استلام من المحل' }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $order->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- بيانات العميل --}}
                            @if ($order->customer)
                                <div class="mt-1 flex items-center gap-2 text-sm">
                                    <i class="fas fa-user text-indigo-400 text-xs"></i>
                                    <span class="font-semibold text-indigo-700">{{ $order->customer->name }}</span>
                                    <span class="font-mono text-xs text-gray-400">{{ $order->customer->code }}</span>
                                    @if ($order->customer->phone)
                                        <a href="tel:{{ $order->customer->phone }}"
                                            class="text-gray-500 hover:text-indigo-600 text-xs">
                                            <i class="fas fa-phone ml-1"></i>{{ $order->customer->phone }}
                                        </a>
                                    @endif
                                </div>
                            @endif

                            {{-- بيانات التوصيل --}}
                            @if ($order->delivery_type === 'delivery')
                                <div class="mt-1 space-y-0.5">
                                    @if ($order->delivery_address)
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="fas fa-map-marker-alt text-red-400"></i>
                                            {{ $order->delivery_address }}
                                        </div>
                                    @endif
                                    @if ($order->delivery_phone)
                                        <div class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="fas fa-phone text-green-400"></i>
                                            {{ $order->delivery_phone }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($order->notes)
                                <div class="mt-1 text-xs text-gray-400 italic">
                                    <i class="fas fa-sticky-note ml-1"></i>{{ $order->notes }}
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- الإجمالي --}}
                    <div class="text-right flex-shrink-0">
                        <div class="text-xl font-bold text-indigo-600">{{ number_format($order->net_total, 2) }} ج.م</div>
                        @if ($order->discount > 0)
                            <div class="text-xs text-orange-500">خصم: {{ number_format($order->discount, 2) }} ج.م</div>
                        @endif
                        <div class="text-xs text-gray-400 mt-0.5">{{ $order->items->count() }} منتج</div>
                    </div>
                </div>

                {{-- المنتجات --}}
                <div class="px-4 pb-3">
                    <div class="bg-gray-50 rounded-xl p-3 space-y-2">
                        @foreach ($order->items as $item)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 bg-indigo-100 text-indigo-600 rounded-lg text-xs flex items-center justify-center font-bold">
                                        {{ $item->quantity }}
                                    </span>
                                    <span
                                        class="text-gray-700 font-medium">{{ $item->drug->name_ar ?? ($item->drug->name_en ?? 'محذوف') }}</span>
                                    @if ($item->drug?->category)
                                        <span class="text-xs text-gray-400">{{ $item->drug->category }}</span>
                                    @endif
                                </div>
                                <div class="text-gray-600 font-semibold">{{ number_format($item->subtotal, 2) }} ج.م</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ===== قسم التأكيد ===== --}}
                <div class="border-t border-gray-100 p-4 bg-gray-50">
                    <form action="{{ route('pending.confirm', $order) }}" method="POST"
                        class="flex flex-wrap items-end gap-3">
                        @csrf

                        {{-- طريقة الدفع --}}
                        <div class="flex-1 min-w-36">
                            <label class="block text-xs text-gray-500 mb-1 font-medium">طريقة الدفع *</label>
                            <select name="payment_method" required onchange="toggleCardType(this, {{ $order->id }})"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                                <option value="cash">💵 كاش</option>
                                <option value="card">💳 بطاقة</option>
                                <option value="insurance">🏥 تأمين</option>
                                @if ($order->customer_id)
                                    <option value="deferred">📋 آجل</option>
                                @endif
                            </select>
                        </div>

                        {{-- نوع البطاقة --}}
                        <div class="min-w-32" id="card-type-{{ $order->id }}" style="display:none">
                            <label class="block text-xs text-gray-500 mb-1 font-medium">نوع البطاقة</label>
                            <select name="card_type"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400">
                                <option value="visa">💳 فيزا</option>
                                <option value="instapay">⚡ إنستاباي</option>
                                <option value="wallet">📱 محفظة</option>
                            </select>
                        </div>

                        {{-- المبلغ المدفوع --}}
                        <div class="flex-1 min-w-32">
                            <label class="block text-xs text-gray-500 mb-1 font-medium">المبلغ المدفوع</label>
                            <input type="number" name="paid" value="{{ number_format($order->net_total, 2, '.', '') }}"
                                min="0" step="0.01"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400">
                        </div>

                        {{-- أزرار --}}
                        <div class="flex gap-2">
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2 rounded-xl transition text-sm flex items-center gap-2">
                                <i class="fas fa-check-circle"></i> تأكيد وإنشاء فاتورة
                            </button>
                        </div>
                    </form>

                    {{-- زرار الإلغاء منفصل --}}
                    <form action="{{ route('pending.destroy', $order) }}" method="POST" class="mt-2"
                        onsubmit="return confirm('هتلغي الطلب {{ $order->order_number }}؟')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="text-red-500 hover:text-red-700 text-xs flex items-center gap-1 hover:underline transition">
                            <i class="fas fa-times-circle"></i> إلغاء الطلب وإعادة المخزن
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-lg font-bold text-gray-600 mb-2">لا توجد طلبات معلقة</h3>
                <p class="text-gray-400 text-sm mb-6">كل الطلبات تم تأكيدها أو إلغاؤها</p>
                <a href="{{ route('sales.create') }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-xl text-sm font-semibold transition">
                    إضافة طلب جديد
                </a>
            </div>
        @endforelse
    </div>

@endsection

@section('scripts')
    <script>
        function toggleCardType(select, orderId) {
            const cardDiv = document.getElementById('card-type-' + orderId);
            cardDiv.style.display = select.value === 'card' ? 'block' : 'none';

            // لو آجل، المبلغ المدفوع = 0
            const paidInput = select.closest('form').querySelector('input[name="paid"]');
            if (select.value === 'deferred') {
                paidInput.value = '0';
                paidInput.readOnly = true;
            } else {
                paidInput.readOnly = false;
            }
        }
    </script>
@endsection
