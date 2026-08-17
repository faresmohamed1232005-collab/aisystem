@extends('layouts.app')
@section('title', 'الفروع')

@php
    $typeLabels = ['pharmacy' => 'صيدلية', 'warehouse' => 'مخزن', 'office' => 'مكتب'];
    $statusBadge = [
        'active'  => ['نشط', 'bg-green-100 text-green-700'],
        'stopped' => ['متوقف', 'bg-red-100 text-red-700'],
    ];
    $flags = [
        'allow_transfer_out' => 'السماح بالتحويل الصادر',
        'allow_transfer_in'  => 'السماح بالتحويل الوارد',
        'allow_pricing'      => 'السماح بالتسعير',
        'allow_stocktake'    => 'السماح بالجرد',
    ];
@endphp

@section('styles')
.edit-modal { display:none; position:fixed; inset:0; z-index:50; background:rgba(0,0,0,.4); backdrop-filter:blur(4px); padding:1rem; align-items:center; justify-content:center; }
.edit-modal.open { display:flex; }
.edit-modal-box { background:#fff; border-radius:1rem; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); width:100%; max-width:42rem; max-height:90vh; overflow-y:auto; }
@endsection

@section('content')
<div class="space-y-6">

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
            <h2 class="text-xl font-bold text-gray-800">الفروع</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $branches->total() }} فرع</span>
        </div>
        <a href="{{ route('branches.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 text-sm">
            <i class="fas fa-plus"></i> فرع جديد
        </a>
    </div>

    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-sm text-blue-700">
        <i class="fas fa-circle-info"></i> عرّف فروعك هنا واضبط مخزون وفواتير كل فرع، ثم وصّل أجهزة الفرع بكوده من «شاشة الإعداد» على الجهاز. (الفرع يُنشأ أيضاً تلقائياً عند تسجيل أول جهاز بكوده.)
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600 space-y-1">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
    @endif

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
                        @php($sb = $statusBadge[$branch->status] ?? [$branch->status, 'bg-gray-100 text-gray-700'])
                        <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $sb[1] }}">{{ $sb[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('branches.show', $branch) }}" title="عرض" class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1 bg-indigo-50 rounded-lg transition"><i class="fas fa-eye"></i></a>
                            <button type="button" title="تعديل"
                                    data-update-url="{{ route('branches.update', $branch) }}"
                                    data-code="{{ $branch->code }}"
                                    data-name="{{ $branch->name }}"
                                    data-type="{{ $branch->type }}"
                                    data-status="{{ $branch->status }}"
                                    data-governorate="{{ $branch->governorate }}"
                                    data-phone="{{ $branch->phone }}"
                                    data-address="{{ $branch->address }}"
                                    data-allow_transfer_out="{{ (int) $branch->allow_transfer_out }}"
                                    data-allow_transfer_in="{{ (int) $branch->allow_transfer_in }}"
                                    data-allow_pricing="{{ (int) $branch->allow_pricing }}"
                                    data-allow_stocktake="{{ (int) $branch->allow_stocktake }}"
                                    onclick="openEditModal(this)"
                                    class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></button>
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

{{-- Modal تعديل الفرع --}}
<div id="edit-modal" class="edit-modal" onclick="if(event.target===this) closeEditModal()">
    <div class="edit-modal-box">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="font-bold text-gray-800"><i class="fas fa-code-branch text-amber-500 ml-1"></i> تعديل الفرع — <span id="modal-branch-code"></span></h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="edit-form" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم الفرع</label>
                    <input type="text" name="name" data-field="name"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">النوع *</label>
                    <select name="type" data-field="type" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                        <option value="pharmacy">صيدلية</option>
                        <option value="warehouse">مخزن</option>
                        <option value="office">مكتب</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الحالة *</label>
                    <select name="status" data-field="status" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                        <option value="active">نشط</option>
                        <option value="stopped">متوقف</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المحافظة</label>
                    <input type="text" name="governorate" data-field="governorate"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">التليفون</label>
                    <input type="text" name="phone" data-field="phone"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" data-field="address"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="text-sm font-medium text-gray-700 mb-2">الصلاحيات</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($flags as $key => $label)
                    <label class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2.5 cursor-pointer">
                        <input type="checkbox" name="{{ $key }}" value="1" data-field="{{ $key }}" class="w-4 h-4 rounded text-indigo-600">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <button type="button" onclick="closeEditModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openEditModal(btn) {
    const modal = document.getElementById('edit-modal');
    const form  = document.getElementById('edit-form');
    form.action = btn.dataset.updateUrl;
    document.getElementById('modal-branch-code').textContent = btn.dataset.code ?? '';
    modal.querySelectorAll('[data-field]').forEach(el => {
        const key = el.dataset.field;
        const val = btn.dataset[key] ?? '';
        if (el.type === 'checkbox') el.checked = val === '1' || val === 'true';
        else el.value = val;
    });
    modal.classList.add('open');
}
function closeEditModal() { document.getElementById('edit-modal').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
</script>
@endsection
