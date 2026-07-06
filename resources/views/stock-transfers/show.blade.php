@extends('layouts.app')
@section('title', 'تحويل — '.$transfer->transfer_number)

@php
    $statusBadge = [
        'draft' => ['مسودة', 'gray'], 'sent' => ['مُرسل', 'blue'],
        'received' => ['مُستلم', 'green'], 'rejected' => ['مرفوض', 'red'],
    ];
    $b = $statusBadge[$transfer->status] ?? [$transfer->status, 'gray'];
    $bn = $branchNames ?? collect();
    $isIncoming = $transfer->to_branch_id === $me;
    $canReceive = $isIncoming && $transfer->status === 'sent';
@endphp

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('stock-transfers.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">{{ $transfer->transfer_number }}</h2>
            <span class="bg-{{ $b[1] }}-50 text-{{ $b[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $b[0] }}</span>
        </div>
        @if($canReceive)
        <div class="flex gap-2">
            <a href="{{ route('stock-transfers.receive', $transfer) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl font-semibold text-sm transition"><i class="fas fa-box-open"></i> استلام</a>
            <button onclick="document.getElementById('reject-box').classList.toggle('hidden')" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-xl font-semibold text-sm transition">رفض</button>
        </div>
        @endif
    </div>

    {{-- رقم التحويل (للمسح) --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 flex items-center justify-between">
        <div class="grid grid-cols-2 gap-4 text-sm flex-1">
            <div><div class="text-gray-400 mb-1">من فرع</div><div class="font-semibold text-gray-800">{{ $bn[$transfer->from_branch_id] ?? $transfer->from_branch_id }}</div></div>
            <div><div class="text-gray-400 mb-1">إلى فرع</div><div class="font-semibold text-gray-800">{{ $bn[$transfer->to_branch_id] ?? $transfer->to_branch_id }}</div></div>
            <div><div class="text-gray-400 mb-1">أُرسل</div><div class="font-semibold text-gray-800">{{ optional($transfer->sent_at)->format('Y-m-d H:i') ?? '—' }}</div></div>
            <div><div class="text-gray-400 mb-1">استُلم</div><div class="font-semibold text-gray-800">{{ optional($transfer->received_at)->format('Y-m-d H:i') ?? '—' }}</div></div>
        </div>
        <div class="text-center border-r pr-6 mr-4">
            <div class="font-mono text-lg font-bold tracking-widest text-indigo-600 border-2 border-dashed border-indigo-200 rounded-lg px-4 py-3">{{ $transfer->transfer_number }}</div>
            <div class="text-xs text-gray-400 mt-1">رقم التحويل</div>
        </div>
    </div>

    @if($transfer->status === 'rejected' && $transfer->reject_reason)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm"><span class="font-semibold">سبب الرفض:</span> {{ $transfer->reject_reason }}</div>
    @endif

    {{-- رفض (نموذج مخفي) --}}
    @if($canReceive)
    <div id="reject-box" class="hidden bg-white rounded-2xl shadow-sm p-6">
        <form action="{{ route('stock-transfers.reject', $transfer) }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">سبب الرفض *</label>
                <input type="text" name="reject_reason" required class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-red-400 text-right text-sm">
            </div>
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-sm">تأكيد الرفض</button>
        </form>
    </div>
    @endif

    {{-- الأصناف --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b font-bold text-gray-800">الأصناف ({{ $transfer->items->count() }})</div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الدواء</th>
                    <th class="px-4 py-3 font-medium">الصلاحية</th>
                    <th class="px-4 py-3 font-medium">المُرسل</th>
                    @if($transfer->status === 'received')
                    <th class="px-4 py-3 font-medium">المُستلم</th>
                    <th class="px-4 py-3 font-medium">التالف</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($transfer->items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $item->drug->name_ar ?? $item->drug->name_en ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ optional($item->expiry_date)->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ number_format($item->quantity, 0) }}</td>
                    @if($transfer->status === 'received')
                    <td class="px-4 py-3 font-semibold text-green-600">{{ number_format($item->received_quantity ?? 0, 0) }}</td>
                    <td class="px-4 py-3 text-red-500">{{ number_format($item->damaged_quantity ?? 0, 0) }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
