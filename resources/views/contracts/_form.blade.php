@php
    $typeLabels = [
        'insurance'  => 'تأمين',
        'government' => 'جهة حكومية',
        'company'    => 'شركة',
        'syndicate'  => 'نقابة',
        'hospital'   => 'مستشفى',
        'university' => 'جامعة',
    ];
    $statusLabels = [
        'active'    => 'نشط',
        'suspended' => 'موقوف',
        'expired'   => 'منتهي',
    ];
    $c = $contract ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">نوع العقد *</label>
        <select name="type"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
            @foreach($typeLabels as $val => $label)
                <option value="{{ $val }}" @selected(old('type', $c->type ?? 'insurance') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">الحالة *</label>
        <select name="status"
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
            @foreach($statusLabels as $val => $label)
                <option value="{{ $val }}" @selected(old('status', $c->status ?? 'active') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">اسم الجهة *</label>
        <input type="text" name="name" value="{{ old('name', $c->name ?? '') }}" required
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
               placeholder="اسم شركة التأمين / الجهة">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">الرقم الضريبي</label>
        <input type="text" name="tax_number" value="{{ old('tax_number', $c->tax_number ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">مسؤول الاتصال</label>
        <input type="text" name="contact_person" value="{{ old('contact_person', $c->contact_person ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">الموبايل</label>
        <input type="text" name="mobile" value="{{ old('mobile', $c->mobile ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
               placeholder="01xxxxxxxxx">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
        <input type="email" name="email" value="{{ old('email', $c->email ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
        <input type="text" name="address" value="{{ old('address', $c->address ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ البداية</label>
        <input type="date" name="start_date" value="{{ old('start_date', optional($c->start_date ?? null)->format('Y-m-d')) }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ النهاية</label>
        <input type="date" name="end_date" value="{{ old('end_date', optional($c->end_date ?? null)->format('Y-m-d')) }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            ملف العقد (PDF)
            @if($c && $c->contract_pdf)
                <a href="{{ asset('storage/'.$c->contract_pdf) }}" target="_blank" class="text-indigo-600 text-xs mr-2">
                    <i class="fas fa-file-pdf"></i> عرض الملف الحالي
                </a>
            @endif
        </label>
        <input type="file" name="contract_pdf" accept="application/pdf"
               class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-indigo-400 text-right bg-white">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">{{ old('notes', $c->notes ?? '') }}</textarea>
    </div>
</div>
