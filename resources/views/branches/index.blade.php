@extends('layouts.app')
@section('title', 'الفروع')

@php
    $typeLabels = ['pharmacy' => 'صيدلية', 'warehouse' => 'مخزن', 'office' => 'مكتب'];
    $statusBadge = ['active' => ['نشط', 'green'], 'stopped' => ['متوقف', 'red']];
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">الفروع</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $branches->total() }} فرع</span>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-sm text-blue-700">
        <i class="fas fa-circle-info"></i> الفروع تُنشأ تلقائياً عند تسجيل كل جهاز فرع من «شاشة إعداد أول تشغيل». هنا تحرّر بياناتها وصلاحياتها.
    </div>

    <form method="GET" class="relative">
        <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="text" name="q" value="{{ $q }}" placeholder="ابحث بالاسم أو الكود..."
               class="w-full border border-gray-200 rounded-xl pr-10 pl-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الكود</th>
                    <th class="px-4 py-3 font-medium">الفرع</th>
                    <th class="px-4 py-3 font-medium">النوع</th>
                    <th class="px-4 py-3 font-medium">المحافظة</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($branches as $branch)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3"><span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $branch->code }}</span></td>
                    <td class="px-4 py-3">
                        <a href="{{ route('branches.show', $branch) }}" class="font-semibold text-gray-800 hover:text-indigo-600 transition">
                            {{ $branch->name ?? '— بلا اسم —' }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $typeLabels[$branch->type] ?? $branch->type }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $branch->governorate ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php($sb = $statusBadge[$branch->status] ?? [$branch->status, 'gray'])
                        <span class="bg-{{ $sb[1] }}-50 text-{{ $sb[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $sb[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('branches.show', $branch) }}" class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('branches.edit', $branch) }}" class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-300">
                        <i class="fas fa-code-branch text-4xl block mb-2"></i>
                        لا توجد فروع مسجّلة بعد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($branches->hasPages())<div class="p-4 border-t">{{ $branches->links() }}</div>@endif
    </div>
</div>
@endsection
