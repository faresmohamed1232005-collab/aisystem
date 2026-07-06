@extends('layouts.app')
@section('title', 'التعاقدات')

@php
    $typeLabels = [
        'insurance'  => 'تأمين', 'government' => 'جهة حكومية', 'company' => 'شركة',
        'syndicate'  => 'نقابة', 'hospital' => 'مستشفى', 'university' => 'جامعة',
    ];
    $statusBadge = [
        'active'    => ['نشط', 'green'],
        'suspended' => ['موقوف', 'amber'],
        'expired'   => ['منتهي', 'red'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">التعاقدات</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $contracts->total() }} عقد
            </span>
        </div>
        <a href="{{ route('contracts.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إضافة عقد
        </a>
    </div>

    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="q" value="{{ $q }}"
                   placeholder="ابحث بالاسم أو الكود أو الرقم الضريبي..."
                   class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
        </div>
        <select name="type" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
            <option value="">كل الأنواع</option>
            @foreach($typeLabels as $val => $label)
                <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الكود</th>
                    <th class="px-4 py-3 font-medium">الجهة</th>
                    <th class="px-4 py-3 font-medium">النوع</th>
                    <th class="px-4 py-3 font-medium">المرضى</th>
                    <th class="px-4 py-3 font-medium">المطالبات</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contracts as $contract)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">
                        <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $contract->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('contracts.show', $contract) }}" class="font-semibold text-gray-800 hover:text-indigo-600 transition">
                            {{ $contract->name }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $typeLabels[$contract->type] ?? $contract->type }}</td>
                    <td class="px-4 py-3">
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $contract->insured_patients_count }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $contract->claims_count }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php($sb = $statusBadge[$contract->status] ?? [$contract->status, 'gray'])
                        <span class="bg-{{ $sb[1] }}-50 text-{{ $sb[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $sb[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('contracts.show', $contract) }}"
                               class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('contracts.edit', $contract) }}"
                               class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('contracts.destroy', $contract) }}" method="POST"
                                  onsubmit="return confirm('هتحذف العقد ده؟')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg transition"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-gray-300">
                        <i class="fas fa-file-contract text-4xl block mb-2"></i>
                        مفيش تعاقدات لحد دلوقتي
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($contracts->hasPages())
        <div class="p-4 border-t">{{ $contracts->links() }}</div>
        @endif
    </div>
</div>
@endsection
