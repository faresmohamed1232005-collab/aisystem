@extends('layouts.app')
@section('title', 'المرضى المؤمّن عليهم')

@section('styles')
.add-form-wrap { overflow:hidden; transition: max-height .35s ease, opacity .3s; max-height:0; opacity:0; }
.add-form-wrap.open { max-height:1200px; opacity:1; }
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
            <h2 class="text-xl font-bold text-gray-800">المرضى المؤمّن عليهم</h2>
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">{{ $patients->total() }} مريض</span>
        </div>
        <button type="button" onclick="toggleAddForm()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> إضافة مريض
        </button>
    </div>

    {{-- Panel إضافة (قابل للطي) --}}
    <div id="add-form-wrap" class="add-form-wrap">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-800"><i class="fas fa-user-plus text-indigo-500 ml-1"></i> إضافة مريض جديد</h3>
                <button type="button" onclick="toggleAddForm()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('insured-patients.store') }}" method="POST" class="space-y-4">
                @csrf
                @include('insured-patients._form', ['contracts' => $activeContracts])
                @if($errors->any() && ! session('edit_errors'))
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
                            <button type="button" title="تعديل"
                                    data-update-url="{{ route('insured-patients.update', $p) }}"
                                    data-contract_id="{{ $p->contract_id }}"
                                    data-name="{{ $p->name }}"
                                    data-card_number="{{ $p->card_number }}"
                                    data-membership_number="{{ $p->membership_number }}"
                                    data-coverage_end_date="{{ optional($p->coverage_end_date)->format('Y-m-d') }}"
                                    onclick="openEditModal(this)"
                                    class="text-amber-500 hover:text-amber-700 text-xs px-2 py-1 bg-amber-50 rounded-lg transition"><i class="fas fa-edit"></i></button>
                            <form action="{{ route('insured-patients.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('هتحذف المريض ده؟')">
                                @csrf @method('DELETE')
                                <button title="حذف" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg transition"><i class="fas fa-trash"></i></button>
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

{{-- Modal تعديل --}}
<div id="edit-modal" class="edit-modal" onclick="if(event.target===this) closeEditModal()">
    <div class="edit-modal-box">
        <div class="flex items-center justify-between p-4 border-b">
            <h3 class="font-bold text-gray-800"><i class="fas fa-user-edit text-amber-500 ml-1"></i> تعديل بيانات المريض</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="edit-form" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العقد (شركة التأمين) *</label>
                    <select name="contract_id" data-field="contract_id" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                        @foreach($activeContracts as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->name }} ({{ $ct->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم المريض *</label>
                    <input type="text" name="name" data-field="name" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم البطاقة</label>
                    <input type="text" name="card_number" data-field="card_number"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم العضوية</label>
                    <input type="text" name="membership_number" data-field="membership_number"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ انتهاء التغطية</label>
                    <input type="date" name="coverage_end_date" data-field="coverage_end_date"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
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
function toggleAddForm() { document.getElementById('add-form-wrap').classList.toggle('open'); }
function openEditModal(btn) {
    const modal = document.getElementById('edit-modal');
    const form  = document.getElementById('edit-form');
    form.action = btn.dataset.updateUrl;
    modal.querySelectorAll('[data-field]').forEach(el => {
        const key = el.dataset.field;
        el.value = btn.dataset[key] ?? '';
    });
    modal.classList.add('open');
}
function closeEditModal() { document.getElementById('edit-modal').classList.remove('open'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEditModal(); });
@if($errors->any()) document.getElementById('add-form-wrap').classList.add('open'); @endif
</script>
@endsection
