@extends('layouts.app')
@section('title', 'إضافة منتج')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm p-6">

            <div class="flex items-center gap-3 mb-6">
                <a href="{{ route('products.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-arrow-right text-lg"></i>
                </a>
                <h2 class="text-xl font-bold text-gray-800">إضافة منتج جديد</h2>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4 text-red-600 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                {{-- ===== بيانات أساسية ===== --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide">البيانات الأساسية</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">اسم المنتج *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                                placeholder="مثال: باراسيتامول 500 مج">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الباركود</label>
                            <input type="text" name="barcode" value="{{ old('barcode') }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                                placeholder="اختياري">
                        </div>

                        <!-- الفئة -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الفئة *</label>
                            <select name="category" required
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 bg-white">
                                <option value="">— اختر الفئة —</option>
                                <optgroup label="🔵 أدوية">
                                    <option value="أدوية أطفال" {{ old('category') == 'أدوية أطفال' ? 'selected' : '' }}>
                                        أدوية أطفال</option>
                                    <option value="أدوية السكري" {{ old('category') == 'أدوية السكري' ? 'selected' : '' }}>
                                        أدوية السكري</option>
                                    <option value="أدوية ضغط" {{ old('category') == 'أدوية ضغط' ? 'selected' : '' }}>
                                        أدوية ضغط</option>
                                    <option value="أدوية قلب" {{ old('category') == 'أدوية قلب' ? 'selected' : '' }}>
                                        أدوية قلب</option>
                                    <option value="أدوية حساسية" {{ old('category') == 'أدوية حساسية' ? 'selected' : '' }}>
                                        أدوية حساسية</option>
                                    <option value="أدوية عامة" {{ old('category') == 'أدوية عامة' ? 'selected' : '' }}>
                                        أدوية عامة</option>
                                    <option value="أدوية عظام ومفاصل"
                                        {{ old('category') == 'أدوية عظام ومفاصل' ? 'selected' : '' }}>أدوية عظام ومفاصل
                                    </option>
                                    <option value="أدوية نسائية" {{ old('category') == 'أدوية نسائية' ? 'selected' : '' }}>
                                        أدوية نسائية</option>
                                    <option value="أدوية نفسية وعصبية"
                                        {{ old('category') == 'أدوية نفسية وعصبية' ? 'selected' : '' }}>أدوية نفسية وعصبية
                                    </option>
                                    <option value="أدوية الفياجرا"
                                        {{ old('category') == 'أدوية الفياجرا' ? 'selected' : '' }}>أدوية الفياجرا</option>
                                    <option value="مسكنات" {{ old('category') == 'مسكنات' ? 'selected' : '' }}>
                                        مسكنات</option>
                                    <option value="مضادات حيوية" {{ old('category') == 'مضادات حيوية' ? 'selected' : '' }}>
                                        مضادات حيوية</option>
                                    <option value="أمبولات" {{ old('category') == 'أمبولات' ? 'selected' : '' }}>
                                        أمبولات</option>
                                </optgroup>
                                <optgroup label="🟢 مكملات وفيتامينات">
                                    <option value="فيتامينات" {{ old('category') == 'فيتامينات' ? 'selected' : '' }}>
                                        فيتامينات</option>
                                </optgroup>
                                <optgroup label="🟣 تجميل ومستلزمات">
                                    <option value="مستحضرات تجميل"
                                        {{ old('category') == 'مستحضرات تجميل' ? 'selected' : '' }}>
                                        مستحضرات تجميل</option>
                                    <option value="مستلزمات طبية"
                                        {{ old('category') == 'مستلزمات طبية' ? 'selected' : '' }}>
                                        مستلزمات طبية</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">سعر البيع (للوحدة الكبيرة) *</label>
                            <div class="relative">
                                <input type="number" name="price" id="price-input" value="{{ old('price') }}" required
                                    step="0.01" min="0"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 pl-14 focus:outline-none focus:border-indigo-400 text-right"
                                    oninput="updateUnitPrices()">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">ج.م</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">سعر الشراء (التكلفة)</label>
                            <div class="relative">
                                <input type="number" name="cost_price" value="{{ old('cost_price') }}" step="0.01"
                                    min="0"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 pl-14 focus:outline-none focus:border-indigo-400 text-right">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">ج.م</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الكمية المتاحة *</label>
                            <input type="number" name="quantity" value="{{ old('quantity', 0) }}" required min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">حد التنبيه</label>
                            <input type="number" name="min_quantity" value="{{ old('min_quantity', 5) }}" min="0"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الصلاحية</label>
                            <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الشركة المصنّعة</label>
                            <input type="text" name="manufacturer" value="{{ old('manufacturer') }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"
                                placeholder="اختياري">
                        </div>
                    </div>
                </div>

                {{-- ===== قسم الوحدات ===== --}}
                <div class="border border-indigo-100 rounded-2xl p-4 bg-indigo-50/30 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-indigo-700 flex items-center gap-2">
                            <i class="fas fa-layer-group"></i> الوحدات والتغليف
                        </h3>
                        <span class="text-xs text-gray-400">مثال: علبة ← شريط ← حبة</span>
                    </div>

                    {{-- الوحدة الكبيرة --}}
                    <div class="bg-white rounded-xl p-4 border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-3 flex items-center gap-1">
                            <i class="fas fa-box text-indigo-400"></i> الوحدة الكبيرة (الأساسية)
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">اسم الوحدة</label>
                                <input type="text" name="unit_name" value="{{ old('unit_name', 'علبة') }}"
                                    placeholder="علبة / عبوة / كرتونة"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">السعر</label>
                                <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-3 py-2.5 text-sm font-bold text-indigo-700 text-right"
                                    id="pack-price-display">
                                    0.00 ج.م
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- الوحدة الوسطى --}}
                    <div class="bg-white rounded-xl p-4 border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-3 flex items-center gap-1">
                            <i class="fas fa-th-list text-orange-400"></i> الوحدة الوسطى (اختياري)
                            <span class="text-gray-400 font-normal">— مثال: شريط</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">اسم الوحدة</label>
                                <input type="text" name="sub_unit_name" value="{{ old('sub_unit_name') }}"
                                    id="sub-unit-name" placeholder="شريط / كيس" oninput="updateUnitPrices()"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">عدد الوحدات في العلبة</label>
                                <input type="number" name="units_per_pack" value="{{ old('units_per_pack', 1) }}"
                                    id="units-per-pack" min="1" step="1" oninput="updateUnitPrices()"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">سعر الوحدة</label>
                                <div class="bg-orange-50 border border-orange-200 rounded-xl px-3 py-2.5 text-sm font-bold text-orange-700 text-right"
                                    id="sub-price-display">
                                    —
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- أصغر وحدة --}}
                    <div class="bg-white rounded-xl p-4 border border-gray-100">
                        <div class="text-xs font-bold text-gray-500 mb-3 flex items-center gap-1">
                            <i class="fas fa-circle text-green-400 text-xs"></i> أصغر وحدة (اختياري)
                            <span class="text-gray-400 font-normal">— مثال: حبة / أمبولة</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">اسم الوحدة</label>
                                <input type="text" name="smallest_unit_name" value="{{ old('smallest_unit_name') }}"
                                    id="smallest-unit-name" placeholder="حبة / أمبولة / كبسولة"
                                    oninput="updateUnitPrices()"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">عدد الحبات في الشريط</label>
                                <input type="number" name="sub_units_per_unit"
                                    value="{{ old('sub_units_per_unit', 1) }}" id="sub-units-per-unit" min="1"
                                    step="1" oninput="updateUnitPrices()"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">سعر الوحدة</label>
                                <div class="bg-green-50 border border-green-200 rounded-xl px-3 py-2.5 text-sm font-bold text-green-700 text-right"
                                    id="smallest-price-display">
                                    —
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ملخص الحساب --}}
                    <div id="units-summary" class="hidden bg-indigo-600 text-white rounded-xl p-3 text-xs text-center">
                    </div>
                </div>

                {{-- صورة المنتج --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">صورة المنتج</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-xl p-5 text-center cursor-pointer hover:border-indigo-400 transition"
                        onclick="document.getElementById('img-input').click()">
                        <i class="fas fa-image text-2xl text-gray-300 mb-2 block"></i>
                        <p class="text-gray-400 text-sm">اضغط لرفع صورة المنتج</p>
                        <p id="img-name" class="text-indigo-500 text-xs mt-1 hidden"></p>
                    </div>
                    <input type="file" id="img-input" name="image" accept="image/*" class="hidden"
                        onchange="document.getElementById('img-name').textContent=this.files[0]?.name;
                                 document.getElementById('img-name').classList.remove('hidden')">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-save"></i> حفظ المنتج
                    </button>
                    <a href="{{ route('products.index') }}"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-xl transition">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

@section('scripts')
    <script>
        function updateUnitPrices() {
            const price = parseFloat(document.getElementById('price-input').value) || 0;
            const perPack = parseInt(document.getElementById('units-per-pack').value) || 1;
            const perUnit = parseInt(document.getElementById('sub-units-per-unit').value) || 1;
            const subName = document.getElementById('sub-unit-name').value.trim();
            const smallName = document.getElementById('smallest-unit-name').value.trim();

            // سعر الوحدة الكبيرة
            document.getElementById('pack-price-display').textContent = price.toFixed(2) + ' ج.م';

            // سعر الوحدة الوسطى
            if (subName && perPack > 1) {
                const subPrice = price / perPack;
                document.getElementById('sub-price-display').textContent = subPrice.toFixed(2) + ' ج.م';
            } else {
                document.getElementById('sub-price-display').textContent = '—';
            }

            // سعر أصغر وحدة
            const total = perPack * perUnit;
            if (smallName && total > 1) {
                const smallPrice = price / total;
                document.getElementById('smallest-price-display').textContent = smallPrice.toFixed(2) + ' ج.م';
            } else {
                document.getElementById('smallest-price-display').textContent = '—';
            }

            // ملخص
            const summary = document.getElementById('units-summary');
            if (price > 0 && (subName || smallName)) {
                let txt = `علبة واحدة = ${price.toFixed(2)} ج.م`;
                if (subName && perPack > 1) txt += ` ← ${perPack} ${subName} (${(price/perPack).toFixed(2)} ج.م للشريط)`;
                if (smallName && total > 1) txt += ` ← ${total} ${smallName} (${(price/total).toFixed(2)} ج.م للحبة)`;
                summary.textContent = txt;
                summary.classList.remove('hidden');
            } else {
                summary.classList.add('hidden');
            }
        }

        // تحديث عند التحميل
        updateUnitPrices();
    </script>
@endsection
@endsection
