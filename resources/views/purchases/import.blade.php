@extends('layouts.app')
@section('title', 'استيراد فاتورة شراء من صورة')

@section('content')
    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-camera text-indigo-500 ml-1"></i> استيراد فاتورة شراء من صورة
                </h2>
            </div>
            <a href="{{ route('purchases.index') }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm flex items-center gap-2 transition">
                <i class="fas fa-arrow-right"></i> رجوع للفواتير
            </a>
        </div>

        {{-- ========== خطوة 1: الرفع ========== --}}
        <div id="upload-step" class="bg-white rounded-2xl shadow-sm p-6">
            <div class="grid md:grid-cols-2 gap-6 items-start">
                <div>
                    <label id="drop-zone"
                        class="flex flex-col items-center justify-center border-2 border-dashed border-indigo-200 rounded-2xl p-8 cursor-pointer hover:bg-indigo-50 transition text-center">
                        <i class="fas fa-cloud-upload-alt text-4xl text-indigo-400 mb-3"></i>
                        <span class="text-sm font-semibold text-gray-700">اسحب صورة الفاتورة هنا أو اضغط للاختيار</span>
                        <span class="text-xs text-gray-400 mt-1">JPG / PNG / WEBP — حتى 8 ميجا</span>
                        <input type="file" id="image-input" accept="image/*" class="hidden">
                    </label>
                </div>
                <div>
                    <div id="preview-wrap" class="hidden">
                        <p class="text-xs text-gray-500 mb-2">معاينة الصورة:</p>
                        <img id="preview-img" class="max-h-72 rounded-xl border border-gray-200 mx-auto" />
                    </div>
                    <button id="extract-btn" onclick="extractInvoice()" disabled
                        class="mt-4 w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-magic"></i> اقرأ الفاتورة بالذكاء الاصطناعي
                    </button>
                    <p class="text-xs text-gray-400 mt-2 text-center">قد تستغرق القراءة 10–30 ثانية حسب حجم الفاتورة.</p>
                </div>
            </div>
        </div>

        {{-- ========== خطوة 2: المراجعة ========== --}}
        <div id="review-step" class="hidden grid lg:grid-cols-3 gap-6 items-start">

            {{-- جدول الأصناف --}}
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list text-indigo-500"></i> الأصناف المستخرجة
                        <span id="items-count" class="bg-indigo-100 text-indigo-700 text-xs px-2 py-0.5 rounded-full"></span>
                    </h3>
                    <button onclick="addRow()" class="text-xs text-indigo-600 hover:underline">
                        <i class="fas fa-plus"></i> إضافة صنف
                    </button>
                </div>
                <div class="flex flex-wrap gap-3 text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-lg p-2 mb-3">
                    <span><i class="fas fa-link text-green-500 ml-1"></i> مربوط بصنف في السيستم</span>
                    <span><i class="fas fa-plus-circle text-indigo-500 ml-1"></i> سيُضاف كصنف جديد</span>
                    <span><i class="fas fa-lightbulb text-amber-500 ml-1"></i> اقتراح يحتاج تأكيد</span>
                    <span><i class="fas fa-calendar-times text-red-500 ml-1"></i> ناقص صلاحية</span>
                </div>
                <p class="text-xs text-gray-400 mb-3">
                    سعر الشراء يُحسب تلقائياً = سعر البيع × (1 − الخصم٪) ويمكنك تعديله. الإجماليات تُحسب من القيم المراجَعة.
                </p>
                <div id="items-body" class="space-y-3"></div>
            </div>

            {{-- بيانات الفاتورة --}}
            <div class="bg-white rounded-2xl shadow-sm p-4 space-y-3">
                <h3 class="font-bold text-gray-800 flex items-center gap-2 mb-1">
                    <i class="fas fa-file-invoice-dollar text-green-500"></i> بيانات الفاتورة
                </h3>

                {{-- المورد --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">المورد</label>
                    <div id="ai-supplier-hint" class="hidden text-xs text-indigo-500 mb-1"></div>
                    <div id="selected-supplier"
                        class="hidden mb-2 bg-green-50 border border-green-200 rounded-xl p-2.5 flex items-center justify-between">
                        <div>
                            <span id="sel-sup-name" class="text-sm font-bold text-green-700"></span>
                            <span id="sel-sup-code" class="text-xs text-green-400 mr-2"></span>
                        </div>
                        <button type="button" onclick="clearSupplier()" class="text-gray-400 hover:text-red-500 text-xs p-1">
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

                {{-- رقم الفاتورة --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">رقم الفاتورة *</label>
                    <input type="text" id="invoice-number" placeholder="رقم فاتورة المورد"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-green-400">
                </div>

                {{-- تاريخ الفاتورة --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">تاريخ الفاتورة</label>
                    <input type="date" id="invoice-date" value="{{ now()->format('Y-m-d') }}"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-green-400">
                </div>

                {{-- الإجمالي --}}
                <div class="flex justify-between text-sm font-bold text-green-700 bg-green-50 rounded-xl p-3">
                    <span>إجمالي الشراء (المحسوب)</span>
                    <span id="net-total">0.00 ج.م</span>
                </div>
                {{-- مقارنة بالإجمالي المطبوع في الفاتورة --}}
                <div id="total-reconcile" class="hidden text-xs rounded-lg p-2 leading-relaxed"></div>

                {{-- طريقة الدفع --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-2">طريقة الدفع</label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([
            'cash' => ['fa-money-bill-wave', 'كاش'],
            'card' => ['fa-credit-card', 'بطاقة'],
            'transfer' => ['fa-university', 'تحويل'],
            'deferred' => ['fa-file-invoice', 'آجل'],
        ] as $val => [$icon, $label])
                            <button type="button" onclick="selectPayment('{{ $val }}')" id="pay-btn-{{ $val }}"
                                class="pay-btn text-sm py-2.5 rounded-xl border-2 transition font-semibold
                                       {{ $val === 'cash' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 bg-gray-50 text-gray-600' }}">
                                <i class="fas {{ $icon }} ml-1"></i> {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- المبلغ المدفوع --}}
                <div id="paid-section">
                    <label class="block text-xs text-gray-500 mb-1">المبلغ المدفوع</label>
                    <input type="number" id="paid" min="0" step="0.01"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-green-400">
                </div>

                {{-- ملاحظات --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ملاحظات</label>
                    <textarea id="notes" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-green-400 resize-none"
                        placeholder="اختياري..."></textarea>
                </div>

                <div class="space-y-2 pt-1">
                    <button onclick="savePurchase()" id="save-btn"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> تأكيد وحفظ الفاتورة
                    </button>
                    <button onclick="resetImport()"
                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-xl text-sm transition">
                        <i class="fas fa-redo"></i> استيراد صورة أخرى
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast-container"
        style="position:fixed; top:1.25rem; left:50%; transform:translateX(-50%); z-index:9999;
               display:flex; flex-direction:column; gap:10px; align-items:center; pointer-events:none; width:420px; max-width:95vw;">
    </div>
@endsection

@section('scripts')
    <script>
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        let items = [];
        let selectedSupplier = null;
        let selectedPayment = 'cash';
        let supplierTimer = null;
        let pickedFile = null;
        let printedTotal = null; // إجمالي الفاتورة المطبوع (من الصورة) للمقارنة

        /* ============ Toast بسيط ============ */
        function showToast(type, title, msg) {
            const colors = { success: '#16a34a', error: '#dc2626', warning: '#d97706', info: '#4f46e5' };
            const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle', info: 'fa-info-circle' };
            const c = colors[type] || colors.info;
            const t = document.createElement('div');
            t.style.cssText = `display:flex;align-items:flex-start;gap:12px;pointer-events:all;background:#fff;
                border-right:3px solid ${c};border-radius:14px;padding:14px 16px;box-shadow:0 8px 32px rgba(0,0,0,.12);
                width:100%;direction:rtl;`;
            t.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${c};font-size:18px;margin-top:2px;"></i>
                <div style="flex:1;"><p style="margin:0 0 3px;font-size:14px;font-weight:700;">${title}</p>
                <p style="margin:0;font-size:13px;color:#666;">${msg}</p></div>`;
            document.getElementById('toast-container').prepend(t);
            setTimeout(() => t.remove(), 4500);
        }

        /* ============ رفع الصورة ============ */
        const input = document.getElementById('image-input');
        const dropZone = document.getElementById('drop-zone');

        input.addEventListener('change', e => handleFile(e.target.files[0]));
        ['dragover', 'dragenter'].forEach(ev => dropZone.addEventListener(ev, e => {
            e.preventDefault(); dropZone.classList.add('bg-indigo-50');
        }));
        ['dragleave', 'drop'].forEach(ev => dropZone.addEventListener(ev, e => {
            e.preventDefault(); dropZone.classList.remove('bg-indigo-50');
        }));
        dropZone.addEventListener('drop', e => handleFile(e.dataTransfer.files[0]));

        function handleFile(file) {
            if (!file || !file.type.startsWith('image/')) {
                showToast('error', 'ملف غير صالح', 'اختر صورة صحيحة');
                return;
            }
            if (file.size > 8 * 1024 * 1024) {
                showToast('error', 'حجم كبير', 'الحد الأقصى 8 ميجا');
                return;
            }
            pickedFile = file;
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('preview-img').src = e.target.result;
                document.getElementById('preview-wrap').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
            document.getElementById('extract-btn').disabled = false;
        }

        /* ============ قراءة الفاتورة ============ */
        async function extractInvoice() {
            if (!pickedFile) return;
            const btn = document.getElementById('extract-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري القراءة...';

            const fd = new FormData();
            fd.append('image', pickedFile);

            try {
                const res = await fetch('{{ route('purchases.import.extract') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (!data.success) {
                    showToast('error', 'تعذّرت القراءة', data.message || 'حاول بصورة أوضح');
                    return;
                }
                items = (data.items || []).map(it => {
                    const linked = it.match_exact && it.match;
                    return {
                        raw_name: it.name || '',                       // الاسم كما قُرئ من الفاتورة
                        drug_id: linked ? it.match.id : null,          // null = صنف جديد
                        system_name: linked ? it.match.name : null,    // اسم الصنف في السيستم
                        barcode: linked ? (it.match.barcode || '') : '',
                        major_units: linked ? (it.match.major_units || 1) : 1,
                        minor_units: linked ? (it.match.minor_units || 1) : 1,
                        suggestion: (!linked && it.match) ? it.match : null, // اقتراح قريب يحتاج تأكيد
                        quantity: +it.quantity || 1,
                        selling_price: +it.selling_price || 0,
                        discount: +it.discount || 0,
                        purchase_price: +it.purchase_price || 0,
                        expiry: it.expiry || '',
                        batch: it.batch || '',
                        _results: null,                                // نتائج بحث الربط لكل صف
                    };
                });
                if (data.invoice?.number) document.getElementById('invoice-number').value = data.invoice.number;
                if (data.invoice?.date) document.getElementById('invoice-date').value = data.invoice.date;
                printedTotal = data.invoice?.printed_total || null;

                // تلميح المورد + بحث تلقائي
                if (data.supplier) {
                    const hint = document.getElementById('ai-supplier-hint');
                    hint.textContent = 'المورد في الصورة: ' + data.supplier + ' — تأكد من اختياره';
                    hint.classList.remove('hidden');
                    searchSuppliers(data.supplier);
                    document.getElementById('supplier-search').value = data.supplier;
                }

                document.getElementById('upload-step').classList.add('hidden');
                document.getElementById('review-step').classList.remove('hidden');
                document.getElementById('review-step').classList.add('grid');
                renderItems();
                showToast('success', 'تمت القراءة ✅', `تم استخراج ${items.length} صنف — راجعها قبل الحفظ`);
            } catch (e) {
                console.error(e);
                showToast('error', 'خطأ في الاتصال', 'تعذّر الاتصال بالخادم');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-magic"></i> اقرأ الفاتورة بالذكاء الاصطناعي';
            }
        }

        /* ============ كروت الأصناف ============ */
        const sysTimers = {};

        function renderItems() {
            const body = document.getElementById('items-body');
            body.innerHTML = items.map((it, i) => {
                const missing = !it.expiry;
                const lineTotal = (it.purchase_price * it.quantity) || 0;

                // ----- خلية الربط بالسيستم -----
                let systemCell;
                if (it.drug_id) {
                    systemCell = `
                        <div class="bg-green-50 border border-green-200 rounded-lg p-2 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-green-700 truncate"><i class="fas fa-link ml-1"></i>${escapeHtml(it.system_name)}</div>
                                ${it.barcode ? `<div class="font-mono text-[11px] text-green-500"><i class="fas fa-barcode ml-1"></i>${escapeHtml(it.barcode)}</div>` : '<div class="text-[11px] text-green-400">بدون باركود</div>'}
                            </div>
                            <button onclick="unlinkDrug(${i})" class="text-gray-400 hover:text-red-500 text-xs flex-shrink-0" title="فك الربط / تغيير">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>`;
                } else {
                    const sugg = it.suggestion ? `
                        <div class="text-[11px] text-amber-600 mt-1">
                            <i class="fas fa-lightbulb ml-1"></i>اقتراح: ${escapeHtml(it.suggestion.name)}
                            <button onclick="applySuggestion(${i})" class="text-indigo-600 hover:underline font-semibold mr-1">ربط</button>
                        </div>` : '';
                    systemCell = `
                        <div class="border border-indigo-200 bg-indigo-50 rounded-lg p-2">
                            <div class="text-xs text-indigo-700 font-semibold mb-1"><i class="fas fa-plus-circle ml-1"></i>سيُضاف كصنف جديد</div>
                            <div class="relative">
                                <input id="sysq-${i}" oninput="searchSystemDrug(${i}, this.value)" placeholder="أو اربطه بصنف موجود..."
                                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:outline-none focus:border-indigo-400">
                                <div id="sysr-${i}" class="hidden absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50" style="max-height:200px;overflow-y:auto;"></div>
                            </div>
                            ${sugg}
                        </div>`;
                }

                return `
                <div class="border ${missing ? 'border-red-200 bg-red-50/50' : 'border-gray-200'} rounded-xl p-3">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <span class="text-xs font-bold text-gray-400 mt-2">#${i + 1}</span>
                        <button onclick="removeRow(${i})" class="text-red-400 hover:text-red-600 text-sm mt-1" title="حذف"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1"><i class="fas fa-receipt ml-1"></i>الاسم في الفاتورة</label>
                            <input value="${escapeHtml(it.raw_name)}" oninput="upd(${i},'raw_name',this.value)"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:outline-none focus:border-gray-400">
                        </div>
                        <div>
                            <label class="block text-[11px] text-gray-500 mb-1">الصنف في السيستم</label>
                            ${systemCell}
                        </div>
                    </div>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-2 items-end">
                        <div><label class="block text-[11px] text-gray-500 mb-1">الكمية</label>
                            <input id="q-${i}" type="number" min="0.0001" step="any" value="${it.quantity}" oninput="upd(${i},'quantity',this.value)"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-center text-xs"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">سعر البيع</label>
                            <input id="sp-${i}" type="number" min="0" step="any" value="${it.selling_price}" oninput="upd(${i},'selling_price',this.value)"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-center text-xs"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">الخصم٪</label>
                            <input id="dc-${i}" type="number" min="0" max="100" step="any" value="${it.discount}" oninput="upd(${i},'discount',this.value)"
                                class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-center text-xs"></div>
                        <div><label class="block text-[11px] text-emerald-600 mb-1">سعر الشراء</label>
                            <input id="pp-${i}" type="number" min="0" step="any" value="${it.purchase_price}" oninput="upd(${i},'purchase_price',this.value)"
                                class="w-full border border-emerald-300 bg-emerald-50 rounded-lg px-2 py-1.5 text-center text-xs font-bold text-emerald-700"></div>
                        <div><label class="block text-[11px] text-gray-500 mb-1">إجمالي الصنف</label>
                            <div id="lt-${i}" class="px-2 py-1.5 text-center text-xs font-bold text-gray-700 bg-gray-100 rounded-lg">${lineTotal.toFixed(2)} ج.م</div></div>
                        <div><label class="block text-[11px] ${missing ? 'text-red-500' : 'text-gray-500'} mb-1">الصلاحية${missing ? ' *' : ''}</label>
                            <input type="date" value="${it.expiry}" oninput="upd(${i},'expiry',this.value)"
                                class="w-full border ${missing ? 'border-red-300' : 'border-gray-200'} rounded-lg px-2 py-1.5 text-xs"></div>
                    </div>
                    <div class="mt-2">
                        <label class="block text-[11px] text-gray-500 mb-1">رقم التشغيلة (Batch)</label>
                        <input value="${escapeHtml(it.batch)}" oninput="upd(${i},'batch',this.value)"
                            class="w-full md:w-48 border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-center">
                    </div>
                </div>`;
            }).join('');
            document.getElementById('items-count').textContent = items.length + ' صنف';
            calcTotal();
        }

        function upd(i, field, val) {
            const it = items[i];
            if (['quantity', 'selling_price', 'discount', 'purchase_price'].includes(field)) {
                it[field] = parseFloat(val) || 0;
                // تعديل سعر البيع أو الخصم → أعد حساب سعر الشراء فوراً
                if (field === 'selling_price' || field === 'discount') {
                    it.purchase_price = +(it.selling_price * (1 - it.discount / 100)).toFixed(2);
                    const pp = document.getElementById('pp-' + i);
                    if (pp) pp.value = it.purchase_price;
                }
                // حدّث إجمالي الصنف بدون إعادة رسم (حفاظاً على التركيز)
                const lt = document.getElementById('lt-' + i);
                if (lt) lt.textContent = (it.purchase_price * it.quantity).toFixed(2) + ' ج.م';
                calcTotal();
            } else {
                it[field] = val;
                if (field === 'expiry') renderItems();
            }
        }

        function removeRow(i) { items.splice(i, 1); renderItems(); }

        function addRow() {
            items.push({
                raw_name: '', drug_id: null, system_name: null, barcode: '',
                major_units: 1, minor_units: 1, suggestion: null,
                quantity: 1, selling_price: 0, discount: 0, purchase_price: 0,
                expiry: '', batch: '', _results: null,
            });
            renderItems();
        }

        function calcTotal() {
            const total = items.reduce((s, it) => s + (it.purchase_price * it.quantity), 0);
            document.getElementById('net-total').textContent = total.toFixed(2) + ' ج.م';

            // مقارنة بالإجمالي المطبوع في الفاتورة
            const rec = document.getElementById('total-reconcile');
            if (!printedTotal) { rec.classList.add('hidden'); return; }
            const diff = Math.abs(printedTotal - total);
            const ok = diff <= Math.max(1, printedTotal * 0.01); // سماحية 1%
            rec.className = 'text-xs rounded-lg p-2 leading-relaxed ' +
                (ok ? 'bg-green-50 text-green-600' : 'bg-amber-50 text-amber-700 border border-amber-200');
            rec.innerHTML = `<i class="fas ${ok ? 'fa-check-circle' : 'fa-exclamation-triangle'} ml-1"></i>` +
                ` المطبوع في الفاتورة: <b>${printedTotal.toFixed(2)}</b> ج.م` +
                (ok ? ' — مطابق للمحسوب ✔' : ` — يوجد فرق <b>${diff.toFixed(2)}</b> ج.م، راجع الكميات والأسعار`);
            rec.classList.remove('hidden');
        }

        /* ----- ربط الصنف بالسيستم ----- */
        function searchSystemDrug(i, q) {
            clearTimeout(sysTimers[i]);
            const rb = document.getElementById('sysr-' + i);
            if (!rb) return;
            if (q.length < 1) { rb.classList.add('hidden'); return; }
            sysTimers[i] = setTimeout(async () => {
                try {
                    const drugs = await (await fetch(`/products-search?q=${encodeURIComponent(q)}`)).json();
                    if (!drugs.length) {
                        rb.innerHTML = `<div class="p-2 text-center text-gray-400 text-xs">لا يوجد صنف بهذا الاسم في السيستم</div>`;
                    } else {
                        rb.innerHTML = drugs.map(d => `
                            <div onclick='linkDrug(${i}, ${JSON.stringify(d).replace(/'/g, "&#39;")})'
                                 class="p-2 hover:bg-green-50 cursor-pointer border-b border-gray-50 last:border-0 text-right">
                                <div class="text-xs font-semibold text-gray-800">${d.name}</div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    ${d.barcode ? `<span class="font-mono text-[10px] text-gray-400"><i class="fas fa-barcode ml-1"></i>${d.barcode}</span>` : ''}
                                    <span class="text-[10px] ${d.quantity > 0 ? 'text-green-600' : 'text-gray-400'}"><i class="fas fa-boxes ml-1"></i>${d.quantity} بالمخزن</span>
                                </div>
                            </div>`).join('');
                    }
                    rb.classList.remove('hidden');
                } catch (e) { /* تجاهل */ }
            }, 300);
        }

        function linkDrug(i, d) {
            items[i].drug_id = d.id;
            items[i].system_name = d.name;
            items[i].barcode = d.barcode || '';
            items[i].major_units = d.major_units || 1;
            items[i].minor_units = d.minor_units || 1;
            items[i].suggestion = null;
            renderItems();
        }

        function applySuggestion(i) {
            if (items[i].suggestion) linkDrug(i, items[i].suggestion);
        }

        function unlinkDrug(i) {
            items[i].drug_id = null;
            items[i].system_name = null;
            items[i].barcode = '';
            renderItems();
        }

        function escapeHtml(s) {
            return (s || '').toString().replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        /* ============ طريقة الدفع ============ */
        function selectPayment(method) {
            selectedPayment = method;
            document.querySelectorAll('.pay-btn').forEach(b => b.className =
                'pay-btn text-sm py-2.5 rounded-xl border-2 transition font-semibold border-gray-200 bg-gray-50 text-gray-600');
            const active = document.getElementById('pay-btn-' + method);
            active.className = 'pay-btn text-sm py-2.5 rounded-xl border-2 transition font-semibold border-green-500 bg-green-50 text-green-700';
            document.getElementById('paid-section').style.display = method === 'deferred' ? 'none' : 'block';
        }

        /* ============ بحث المورد (نفس منطق فورم الشراء) ============ */
        function searchSuppliers(q) {
            clearTimeout(supplierTimer);
            const rb = document.getElementById('supplier-results');
            if (q.length < 1) { rb.classList.add('hidden'); return; }
            supplierTimer = setTimeout(async () => {
                try {
                    const suppliers = await (await fetch(`/suppliers/search?q=${encodeURIComponent(q)}`)).json();
                    if (!suppliers.length) {
                        rb.innerHTML = `<div class="p-3 text-center text-gray-400 text-sm">لا يوجد موردين —
                            <a href="/suppliers/create" target="_blank" class="text-green-600 hover:underline">إضافة مورد</a></div>`;
                    } else {
                        rb.innerHTML = suppliers.map(s => `
                            <div onclick='pickSupplier(${JSON.stringify(s).replace(/'/g, "&#39;")})'
                                 class="flex items-center justify-between p-3 hover:bg-green-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                                <div><div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm text-gray-800">${s.name}</span>
                                    <span class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">${s.code}</span>
                                </div>${s.company ? `<div class="text-xs text-gray-400">${s.company}</div>` : ''}</div>
                                ${s.balance > 0 ? `<span class="text-xs font-bold text-red-500">${parseFloat(s.balance).toFixed(2)} ج.م مستحق</span>` : `<span class="text-xs text-green-500">✅ مسدد</span>`}
                            </div>`).join('');
                    }
                    rb.classList.remove('hidden');
                } catch (e) { /* تجاهل */ }
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

        /* ============ حفظ الفاتورة (نفس endpoint الشراء) ============ */
        async function savePurchase() {
            const invoiceNum = document.getElementById('invoice-number').value.trim();
            if (!items.length) { showToast('error', 'لا توجد أصناف', 'أضف صنفاً واحداً على الأقل'); return; }
            if (!invoiceNum) { showToast('error', 'رقم الفاتورة مطلوب', 'ادخل رقم فاتورة المورد'); return; }

            // الاسم الفعّال: المربوط بالسيستم أو الاسم الجديد من الفاتورة
            const effName = it => (it.drug_id ? it.system_name : it.raw_name) || '';

            for (const it of items) {
                const nm = effName(it).trim();
                if (!nm) { showToast('error', 'اسم مطلوب', 'يوجد صنف بدون اسم — اربطه أو اكتب اسمه'); return; }
                if (!it.quantity || it.quantity <= 0) { showToast('error', 'كمية غير صحيحة', `راجع كمية: ${nm}`); return; }
                if (!it.purchase_price || it.purchase_price <= 0) { showToast('error', 'سعر الشراء مطلوب', `راجع: ${nm}`); return; }
                if (!it.selling_price || it.selling_price <= 0) { showToast('error', 'سعر البيع مطلوب', `راجع: ${nm}`); return; }
            }

            const btn = document.getElementById('save-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';

            // ضمّ أرقام التشغيلات للملاحظات (السيستم لا يخزّن التشغيلة كحقل مستقل)
            const batches = items.filter(it => it.batch).map(it => `${effName(it)}: ${it.batch}`).join(' | ');
            const userNotes = document.getElementById('notes').value.trim();
            const notes = [userNotes, batches ? 'تشغيلات: ' + batches : ''].filter(Boolean).join('\n');

            const payload = {
                supplier_id: selectedSupplier?.id ?? null,
                invoice_number: invoiceNum,
                invoice_date: document.getElementById('invoice-date').value || null,
                payment_method: selectedPayment,
                paid: selectedPayment === 'deferred' ? 0 : (parseFloat(document.getElementById('paid').value) || 0),
                invoice_discount: 0,
                invoice_extra: 0,
                notes: notes || null,
                items: items.map(it => ({
                    product_name: effName(it),          // المربوط → اسم السيستم بالظبط (يطابقه store)، وإلا اسم جديد
                    category: it.drug_id ? null : null,
                    barcode: it.drug_id ? (it.barcode || null) : null,
                    purchase_price: it.purchase_price,
                    selling_price: it.selling_price,
                    quantity: it.quantity,
                    expiry_date: it.expiry || null,
                    major_units: it.major_units || 1,
                    minor_units: it.minor_units || 1,
                })),
            };

            try {
                const res = await fetch('{{ route('purchases.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    showToast('error', 'خطأ في السيرفر', `كود: ${res.status}`);
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    showToast('success', '✅ تم الحفظ!', 'تم تسجيل الفاتورة وتحديث المخزن');
                    setTimeout(() => window.location.href = data.redirect, 1200);
                } else {
                    const err = data.message || (data.errors ? Object.values(data.errors).flat().join(' | ') : 'تحقق من البيانات');
                    showToast('error', 'حدث خطأ', err);
                }
            } catch (e) {
                showToast('error', 'خطأ في الاتصال', 'تأكد من الاتصال');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> تأكيد وحفظ الفاتورة';
            }
        }

        function resetImport() {
            location.reload();
        }
    </script>
@endsection
