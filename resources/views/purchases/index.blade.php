@extends('layouts.app')
@section('title', 'فواتير الشراء')

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-800">فواتير الشراء</h2>
                <span class="bg-green-100 text-green-700 text-sm px-3 py-1 rounded-full font-semibold">
                    {{ $purchases->total() }} فاتورة
                </span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.import') }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
                    <i class="fas fa-camera"></i> استيراد من صورة
                </a>
                <a href="{{ route('purchases.create') }}"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
                    <i class="fas fa-plus"></i> فاتورة شراء جديدة
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        {{-- بطاقات الإحصائيات --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-gray-800">{{ $purchases->total() }}</div>
                <div class="text-xs text-gray-400 mt-1">إجمالي الفواتير</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ number_format($totalSpent, 0) }}</div>
                <div class="text-xs text-gray-400 mt-1">إجمالي المشتريات (ج.م)</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold {{ $totalDeferred > 0 ? 'text-red-500' : 'text-green-600' }}">
                    {{ number_format($totalDeferred, 0) }}
                </div>
                <div class="text-xs text-gray-400 mt-1">متبقي للموردين (ج.م)</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600">
                    {{ $purchases->where('payment_status', 'paid')->count() }}
                </div>
                <div class="text-xs text-gray-400 mt-1">فواتير مسددة</div>
            </div>
        </div>

        {{-- فلتر + بحث --}}
        <div class="bg-white rounded-2xl shadow-sm p-4">
            <form method="GET" class="flex flex-wrap items-center gap-3">
                {{-- بحث --}}
                <div class="relative flex-1 min-w-48">
                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="q" value="{{ $q }}"
                        placeholder="ابحث برقم الفاتورة أو اسم المورد..."
                        class="w-full border border-gray-200 rounded-xl pr-9 pl-4 py-2.5 text-sm focus:outline-none focus:border-green-400 text-right">
                </div>

                {{-- فلتر الفترة --}}
                @foreach (['all' => 'الكل', 'today' => 'اليوم', 'week' => 'هذا الأسبوع', 'month' => 'هذا الشهر'] as $val => $label)
                    <button type="submit" name="period" value="{{ $val }}"
                        class="text-sm px-4 py-2.5 rounded-xl border transition font-medium
                        {{ ($period ?? 'all') === $val
                            ? 'bg-green-600 text-white border-green-600'
                            : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-green-50 hover:border-green-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </form>
        </div>

        {{-- الجدول --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-4 py-3 font-medium">رقم الفاتورة</th>
                        <th class="px-4 py-3 font-medium">المورد</th>
                        <th class="px-4 py-3 font-medium">التاريخ</th>
                        <th class="px-4 py-3 font-medium">الأصناف</th>
                        <th class="px-4 py-3 font-medium">الإجمالي</th>
                        <th class="px-4 py-3 font-medium">المدفوع</th>
                        <th class="px-4 py-3 font-medium">المتبقي</th>
                        <th class="px-4 py-3 font-medium">طريقة الدفع</th>
                        <th class="px-4 py-3 font-medium">الحالة</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($purchases as $invoice)
                        @php
                            $payIcons = ['cash' => '💵', 'card' => '💳', 'transfer' => '🏦', 'deferred' => '📋'];
                            $payLabels = [
                                'cash' => 'كاش',
                                'card' => 'بطاقة',
                                'transfer' => 'تحويل',
                                'deferred' => 'آجل',
                            ];
                            $statusMap = [
                                'paid' => ['✅ مسدد', 'bg-green-100 text-green-700'],
                                'partial' => ['⏳ جزئي', 'bg-orange-100 text-orange-700'],
                                'unpaid' => ['🔴 غير مسدد', 'bg-red-100 text-red-700'],
                                'deferred' => ['🔴 آجل', 'bg-red-100 text-red-700'],
                            ];
                            [$statusLabel, $statusClass] = $statusMap[$invoice->payment_status] ?? [
                                '—',
                                'bg-gray-100 text-gray-500',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <a href="{{ route('purchases.show', $invoice) }}"
                                    class="font-mono text-xs bg-gray-100 text-gray-700 hover:bg-green-100 hover:text-green-700 px-2 py-1 rounded-lg transition">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @if ($invoice->supplier)
                                    <a href="{{ route('suppliers.show', $invoice->supplier) }}"
                                        class="font-semibold text-gray-800 hover:text-green-600 transition">
                                        {{ $invoice->supplier->name }}
                                    </a>
                                    <div class="font-mono text-xs text-gray-400">{{ $invoice->supplier->code }}</div>
                                @else
                                    <span class="text-gray-400 text-xs">— بدون مورد —</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">
                                <div>
                                    {{ optional($invoice->invoice_date)->format('Y-m-d') ?? $invoice->created_at->format('Y-m-d') }}
                                </div>
                                <div class="text-gray-400">{{ $invoice->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="toggleItems('inv-{{ $invoice->id }}')"
                                    class="text-green-600 text-xs hover:underline font-semibold">
                                    {{ $invoice->items->count() }} صنف
                                    <i class="fas fa-chevron-down text-xs mr-1"></i>
                                </button>
                                <div id="inv-{{ $invoice->id }}" class="hidden mt-2 space-y-1">
                                    @foreach ($invoice->items as $item)
                                        <div
                                            class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded-lg flex justify-between">
                                            <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
                                            <span>{{ number_format($item->subtotal, 2) }} ج.م</span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 font-bold text-gray-800">
                                {{ number_format($invoice->net_total, 2) }} ج.م
                            </td>
                            <td class="px-4 py-3 text-green-600 font-semibold">
                                {{ number_format($invoice->paid, 2) }} ج.م
                            </td>
                            <td
                                class="px-4 py-3 font-bold {{ $invoice->remaining > 0 ? 'text-red-500' : 'text-green-600' }}">
                                {{ number_format($invoice->remaining, 2) }} ج.م
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                    {{ $payIcons[$invoice->payment_method] ?? '💰' }}
                                    {{ $payLabels[$invoice->payment_method] ?? $invoice->payment_method }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs px-2 py-1 rounded-full {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('purchases.print', $invoice) }}" target="_blank"
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
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-14 text-gray-300">
                                <i class="fas fa-file-invoice-dollar text-4xl block mb-2"></i>
                                مفيش فواتير شراء لحد دلوقتي
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($purchases->hasPages())
                <div class="p-4 border-t">{{ $purchases->links() }}</div>
            @endif
        </div>

    </div>
@endsection

@section('scripts')
    <script>
        function toggleItems(id) {
            document.getElementById(id).classList.toggle('hidden');
        }
    </script>
@endsection
