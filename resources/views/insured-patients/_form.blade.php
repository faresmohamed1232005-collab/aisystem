@php($p = $insuredPatient ?? null)

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">العقد (شركة التأمين) *</label>
        <select name="contract_id" required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
            <option value="">— اختر العقد —</option>
            @foreach($contracts as $ct)
                <option value="{{ $ct->id }}" @selected(old('contract_id', $p->contract_id ?? '') == $ct->id)>{{ $ct->name }} ({{ $ct->code }})</option>
            @endforeach
        </select>
        @if($contracts->isEmpty())
            <p class="text-xs text-amber-600 mt-1">لا يوجد عقود تأمين نشطة — <a href="{{ route('contracts.create') }}" class="underline">أضف عقداً أولاً</a>.</p>
        @endif
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">اسم المريض *</label>
        <input type="text" name="name" value="{{ old('name', $p->name ?? '') }}" required
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">رقم البطاقة</label>
        <input type="text" name="card_number" value="{{ old('card_number', $p->card_number ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">رقم العضوية</label>
        <input type="text" name="membership_number" value="{{ old('membership_number', $p->membership_number ?? '') }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ انتهاء التغطية</label>
        <input type="date" name="coverage_end_date" value="{{ old('coverage_end_date', optional($p->coverage_end_date ?? null)->format('Y-m-d')) }}"
               class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
    </div>
</div>
