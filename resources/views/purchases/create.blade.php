@extends('layouts.app')
@section('title', 'فاتورة شراء جديدة')

@section('styles')
    <style>
        @keyframes toastIn {
            from {
                transform: translateY(-60px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        @keyframes toastOut {
            from {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

            to {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
        }

        @keyframes toastProgress {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        @keyframes scannerPulse {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
        }

        #unified-search.scanner-active {
            border-color: #10b981 !important;
            animation: scannerPulse 0.8s ease-in-out;
        }

        /* ===== جدول الأصناف بشكل Excel ===== */
        #cart-table-wrapper {
            position: relative;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
        }

        #cart-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            min-width: 1280px;
            font-size: 12.5px;
        }

        #cart-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #f0fdf4;
            color: #166534;
            font-weight: 700;
            font-size: 11.5px;
            padding: 8px 6px;
            border-bottom: 2px solid #bbf7d0;
            white-space: nowrap;
            text-align: center;
        }

        #cart-table thead th:first-child {
            border-radius: 0;
        }

        #cart-table tbody td {
            padding: 4px 5px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            white-space: nowrap;
        }

        #cart-table tbody tr:hover {
            background: #f9fffb;
        }

        #cart-table tbody tr.row-missing-expiry {
            background: #fef2f2;
        }

        #cart-table tbody tr.row-missing-expiry:hover {
            background: #fee8e8;
        }

        .cell-input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 5px 6px;
            font-size: 12.5px;
            text-align: center;
            background: #fff;
            transition: border-color .15s;
        }

        .cell-input:focus {
            outline: none;
            border-color: #34d399;
            box-shadow: 0 0 0 2px rgba(52, 211, 153, .15);
        }

        .cell-input-name {
            text-align: right;
            font-weight: 600;
            min-width: 150px;
        }

        .cell-input-barcode {
            font-family: monospace;
            min-width: 110px;
        }

        .cell-input-money {
            min-width: 68px;
        }

        .cell-input-qty {
            min-width: 50px;
        }

        .cell-input-units {
            min-width: 44px;
        }

        .cell-select {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 5px 4px;
            font-size: 11.5px;
            background: #fff;
            min-width: 110px;
        }

        .cell-expiry-missing {
            border-color: #fca5a5 !important;
            background: #fff5f5;
        }

        .qty-stepper {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .qty-stepper button {
            width: 20px;
            height: 26px;
            flex-shrink: 0;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .row-remove-btn {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            color: #f87171;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, color .15s;
        }

        .row-remove-btn:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        .units-badge {
            font-size: 10.5px;
            color: #7c3aed;
            background: #f5f3ff;
            border-radius: 6px;
            padding: 2px 5px;
            white-space: nowrap;
        }
    </style>
@endsection

@section('content')
    {{-- ══ AD BANNER ══ --}}
    @php $activeAd = \App\Models\Ad::activeAd(); @endphp
    @if ($activeAd)
        <div class="mb-4 relative overflow-hidden rounded-2xl shadow-sm border border-indigo-100" style="max-height:140px;">
            <img src="{{ asset('storage/' . $activeAd->image_path) }}" alt="{{ $activeAd->title }}" class="w-full object-cover"
                style="max-height:140px;">
            @if ($activeAd->title)
                <div class="absolute bottom-0 right-0 left-0 bg-gradient-to-t from-black/50 to-transparent px-4 py-2">
                    <span class="text-white text-sm font-bold">{{ $activeAd->title }}</span>
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">

        <!-- ===== يسار: بحث + جدول الأصناف ===== -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">

                <!-- حقل موحد: اسم أو باركود -->
                <div class="relative">
                    <i id="search-icon"
                        class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition-all"></i>
                    <input type="text" id="unified-search" placeholder="ابحث بالاسم أو اسكان الباركود مباشرة..."
                        class="w-full border-2 border-gray-200 rounded-xl pr-10 pl-24 py-3
                               focus:outline-none focus:border-green-400 text-right text-sm transition"
                        oninput="onUnifiedInput(this.value)" onkeydown="onUnifiedKeydown(event)" autocomplete="off">
                    <span id="search-mode-badge"
                        class="absolute left-3 top-1/2 -translate-y-1/2
                               text-xs px-2 py-1 rounded-lg font-semibold
                               bg-gray-100 text-gray-400 transition-all select-none">
                        اسم / باركود
                    </span>
                </div>

                <p class="text-xs text-gray-400 text-right -mt-1">
                    <i class="fas fa-info-circle ml-1 text-green-400"></i>
                    اكتب اسم الدواء للبحث — أو اسكان الباركود مباشرة وهيُضاف تلقائياً
                </p>

                <!-- فلاتر الفئات -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterCat('')" data-cat=""
                        class="cat-btn text-xs px-3 py-1.5 rounded-full border bg-green-600 text-white border-green-600 transition">الكل</button>
                    @foreach ([
            'أدوية أطفال' => '👶',
            'أدوية السكري' => '💉',
            'أدوية ضغط' => '🩺',
            'أدوية قلب' => '❤️',
            'أدوية حساسية' => '🤧',
            'أدوية عامة' => '💊',
            'أدوية عظام ومفاصل' => '🦴',
            'أدوية نسائية' => '🌸',
            'أدوية نفسية وعصبية' => '🧠',
            'أدوية الفياجرا' => '💙',
            'مسكنات' => '🩹',
            'مضادات حيوية' => '🔬',
            'أمبولات' => '💉',
            'فيتامينات' => '🌿',
            'مستحضرات تجميل' => '💄',
            'مستلزمات طبية' => '🏥',
        ] as $cat => $icon)
                        <button onclick="filterCat('{{ $cat }}')" data-cat="{{ $cat }}"
                            class="cat-btn text-xs px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-600 hover:bg-green-50 hover:border-green-300 hover:text-green-700 transition">
                            {{ $icon }} {{ $cat }}
                        </button>
                    @endforeach
                </div>

                <!-- نتائج البحث -->
                <div id="search-results" class="mt-2 space-y-2 max-h-64 overflow-y-auto hidden"></div>

                <!-- إضافة منتج جديد يدوياً -->
                <div id="new-product-btn" class="hidden">
                    <button onclick="addNewProductManually()"
                        class="w-full border-2 border-dashed border-green-300 text-green-600 hover:bg-green-50 rounded-xl py-2.5 text-sm font-semibold transition flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i>
                        إضافة "<span id="new-product-name-preview"></span>" كمنتج جديد
                    </button>
                </div>
            </div>

            <!-- جدول الأصناف المضافة للفاتورة -->
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-700 flex items-center gap-2">
                        <i class="fas fa-table text-green-500"></i> أصناف الفاتورة
                    </h3>
                    <span id="cart-badge"
                        class="hidden bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full"></span>
                </div>

                <div id="cart-empty-state" class="text-gray-400 text-center py-8 text-sm">
                    <i class="fas fa-table text-2xl block mb-2"></i>
                    ابحث عن منتج وأضفه للفاتورة
                </div>

                <div id="cart-table-wrapper" class="hidden" style="max-height: 480px;">
                    <table id="cart-table">
                        <thead>
                            <tr>
                                <th style="width:28px;"></th>
                                <th style="text-align:right; min-width:150px;">اسم الصنف</th>
                                <th>الفئة</th>
                                <th>الباركود</th>
                                <th>سعر الصيدلي *</th>
                                <th>سعر الجمهور *</th>
                                <th>الكمية *</th>
                                <th>الصلاحية *</th>
                                <th>شريط/علبة</th>
                                <th>حبة/شريط</th>
                                <th>خصم</th>
                                <th>ضريبة %</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody id="cart-tbody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ===== يمين: بيانات الفاتورة + إجماليات ===== -->
        <div class="bg-white rounded-2xl shadow-sm p-5 flex flex-col"
            style="max-height:calc(100vh - 140px); overflow-y:auto; overflow-x:visible;">

            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-green-500"></i> فاتورة الشراء
            </h3>

            <div class="space-y-3 flex-1">

                <!-- المورد -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">المورد</label>
                    <div id="selected-supplier"
                        class="hidden mb-2 bg-green-50 border border-green-200 rounded-xl p-2.5 flex items-center justify-between">
                        <div>
                            <span id="sel-sup-name" class="text-sm font-bold text-green-700"></span>
                            <span id="sel-sup-code" class="text-xs text-green-400 mr-2"></span>
                        </div>
                        <button type="button" onclick="clearSupplier()"
                            class="text-gray-400 hover:text-red-500 text-xs p-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="relative" id="supplier-wrapper">
                        <i class="fas fa-truck absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs z-10"></i>
                        <input type="text" id="supplier-search" placeholder="ابحث عن المورد..." autocomplete="off"
                            class="w-full border border-gray-200 rounded-xl pr-8 pl-3 py-2.5 text-sm text-right focus:outline-none focus:border-green-400"
                            oninput="searchSuppliers(this.value)">
                        <div id="supplier-results"
                            class="hidden absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden"
                            style="z-index:9999; max-height:220px; overflow-y:auto;"></div>
                    </div>
                </div>

                <!-- رقم الفاتورة -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">رقم الفاتورة *</label>
                    <input type="text" id="invoice-number" placeholder="رقم فاتورة المورد"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-green-400">
                </div>

                <!-- تاريخ الفاتورة -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">تاريخ الفاتورة</label>
                    <input type="date" id="invoice-date" value="{{ now()->format('Y-m-d') }}"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-green-400">
                </div>

                <!-- الإجماليات -->
                <div class="bg-gray-50 rounded-xl p-3 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>إجمالي الأصناف</span>
                        <span id="total" class="font-bold text-gray-800">0.00 ج.م</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>إجمالي الضرائب</span>
                        <span id="total-tax" class="text-orange-500 font-semibold">0.00 ج.م</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>إجمالي خصومات الأصناف</span>
                        <span id="total-discount" class="text-red-400 font-semibold">0.00 ج.م</span>
                    </div>
                </div>

                <!-- تعديلات الفاتورة -->
                <div class="border border-orange-100 bg-orange-50 rounded-xl p-3 space-y-2">
                    <p class="text-xs font-bold text-orange-700 flex items-center gap-1.5">
                        <i class="fas fa-sliders-h"></i> تعديلات على الفاتورة
                    </p>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            <i class="fas fa-tag text-red-400 ml-1"></i>خصم على الفاتورة (ج.م)
                        </label>
                        <div class="relative">
                            <input type="number" id="invoice-discount" min="0" step="0.01"
                                oninput="calcTotals()" onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                                placeholder="0.00"
                                class="w-full border border-red-200 bg-white rounded-xl px-3 py-2 pl-8 text-sm text-right focus:outline-none focus:border-red-400">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-red-400 text-xs font-bold">−</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            <i class="fas fa-plus-circle text-blue-400 ml-1"></i>إضافة / مصاريف (ج.م)
                        </label>
                        <div class="relative">
                            <input type="number" id="invoice-extra" min="0" step="0.01"
                                oninput="calcTotals()" onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                                placeholder="0.00"
                                class="w-full border border-blue-200 bg-white rounded-xl px-3 py-2 pl-8 text-sm text-right focus:outline-none focus:border-blue-400">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-blue-400 text-xs font-bold">+</span>
                        </div>
                    </div>
                    <div id="adjustment-preview" class="hidden text-xs text-center font-semibold py-1 rounded-lg"></div>
                </div>

                <!-- الإجمالي النهائي -->
                <div class="flex justify-between text-sm font-bold text-green-700 bg-green-50 rounded-xl p-3">
                    <span>الإجمالي النهائي</span>
                    <span id="net-total">0.00 ج.م</span>
                </div>

                <!-- طريقة الدفع -->
                <div>
                    <label class="block text-xs text-gray-500 mb-2">طريقة الدفع</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([
            'cash' => ['fa-money-bill-wave', 'كاش'],
            'card' => ['fa-credit-card', 'بطاقة'],
            'transfer' => ['fa-university', 'تحويل'],
            'deferred' => ['fa-file-invoice', 'آجل'],
        ] as $val => [$icon, $label])
                            <button type="button" onclick="selectPayment('{{ $val }}')"
                                id="pay-btn-{{ $val }}"
                                class="pay-btn text-sm py-2.5 rounded-xl border-2 transition font-semibold
                                       {{ $val === 'cash' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-600' }}">
                                <i class="fas {{ $icon }} ml-1"></i> {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- تنبيه آجل -->
                <div id="deferred-info"
                    class="hidden bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600">
                    <i class="fas fa-exclamation-triangle ml-1"></i>
                    الفاتورة ستُسجّل كآجل — المبلغ سيُضاف لرصيد المورد
                </div>

                <!-- المبلغ المدفوع -->
                <div id="paid-section">
                    <label class="block text-xs text-gray-500 mb-1">المبلغ المدفوع</label>
                    <input type="number" id="paid" min="0" step="0.01" oninput="calcChange()"
                        onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-green-400">
                </div>

                <div class="flex justify-between text-sm bg-gray-50 rounded-xl p-3">
                    <span class="text-gray-600" id="change-label">المتبقي للمورد</span>
                    <span id="change" class="font-bold text-red-500">0.00 ج.م</span>
                </div>

                <!-- ملاحظات -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ملاحظات</label>
                    <textarea id="notes" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-green-400 resize-none"
                        placeholder="اختياري..."></textarea>
                </div>
            </div>

            <!-- زر الحفظ -->
            <div class="mt-4 space-y-2">
                <button onclick="savePurchase()" id="save-btn"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i> حفظ الفاتورة وتحديث المخزن
                </button>
                <button onclick="clearAll()"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-xl text-sm transition">
                    <i class="fas fa-trash"></i> مسح الكل
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"
        style="position:fixed; top:1.25rem; left:50%; transform:translateX(-50%);
               z-index:9999; display:flex; flex-direction:column; gap:10px;
               align-items:center; pointer-events:none; width:420px; max-width:95vw;">
    </div>
@endsection

@section('scripts')
    <script>
        /* =====================================================
               منع اختصارات DevTools
               ===================================================== */
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') {
                e.preventDefault();
                return;
            }
            if (e.ctrlKey && e.shiftKey && ['I', 'i', 'J', 'j', 'C', 'c', 'M', 'm'].includes(e.key)) {
                e.preventDefault();
                return;
            }
            if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) {
                e.preventDefault();
                return;
            }
        }, true);

        /* =====================================================
           مسح الصفر عند الفوكس — إعادته عند الخروج لو فاضي
           ===================================================== */
        function clearZeroInput(el) {
            if (el.value === '0' || el.value === '') el.value = '';
        }

        function restoreZeroInput(el) {
            if (el.value === '' || el.value === null) {
                el.value = '0';
                calcTotals();
            }
        }

        /* =====================================================
           STATE
           ===================================================== */
        let cart = [];
        let activeCat = '';
        let searchTimer = null;
        let supplierTimer = null;
        let selectedSupplier = null;
        let selectedPayment = 'cash';
        let lastKeyTime = 0;
        let keyIntervals = [];
        const SCANNER_THRESHOLD_MS = 50;
        const MIN_BARCODE_LEN = 4;

        const CATEGORIES = [
            'أدوية أطفال', 'أدوية السكري', 'أدوية ضغط', 'أدوية قلب', 'أدوية حساسية',
            'أدوية عامة', 'أدوية عظام ومفاصل', 'أدوية نسائية', 'أدوية نفسية وعصبية',
            'أدوية الفياجرا', 'مسكنات', 'مضادات حيوية', 'أمبولات',
            'فيتامينات', 'مستحضرات تجميل', 'مستلزمات طبية',
        ];

        /* =====================================================
           TOAST
           ===================================================== */
        const toastConfig = {
            success: {
                icon: 'fa-check-circle',
                border: '#1D9E75',
                iconBg: '#E1F5EE',
                iconColor: '#0F6E56',
                barColor: '#1D9E75'
            },
            error: {
                icon: 'fa-exclamation-circle',
                border: '#E24B4A',
                iconBg: '#FCEBEB',
                iconColor: '#A32D2D',
                barColor: '#E24B4A'
            },
            warning: {
                icon: 'fa-exclamation-triangle',
                border: '#BA7517',
                iconBg: '#FAEEDA',
                iconColor: '#854F0B',
                barColor: '#BA7517'
            },
        };

        function showToast(type, title, msg) {
            const cfg = toastConfig[type] || toastConfig.success;
            const toast = document.createElement('div');
            toast.setAttribute('data-toast', '');
            toast.style.cssText = `display:flex;align-items:flex-start;gap:12px;pointer-events:all;
        background:#fff;border:0.5px solid #e0e0e0;border-right:3px solid ${cfg.border};
        border-radius:14px;padding:14px 16px;box-shadow:0 8px 32px rgba(0,0,0,0.12);
        width:100%;position:relative;overflow:hidden;direction:rtl;
        animation:toastIn .38s cubic-bezier(.21,1.02,.73,1) forwards;`;
            toast.innerHTML = `
        <div style="width:38px;height:38px;border-radius:50%;background:${cfg.iconBg};color:${cfg.iconColor};
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:16px;">
            <i class="fas ${cfg.icon}"></i></div>
        <div style="flex:1;min-width:0;padding-top:1px;">
            <p style="margin:0 0 3px;font-size:14px;font-weight:700;color:#1a1a1a;">${title}</p>
            <p style="margin:0;font-size:13px;color:#666;">${msg}</p></div>
        <button onclick="dismissToast(this)"
                style="background:none;border:none;cursor:pointer;font-size:13px;color:#aaa;padding:3px 5px;flex-shrink:0;">
            <i class="fas fa-times"></i></button>
        <div style="position:absolute;bottom:0;right:0;left:0;height:2.5px;background:${cfg.barColor};
                    animation:toastProgress 4.5s linear forwards;transform-origin:right;"></div>`;
            document.getElementById('toast-container').prepend(toast);
            toast._timer = setTimeout(() => dismissToast(toast.querySelector('button')), 4500);
        }

        function dismissToast(btn) {
            const toast = btn.closest('[data-toast]');
            if (!toast || toast._dismissed) return;
            toast._dismissed = true;
            clearTimeout(toast._timer);
            toast.style.animation = 'toastOut .32s ease forwards';
            setTimeout(() => toast.remove(), 320);
        }

        /* =====================================================
           UNIFIED INPUT
           ===================================================== */
        function onUnifiedKeydown(e) {
            const now = Date.now();
            if (lastKeyTime > 0) {
                keyIntervals.push(now - lastKeyTime);
                if (keyIntervals.length > 10) keyIntervals.shift();
            }
            lastKeyTime = now;
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = e.target.value.trim();
                if (!val) return;
                if (isLikelyScanner(val)) handleBarcodeInput(val);
                else {
                    const first = document.querySelector('#search-results [onclick]');
                    if (first) first.click();
                }
            }
        }

        function onUnifiedInput(val) {
            clearTimeout(searchTimer);
            const newBtn = document.getElementById('new-product-btn');
            if (!val) {
                document.getElementById('search-results').classList.add('hidden');
                newBtn.classList.add('hidden');
                setBadge('اسم / باركود', 'bg-gray-100 text-gray-400', 'fa-search', 'text-gray-400');
                keyIntervals = [];
                return;
            }
            const looksLikeBarcode = /^[0-9\-]+$/.test(val) && val.length >= MIN_BARCODE_LEN;
            if (looksLikeBarcode) {
                setBadge('باركود 🔍', 'bg-green-100 text-green-700', 'fa-barcode', 'text-green-500');
                const input = document.getElementById('unified-search');
                input.classList.add('scanner-active');
                setTimeout(() => input.classList.remove('scanner-active'), 800);
            } else {
                setBadge('بحث بالاسم', 'bg-indigo-50 text-indigo-600', 'fa-search', 'text-indigo-400');
            }
            if (val.length >= 2 || activeCat !== '') searchTimer = setTimeout(() => doSearch(val), 280);
            if (val.length >= 2) {
                document.getElementById('new-product-name-preview').textContent = val;
                newBtn.classList.remove('hidden');
            } else {
                newBtn.classList.add('hidden');
            }
        }

        function setBadge(text, classes, iconClass, iconColor) {
            const badge = document.getElementById('search-mode-badge');
            const icon = document.getElementById('search-icon');
            badge.textContent = text;
            badge.className =
                `absolute left-3 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded-lg font-semibold transition-all select-none ${classes}`;
            icon.className = `fas ${iconClass} absolute right-4 top-1/2 -translate-y-1/2 transition-all ${iconColor}`;
        }

        function isLikelyScanner(val) {
            if (keyIntervals.length < 3) return false;
            const avg = keyIntervals.reduce((a, b) => a + b, 0) / keyIntervals.length;
            return avg < SCANNER_THRESHOLD_MS && val.length >= MIN_BARCODE_LEN;
        }

        /* =====================================================
           BARCODE HANDLER
           ===================================================== */
        async function handleBarcodeInput(code) {
            setBadge('جاري البحث...', 'bg-yellow-100 text-yellow-700', 'fa-spinner fa-spin', 'text-yellow-500');
            try {
                const products = await (await fetch(`/products-search?barcode=${encodeURIComponent(code)}`)).json();
                if (products.length === 1) {
                    addToCart(products[0]);
                    showToast('success', '✅ تم إضافة المنتج', products[0].name);
                    clearSearch();
                } else if (products.length > 1) {
                    renderResults(products, code);
                    showToast('warning', 'أكثر من منتج', 'اختر المنتج المناسب من القائمة');
                } else {
                    showToast('warning', 'الباركود غير موجود', `يمكنك إضافة "${code}" كمنتج جديد`);
                    document.getElementById('new-product-name-preview').textContent = code;
                    document.getElementById('new-product-btn').classList.remove('hidden');
                    setBadge('غير موجود', 'bg-red-100 text-red-500', 'fa-times', 'text-red-400');
                }
            } catch (e) {
                showToast('error', 'خطأ في البحث', 'تحقق من الاتصال');
                setBadge('خطأ', 'bg-red-100 text-red-500', 'fa-exclamation', 'text-red-400');
            }
        }

        /* =====================================================
           CATEGORY FILTER
           ===================================================== */
        function filterCat(cat) {
            activeCat = cat;
            document.querySelectorAll('.cat-btn').forEach(btn => {
                const active = btn.dataset.cat === cat;
                btn.className = active ?
                    'cat-btn text-xs px-3 py-1.5 rounded-full border bg-green-600 text-white border-green-600 transition' :
                    'cat-btn text-xs px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-600 hover:bg-green-50 hover:border-green-300 hover:text-green-700 transition';
            });
            const q = document.getElementById('unified-search').value.trim();
            if (q.length >= 2 || cat !== '') doSearch(q);
            else document.getElementById('search-results').classList.add('hidden');
        }

        /* =====================================================
           PRODUCT SEARCH
           ===================================================== */
        async function doSearch(q) {
            try {
                const p = new URLSearchParams();
                if (q) p.append('q', q);
                if (activeCat) p.append('category', activeCat);
                const products = await (await fetch(`/products-search?${p}`)).json();
                renderResults(products, q);
            } catch (e) {
                console.error(e);
            }
        }

        function renderResults(products, q) {
            const c = document.getElementById('search-results');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const soon = new Date(today);
            soon.setDate(soon.getDate() + 30);
            if (!products.length) {
                c.innerHTML = '<p class="text-gray-400 text-sm text-center py-3">لا توجد نتائج في المخزن</p>';
                c.classList.remove('hidden');
                return;
            }
            c.innerHTML = products.map(p => {
                let badge = '';
                if (p.expiry_date) {
                    const exp = new Date(p.expiry_date);
                    exp.setHours(0, 0, 0, 0);
                    if (exp < today) badge =
                        `<span class="text-xs text-white bg-red-500 px-2 py-0.5 rounded-full">منتهي</span>`;
                    else if (exp <= soon) badge =
                        `<span class="text-xs text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">ينتهي قريباً</span>`;
                }
                const qtyClass = p.quantity === 0 ? 'text-red-500' : p.quantity <= 5 ? 'text-orange-500' :
                    'text-gray-400';
                const bcBadge = p.barcode ?
                    `<span class="font-mono text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><i class="fas fa-barcode ml-1"></i>${p.barcode}</span>` :
                    '';
                return `
                <div onclick='addToCart(${JSON.stringify(p).replace(/'/g,"&#39;")})'
                     class="flex items-center justify-between p-3 rounded-xl border border-transparent bg-gray-50 hover:bg-green-50 hover:border-green-200 cursor-pointer transition">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm text-gray-800">${p.name}</div>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            ${p.category?`<span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full">${p.category}</span>`:''}
                            <span class="text-xs ${qtyClass}"><i class="fas fa-boxes ml-1"></i>${p.quantity} وحدة</span>
                            ${bcBadge} ${badge}
                        </div>
                    </div>
                    <div class="text-right mr-3 flex-shrink-0">
                        <div class="text-green-700 font-bold text-sm">${parseFloat(p.cost_price??0).toFixed(2)} ج.م</div>
                        <div class="text-gray-300 text-xs"><i class="fas fa-plus-circle"></i> إضافة</div>
                    </div>
                </div>`;
            }).join('');
            c.classList.remove('hidden');
        }

        /* =====================================================
           CART MANAGEMENT
           ===================================================== */
        function addToCart(product) {
            const ex = cart.find(i => i._id === (product.id ?? product._id));
            if (ex) {
                ex.qty++;
                renderCart();
                clearSearch();
                return;
            }
            cart.push({
                _id: product.id ?? `new_${Date.now()}`,
                product_id: product.id ?? null,
                name: product.name,
                category: product.category ?? '',
                barcode: product.barcode ?? '',
                cost_price: parseFloat(product.cost_price ?? product.price ?? 0),
                sale_price: parseFloat(product.price ?? 0),
                qty: 1,
                tax: 0,
                discount: 0,
                expiry: product.expiry_date ?? '',
                major_units: parseInt(product.major_units ?? 1),
                minor_units: parseInt(product.minor_units ?? 1),
            });
            renderCart();
            clearSearch();
        }

        function clearSearch() {
            document.getElementById('unified-search').value = '';
            document.getElementById('search-results').classList.add('hidden');
            document.getElementById('new-product-btn').classList.add('hidden');
            keyIntervals = [];
            setBadge('اسم / باركود', 'bg-gray-100 text-gray-400', 'fa-search', 'text-gray-400');
            setTimeout(() => document.getElementById('unified-search')?.focus(), 100);
        }

        function addNewProductManually() {
            const name = document.getElementById('unified-search').value.trim();
            if (!name) return;
            addToCart({
                id: null,
                name,
                category: '',
                barcode: '',
                cost_price: 0,
                price: 0,
                expiry_date: '',
                major_units: 1,
                minor_units: 1
            });
        }

        function removeFromCart(idx) {
            cart.splice(idx, 1);
            renderCart();
        }

        function numInputVal(val) {
            return val == 0 ? '' : val;
        }

        /* =====================================================
           RENDER CART AS TABLE (Excel-like)
           ===================================================== */
        function renderCart() {
            const emptyState = document.getElementById('cart-empty-state');
            const tableWrapper = document.getElementById('cart-table-wrapper');
            const tbody = document.getElementById('cart-tbody');
            const b = document.getElementById('cart-badge');

            if (!cart.length) {
                emptyState.classList.remove('hidden');
                tableWrapper.classList.add('hidden');
                b.classList.add('hidden');
                calcTotals();
                return;
            }

            emptyState.classList.add('hidden');
            tableWrapper.classList.remove('hidden');
            b.textContent = cart.length + ' صنف';
            b.classList.remove('hidden');

            tbody.innerHTML = cart.map((item, idx) => {
                const missingExpiry = !item.expiry;
                const unitsPerBox = (item.major_units ?? 1) * (item.minor_units ?? 1);
                return `
                <tr class="${missingExpiry ? 'row-missing-expiry' : ''}">
                    <td>
                        <button type="button" onclick="removeFromCart(${idx})" class="row-remove-btn" title="حذف الصنف">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </td>
                    <td>
                        <input type="text" value="${(item.name ?? '').replace(/"/g, '&quot;')}"
                               onchange="updateCart(${idx},'name',this.value)"
                               class="cell-input cell-input-name" title="${item.name ?? ''}">
                    </td>
                    <td>
                        <select onchange="updateCart(${idx},'category',this.value)" class="cell-select">
                            <option value="" ${!item.category ? 'selected' : ''}>— بدون —</option>
                            ${CATEGORIES.map(cat => `<option value="${cat}" ${item.category===cat?'selected':''}>${cat}</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="text" value="${item.barcode ?? ''}" inputmode="numeric"
                               placeholder="اختياري"
                               onchange="updateCart(${idx},'barcode',this.value)"
                               class="cell-input cell-input-barcode">
                    </td>
                    <td>
                        <input type="number" value="${numInputVal(item.cost_price)}" min="0" step="0.01" placeholder="0.00"
                               onchange="updateCart(${idx},'cost_price',this.value)"
                               onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                               class="cell-input cell-input-money">
                    </td>
                    <td>
                        <input type="number" value="${numInputVal(item.sale_price)}" min="0" step="0.01" placeholder="0.00"
                               onchange="updateCart(${idx},'sale_price',this.value)"
                               onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                               class="cell-input cell-input-money">
                    </td>
                    <td>
                        <div class="qty-stepper">
                            <button type="button" onclick="changeQty(${idx},-1)" class="bg-gray-200 hover:bg-gray-300">−</button>
                            <input type="number" value="${item.qty}" min="1" step="1"
                                   onchange="updateCart(${idx},'qty',this.value)"
                                   onfocus="clearZeroInput(this)"
                                   class="cell-input cell-input-qty">
                            <button type="button" onclick="changeQty(${idx},1)" class="bg-green-100 hover:bg-green-200 text-green-600">+</button>
                        </div>
                    </td>
                    <td>
                        <input type="month" value="${item.expiry ? item.expiry.substring(0,7) : ''}"
                               onchange="updateCart(${idx},'expiry', this.value ? this.value + '-01' : '')"
                               class="cell-input ${missingExpiry ? 'cell-expiry-missing' : ''}" style="min-width:108px;">
                    </td>
                    <td>
                        <input type="number" value="${item.major_units ?? 1}" min="1" step="1"
                               onchange="updateCart(${idx},'major_units',this.value)"
                               onfocus="clearZeroInput(this)"
                               class="cell-input cell-input-units">
                    </td>
                    <td>
                        <input type="number" value="${item.minor_units ?? 1}" min="1" step="1"
                               onchange="updateCart(${idx},'minor_units',this.value)"
                               onfocus="clearZeroInput(this)"
                               class="cell-input cell-input-units">
                    </td>
                    <td>
                        <input type="number" value="${numInputVal(item.discount)}" min="0" step="0.01" placeholder="0.00"
                               onchange="updateCart(${idx},'discount',this.value)"
                               onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                               class="cell-input cell-input-money">
                    </td>
                    <td>
                        <input type="number" value="${numInputVal(item.tax)}" min="0" max="100" step="0.1" placeholder="0"
                               onchange="updateCart(${idx},'tax',this.value)"
                               onfocus="clearZeroInput(this)" onblur="restoreZeroInput(this)"
                               class="cell-input cell-input-money">
                    </td>
                    <td>
                        <div class="font-bold text-green-700 text-center" style="min-width:75px;">
                            ${calcItemSubtotal(item).toFixed(2)}
                        </div>
                        <div class="units-badge text-center">${unitsPerBox} حبة/علبة</div>
                    </td>
                </tr>`;
            }).join('');

            calcTotals();
        }

        function calcItemSubtotal(item) {
            const base = parseFloat(item.cost_price) * parseInt(item.qty);
            return (base - parseFloat(item.discount)) * (1 + parseFloat(item.tax) / 100);
        }

        function updateCart(idx, field, value) {
            if (['qty', 'major_units', 'minor_units'].includes(field)) {
                const n = parseInt(value);
                if (field === 'qty' && n < 1) {
                    removeFromCart(idx);
                    return;
                }
                cart[idx][field] = Math.max(1, n || 1);
            } else if (['cost_price', 'sale_price', 'discount', 'tax'].includes(field)) {
                cart[idx][field] = parseFloat(value) || 0;
            } else {
                cart[idx][field] = value;
            }
            renderCart();
        }

        function changeQty(idx, delta) {
            const nq = cart[idx].qty + delta;
            if (nq < 1) {
                removeFromCart(idx);
                return;
            }
            cart[idx].qty = nq;
            renderCart();
        }

        /* =====================================================
           TOTALS
           ===================================================== */
        function calcTotals() {
            let subtotal = 0,
                totalTax = 0,
                totalDiscount = 0;
            cart.forEach(item => {
                const base = parseFloat(item.cost_price) * parseInt(item.qty);
                const disc = parseFloat(item.discount) || 0;
                const rate = parseFloat(item.tax) || 0;
                totalDiscount += disc;
                totalTax += (base - disc) * (rate / 100);
                subtotal += (base - disc) * (1 + rate / 100);
            });
            const invDiscount = parseFloat(document.getElementById('invoice-discount').value) || 0;
            const invExtra = parseFloat(document.getElementById('invoice-extra').value) || 0;
            const netTotal = subtotal - invDiscount + invExtra;
            document.getElementById('total').textContent = subtotal.toFixed(2) + ' ج.م';
            document.getElementById('total-tax').textContent = totalTax.toFixed(2) + ' ج.م';
            document.getElementById('total-discount').textContent = totalDiscount.toFixed(2) + ' ج.م';
            document.getElementById('net-total').textContent = netTotal.toFixed(2) + ' ج.م';
            const preview = document.getElementById('adjustment-preview');
            const diff = invExtra - invDiscount;
            if (invDiscount > 0 || invExtra > 0) {
                preview.classList.remove('hidden');
                if (diff < 0) {
                    preview.textContent = `✂️ وفّرت ${Math.abs(diff).toFixed(2)} ج.م`;
                    preview.style.cssText = 'color:#b45309;background:#fef3c7;';
                } else if (diff > 0) {
                    preview.textContent = `➕ أُضيف ${diff.toFixed(2)} ج.م مصاريف`;
                    preview.style.cssText = 'color:#1d4ed8;background:#dbeafe;';
                } else {
                    preview.textContent = 'الخصم والإضافة يُلغيان بعضهما';
                    preview.style.cssText = 'color:#6b7280;background:#f3f4f6;';
                }
                preview.style.cssText +=
                    'display:block;font-size:12px;font-weight:600;text-align:center;padding:4px 8px;border-radius:8px;';
            } else {
                preview.classList.add('hidden');
            }
            calcChange();
        }

        function calcChange() {
            const net = parseFloat(document.getElementById('net-total').textContent) || 0;
            const paid = parseFloat(document.getElementById('paid').value) || 0;
            const diff = net - paid;
            const el = document.getElementById('change');
            const lbl = document.getElementById('change-label');
            if (selectedPayment === 'deferred') {
                el.textContent = net.toFixed(2) + ' ج.م';
                el.className = 'font-bold text-red-500';
                lbl.textContent = 'المبلغ الآجل للمورد';
            } else {
                el.textContent = diff.toFixed(2) + ' ج.م';
                el.className = `font-bold ${diff<=0?'text-green-600':'text-red-500'}`;
                lbl.textContent = diff <= 0 ? 'مدفوع بالكامل ✅' : 'متبقي للمورد';
            }
        }

        /* =====================================================
           PAYMENT METHOD
           ===================================================== */
        const payColors = {
            cash: 'border-green-500 bg-green-50 text-green-700',
            card: 'border-blue-500 bg-blue-50 text-blue-700',
            transfer: 'border-purple-500 bg-purple-50 text-purple-700',
            deferred: 'border-red-500 bg-red-50 text-red-700',
        };

        function selectPayment(method) {
            selectedPayment = method;
            document.querySelectorAll('.pay-btn').forEach(btn => {
                const val = btn.id.replace('pay-btn-', '');
                btn.className =
                    `pay-btn text-sm py-2.5 rounded-xl border-2 transition font-semibold ${val===method?payColors[method]:'border-gray-200 bg-gray-50 text-gray-600'}`;
            });
            document.getElementById('deferred-info').classList.toggle('hidden', method !== 'deferred');
            if (method === 'deferred') document.getElementById('paid').value = '';
            calcChange();
        }

        /* =====================================================
           SUPPLIER SEARCH
           ===================================================== */
        function searchSuppliers(q) {
            clearTimeout(supplierTimer);
            const rb = document.getElementById('supplier-results');
            if (q.length < 1) {
                rb.classList.add('hidden');
                return;
            }
            supplierTimer = setTimeout(async () => {
                try {
                    const suppliers = await (await fetch(`/suppliers/search?q=${encodeURIComponent(q)}`))
                .json();
                    if (!suppliers.length) {
                        rb.innerHTML =
                            `<div class="p-3 text-center text-gray-400 text-sm">لا يوجد موردين —
                            <a href="/suppliers/create" target="_blank" class="text-green-600 hover:underline">إضافة مورد</a></div>`;
                    } else {
                        rb.innerHTML = suppliers.map(s => `
                            <div onclick='pickSupplier(${JSON.stringify(s).replace(/'/g,"&#39;")})'
                                 class="flex items-center justify-between p-3 hover:bg-green-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-sm text-gray-800">${s.name}</span>
                                        <span class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">${s.code}</span>
                                    </div>
                                    ${s.company?`<div class="text-xs text-gray-400">${s.company}</div>`:''}
                                </div>
                                ${s.balance>0?`<span class="text-xs font-bold text-red-500">${parseFloat(s.balance).toFixed(2)} ج.م مستحق</span>`:`<span class="text-xs text-green-500">✅ مسدد</span>`}
                            </div>`).join('');
                    }
                    rb.classList.remove('hidden');
                } catch (e) {
                    console.error(e);
                }
            }, 300);
        }

        function pickSupplier(s) {
            selectedSupplier = s;
            document.getElementById('supplier-results').classList.add('hidden');
            document.getElementById('supplier-search').value = '';
            document.getElementById('selected-supplier').classList.remove('hidden');
            document.getElementById('sel-sup-name').textContent = s.name;
            document.getElementById('sel-sup-code').textContent = s.code;
        }

        function clearSupplier() {
            selectedSupplier = null;
            document.getElementById('selected-supplier').classList.add('hidden');
            document.getElementById('supplier-search').value = '';
        }

        document.addEventListener('click', e => {
            if (!document.getElementById('supplier-wrapper')?.contains(e.target))
                document.getElementById('supplier-results')?.classList.add('hidden');
        });

        /* =====================================================
           SAVE PURCHASE
           ===================================================== */
        async function savePurchase() {
            const invoiceNum = document.getElementById('invoice-number').value.trim();
            if (!cart.length) {
                showToast('error', 'الفاتورة فارغة', 'أضف صنفاً واحداً على الأقل');
                return;
            }
            if (!invoiceNum) {
                showToast('error', 'رقم الفاتورة مطلوب', 'ادخل رقم فاتورة المورد');
                return;
            }

            for (let i = 0; i < cart.length; i++) {
                const item = cart[i];
                if (!item.cost_price || item.cost_price <= 0) {
                    showToast('error', 'سعر مطلوب', `ادخل سعر الصيدلي للصنف: ${item.name}`);
                    return;
                }
                if (!item.sale_price || item.sale_price <= 0) {
                    showToast('error', 'سعر مطلوب', `ادخل سعر الجمهور للصنف: ${item.name}`);
                    return;
                }
                // ── تاريخ الصلاحية إلزامي ──
                if (!item.expiry) {
                    showToast('error', 'تاريخ الصلاحية مطلوب', `ادخل تاريخ صلاحية الصنف: ${item.name}`);
                    return;
                }
            }

            /* ── التحقق من المبلغ المدفوع ── */
            if (selectedPayment !== 'deferred') {
                const paidVal = document.getElementById('paid').value.trim();
                const paid = parseFloat(paidVal);
                if (paidVal === '' || isNaN(paid)) {
                    showToast('error', 'المبلغ المدفوع مطلوب', 'ادخل المبلغ المدفوع أولاً');
                    document.getElementById('paid').focus();
                    return;
                }
                if (paid < 0) {
                    showToast('error', 'مبلغ غير صحيح', 'المبلغ المدفوع لا يمكن أن يكون سالباً');
                    document.getElementById('paid').focus();
                    return;
                }
            }

            const invDiscount = parseFloat(document.getElementById('invoice-discount').value) || 0;
            const invExtra = parseFloat(document.getElementById('invoice-extra').value) || 0;
            let subtotal = 0;
            cart.forEach(item => {
                const base = parseFloat(item.cost_price) * parseInt(item.qty);
                const disc = parseFloat(item.discount) || 0;
                const rate = parseFloat(item.tax) || 0;
                subtotal += (base - disc) * (1 + rate / 100);
            });
            const netTotal = subtotal - invDiscount + invExtra;
            if (netTotal < 0) {
                showToast('warning', 'خصم كبير', 'خصم الفاتورة أكبر من الإجمالي');
                return;
            }

            const btn = document.getElementById('save-btn');
            const token = document.querySelector('meta[name=csrf-token]').content;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> جاري الحفظ...';

            const payload = {
                supplier_id: selectedSupplier?.id ?? null,
                invoice_number: invoiceNum,
                invoice_date: document.getElementById('invoice-date').value,
                payment_method: selectedPayment,
                paid: selectedPayment === 'deferred' ? 0 : (parseFloat(document.getElementById('paid').value) || 0),
                invoice_discount: invDiscount,
                invoice_extra: invExtra,
                notes: document.getElementById('notes').value,
                items: cart.map(item => ({
                    product_name: item.name,
                    category: item.category,
                    barcode: item.barcode || null,
                    purchase_price: item.cost_price,
                    selling_price: item.sale_price,
                    quantity: item.qty,
                    expiry_date: item.expiry || null,
                    discount: item.discount,
                    tax: item.tax,
                    major_units: item.major_units ?? 1,
                    minor_units: item.minor_units ?? 1,
                })),
            };

            try {
                const res = await fetch('/purchases', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });
                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await res.text();
                    console.error('Non-JSON:', text.substring(0, 500));
                    showToast('error', 'خطأ في السيرفر', `كود الخطأ: ${res.status}`);
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    showToast('success', '✅ تم حفظ الفاتورة!', 'تم تحديث المخزن بنجاح');
                    setTimeout(() => window.location.href = data.redirect, 1200);
                } else {
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' | ') :
                        'تحقق من البيانات');
                    showToast('error', 'حدث خطأ', errMsg);
                }
            } catch (e) {
                console.error('Fetch error:', e);
                showToast('error', 'خطأ في الاتصال', 'تأكد من الاتصال بالإنترنت');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> حفظ الفاتورة وتحديث المخزن';
            }
        }

        /* =====================================================
           CLEAR ALL
           ===================================================== */
        function clearAll() {
            cart = [];
            renderCart();
            clearSupplier();
            document.getElementById('invoice-number').value = '';
            document.getElementById('invoice-date').value = '{{ now()->format('Y-m-d') }}';
            document.getElementById('paid').value = '';
            document.getElementById('invoice-discount').value = '';
            document.getElementById('invoice-extra').value = '';
            document.getElementById('notes').value = '';
            document.getElementById('adjustment-preview').classList.add('hidden');
            clearSearch();
            selectPayment('cash');
        }

        /* =====================================================
           PRELOAD SUPPLIER
           ===================================================== */
        const preloadSupplierId = "{{ request('supplier_id') }}";
        if (preloadSupplierId) {
            fetch(`/suppliers/search?q=${preloadSupplierId}`)
                .then(r => r.json())
                .then(suppliers => {
                    const s = suppliers.find(x => x.id == preloadSupplierId);
                    if (s) pickSupplier(s);
                }).catch(() => {});
        }

        /* تفريغ الحقول من الصفر عند أول تحميل */
        document.addEventListener('DOMContentLoaded', () => {
            ['paid', 'invoice-discount', 'invoice-extra'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        });
    </script>
@endsection