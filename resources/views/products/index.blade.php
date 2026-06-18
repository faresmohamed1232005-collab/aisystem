@extends('layouts.app')
@section('title', 'المخزن')

@section('content')


   
    @php
        $isSubUser = session()->has('sub_user');
        $isAdmin = !$isSubUser; 
    @endphp
    <div class="space-y-5">

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-800">إدارة المخزن</h2>
                <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">
                    {{ $products->total() }} صنف
                </span>
            </div>
            @if ($isAdmin)
                <button onclick="openAddModal()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-xl font-semibold text-sm flex items-center gap-2 transition">
                    <i class="fas fa-plus"></i> تحديث كمية
                </button>
            @endif
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600"><i
                        class="fas fa-pills"></i></div>
                <div>
                    <div class="text-xl font-bold text-gray-800">{{ number_format($stats['total_drugs']) }}</div>
                    <div class="text-xs text-gray-500">إجمالي الكتالوج</div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600"><i
                        class="fas fa-box-open"></i></div>
                <div>
                    <div class="text-xl font-bold text-gray-800">{{ number_format($stats['total_in_stock']) }}</div>
                    <div class="text-xs text-gray-500">أصناف عندك</div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600"><i
                        class="fas fa-exclamation-triangle"></i></div>
                <div>
                    <div class="text-xl font-bold text-gray-800">{{ number_format($stats['low_stock']) }}</div>
                    <div class="text-xs text-gray-500">كمية منخفضة</div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET"
            class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-gray-500 mb-1">بحث</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="اسم الدواء، باركود، مادة فعالة..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2 text-sm pr-9 focus:ring-2 focus:ring-indigo-300 outline-none">
                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">عرض</label>
                <select name="in_stock"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">الكل</option>
                    <option value="1" {{ request('in_stock') ? 'selected' : '' }}>عندي فقط</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">الكمية</label>
                <select name="low_stock"
                    class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">الكل</option>
                    <option value="1" {{ request('low_stock') ? 'selected' : '' }}>منخفضة فقط</option>
                </select>
            </div>
            <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition">
                <i class="fas fa-filter ml-1"></i> تصفية
            </button>
            @if (request()->anyFilled(['search', 'in_stock', 'low_stock']))
                <a href="{{ route('products.index') }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-semibold transition">
                    <i class="fas fa-times ml-1"></i> مسح
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr class="text-right">
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الدواء</th>
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الفئة / الشكل</th>
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الأسعار</th>
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الكمية</th>
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الصلاحية</th>
                            <th class="px-4 py-3 font-semibold text-gray-500 text-xs">الحالة</th>
                            @if ($isAdmin)
                                <th class="px-4 py-3 font-semibold text-gray-500 text-xs text-center">تعديل</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($products as $row)
                            @php
                                $qty = (float) $row->quantity;
                                $minQty = (int) ($row->min_quantity ?? 5);
                                $price = (float) ($row->custom_price ?? ($row->new_price ?? ($row->old_price ?? 0)));
                                $nameAr = $row->name_ar ?? ($row->name_en ?? '—');
                                $nameEn = $row->name_en ?? '';
                                $majorU = max(1, (int) $row->major_units);
                                $minorU = max(1, (int) $row->minor_units);
                                $stripName = $row->strip_unit_name ?? 'شريط';
                                $pieceName = $row->piece_unit_name ?? 'حبة';
                                $stripPrice = $majorU > 1 ? round($price / $majorU, 2) : null;
                                $piecePrice = $majorU * $minorU > 1 ? round($price / ($majorU * $minorU), 2) : null;
                                $qtyDisplay = \App\Http\Controllers\ProductsController::formatBoxStrip(
                                    $qty,
                                    $majorU,
                                    $stripName,
                                );

                                // ✅ الصلاحية — مصحوحة
                                $expiry = null;
                                $expired = false;
                                $expSoon = false;
                                $daysLeft = null;

                                if (!empty($row->expiry_date)) {
                                    try {
                                        $expiry = \Carbon\Carbon::parse($row->expiry_date)->startOfDay();
                                        $today = \Carbon\Carbon::today();
                                        $expired = $expiry->lt($today); // قبل اليوم = منتهي
                                        $daysLeft = $today->diffInDays($expiry, false); // سالب لو منتهي
                                        $expSoon = !$expired && $daysLeft >= 0 && $daysLeft <= 90; // مستقبل وأقل من 90 يوم
                                    } catch (\Exception $e) {
                                        $expiry = null;
                                    }
                                }
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition">

                                {{-- الدواء --}}
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="font-semibold text-gray-800 text-sm leading-tight">{{ $nameAr }}</div>
                                    @if ($nameEn)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ Str::limit($nameEn, 40) }}</div>
                                    @endif
                                    @if ($row->barcode)
                                        <div class="text-xs text-gray-300 font-mono mt-0.5">
                                            <i class="fas fa-barcode ml-0.5"></i>{{ $row->barcode }}
                                        </div>
                                    @endif
                                    @if ($row->company)
                                        <div class="text-xs text-indigo-400 mt-0.5">{{ Str::limit($row->company, 30) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- الفئة / الشكل --}}
                                <td class="px-4 py-3">
                                    @if ($row->category)
                                        <div class="text-xs text-gray-600">{{ Str::limit($row->category, 25) }}</div>
                                    @endif
                                    @if ($row->dosage_form)
                                        <span
                                            class="inline-block bg-blue-50 text-blue-600 text-xs px-2 py-0.5 rounded-full mt-0.5">{{ $row->dosage_form }}</span>
                                    @endif
                                    @if ($row->concentration)
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $row->concentration }}</div>
                                    @endif
                                </td>

                                {{-- الأسعار --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($price > 0)
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span class="text-xs text-gray-400">📦</span>
                                            <span class="font-bold text-indigo-600 text-sm">{{ number_format($price, 2) }}
                                                ج.م</span>
                                            <span class="text-xs text-gray-400">/ علبة</span>
                                        </div>
                                        @if ($stripPrice !== null)
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-gray-400">📋</span>
                                                <span
                                                    class="font-semibold text-orange-600 text-xs">{{ number_format($stripPrice, 2) }}
                                                    ج.م</span>
                                                <span class="text-xs text-gray-400">/ {{ $stripName }}</span>
                                            </div>
                                        @endif
                                        @if ($piecePrice !== null && $minorU > 1)
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-xs text-gray-400">💊</span>
                                                <span
                                                    class="font-semibold text-green-600 text-xs">{{ number_format($piecePrice, 2) }}
                                                    ج.م</span>
                                                <span class="text-xs text-gray-400">/ {{ $pieceName }}</span>
                                            </div>
                                        @endif
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ $majorU }} {{ $stripName }} × {{ $minorU }}
                                            {{ $pieceName }}
                                        </div>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>

                                {{-- الكمية --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($qty <= 0)
                                        <span class="text-red-400 font-bold text-xs">صفر</span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 text-xs font-bold px-2.5 py-1 rounded-lg border border-indigo-200">
                                            <i class="fas fa-box text-indigo-400" style="font-size:10px"></i>
                                            {{ $qtyDisplay }}
                                        </span>
                                        @if ($majorU > 1)
                                            <div class="text-xs text-gray-400 mt-1">شرايط:
                                                {{ number_format($qty * $majorU, 0) }}</div>
                                        @endif
                                        @if ($minorU > 1)
                                            <div class="text-xs text-gray-400">{{ $pieceName }}:
                                                {{ number_format($qty * $majorU * $minorU, 0) }}</div>
                                        @endif
                                    @endif
                                </td>

                                {{-- الصلاحية ✅ مصحوحة --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($expiry)
                                        <span
                                            class="text-xs {{ $expired ? 'text-red-600 font-bold' : ($expSoon ? 'text-orange-500 font-semibold' : 'text-gray-500') }}">
                                            {{ $expiry->format('Y-m-d') }}
                                            @if ($expired)
                                                <span class="block text-red-500 font-bold">منتهي!</span>
                                            @elseif($expSoon)
                                                <span class="block text-orange-400">{{ $daysLeft }} يوم متبقي</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>

                                {{-- الحالة ✅ مصحوحة --}}
                                <td class="px-4 py-3">
                                    @if ($expired)
                                        <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full">منتهي
                                            الصلاحية</span>
                                    @elseif($qty <= 0)
                                        <span class="bg-red-50 text-red-400 text-xs px-2 py-1 rounded-full">نفد</span>
                                    @elseif($qty <= $minQty)
                                        <span
                                            class="bg-orange-100 text-orange-600 text-xs px-2 py-1 rounded-full">منخفض</span>
                                    @elseif($expSoon)
                                        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full">قارب
                                            الانتهاء</span>
                                    @else
                                        <span class="bg-green-100 text-green-600 text-xs px-2 py-1 rounded-full">جيد</span>
                                    @endif
                                </td>

                                @if ($isAdmin)
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            onclick="openEditModal(
                                        {{ $row->id }},
                                        '{{ addslashes($nameAr) }}',
                                        {{ $qty }},
                                        {{ $minQty }},
                                        {{ $price }},
                                        {{ $row->cost_price ?? 'null' }},
                                        '{{ $row->expiry_date ?? '' }}',
                                        '{{ addslashes($stripName) }}',
                                        {{ $majorU }},
                                        {{ $minorU }},
                                        '{{ addslashes($pieceName) }}'
                                    )"
                                            class="text-indigo-500 hover:text-indigo-700 text-xs px-2 py-1.5 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                @endif

                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isAdmin ? 7 : 6 }}" class="text-center py-16 text-gray-400">
                                    <i class="fas fa-pills text-4xl mb-3 block opacity-30"></i>
                                    <p class="text-sm">
                                        @if (request()->anyFilled(['search', 'in_stock', 'low_stock']))
                                            مفيش نتائج للبحث ده
                                        @else
                                            ابحث عن دواء بالاسم أو الباركود لتحديث كميته
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($products->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $products->links() }}</div>
            @endif
        </div>
    </div>

    {{-- مودالات الأدمن --}}
    @if ($isAdmin)
        <div id="editModal"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div
                class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 border border-gray-100 max-h-screen overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                        <i class="fas fa-edit text-indigo-500"></i> تحديث المخزون
                    </h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition"><i
                            class="fas fa-times"></i></button>
                </div>
                <p id="modal-drug-name"
                    class="text-sm text-indigo-600 font-semibold mb-4 bg-indigo-50 px-3 py-2 rounded-lg"></p>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">الكمية (علب)</label>
                            <input type="number" id="edit-quantity" min="0" step="0.01"
                                oninput="updateStripPreview()"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"
                                dir="ltr">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">حد التنبيه (علب)</label>
                            <input type="number" id="edit-min-quantity" min="0"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"
                                dir="ltr">
                        </div>
                    </div>
                    <div id="strip-qty-preview"
                        class="hidden bg-indigo-50 border border-indigo-200 rounded-xl px-3 py-2 text-xs text-indigo-700 font-semibold text-center">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">سعر البيع / علبة</label>
                            <input type="number" id="edit-custom-price" min="0" step="0.01"
                                placeholder="السعر الرسمي" oninput="updateStripPreview()"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"
                                dir="ltr">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">سعر الشراء / علبة</label>
                            <input type="number" id="edit-cost-price" min="0" step="0.01"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"
                                dir="ltr">
                        </div>
                    </div>
                    <div id="strip-price-preview"
                        class="hidden bg-orange-50 border border-orange-200 rounded-xl px-3 py-2 text-xs text-orange-700 text-center">
                    </div>
                    <div id="piece-price-preview"
                        class="hidden bg-green-50 border border-green-200 rounded-xl px-3 py-2 text-xs text-green-700 text-center">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">تاريخ الصلاحية</label>
                        <input type="date" id="edit-expiry"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300"
                            dir="ltr">
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">اسم الوحدة الوسطى (الشريط)</label>
                        <input type="text" id="edit-strip-name" placeholder="شريط" oninput="updateStripPreview()"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300 text-right">
                        <p class="text-xs text-gray-400 mt-1">عدد الشرايط في العلبة: <span id="modal-major-info"
                                class="font-semibold text-gray-600"></span></p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-3">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">اسم الوحدة الصغيرة (الحبة)</label>
                        <input type="text" id="edit-piece-name" placeholder="حبة" oninput="updateStripPreview()"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-300 text-right">
                        <p class="text-xs text-gray-400 mt-1">عدد الحبايب في الشريط: <span id="modal-minor-info"
                                class="font-semibold text-gray-600"></span></p>
                    </div>
                </div>
                <div id="edit-alert" class="hidden mt-3 text-sm rounded-xl px-3 py-2"></div>
                <div class="flex gap-3 mt-5">
                    <button id="edit-save-btn" onclick="saveInventory()"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <button onclick="closeEditModal()"
                        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-bold transition">إلغاء</button>
                </div>
            </div>
        </div>

        <div id="addModal"
            class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                        <i class="fas fa-search text-indigo-500"></i> ابحث عن دواء لتحديث كميته
                    </h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition"><i
                            class="fas fa-times"></i></button>
                </div>
                <div class="relative mb-3">
                    <input type="text" id="add-search" placeholder="اسم الدواء أو الباركود..."
                        oninput="searchDrugs(this.value)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm pr-10 outline-none focus:ring-2 focus:ring-indigo-300">
                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                </div>
                <div id="add-results" class="max-h-64 overflow-y-auto text-sm text-gray-400 text-center py-4">ابدأ الكتابة
                    للبحث...</div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    @if ($isAdmin)
        <script>
            const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let currentDrugId = null,
                currentMajorU = 1,
                currentMinorU = 1;

            function fmtBoxStrip(qty, major, stripName) {
                if (major <= 1 || qty <= 0) return qty.toFixed(2) + ' علبة';
                const totalStrips = Math.round(qty * major);
                const boxes = Math.floor(totalStrips / major);
                const strips = totalStrips % major;
                if (boxes > 0 && strips > 0) return `${boxes} علبة و ${strips} ${stripName}`;
                if (boxes > 0) return `${boxes} علبة`;
                return `${strips} ${stripName}`;
            }

            function openEditModal(id, name, qty, minQty, price, costPrice, expiry, stripName, majorU, minorU, pieceName) {
                currentDrugId = id;
                currentMajorU = majorU || 1;
                currentMinorU = minorU || 1;
                document.getElementById('modal-drug-name').textContent = name;
                document.getElementById('edit-quantity').value = qty;
                document.getElementById('edit-min-quantity').value = minQty;
                document.getElementById('edit-custom-price').value = price > 0 ? price : '';
                document.getElementById('edit-cost-price').value = costPrice ?? '';
                document.getElementById('edit-expiry').value = expiry ?? '';
                document.getElementById('edit-strip-name').value = stripName || 'شريط';
                document.getElementById('edit-piece-name').value = pieceName || 'حبة';
                document.getElementById('modal-major-info').textContent = `${majorU} ${stripName || 'شريط'} في العلبة`;
                document.getElementById('modal-minor-info').textContent =
                    `${minorU} ${pieceName || 'حبة'} في الـ${stripName || 'شريط'}`;
                document.getElementById('edit-alert').classList.add('hidden');
                document.getElementById('editModal').classList.remove('hidden');
                updateStripPreview();
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                currentDrugId = null;
            }

            function updateStripPreview() {
                const qty = parseFloat(document.getElementById('edit-quantity').value) || 0;
                const price = parseFloat(document.getElementById('edit-custom-price').value) || 0;
                const stripName = document.getElementById('edit-strip-name').value.trim() || 'شريط';
                const pieceName = document.getElementById('edit-piece-name').value.trim() || 'حبة';
                const major = currentMajorU,
                    minor = currentMinorU;

                const qtyEl = document.getElementById('strip-qty-preview');
                if (qty > 0 && major > 1) {
                    const totalStrips = Math.round(qty * major),
                        totalPieces = totalStrips * minor;
                    qtyEl.textContent =
                        `📦 ${fmtBoxStrip(qty, major, stripName)} | إجمالي: ${totalStrips} ${stripName} — ${totalPieces} ${pieceName}`;
                    qtyEl.classList.remove('hidden');
                } else qtyEl.classList.add('hidden');

                const priceEl = document.getElementById('strip-price-preview');
                if (price > 0 && major > 1) {
                    priceEl.textContent =
                        `📋 سعر الـ${stripName} = ${price.toFixed(2)} ÷ ${major} = ${(price/major).toFixed(2)} ج.م`;
                    priceEl.classList.remove('hidden');
                } else priceEl.classList.add('hidden');

                const pieceEl = document.getElementById('piece-price-preview');
                if (price > 0 && minor > 1) {
                    const totalUnits = major * minor;
                    pieceEl.textContent =
                        `💊 سعر الـ${pieceName} = ${price.toFixed(2)} ÷ ${totalUnits} = ${(price/totalUnits).toFixed(2)} ج.م`;
                    pieceEl.classList.remove('hidden');
                } else pieceEl.classList.add('hidden');
            }

            async function saveInventory() {
                if (!currentDrugId) return;
                const btn = document.getElementById('edit-save-btn');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ الحفظ...';
                const body = new FormData();
                body.append('_token', CSRF);
                body.append('quantity', document.getElementById('edit-quantity').value);
                body.append('min_quantity', document.getElementById('edit-min-quantity').value);
                body.append('custom_price', document.getElementById('edit-custom-price').value);
                body.append('cost_price', document.getElementById('edit-cost-price').value);
                body.append('expiry_date', document.getElementById('edit-expiry').value);
                body.append('strip_unit_name', document.getElementById('edit-strip-name').value || 'شريط');
                body.append('piece_unit_name', document.getElementById('edit-piece-name').value || 'حبة');
                body.append('_method', 'PUT');
                try {
                    const res = await fetch(`/products/inventory/${currentDrugId}`, {
                        method: 'POST',
                        body
                    });
                    const data = await res.json();
                    const el = document.getElementById('edit-alert');
                    el.className =
                        `mt-3 text-sm rounded-xl px-3 py-2 ${data.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-600'}`;
                    el.textContent = data.message;
                    el.classList.remove('hidden');
                    if (data.success) setTimeout(() => {
                        closeEditModal();
                        location.reload();
                    }, 800);
                } catch (e) {
                    const el = document.getElementById('edit-alert');
                    el.className = 'mt-3 text-sm rounded-xl px-3 py-2 bg-red-50 text-red-600';
                    el.textContent = 'حدث خطأ، حاول تاني';
                    el.classList.remove('hidden');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> حفظ';
                }
            }

            function openAddModal() {
                document.getElementById('add-search').value = '';
                document.getElementById('add-results').innerHTML =
                    '<div class="text-gray-400 text-center py-4">ابدأ الكتابة للبحث...</div>';
                document.getElementById('addModal').classList.remove('hidden');
                setTimeout(() => document.getElementById('add-search').focus(), 100);
            }

            function closeAddModal() {
                document.getElementById('addModal').classList.add('hidden');
            }

            let searchTimer;

            function searchDrugs(term) {
                clearTimeout(searchTimer);
                if (term.length < 2) {
                    document.getElementById('add-results').innerHTML =
                        '<div class="text-gray-400 text-center py-4">اكتب حرفين على الأقل</div>';
                    return;
                }
                document.getElementById('add-results').innerHTML =
                    '<div class="text-gray-400 text-center py-4"><i class="fas fa-spinner fa-spin ml-2"></i>جارٍ البحث...</div>';
                searchTimer = setTimeout(async () => {
                    const res = await fetch(`{{ route('products.search') }}?q=${encodeURIComponent(term)}`);
                    const drugs = await res.json();
                    if (!drugs.length) {
                        document.getElementById('add-results').innerHTML =
                            '<div class="text-gray-400 text-center py-4">مفيش نتائج</div>';
                        return;
                    }
                    document.getElementById('add-results').innerHTML = drugs.map(d => {
                        const strip = d.strip_unit_name || 'شريط',
                            piece = d.piece_unit_name || 'حبة';
                        const major = d.major_units || 1,
                            minor = d.minor_units || 1;
                        return `<div class="flex items-center justify-between bg-gray-50 hover:bg-indigo-50 rounded-xl px-3 py-2.5 cursor-pointer transition border border-transparent hover:border-indigo-200 mb-2"
                 onclick="selectDrugFromAdd(${d.id}, '${(d.name||'').replace(/'/g,"\\'")}', '${strip.replace(/'/g,"\\'")}', '${piece.replace(/'/g,"\\'")}', ${major}, ${minor})">
                <div>
                    <div class="font-semibold text-gray-800 text-sm">${d.name||'—'}</div>
                    <div class="text-xs text-gray-400">${d.dosage_form||''} ${d.concentration||''}</div>
                    <div class="text-xs text-indigo-400 mt-0.5">علبة = ${major} ${strip} × ${minor} ${piece}</div>
                </div>
                <div class="text-left">
                    <div class="text-xs font-bold text-indigo-600">${d.price > 0 ? d.price.toFixed(2)+' ج.م' : ''}</div>
                    <div class="text-xs ${d.quantity > 0 ? 'text-green-600' : 'text-gray-400'}">${d.quantity > 0 ? fmtBoxStrip(d.quantity,major,strip) : 'صفر'}</div>
                </div>
            </div>`;
                    }).join('');
                }, 350);
            }

            function selectDrugFromAdd(id, name, stripName, pieceName, majorU, minorU) {
                closeAddModal();
                openEditModal(id, name, 0, 5, 0, null, '', stripName, majorU, minorU, pieceName);
            }
            ['editModal', 'addModal'].forEach(id => {
                document.getElementById(id).addEventListener('click', function(e) {
                    if (e.target === this) id === 'editModal' ? closeEditModal() : closeAddModal();
                });
            });
        </script>
    @endif
@endsection
