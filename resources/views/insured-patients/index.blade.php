@extends('layouts.app')
@section('title', 'المرضى المؤمّن عليهم')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">المرضى المؤمّن عليهم</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $patients->total() }} مريض</span>
        </div>
        <a href="{{ route('insured-patients.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إضافة مريض
        </a>
    </div>

    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" name="q" value="{{ $q }}" placeholder="ابحث بالاسم أو رقم البطاقة أو العضوية..."
                   class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
        </div>
        <select name="contract_id" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
            <option value="">كل العقود</option>
            @foreach($contracts as $ct)
                <option value="{{ $ct->id }}" @selected((string)$contractId === (string)$ct->id)>{{ $ct->name }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">المريض</th>
                    <th class="px-4 py-3 font-medium">العقد</th>
                    <th class="px-4 py-3 font-medium">رقم البطاقة</th>
                    <th class="px-4 py-3 font-medium">رقم العضوية</th>
                    <th class="px-4 py-3 font-medium">انتهاء التغطية</th>
                    <th class="px-4 py-3 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($patients as $p)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $p->contract->name ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $p->card_number ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $p->membership_number ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($p->coverage_end_date)
                            <span class="{{ $p->isCoverageExpired() ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                {{ $p->coverage_end_date->format('Y-m-d') }}
                                @if($p->isCoverageExpired()) <span class="text-xs">(منتهية)</span>@endif
                            </span>
                        @else — @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('insured-patients.edit', $p) }}"
                               class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('insured-patients.destroy', $p) }}" method="POST" onsubmit="return confirm('هتحذف المريض ده؟')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg transition"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-300">
                        <i class="fas fa-user-injured text-4xl block mb-2"></i>
                        مفيش مرضى مؤمّن عليهم
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($patients->hasPages())<div class="p-4 border-t">{{ $patients->links() }}</div>@endif
    </div>
</div>
@endsection
