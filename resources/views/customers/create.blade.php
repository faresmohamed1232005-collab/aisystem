{{-- resources/views/customers/create.blade.php --}}
@extends('layouts.app')
@section('title', 'إضافة عميل')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('customers.index') }}" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-arrow-right"></i>
            </a>
            <h2 class="text-xl font-bold text-gray-800">إضافة عميل جديد</h2>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
            @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
        </div>
        @endif

        <form action="{{ route('customers.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="الاسم كامل">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم التليفون</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="01xxxxxxxxx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400"
                           placeholder="اختياري">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="اختياري">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        حد الآجل (ج.م)
                        <span class="text-xs text-gray-400 font-normal">— أقصى مبلغ مسموح بيه للآجل</span>
                    </label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', 0) }}"
                           min="0" step="0.01"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="2"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                              placeholder="اختياري">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-user-plus"></i> إضافة العميل
                </button>
                <a href="{{ route('customers.index') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection