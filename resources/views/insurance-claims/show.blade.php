@extends('layouts.app')
@section('title', 'مطالبة — '.$claim->claim_number)

@php
    $statusBadge = [
        'draft'     => ['مسودة', 'bg-gray-100 text-gray-700'],
        'submitted' => ['مُرسلة', 'bg-blue-100 text-blue-700'],
        'paid'      => ['مسددة', 'bg-green-100 text-green-700'],
        'rejected'  => ['مرفوضة', 'bg-red-100 text-red-700'],
    ];
    $b = $statusBadge[$claim->status] ?? [$claim->status, 'bg-gray-100 text-gray-700'];
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('insurance-claims.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">مطالبة {{ $claim->claim_number }}</h2>
            <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $b[1] }}">{{ $b[0] }}</span>
        </div>
        @if($claim->status !== 'paid')
        <form action="{{ route('insurance-claims.destroy', $claim) }}" method="POST" onsubmit="return confirm('هتحذف المطالبة دي؟')">
            @csrf @method('DELETE')
            <button class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-xl font-semibold text-sm transition"><i class="fas fa-trash"></i> حذف</button>
        </form>
        @endif
    </div>

    {{-- ملخص --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><div class="text-gray-400 mb-1">العقد</div><div class="font-semibold text-gray-800">{{ $claim->contract->name ?? '—' }}</div></div>
        <div><div class="text-gray-400 mb-1">عدد الفواتير</div><div class="font-semibold text-gray-800">{{ $claim->items->count() }}</div></div>
        <div><div class="text-gray-400 mb-1">التاريخ</div><div class="font-semibold text-gray-800">{{ optional($claim->claim_date)->format('Y-m-d') ?? '—' }}</div></div>
        <div><div class="text-gray-400 mb-1">إجمالي المطالبة</div><div class="font-bold text-indigo-600 text-lg">{{ number_format($claim->amount, 2) }} ج.م</div></div>
        @if($claim->notes)<div class="col-span-2 md:col-span-4"><div class="text-gray-400 mb-1">ملاحظات</div><div class="text-gray-700">{{ $claim->notes }}</div></div>@endif
        @if($claim->status === 'rejected' && $claim->rejection_reason)
        <div class="col-span-2 md:col-span-4 bg-red-50 rounded-xl p-3 text-red-700"><span class="font-semibold">سبب الرفض:</span> {{ $claim->rejection_reason }}</div>
        @endif
    </div>

    {{-- تغيير الحالة --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4">تحديث الحالة</h3>
        <form action="{{ route('insurance-claims.status', $claim) }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-end"
              onsubmit="return confirm('تحديث حالة المطالبة؟')">
            @csrf @method('PATCH')
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الحالة</label>
                <select name="status" id="status-select"
                        class="border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-400 text-right bg-white text-sm">
                    @foreach($statusBadge as $val => $lbl)
                        <option value="{{ $val }}" @selected($claim->status === $val)>{{ $lbl[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1" id="reject-reason-wrap" style="{{ $claim->status === 'rejected' ? '' : 'display:none' }}">
                <label class="block text-xs font-medium text-gray-600 mb-1">سبب الرفض</label>
                <input type="text" name="rejection_reason" value="{{ $claim->rejection_reason }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-400 text-right text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-sm">
                <i class="fas fa-save"></i> حفظ
            </button>
        </form>
    </div>

    {{-- الفواتير --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b font-bold text-gray-800">الفواتير المشمولة</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الفاتورة</th>
                    <th class="px-4 py-3 font-medium">الإجمالي</th>
                    <th class="px-4 py-3 font-medium">المطالَب به (تأمين)</th>
                    <th class="px-4 py-3 font-medium">تحمّل المريض</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($claim->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        @if($item->sale)
                            <a href="{{ route('sales.show', $item->sale) }}" class="font-mono text-xs text-indigo-600 hover:underline">{{ $item->sale->invoice_number }}</a>
                        @else <span class="text-gray-400">—</span> @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format(optional($item->sale)->total ?? 0, 2) }}</td>
                    <td class="px-4 py-3 font-semibold text-green-600">{{ number_format($item->covered_amount, 2) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ number_format(optional($item->sale)->patient_amount ?? 0, 2) }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ optional(optional($item->sale)->created_at)->format('Y-m-d') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('status-select').addEventListener('change', function () {
        document.getElementById('reject-reason-wrap').style.display = this.value === 'rejected' ? '' : 'none';
    });
</script>
@endsection
