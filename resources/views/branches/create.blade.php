@extends('layouts.app')
@section('title', 'فرع جديد')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('branches.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-code-branch text-indigo-500 ml-1"></i> إنشاء فرع جديد</h2>
    </div>

    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-sm text-blue-700">
        <i class="fas fa-circle-info"></i> عرّف الفرع هنا أولاً، ثم اضبط مخزونه وفواتيره من الموقع. بعد كده تقدر توصّل جهاز (أو أكتر) بالفرع من «شاشة الإعداد» على الجهاز باستخدام <b>كود الفرع</b> — والجهاز هيسحب بيانات الفرع تلقائياً.
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600 space-y-1">
        @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('branches.store') }}" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">كود الفرع * <span class="text-gray-400 text-xs">(فريد — بادئة الفواتير)</span></label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="16" placeholder="مثال: A"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right uppercase">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الفرع</label>
                <input type="text" name="name" value="{{ old('name') }}" maxlength="255" placeholder="مثال: فرع المعادي"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">النوع *</label>
                <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                    <option value="pharmacy" @selected(old('type')==='pharmacy')>صيدلية</option>
                    <option value="warehouse" @selected(old('type')==='warehouse')>مخزن</option>
                    <option value="office" @selected(old('type')==='office')>مكتب</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المحافظة</label>
                <input type="text" name="governorate" value="{{ old('governorate') }}" maxlength="255"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">التليفون</label>
                <input type="text" name="phone" value="{{ old('phone') }}" maxlength="30"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                <input type="text" name="address" value="{{ old('address') }}" maxlength="500"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                <i class="fas fa-plus"></i> إنشاء الفرع
            </button>
            <a href="{{ route('branches.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</a>
        </div>
    </form>
</div>
@endsection
