@extends('layouts.app')
@section('title', 'تعديل بيانات العميل')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-6">

        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('customers.show', $customer) }}" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-arrow-right text-lg"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">تعديل بيانات العميل</h2>
                <span class="font-mono text-xs bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded-lg">
                    {{ $customer->code }}
                </span>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
            @foreach($errors->all() as $e)
                <div class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $e }}</div>
            @endforeach
        </div>
        @endif

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4 text-green-600 text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- الاسم -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم العميل *</label>
                    <input type="text" name="name"
                           value="{{ old('name', $customer->name) }}"
                           required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="الاسم كامل">
                </div>

                <!-- التليفون -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم التليفون</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $customer->phone) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="01xxxxxxxxx">
                </div>

                <!-- البريد -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email"
                           value="{{ old('email', $customer->email) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400"
                           placeholder="اختياري">
                </div>

                <!-- العنوان -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">العنوان</label>
                    <input type="text" name="address"
                           value="{{ old('address', $customer->address) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                           placeholder="اختياري">
                </div>

                <!-- حد الآجل -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        حد الآجل (ج.م)
                        <span class="text-xs text-gray-400 font-normal">— أقصى مبلغ مسموح بيه للآجل</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="credit_limit"
                               value="{{ old('credit_limit', $customer->credit_limit) }}"
                               min="0" step="0.01"
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 pl-14 focus:outline-none focus:border-indigo-400 text-right">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">ج.م</span>
                    </div>
                </div>

                <!-- الرصيد الحالي (للعرض فقط) -->
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <span class="text-sm text-gray-500">الرصيد المديون الحالي</span>
                        <span class="font-bold {{ $customer->balance > 0 ? 'text-red-500' : 'text-green-600' }}">
                            {{ number_format($customer->balance, 2) }} ج.م
                            @if($customer->balance <= 0)
                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            @endif
                        </span>
                    </div>
                </div>

                <!-- ملاحظات -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="3"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right resize-none"
                              placeholder="اختياري">{{ old('notes', $customer->notes) }}</textarea>
                </div>
            </div>

            <!-- أزرار -->
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <a href="{{ route('customers.show', $customer) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-times"></i> إلغاء
                </a>
                <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                      class="mr-auto"
                      onsubmit="return confirm('هتحذف العميل {{ $customer->name }}؟ الفواتير المرتبطة به هتتأثر!')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-3 rounded-xl transition flex items-center gap-2 text-sm">
                        <i class="fas fa-trash"></i> حذف العميل
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection