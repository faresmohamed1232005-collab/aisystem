@extends('layouts.app')
@section('title', 'تعديل منتج')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm p-6">

        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('products.index') }}"
               class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-arrow-right text-lg"></i>
            </a>
            <h2 class="text-xl font-bold text-gray-800">تعديل منتج</h2>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('products.update', $product) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <!-- اسم المنتج -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم المنتج *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- الباركود -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الباركود</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- الفئة -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الفئة</label>
                    <select name="category"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
                        <option value="">اختر الفئة</option>
                        @foreach(['أدوية','مستلزمات طبية','مستحضرات تجميل','مكملات غذائية','أخرى'] as $cat)
                            <option value="{{ $cat }}"
                                {{ old('category', $product->category) === $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- سعر البيع -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سعر البيع *</label>
                    <input type="number" name="price" value="{{ old('price', $product->price) }}"
                           required step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- سعر التكلفة -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سعر التكلفة</label>
                    <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                           step="0.01" min="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- الكمية -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الكمية المتاحة *</label>
                    <input type="number" name="quantity" value="{{ old('quantity', $product->quantity) }}"
                           required min="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- حد التنبيه -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">حد التنبيه (كمية أدنى)</label>
                    <input type="number" name="min_quantity"
                           value="{{ old('min_quantity', $product->min_quantity) }}" min="0"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- تاريخ الصلاحية -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الصلاحية</label>
                    <input type="date" name="expiry_date"
                           value="{{ old('expiry_date', $product->expiry_date?->format('Y-m-d')) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
                </div>

                <!-- الشركة المصنعة -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الشركة المصنّعة</label>
                    <input type="text" name="manufacturer"
                           value="{{ old('manufacturer', $product->manufacturer) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                </div>

                <!-- الوصف -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
                    <textarea name="description" rows="2"
                              class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">{{ old('description', $product->description) }}</textarea>
                </div>

                <!-- صورة المنتج -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">صورة المنتج</label>

                    {{-- لو في صورة موجودة --}}
                    @if($product->image)
                    <div class="flex items-center gap-4 mb-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <img src="{{ asset('storage/' . $product->image) }}"
                             alt="صورة المنتج"
                             class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                        <div class="text-sm text-gray-500">الصورة الحالية — ارفع صورة جديدة لو عايز تغيرها</div>
                    </div>
                    @endif

                    <input type="file" name="image" accept="image/*"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none">
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-save"></i> حفظ التعديلات
                </button>
                <a href="{{ route('products.index') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">
                    إلغاء
                </a>
            </div>

        </form>
    </div>
</div>
@endsection