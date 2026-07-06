@extends('layouts.app')
@section('title', 'مطالبات التأمين')

@php
    $statusBadge = [
        'draft'     => ['مسودة', 'gray'],
        'submitted' => ['مُرسلة', 'blue'],
        'paid'      => ['مسددة', 'green'],
        'rejected'  => ['مرفوضة', 'red'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">مطالبات التأمين</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $claims->total() }} مطالبة</span>
        </div>
        <a href="{{ route('insurance-claims.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إنشاء مطالبة
        </a>
    </div>

    <form method="GET">
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
            <option value="">كل الحالات</option>
            @foreach($statusBadge as $val => $b)
                <option value="{{ $val }}" @selected($status === $val)>{{ $b[0] }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">رقم المطالبة</th>
                    <th class="px-4 py-3 font-medium">العقد</th>
                    <th class="px-4 py-3 font-medium">عدد الفواتير</th>
                    <th class="px-4 py-3 font-medium">المبلغ</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($claims as $claim)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3"><span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $claim->claim_number }}</span></td>
                    <td class="px-4 py-3 text-gray-700">{{ $claim->contract->name ?? '—' }}</td>
                    <td class="px-4 py-3"><span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $claim->items_count }}</span></td>
                    <td class="px-4 py-3 font-bold text-gray-800">{{ number_format($claim->amount, 2) }} ج.م</td>
                    <td class="px-4 py-3 text-gray-500">{{ optional($claim->claim_date)->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php($b = $statusBadge[$claim->status] ?? [$claim->status, 'gray'])
                        <span class="bg-{{ $b[1] }}-50 text-{{ $b[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $b[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('insurance-claims.show', $claim) }}"
                           class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-300">
                        <i class="fas fa-file-invoice-dollar text-4xl block mb-2"></i>
                        مفيش مطالبات لحد دلوقتي
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($claims->hasPages())<div class="p-4 border-t">{{ $claims->links() }}</div>@endif
    </div>
</div>
@endsection
