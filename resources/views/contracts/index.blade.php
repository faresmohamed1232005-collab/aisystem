@extends('layouts.app')
@section('title', 'التعاقدات')

@php
    $typeLabels = [
        'insurance'  => 'تأمين', 'government' => 'جهة حكومية', 'company' => 'شركة',
        'syndicate'  => 'نقابة', 'hospital' => 'مستشفى', 'university' => 'جامعة',
    ];
    $statusBadge = [
        'active'    => ['نشط', 'bg-green-100 text-green-700'],
        'suspended' => ['موقوف', 'bg-amber-100 text-amber-700'],
        'expired'   => ['منتهي', 'bg-red-100 text-red-700'],
    ];
@endphp

@section('styles')
.add-form-wrap { overflow:hidden; transition: max-height .35s ease, opacity .3s; max-height:0; opacity:0; }
.add-form-wrap.open { max-height:2000px; opacity:1; }
.edit-modal { display:none; position:fixed; inset:0; z-index:50; background:rgba(0,0,0,.4); backdrop-filter:blur(4px); padding:1rem; align-items:center; justify-content:center; }
.edit-modal.open { display:flex; }
.edit-modal-box { background:#fff; border-radius:1rem; box-shadow:0 25px 50px -12px rgba(0,0,0,.25); width:100%; max-width:48rem; max-height:90vh; overflow-y:auto; }
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
            <h2 class="text-xl font-bold text-gray-800">التعاقدات</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $contracts->total() }} عقد
            </span>
        </div>
        <button type="button" onclick="toggleAddForm()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إضافة عقد
        </button>
    </div>

    {{-- Panel إضافة عقد --}}
    <div id="add-form-wrap" class="add-form-wrap">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800"><i class="fas fa-file-contract text-indigo-500 ml-1"></i> إضافة عقد جديد</h3>
                <button type="button" onclick="toggleAddForm()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('contracts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('contracts._form')
                @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600 space-y-1">
                    @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
                </div>
                @endif
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <button type="button" onclick="toggleAddForm()" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</button>
                </div>
            </form>
        </div>
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
                        @php($sb = $statusBadge[$contract->status] ?? [$contract->status, 'bg-gray-100 text-gray-700'])
                        <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $sb[1] }}">{{ $sb[0] }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button type="button" title="تعديل" onclick="openEditModal({{ $contract->id }})"
                                    class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></button>
                            <form action="{{ route('contracts.destroy', $contract) }}" method="POST" class="inline"
                                  onsubmit="return confirm('هتحذف العقد ده؟')">
                                @csrf @method('DELETE')
                                <button title="حذف" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg transition"><i class="fas fa-trash"></i></button>
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

{{-- Modal تعديل لكل عقد (يستخدم _form مع $contract) --}}
@foreach($contracts as $contract)
<div id="edit-modal-{{ $contract->id }}" class="edit-modal" onclick="if(event.target===this) closeEditModal({{ $contract->id }})">
    <div class="edit-modal-box">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="font-bold text-gray-800"><i class="fas fa-file-signature text-amber-500 ml-1"></i> تعديل العقد — {{ $contract->code }}</h3>
            <button type="button" onclick="closeEditModal({{ $contract->id }})" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('contracts.update', $contract) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf @method('PUT')
            @include('contracts._form', ['contract' => $contract])
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <button type="button" onclick="closeEditModal({{ $contract->id }})" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
<script>
function toggleAddForm() { document.getElementById('add-form-wrap').classList.toggle('open'); }
function openEditModal(id) { document.getElementById('edit-modal-' + id).classList.add('open'); }
function closeEditModal(id) { document.getElementById('edit-modal-' + id).classList.remove('open'); }
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.edit-modal.open').forEach(m => m.classList.remove('open'));
});
@if($errors->any()) document.getElementById('add-form-wrap').classList.add('open'); @endif
</script>
@endsection
