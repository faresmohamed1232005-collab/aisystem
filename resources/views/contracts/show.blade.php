@extends('layouts.app')
@section('title', 'العقد — '.$contract->name)

@php
    $typeLabels = [
        'insurance'  => 'تأمين', 'government' => 'جهة حكومية', 'company' => 'شركة',
        'syndicate'  => 'نقابة', 'hospital' => 'مستشفى', 'university' => 'جامعة',
    ];
    $statusBadge = [
        'active' => ['نشط', 'green'], 'suspended' => ['موقوف', 'amber'], 'expired' => ['منتهي', 'red'],
    ];
    $catLabels = [
        'medicines' => 'أدوية', 'cosmetics' => 'مستحضرات تجميل',
        'medical_devices' => 'أجهزة طبية', 'baby_care' => 'عناية أطفال',
    ];
    $rule = $contract->insuranceRule;
@endphp

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('contracts.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">{{ $contract->name }}</h2>
            <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg">{{ $contract->code }}</span>
            @php($sb = $statusBadge[$contract->status] ?? [$contract->status, 'gray'])
            <span class="bg-{{ $sb[1] }}-50 text-{{ $sb[1] }}-600 text-xs px-2 py-1 rounded-full font-semibold">{{ $sb[0] }}</span>
        </div>
        <a href="{{ route('contracts.edit', $contract) }}"
           class="bg-amber-50 hover:bg-amber-100 text-amber-600 px-4 py-2 rounded-xl font-semibold text-sm transition flex items-center gap-2">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>

    {{-- بيانات العقد --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div><div class="text-gray-400 mb-1">النوع</div><div class="font-semibold text-gray-800">{{ $typeLabels[$contract->type] ?? $contract->type }}</div></div>
            <div><div class="text-gray-400 mb-1">الرقم الضريبي</div><div class="font-semibold text-gray-800">{{ $contract->tax_number ?? '—' }}</div></div>
            <div><div class="text-gray-400 mb-1">مسؤول الاتصال</div><div class="font-semibold text-gray-800">{{ $contract->contact_person ?? '—' }}</div></div>
            <div><div class="text-gray-400 mb-1">الموبايل</div><div class="font-semibold text-gray-800">{{ $contract->mobile ?? '—' }}</div></div>
            <div><div class="text-gray-400 mb-1">البريد</div><div class="font-semibold text-gray-800">{{ $contract->email ?? '—' }}</div></div>
            <div><div class="text-gray-400 mb-1">المدة</div><div class="font-semibold text-gray-800">{{ optional($contract->start_date)->format('Y-m-d') ?? '—' }} → {{ optional($contract->end_date)->format('Y-m-d') ?? '—' }}</div></div>
            @if($contract->address)<div class="col-span-2 md:col-span-3"><div class="text-gray-400 mb-1">العنوان</div><div class="font-semibold text-gray-800">{{ $contract->address }}</div></div>@endif
            @if($contract->contract_pdf)<div class="col-span-2 md:col-span-3"><a href="{{ asset('storage/'.$contract->contract_pdf) }}" target="_blank" class="text-indigo-600 font-semibold"><i class="fas fa-file-pdf"></i> عرض ملف العقد</a></div>@endif
        </div>
    </div>

    @if($contract->isInsurance())
    {{-- قواعد التأمين (1:1) --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-shield-heart text-indigo-500"></i> قواعد التأمين</h3>
        <form action="{{ route('contracts.insurance-rule', $contract) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نسبة التغطية % *</label>
                <input type="number" name="coverage_percent" step="0.01" min="0" max="100" required
                       value="{{ old('coverage_percent', $rule->coverage_percent ?? 0) }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">نسبة تحمّل المريض % *</label>
                <input type="number" name="patient_contribution_percent" step="0.01" min="0" max="100" required
                       value="{{ old('patient_contribution_percent', $rule->patient_contribution_percent ?? 100) }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">حد أقصى للروشتة (ج.م)</label>
                <input type="number" name="max_per_prescription" step="0.01" min="0"
                       value="{{ old('max_per_prescription', $rule->max_per_prescription ?? '') }}"
                       placeholder="بدون حد"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">حد مبلغ الموافقة المسبقة (ج.م)</label>
                <input type="number" name="approval_amount_limit" step="0.01" min="0"
                       value="{{ old('approval_amount_limit', $rule->approval_amount_limit ?? '') }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="approval_required" value="1" id="approval_required"
                       @checked(old('approval_required', $rule->approval_required ?? false))
                       class="w-4 h-4 rounded text-indigo-600">
                <label for="approval_required" class="text-sm text-gray-700">يتطلب موافقة مسبقة عند تجاوز الحد</label>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-xl transition text-sm">
                    <i class="fas fa-save"></i> حفظ قواعد التأمين
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- قواعد التسعير (n) --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-tags text-indigo-500"></i> قواعد التسعير حسب الفئة</h3>

        @if($contract->pricingRules->isNotEmpty())
        <table class="w-full text-sm mb-4">
            <thead class="bg-gray-50 text-gray-500 text-right">
                <tr>
                    <th class="px-3 py-2 font-medium">الفئة</th>
                    <th class="px-3 py-2 font-medium">نوع الخصم</th>
                    <th class="px-3 py-2 font-medium">القيمة</th>
                    <th class="px-3 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($contract->pricingRules as $pr)
                <tr>
                    <td class="px-3 py-2 font-semibold text-gray-800">{{ $catLabels[$pr->product_category] ?? $pr->product_category }}</td>
                    <td class="px-3 py-2 text-gray-500">{{ $pr->discount_type === 'percentage' ? 'نسبة %' : 'مبلغ ثابت' }}</td>
                    <td class="px-3 py-2 font-semibold text-gray-800">{{ number_format($pr->discount_value, 2) }}{{ $pr->discount_type === 'percentage' ? '%' : ' ج.م' }}</td>
                    <td class="px-3 py-2 text-left">
                        <form action="{{ route('contracts.pricing-rules.destroy', [$contract, $pr]) }}" method="POST" onsubmit="return confirm('حذف قاعدة التسعير؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <form action="{{ route('contracts.pricing-rules.store', $contract) }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">الفئة</label>
                <select name="product_category" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-400 text-right bg-white text-sm">
                    @foreach($catLabels as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">نوع الخصم</label>
                <select name="discount_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-400 text-right bg-white text-sm">
                    <option value="percentage">نسبة %</option>
                    <option value="amount">مبلغ ثابت</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">القيمة</label>
                <input type="number" name="discount_value" step="0.01" min="0" required
                       class="w-full border border-gray-200 rounded-xl px-3 py-2.5 focus:outline-none focus:border-indigo-400 text-right text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2.5 rounded-xl transition text-sm">
                <i class="fas fa-plus"></i> إضافة
            </button>
        </form>
    </div>

    {{-- المرضى والمطالبات (روابط سريعة) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-user-injured text-indigo-500"></i> المرضى المؤمّن عليهم</h3>
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $contract->insuredPatients->count() }}</span>
            </div>
            <a href="{{ route('insured-patients.index', ['contract_id' => $contract->id]) }}" class="text-indigo-600 text-sm font-semibold">عرض المرضى ←</a>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-file-invoice-dollar text-indigo-500"></i> المطالبات</h3>
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $contract->claims->count() }}</span>
            </div>
            @if($contract->isInsurance())
            <a href="{{ route('insurance-claims.create', ['contract_id' => $contract->id]) }}" class="text-indigo-600 text-sm font-semibold">إنشاء مطالبة جديدة ←</a>
            @else
            <span class="text-gray-400 text-sm">المطالبات لعقود التأمين فقط</span>
            @endif
        </div>
    </div>
</div>
@endsection
