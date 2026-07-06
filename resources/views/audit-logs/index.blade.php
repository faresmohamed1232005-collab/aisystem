@extends('layouts.app')
@section('title', 'سجل التدقيق')

@php
    $eventBadge = [
        'created' => ['إنشاء', 'green'], 'updated' => ['تعديل', 'blue'], 'deleted' => ['حذف', 'red'],
    ];
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">سجل التدقيق</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $logs->total() }} حدث</span>
        </div>
    </div>

    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 text-sm text-gray-500">
        <i class="fas fa-lock"></i> السجل غير قابل للتعديل أو الحذف — يوثّق من فعل ماذا ومتى.
    </div>

    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <select name="event" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
            <option value="">كل الأحداث</option>
            @foreach($eventBadge as $val => $b)
                <option value="{{ $val }}" @selected($event === $val)>{{ $b[0] }}</option>
            @endforeach
        </select>
        <select name="type" onchange="this.form.submit()"
                class="border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white shadow-sm">
            <option value="">كل الأنواع</option>
            @foreach($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الوقت</th>
                    <th class="px-4 py-3 font-medium">الفاعل</th>
                    <th class="px-4 py-3 font-medium">الحدث</th>
                    <th class="px-4 py-3 font-medium">السجل</th>
                    <th class="px-4 py-3 font-medium">التغييرات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-800">{{ $log->actor_name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $log->actor_type === 'owner' ? 'المالك' : 'موظف' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @php($b = $eventBadge[$log->event] ?? [$log->event, 'gray'])
                        <span class="bg-{{ $b[1] }}-50 text-{{ $b[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $b[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-700">{{ $log->model_label }}</div>
                        @if($log->label)<div class="text-xs text-gray-400 font-mono">{{ $log->label }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 max-w-md">
                        @if($log->event === 'updated' && $log->new_values)
                            @foreach(array_slice($log->new_values, 0, 5, true) as $k => $v)
                                <div><span class="text-gray-400">{{ $k }}:</span>
                                    <span class="text-red-500 line-through">{{ \Illuminate\Support\Str::limit((string)($log->old_values[$k] ?? '—'), 20) }}</span>
                                    → <span class="text-green-600">{{ \Illuminate\Support\Str::limit((string)$v, 20) }}</span>
                                </div>
                            @endforeach
                        @elseif($log->event === 'created')
                            <span class="text-green-600">سجل جديد</span>
                        @elseif($log->event === 'deleted')
                            <span class="text-red-500">حُذف</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-gray-300">
                        <i class="fas fa-clipboard-list text-4xl block mb-2"></i>
                        لا توجد أحداث مسجّلة بعد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($logs->hasPages())<div class="p-4 border-t">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
