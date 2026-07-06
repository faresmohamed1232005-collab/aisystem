@extends('layouts.app')
@section('title', 'الفرع — '.($branch->name ?? $branch->code))

@php
    $typeLabels = ['pharmacy' => 'صيدلية', 'warehouse' => 'مخزن', 'office' => 'مكتب'];
    $statusBadge = ['active' => ['نشط', 'green'], 'stopped' => ['متوقف', 'red']];
    $flags = [
        'allow_transfer_out' => 'تحويل صادر',
        'allow_transfer_in'  => 'تحويل وارد',
        'allow_pricing'      => 'التسعير',
        'allow_stocktake'    => 'الجرد',
    ];
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('branches.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">{{ $branch->name ?? '— بلا اسم —' }}</h2>
            <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $branch->code }}</span>
            @php($sb = $statusBadge[$branch->status] ?? [$branch->status, 'gray'])
            <span class="bg-{{ $sb[1] }}-50 text-{{ $sb[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $sb[0] }}</span>
        </div>
        <a href="{{ route('branches.edit', $branch) }}" class="bg-amber-50 hover:bg-amber-100 text-amber-600 px-4 py-2 rounded-xl font-semibold text-sm transition flex items-center gap-2">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>

    {{-- البيانات --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
        <div><div class="text-gray-400 mb-1">النوع</div><div class="font-semibold text-gray-800">{{ $typeLabels[$branch->type] ?? $branch->type }}</div></div>
        <div><div class="text-gray-400 mb-1">المحافظة</div><div class="font-semibold text-gray-800">{{ $branch->governorate ?? '—' }}</div></div>
        <div><div class="text-gray-400 mb-1">التليفون</div><div class="font-semibold text-gray-800">{{ $branch->phone ?? '—' }}</div></div>
        <div class="col-span-2 md:col-span-3"><div class="text-gray-400 mb-1">العنوان</div><div class="font-semibold text-gray-800">{{ $branch->address ?? '—' }}</div></div>
        <div><div class="text-gray-400 mb-1">معرّف الفرع</div><div class="font-mono text-xs text-gray-600">{{ $branch->branch_id }}</div></div>
        <div><div class="text-gray-400 mb-1">آخر ظهور</div><div class="font-semibold text-gray-800">{{ optional($branch->last_seen_at)->diffForHumans() ?? '—' }}</div></div>
    </div>

    {{-- الصلاحيات --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-toggle-on text-indigo-500"></i> الصلاحيات</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($flags as $key => $label)
                <span class="text-xs px-3 py-1.5 rounded-full font-semibold {{ $branch->$key ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                    <i class="fas fa-{{ $branch->$key ? 'check' : 'xmark' }}"></i> {{ $label }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- مخزون الفرع (من اللقطات) --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between">
            <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-boxes-stacked text-indigo-500"></i> مخزون الفرع</h3>
            <div class="flex gap-2 text-xs">
                <span class="bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full font-semibold">{{ number_format($inventoryStats['drugs']) }} صنف</span>
                <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-semibold">{{ number_format($inventoryStats['units'], 0) }} وحدة</span>
            </div>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-4 py-3 font-medium">الدواء</th>
                    <th class="px-4 py-3 font-medium">الكمية</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($items as $it)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-800">{{ $it->name }}</td>
                    <td class="px-4 py-3 font-semibold text-gray-700">{{ number_format($it->quantity, 0) }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="text-center py-10 text-gray-300">لا توجد لقطة مخزون لهذا الفرع بعد (تُبنى عند أول مزامنة مخزون منه)</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
