@extends('layouts.app')
@section('title', 'تحويلات المخزون')

@php
    $statusBadge = [
        'draft' => ['مسودة', 'gray'], 'sent' => ['مُرسل', 'blue'],
        'received' => ['مُستلم', 'green'], 'rejected' => ['مرفوض', 'red'],
    ];
    $bn = $branchNames ?? collect();
@endphp

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">تحويلات المخزون</h2>
        <a href="{{ route('stock-transfers.create') }}"
           class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> تحويل جديد
        </a>
    </div>

    {{-- تبويبات --}}
    <div class="flex gap-2 border-b">
        <a href="{{ route('stock-transfers.index', ['tab' => 'outgoing']) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 transition {{ $tab === 'outgoing' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600' }}">
            <i class="fas fa-arrow-up-from-bracket"></i> صادر ({{ $outgoing->total() }})
        </a>
        <a href="{{ route('stock-transfers.index', ['tab' => 'incoming']) }}"
           class="px-4 py-2 text-sm font-semibold border-b-2 transition {{ $tab === 'incoming' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-400 hover:text-gray-600' }}">
            <i class="fas fa-arrow-down-to-bracket"></i> وارد ({{ $incoming->total() }})
        </a>
    </div>

    @php($list = $tab === 'incoming' ? $incoming : $outgoing)

    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">رقم التحويل</th>
                    <th class="px-4 py-3 font-medium">{{ $tab === 'incoming' ? 'من فرع' : 'إلى فرع' }}</th>
                    <th class="px-4 py-3 font-medium">الأصناف</th>
                    <th class="px-4 py-3 font-medium">التاريخ</th>
                    <th class="px-4 py-3 font-medium">الحالة</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($list as $t)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3"><span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $t->transfer_number }}</span></td>
                    <td class="px-4 py-3 text-gray-700">
                        @php($other = $tab === 'incoming' ? $t->from_branch_id : $t->to_branch_id)
                        {{ $bn[$other] ?? $other }}
                    </td>
                    <td class="px-4 py-3"><span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $t->items_count }}</span></td>
                    <td class="px-4 py-3 text-gray-500">{{ $t->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        @php($b = $statusBadge[$t->status] ?? [$t->status, 'gray'])
                        <span class="bg-{{ $b[1] }}-50 text-{{ $b[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $b[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            @if($tab === 'incoming' && $t->status === 'sent')
                            <a href="{{ route('stock-transfers.receive', $t) }}" class="text-green-600 hover:text-green-700 text-xs px-2 py-1 bg-green-50 rounded-lg transition font-semibold">استلام</a>
                            @endif
                            <a href="{{ route('stock-transfers.show', $t) }}" class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition"><i class="fas fa-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-300">
                        <i class="fas fa-right-left text-4xl block mb-2"></i>
                        لا توجد تحويلات {{ $tab === 'incoming' ? 'واردة' : 'صادرة' }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($list->hasPages())<div class="p-4 border-t">{{ $list->links() }}</div>@endif
    </div>
</div>
@endsection
