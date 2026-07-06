@extends('layouts.app')
@section('title', 'تعديل فرع')

@php
    $flags = [
        'allow_transfer_out' => 'السماح بالتحويل الصادر',
        'allow_transfer_in'  => 'السماح بالتحويل الوارد',
        'allow_pricing'      => 'السماح بالتسعير',
        'allow_stocktake'    => 'السماح بالجرد',
    ];
@endphp

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('branches.show', $branch) }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
            <h2 class="text-xl font-bold text-gray-800">تعديل الفرع — {{ $branch->code }}</h2>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
            @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ route('branches.update', $branch) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم الفرع</label>
                    <input type="text" name="name" value="{{ old('name', $branch->name) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">النوع *</label>
                    <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                        <option value="pharmacy" @selected(old('type', $branch->type) === 'pharmacy')>صيدلية</option>
                        <option value="warehouse" @selected(old('type', $branch->type) === 'warehouse')>مخزن</option>
                        <option value="office" @selected(old('type', $branch->type) === 'office')>مكتب</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الحالة *</label>
                    <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                        <option value="active" @selected(old('status', $branch->status) === 'active')>نشط</option>
                        <option value="stopped" @selected(old('status', $branch->status) === 'stopped')>متوقف</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المحافظة</label>
                    <input type="text" name="governorate" value="{{ old('governorate', $branch->governorate) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">التليفون</label>
                    <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address', $branch->address) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="text-sm font-medium text-gray-700 mb-2">الصلاحيات</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($flags as $key => $label)
                    <label class="flex items-center gap-2 bg-gray-50 rounded-xl px-3 py-2.5 cursor-pointer">
                        <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $branch->$key)) class="w-4 h-4 rounded text-indigo-600">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <a href="{{ route('branches.show', $branch) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
