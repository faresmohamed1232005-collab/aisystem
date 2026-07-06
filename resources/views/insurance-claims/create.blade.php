@extends('layouts.app')
@section('title', 'إنشاء مطالبة')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('insurance-claims.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
        <h2 class="text-xl font-bold text-gray-800">إنشاء مطالبة تأمين</h2>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-sm">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
    @endif

    {{-- اختيار العقد --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">اختر عقد التأمين</label>
                <select name="contract_id" onchange="this.form.submit()"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                    <option value="">— اختر —</option>
                    @foreach($contracts as $ct)
                        <option value="{{ $ct->id }}" @selected((string)$contractId === (string)$ct->id)>{{ $ct->name }} ({{ $ct->code }})</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if($contract)
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-file-invoice text-indigo-500"></i>
            فواتير التأمين غير المطالَب بها — {{ $contract->name }}
        </h3>

        @if($unclaimed->isEmpty())
            <div class="text-center py-10 text-gray-300">
                <i class="fas fa-check-circle text-4xl block mb-2"></i>
                لا توجد فواتير تأمين غير مطالَب بها لهذا العقد
            </div>
        @else
        <form action="{{ route('insurance-claims.store') }}" method="POST">
            @csrf
            <input type="hidden" name="contract_id" value="{{ $contract->id }}">

            <table class="w-full text-sm mb-4">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-3 py-2"><input type="checkbox" id="check-all" class="w-4 h-4 rounded text-indigo-600"></th>
                        <th class="px-3 py-2 font-medium">الفاتورة</th>
                        <th class="px-3 py-2 font-medium">المريض</th>
                        <th class="px-3 py-2 font-medium">الإجمالي</th>
                        <th class="px-3 py-2 font-medium">تحمّل التأمين</th>
                        <th class="px-3 py-2 font-medium">تحمّل المريض</th>
                        <th class="px-3 py-2 font-medium">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($unclaimed as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                            <input type="checkbox" name="sale_ids[]" value="{{ $sale->id }}" checked
                                   class="sale-check w-4 h-4 rounded text-indigo-600" data-covered="{{ $sale->covered_amount }}">
                        </td>
                        <td class="px-3 py-2 font-mono text-xs text-indigo-600">{{ $sale->invoice_number }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ $sale->insuredPatient->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-700">{{ number_format($sale->total, 2) }}</td>
                        <td class="px-3 py-2 font-semibold text-green-600">{{ number_format($sale->covered_amount, 2) }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ number_format($sale->patient_amount, 2) }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $sale->created_at->format('Y-m-d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-end justify-between border-t pt-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">تاريخ المطالبة</label>
                        <input type="date" name="claim_date" value="{{ now()->format('Y-m-d') }}"
                               class="border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-indigo-400 text-right text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">ملاحظات</label>
                        <input type="text" name="notes"
                               class="border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:border-indigo-400 text-right text-sm">
                    </div>
                </div>
                <div class="text-left">
                    <div class="text-sm text-gray-500 mb-1">إجمالي المطالبة</div>
                    <div class="text-2xl font-bold text-indigo-600"><span id="claim-total">0.00</span> ج.م</div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-check"></i> إنشاء المطالبة
                </button>
            </div>
        </form>
        @endif
    </div>
    @endif
</div>

<script>
    (function () {
        const checkAll = document.getElementById('check-all');
        const boxes = () => Array.from(document.querySelectorAll('.sale-check'));
        const totalEl = document.getElementById('claim-total');

        function recalc() {
            const sum = boxes().filter(b => b.checked)
                .reduce((s, b) => s + parseFloat(b.dataset.covered || 0), 0);
            if (totalEl) totalEl.textContent = sum.toFixed(2);
        }
        if (checkAll) {
            checkAll.checked = true;
            checkAll.addEventListener('change', () => { boxes().forEach(b => b.checked = checkAll.checked); recalc(); });
        }
        boxes().forEach(b => b.addEventListener('change', recalc));
        recalc();
    })();
</script>
@endsection
