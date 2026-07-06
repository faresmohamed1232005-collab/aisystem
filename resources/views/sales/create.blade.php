@extends('layouts.app')
@section('title', 'البيع المباشر')

@section('styles')
    <style>
        /* =====================================================
           MULTI-TAB STYLES
           ===================================================== */
        #tabs-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 8px 12px 0;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        #tabs-bar::-webkit-scrollbar { height: 4px; }
        #tabs-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .tab-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 10px 10px 0 0;
            border: 2px solid transparent;
            border-bottom: none;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all .18s;
            background: #fff;
            color: #64748b;
            position: relative;
            bottom: -2px;
            min-width: 110px;
            max-width: 180px;
        }
        .tab-item.active {
            border-color: #6366f1;
            border-bottom-color: #fff;
            color: #4f46e5;
            background: #fff;
            z-index: 2;
        }
        .tab-item:not(.active):hover {
            background: #f1f5f9;
            color: #475569;
        }
        .tab-item .tab-close {
            width: 18px; height: 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px;
            color: #94a3b8;
            flex-shrink: 0;
            transition: background .15s, color .15s;
        }
        .tab-item .tab-close:hover { background: #fee2e2; color: #ef4444; }
        .tab-item .tab-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }
        #add-tab-btn {
            width: 30px; height: 30px;
            border-radius: 8px;
            background: #e0e7ff;
            color: #4f46e5;
            font-size: 18px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .15s;
            border: none;
            margin-bottom: 2px;
        }
        #add-tab-btn:hover { background: #c7d2fe; }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* =====================================================
           ORIGINAL STYLES
           ===================================================== */
        @keyframes toastIn {
            from { transform: translateY(-60px) scale(0.95); opacity: 0; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateY(0) scale(1); }
            to   { opacity: 0; transform: translateY(-20px) scale(0.95); }
        }
        @keyframes toastProgress {
            from { width: 100%; }
            to   { width: 0%; }
        }
        @keyframes scanPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.5); }
            50%      { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
        }
        .scanner-active {
            border-color: #6366f1 !important;
            animation: scanPulse 0.7s ease-in-out;
        }

        @media (max-width: 1023px) {
            .invoice-panel {
                position: fixed; bottom: 0; left: 0; right: 0;
                z-index: 30; max-height: 0; overflow: hidden;
                transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1);
                border-radius: 20px 20px 0 0;
                box-shadow: 0 -8px 32px rgba(0,0,0,0.18);
            }
            .invoice-panel.open { max-height: 90vh; overflow-y: auto; }
            .invoice-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,0.4); z-index: 25; backdrop-filter: blur(2px);
            }
            .invoice-overlay.show { display: block; }
            .invoice-fab { display: flex; }
            .sell-actions-desktop { display: none; }
        }
        @media (min-width: 1024px) {
            .invoice-fab    { display: none !important; }
            .invoice-overlay { display: none !important; }
            .sell-actions-mobile { display: none; }
        }
    </style>
@endsection

@section('content')
    {{-- ══ AD BANNER ══ --}}
    @php $activeAd = \App\Models\Ad::activeAd(); @endphp
    @if ($activeAd)
        <div class="mb-3 relative overflow-hidden rounded-2xl shadow-sm border border-indigo-100" style="max-height:120px;">
            <img src="{{ asset('storage/' . $activeAd->image_path) }}" alt="{{ $activeAd->title }}"
                 class="w-full object-cover" style="max-height:120px;">
            @if ($activeAd->title)
                <div class="absolute bottom-0 right-0 left-0 bg-gradient-to-t from-black/50 to-transparent px-4 py-2">
                    <span class="text-white text-sm font-bold">{{ $activeAd->title }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- ════ TABS BAR ════ --}}
    <div id="tabs-bar">
        {{-- تبويبات تُولّد ديناميكياً --}}
        <button id="add-tab-btn" onclick="addTab()" title="فاتورة جديدة">+</button>
    </div>

    {{-- ════ TAB PANELS ════ --}}
    <div id="tabs-content"></div>

    <!-- Toast Container -->
    <div id="toast-container"
        style="position:fixed;top:1.25rem;left:50%;transform:translateX(-50%);
               z-index:9999;display:flex;flex-direction:column;gap:10px;
               align-items:center;pointer-events:none;width:360px;max-width:92vw;">
    </div>
@endsection

@section('scripts')
<script>
/* =====================================================
   منع DevTools
   ===================================================== */
document.addEventListener('keydown', function(e) {
    if (e.key==='F12') { e.preventDefault(); return; }
    if (e.ctrlKey && e.shiftKey && ['I','i','J','j','C','c','M','m'].includes(e.key)) { e.preventDefault(); return; }
    if (e.ctrlKey && (e.key==='U'||e.key==='u')) { e.preventDefault(); return; }
}, true);

/* =====================================================
   TOAST
   ===================================================== */
const toastConfig = {
    success: { icon:'fa-check-circle', border:'#1D9E75', iconBg:'#E1F5EE', iconColor:'#0F6E56', barColor:'#1D9E75' },
    pending: { icon:'fa-clock',         border:'#BA7517', iconBg:'#FAEEDA', iconColor:'#854F0B', barColor:'#BA7517' },
    error:   { icon:'fa-exclamation-circle', border:'#E24B4A', iconBg:'#FCEBEB', iconColor:'#A32D2D', barColor:'#E24B4A' },
};
function showToast(type, title, msg, pendingLink=false) {
    const cfg = toastConfig[type] || toastConfig.success;
    const t = document.createElement('div');
    t.setAttribute('data-toast','');
    t.style.cssText = `display:flex;align-items:flex-start;gap:12px;pointer-events:all;
        background:#fff;border:0.5px solid #e0e0e0;border-right:3px solid ${cfg.border};
        border-radius:14px;padding:12px 14px;box-shadow:0 8px 32px rgba(0,0,0,.12);
        width:100%;position:relative;overflow:hidden;direction:rtl;
        animation:toastIn .38s cubic-bezier(.21,1.02,.73,1) forwards;`;
    t.innerHTML = `
        <div style="width:34px;height:34px;border-radius:50%;background:${cfg.iconBg};color:${cfg.iconColor};
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px;">
            <i class="fas ${cfg.icon}"></i></div>
        <div style="flex:1;min-width:0;padding-top:1px;">
            <p style="margin:0 0 2px;font-size:13px;font-weight:700;color:#1a1a1a;">${title}</p>
            <p style="margin:0;font-size:12px;color:#666;">${msg}</p>
            ${pendingLink?`<a href="{{ route('pending.index') }}" style="display:inline-flex;align-items:center;gap:5px;margin-top:6px;font-size:11px;font-weight:600;color:#185FA5;text-decoration:none;"><i class="fas fa-arrow-left" style="font-size:10px;"></i>عرض الطلبات</a>`:''}
        </div>
        <button onclick="dismissToast(this)" style="background:none;border:none;cursor:pointer;font-size:12px;color:#aaa;padding:2px 4px;flex-shrink:0;">
            <i class="fas fa-times"></i></button>
        <div style="position:absolute;bottom:0;right:0;left:0;height:2.5px;background:${cfg.barColor};
                    animation:toastProgress 4.5s linear forwards;transform-origin:right;"></div>`;
    document.getElementById('toast-container').prepend(t);
    t._timer = setTimeout(() => dismissToast(t.querySelector('button')), 4500);
}
function dismissToast(btn) {
    const t = btn.closest('[data-toast]');
    if (!t || t._dismissed) return;
    t._dismissed = true;
    clearTimeout(t._timer);
    t.style.animation = 'toastOut .32s ease forwards';
    setTimeout(() => t.remove(), 320);
}
function showError(msg) { showToast('error','حدث خطأ',msg); }

/* =====================================================
   MULTI-TAB MANAGER
   ===================================================== */
let tabs = [];       // مصفوفة كل التبويبات
let activeTabId = null;
let tabCounter = 0;

const CATEGORIES = [
    'أدوية أطفال','أدوية السكري','أدوية ضغط','أدوية قلب','أدوية حساسية',
    'أدوية عامة','أدوية عظام ومفاصل','أدوية نسائية','أدوية نفسية وعصبية',
    'مسكنات','مضادات حيوية','أمبولات','فيتامينات','مستحضرات تجميل','مستلزمات طبية',
];

function createTabState() {
    return {
        id: ++tabCounter,
        label: `فاتورة ${tabCounter}`,
        cart: [],
        activeCat: '',
        selectedCustomer: null,
        selectedPayment: 'cash',
        selectedCard: null,
        selectedDelivery: 'store',
        selectedContract: null,
        selectedPatient: null,
        discountType: 'amount',
        searchTimer: null,
        customerTimer: null,
        patientTimer: null,
        lastKeyTime: 0,
        keyIntervals: [],
        _results: [],
        _customers: [],
        _patients: [],
    };
}

const INSURANCE_CONTRACTS = @json($insuranceContracts ?? []);

function addTab() {
    const state = createTabState();
    tabs.push(state);
    renderTabsBar();
    renderTabPanel(state);
    switchTab(state.id);
}

function closeTab(tabId, e) {
    e && e.stopPropagation();
    if (tabs.length === 1) { showToast('error','لا يمكن الإغلاق','يجب أن يبقى تبويب واحد على الأقل'); return; }
    const idx = tabs.findIndex(t => t.id === tabId);
    document.getElementById(`tab-item-${tabId}`)?.remove();
    document.getElementById(`tab-panel-${tabId}`)?.remove();
    tabs.splice(idx, 1);
    // انتقل للتبويب المجاور
    const newActive = tabs[Math.min(idx, tabs.length-1)];
    switchTab(newActive.id);
}

function switchTab(tabId) {
    activeTabId = tabId;
    // تحديث شريط التبويبات
    document.querySelectorAll('.tab-item').forEach(el => {
        el.classList.toggle('active', el.dataset.tabId == tabId);
    });
    // إظهار/إخفاء الألواح
    document.querySelectorAll('.tab-panel').forEach(el => {
        el.classList.toggle('active', el.dataset.tabId == tabId);
    });
    // ركّز على حقل البحث
    setTimeout(() => {
        const inp = document.querySelector(`#tab-panel-${tabId} .product-search-input`);
        if (inp) inp.focus();
    }, 100);
}

function getTab(tabId) { return tabs.find(t => t.id === tabId); }
function getActiveTab() { return getTab(activeTabId); }

function renderTabsBar() {
    const bar = document.getElementById('tabs-bar');
    // احذف التبويبات القديمة (غير زر الإضافة)
    bar.querySelectorAll('.tab-item').forEach(el => el.remove());
    const addBtn = document.getElementById('add-tab-btn');
    tabs.forEach(tab => {
        const el = document.createElement('div');
        el.className = 'tab-item' + (tab.id === activeTabId ? ' active' : '');
        el.dataset.tabId = tab.id;
        el.onclick = () => switchTab(tab.id);
        el.innerHTML = `
            <i class="fas fa-receipt text-xs"></i>
            <span class="tab-label" id="tab-label-${tab.id}">${tab.label}</span>
            <span class="tab-close" onclick="closeTab(${tab.id},event)" title="إغلاق">
                <i class="fas fa-times"></i>
            </span>`;
        el.id = `tab-item-${tab.id}`;
        bar.insertBefore(el, addBtn);
    });
}

function updateTabLabel(tabId, label) {
    const tab = getTab(tabId);
    if (tab) tab.label = label;
    const el = document.getElementById(`tab-label-${tabId}`);
    if (el) el.textContent = label;
}

/* ===================================================
   RENDER TAB PANEL  (HTML لكل تبويب)
   =================================================== */
function renderTabPanel(state) {
    const tid = state.id;
    const div = document.createElement('div');
    div.className = 'tab-panel';
    div.dataset.tabId = tid;
    div.id = `tab-panel-${tid}`;
    div.innerHTML = buildPanelHTML(tid);
    document.getElementById('tabs-content').appendChild(div);
    // ابدأ بـ cash selected
    setTimeout(() => {
        _selectPaymentUI(tid, 'cash');
        _selectDeliveryUI(tid, 'store');
    }, 0);
}

function buildPanelHTML(tid) {
    const catBtns = CATEGORIES.map(cat => {
        const icons = {
            'أدوية أطفال':'👶','أدوية السكري':'💉','أدوية ضغط':'🩺','أدوية قلب':'❤️','أدوية حساسية':'🤧',
            'أدوية عامة':'💊','أدوية عظام ومفاصل':'🦴','أدوية نسائية':'🌸','أدوية نفسية وعصبية':'🧠',
            'مسكنات':'🩹','مضادات حيوية':'🔬','أمبولات':'💉','فيتامينات':'🌿',
            'مستحضرات تجميل':'💄','مستلزمات طبية':'🏥',
        };
        return `<button onclick="filterCat(${tid},'${cat}')" data-cat="${cat}" data-tid="${tid}"
                    class="cat-btn-${tid} text-xs px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-600
                           hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition whitespace-nowrap">
                    ${icons[cat]||'💊'} ${cat}
                </button>`;
    }).join('');

    return `
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 pt-4">

        <!-- يسار: بحث + سلة -->
        <div class="lg:col-span-2 space-y-3 lg:space-y-4">
            <div class="bg-white rounded-2xl shadow-sm p-3 lg:p-4 space-y-3">
                <!-- حقل البحث -->
                <div class="relative">
                    <i id="search-icon-${tid}" class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 transition-all text-sm"></i>
                    <input type="text" id="product-search-${tid}"
                        placeholder="ابحث بالاسم أو اسكان الباركود..."
                        class="product-search-input w-full border-2 border-gray-200 rounded-xl pr-9 pl-24 py-2.5 focus:outline-none focus:border-indigo-400 text-right text-sm transition"
                        oninput="onSearchInput(${tid},this.value)"
                        onkeydown="onSearchKeydown(${tid},event)"
                        autocomplete="off">
                    <span id="search-badge-${tid}"
                        class="absolute left-2 top-1/2 -translate-y-1/2 text-xs px-2 py-0.5 rounded-lg font-semibold select-none transition-all bg-gray-100 text-gray-400">
                        اسم / باركود
                    </span>
                </div>
                <p class="text-xs text-gray-400 text-right -mt-1 hidden sm:block">
                    <i class="fas fa-info-circle ml-1 text-indigo-400"></i>
                    اكتب اسم الدواء للبحث — أو اسكان الباركود مباشرة
                </p>
                <!-- فلاتر الفئات -->
                <div class="flex flex-wrap gap-1.5 overflow-x-auto pb-1">
                    <button onclick="filterCat(${tid},'')" data-cat="" data-tid="${tid}"
                        class="cat-btn-${tid} text-xs px-3 py-1.5 rounded-full border bg-indigo-600 text-white border-indigo-600 transition whitespace-nowrap">الكل</button>
                    ${catBtns}
                </div>
                <div id="search-results-${tid}" class="mt-1 space-y-2 max-h-64 overflow-y-auto hidden"></div>
            </div>

            <!-- السلة -->
            <div class="bg-white rounded-2xl shadow-sm p-3 lg:p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-700 flex items-center gap-2 text-sm">
                        <i class="fas fa-shopping-cart text-indigo-500"></i> المنتجات المختارة
                    </h3>
                    <div class="flex items-center gap-2">
                        <span id="cart-badge-${tid}" class="hidden bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded-full"></span>
                        <button onclick="openInvoicePanel(${tid})"
                            class="invoice-fab-${tid} lg:hidden flex items-center gap-1.5 bg-indigo-600 text-white text-xs font-bold px-3 py-1.5 rounded-xl">
                            <i class="fas fa-receipt"></i>
                            <span>الفاتورة</span>
                            <span id="fab-net-${tid}" class="bg-white/20 px-1.5 py-0.5 rounded-lg">0 ج.م</span>
                        </button>
                    </div>
                </div>
                <div id="cart-items-${tid}" class="space-y-2 min-h-24">
                    <p class="text-gray-400 text-center py-6 text-sm">
                        <i class="fas fa-cart-plus text-2xl block mb-2"></i>
                        ابحث عن منتج وأضفه للفاتورة
                    </p>
                </div>
                <!-- ملخص الأرباح -->
                <div id="cart-summary-${tid}" class="hidden mt-3 pt-3 border-t border-gray-100">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="bg-gray-50 rounded-xl p-2">
                            <div class="text-xs text-gray-400 mb-0.5">التكلفة</div>
                            <div id="summary-cost-${tid}" class="text-xs font-bold text-gray-600">0.00 ج.م</div>
                        </div>
                        <div class="bg-green-50 rounded-xl p-2">
                            <div class="text-xs text-gray-400 mb-0.5">الربح</div>
                            <div id="summary-profit-${tid}" class="text-xs font-bold text-green-600">0.00 ج.م</div>
                        </div>
                        <div class="bg-indigo-50 rounded-xl p-2">
                            <div class="text-xs text-gray-400 mb-0.5">الهامش</div>
                            <div id="summary-margin-${tid}" class="text-xs font-bold text-indigo-600">0%</div>
                        </div>
                    </div>
                </div>
                <!-- أزرار موبايل -->
                <div class="sell-actions-mobile mt-3 space-y-2">
                    <button onclick="completeSale(${tid})"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-check-circle"></i> إتمام البيع
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="savePending(${tid})"
                            class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-xs">
                            <i class="fas fa-clock"></i> طلب معلق
                        </button>
                        <button onclick="clearCart(${tid})"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-600 py-2.5 rounded-xl text-xs transition">
                            <i class="fas fa-trash ml-1"></i> مسح
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- يمين: الفاتورة -->
        <div id="invoice-overlay-${tid}" class="invoice-overlay" onclick="closeInvoicePanel(${tid})"></div>

        <div id="invoice-panel-${tid}"
             class="invoice-panel bg-white lg:rounded-2xl lg:shadow-sm lg:p-5 lg:flex lg:flex-col p-4"
             style="max-height:calc(100vh - 180px); overflow-y:auto;">

            <div class="lg:hidden flex items-center justify-between mb-3 pb-3 border-b border-gray-100">
                <h3 class="font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <i class="fas fa-receipt text-indigo-500"></i> الفاتورة
                </h3>
                <button onclick="closeInvoicePanel(${tid})" class="text-gray-400 hover:text-gray-600 text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <h3 class="hidden lg:flex font-bold text-gray-700 mb-4 items-center gap-2">
                <i class="fas fa-receipt text-indigo-500"></i> الفاتورة
            </h3>

            <div class="space-y-3 flex-1">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>الإجمالي</span>
                    <span id="total-${tid}" class="font-bold">0.00 ج.م</span>
                </div>

                <!-- الخصم -->
                <div class="bg-gray-50 rounded-xl p-3 space-y-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs text-gray-500 font-medium">الخصم</label>
                        <div class="flex items-center bg-white border border-gray-200 rounded-lg overflow-hidden text-xs">
                            <button type="button" id="disc-type-amount-${tid}" onclick="setDiscountType(${tid},'amount')"
                                class="px-3 py-1 font-semibold transition bg-indigo-600 text-white">ج.م</button>
                            <button type="button" id="disc-type-percent-${tid}" onclick="setDiscountType(${tid},'percent')"
                                class="px-3 py-1 font-semibold transition text-gray-500 hover:bg-gray-50">%</button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1 relative">
                            <input type="number" id="discount-${tid}" min="0" step="0.01"
                                oninput="onDiscountAmountChange(${tid})"
                                onfocus="clearZeroInput(this)" onblur="restoreZeroInput(${tid},this)"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400 pl-8">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">ج.م</span>
                        </div>
                        <div class="flex-1 relative">
                            <input type="number" id="discount-percent-${tid}" min="0" max="100" step="0.1"
                                oninput="onDiscountPercentChange(${tid})"
                                onfocus="clearZeroInput(this)" onblur="restoreZeroInput(${tid},this)"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400 pl-6">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">%</span>
                        </div>
                    </div>
                    <div id="discount-hint-${tid}" class="text-xs text-gray-400 text-center">لا يوجد خصم</div>
                </div>

                <div class="flex justify-between text-sm font-bold text-indigo-600 bg-indigo-50 rounded-lg p-3">
                    <span>الصافي</span>
                    <span id="net-total-${tid}">0.00 ج.م</span>
                </div>

                <!-- نوع الطلب -->
                <div>
                    <label class="block text-xs text-gray-500 mb-2">نوع الطلب</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectDelivery(${tid},'store')" id="del-btn-store-${tid}"
                            class="del-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 border-gray-400 bg-gray-100 text-gray-700 font-semibold transition">
                            <i class="fas fa-store ml-1"></i> من الصيدلية
                        </button>
                        <button type="button" onclick="selectDelivery(${tid},'delivery')" id="del-btn-delivery-${tid}"
                            class="del-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-600 font-semibold transition hover:border-indigo-300">
                            <i class="fas fa-truck ml-1"></i> توصيل
                        </button>
                    </div>
                </div>

                <!-- بيانات التوصيل -->
                <div id="delivery-section-${tid}" class="hidden space-y-2">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 space-y-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">عنوان التوصيل *</label>
                            <input type="text" id="delivery-address-${tid}"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400"
                                placeholder="الشارع والحي والمدينة...">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">تليفون التوصيل</label>
                            <input type="text" id="delivery-phone-${tid}"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400"
                                placeholder="01xxxxxxxxx">
                        </div>
                    </div>
                </div>

                <!-- طريقة الدفع -->
                <div>
                    <label class="block text-xs text-gray-500 mb-2">طريقة الدفع</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="selectPayment(${tid},'cash')" id="pay-btn-cash-${tid}"
                            class="pay-method-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 font-semibold transition border-green-500 bg-green-50 text-green-700">
                            <i class="fas fa-money-bill-wave ml-1"></i> كاش
                        </button>
                        <button type="button" onclick="selectPayment(${tid},'card')" id="pay-btn-card-${tid}"
                            class="pay-method-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 font-semibold transition border-gray-200 bg-gray-50 text-gray-600">
                            <i class="fas fa-credit-card ml-1"></i> بطاقة
                        </button>
                        <button type="button" onclick="selectPayment(${tid},'insurance')" id="pay-btn-insurance-${tid}"
                            class="pay-method-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 font-semibold transition border-gray-200 bg-gray-50 text-gray-600">
                            <i class="fas fa-shield-alt ml-1"></i> تأمين
                        </button>
                        <button type="button" onclick="selectPayment(${tid},'deferred')" id="pay-btn-deferred-${tid}"
                            class="pay-method-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 font-semibold transition border-gray-200 bg-gray-50 text-gray-600">
                            <i class="fas fa-file-invoice ml-1"></i> آجل
                        </button>
                    </div>
                </div>

                <!-- نوع البطاقة -->
                <div id="card-type-section-${tid}" class="hidden">
                    <label class="block text-xs text-gray-500 mb-2">نوع البطاقة</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectCard(${tid},'visa')" id="card-btn-visa-${tid}"
                            class="card-type-btn-${tid} text-xs py-2 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-600 transition font-semibold hover:border-blue-300">
                            <i class="fas fa-cc-visa block text-base mb-0.5"></i> فيزا
                        </button>
                        <button type="button" onclick="selectCard(${tid},'instapay')" id="card-btn-instapay-${tid}"
                            class="card-type-btn-${tid} text-xs py-2 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-600 transition font-semibold hover:border-blue-300">
                            <i class="fas fa-bolt block text-base mb-0.5"></i> إنستاباي
                        </button>
                        <button type="button" onclick="selectCard(${tid},'wallet')" id="card-btn-wallet-${tid}"
                            class="card-type-btn-${tid} text-xs py-2 rounded-xl border-2 border-gray-200 bg-gray-50 text-gray-600 transition font-semibold hover:border-blue-300">
                            <i class="fas fa-wallet block text-base mb-0.5"></i> محفظة
                        </button>
                    </div>
                </div>

                <!-- التأمين -->
                <div id="insurance-section-${tid}" class="hidden space-y-2 bg-purple-50/50 border border-purple-100 rounded-xl p-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">عقد التأمين <span class="text-red-500 font-semibold">*</span></label>
                        <select id="insurance-contract-${tid}" onchange="onContractChange(${tid})"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-purple-400">
                            <option value="">— اختر عقد التأمين —</option>
                            ${INSURANCE_CONTRACTS.map(c=>`<option value="${c.id}">${c.name} (${c.code})</option>`).join('')}
                        </select>
                        ${INSURANCE_CONTRACTS.length===0?`<p class="text-xs text-amber-600 mt-1">لا يوجد عقود تأمين نشطة — <a href="/contracts/create" target="_blank" class="underline">أضف عقداً</a>.</p>`:''}
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">المريض المؤمّن عليه</label>
                        <div id="selected-patient-${tid}" class="hidden mb-2 bg-purple-100 border border-purple-200 rounded-xl p-2.5 flex items-center justify-between">
                            <span id="selected-patient-name-${tid}" class="text-sm font-bold text-purple-700"></span>
                            <button type="button" onclick="clearPatient(${tid})" class="text-gray-400 hover:text-red-500 text-xs p-1"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="relative">
                            <input type="text" id="patient-search-${tid}" placeholder="ابحث باسم المريض أو رقم البطاقة..." autocomplete="off"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm text-right focus:outline-none focus:border-purple-400"
                                oninput="searchPatients(${tid},this.value)">
                            <div id="patient-results-${tid}" class="hidden absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden z-20"></div>
                        </div>
                    </div>
                </div>

                <!-- العميل -->
                <div>
                    <label class="block text-xs text-gray-500 mb-1">
                        العميل
                        <span id="customer-required-label-${tid}" class="hidden text-red-500 font-semibold">* مطلوب للآجل</span>
                    </label>
                    <div id="selected-customer-${tid}"
                        class="hidden mb-2 bg-indigo-50 border border-indigo-200 rounded-xl p-2.5 flex items-center justify-between">
                        <div>
                            <span id="selected-customer-name-${tid}" class="text-sm font-bold text-indigo-700"></span>
                            <span id="selected-customer-code-${tid}" class="text-xs text-indigo-400 mr-2"></span>
                            <div id="selected-customer-debt-${tid}" class="text-xs text-red-500 mt-0.5"></div>
                        </div>
                        <button onclick="clearCustomer(${tid})" class="text-gray-400 hover:text-red-500 text-xs p-1">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="relative" id="customer-wrapper-${tid}">
                        <i class="fas fa-user absolute right-3 top-1/2 -translate-y-1/2 text-gray-300 text-xs z-10"></i>
                        <input type="text" id="customer-search-${tid}" placeholder="ابحث بالاسم أو الكود أو التليفون..."
                            autocomplete="off"
                            class="w-full border border-gray-200 rounded-xl pr-8 pl-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400"
                            oninput="searchCustomers(${tid},this.value)"
                            onfocus="if(this.value.length>0) searchCustomers(${tid},this.value)">
                        <div id="customer-results-${tid}"
                            class="hidden absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden"
                            style="z-index:9999;max-height:200px;overflow-y:auto;"></div>
                    </div>
                </div>

                <!-- المبلغ المدفوع -->
                <div id="paid-section-${tid}">
                    <label class="block text-xs text-gray-500 mb-1">المبلغ المدفوع</label>
                    <input type="number" id="paid-${tid}" min="0" step="0.01"
                        oninput="calcChange(${tid})"
                        onfocus="clearZeroInput(this)" onblur="restoreZeroInput(${tid},this)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:border-indigo-400">
                </div>

                <div class="flex justify-between text-sm bg-green-50 rounded-lg p-3">
                    <span class="text-gray-600" id="change-label-${tid}">الباقي للعميل</span>
                    <span id="change-${tid}" class="font-bold text-green-600">0.00 ج.م</span>
                </div>

                <div id="deferred-info-${tid}" class="hidden bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600">
                    <i class="fas fa-exclamation-triangle ml-1"></i>
                    الفاتورة ستُسجّل كآجل — تأكد من اختيار العميل
                </div>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">ملاحظات</label>
                    <textarea id="notes-${tid}" rows="2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 resize-none"
                        placeholder="اختياري..."></textarea>
                </div>
            </div>

            <!-- الأزرار ديسكتوب -->
            <div class="sell-actions-desktop mt-4 space-y-2">
                <button onclick="completeSale(${tid})" id="sell-btn-${tid}"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i> إتمام البيع مباشرة
                </button>
                <button onclick="savePending(${tid})" id="pending-btn-${tid}"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-clock"></i> حفظ كطلب معلق
                </button>
                <button onclick="clearCart(${tid})"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-xl text-sm transition">
                    <i class="fas fa-trash"></i> مسح الفاتورة
                </button>
            </div>

            <!-- أزرار موبايل داخل Panel -->
            <div class="lg:hidden mt-4 space-y-2 pb-4">
                <button onclick="completeSale(${tid}); closeInvoicePanel(${tid});"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i> إتمام البيع
                </button>
                <button onclick="savePending(${tid}); closeInvoicePanel(${tid});"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-clock"></i> طلب معلق
                </button>
                <button onclick="closeInvoicePanel(${tid})"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-2 rounded-xl text-sm transition">
                    إغلاق
                </button>
            </div>
        </div>
    </div>`;
}

/* =====================================================
   MOBILE PANEL
   ===================================================== */
function openInvoicePanel(tid) {
    document.getElementById(`invoice-panel-${tid}`).classList.add('open');
    document.getElementById(`invoice-overlay-${tid}`).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeInvoicePanel(tid) {
    document.getElementById(`invoice-panel-${tid}`).classList.remove('open');
    document.getElementById(`invoice-overlay-${tid}`).classList.remove('show');
    document.body.style.overflow = '';
}

/* =====================================================
   HELPERS
   ===================================================== */
function clearZeroInput(el) {
    if (el.value==='0'||el.value==='') el.value='';
}
function restoreZeroInput(tid, el) {
    if (el.value===''||el.value===null) { el.value='0'; calcTotal(tid); }
}

function getUnits(p) {
    if (Array.isArray(p.available_units) && p.available_units.length) return p.available_units;
    return [{ key:'pack', name:'علبة', price:parseFloat(p.price)||0, qty_factor:1 }];
}
function isExpired(p) {
    if (!p.expiry_date) return false;
    const exp=new Date(p.expiry_date); exp.setHours(0,0,0,0);
    const now=new Date(); now.setHours(0,0,0,0);
    return exp < now;
}
function fmtDate(d) {
    return new Date(d).toLocaleDateString('ar-EG',{year:'numeric',month:'short',day:'numeric'});
}

/* =====================================================
   SCANNER
   ===================================================== */
const SCANNER_MS = 50;
const MIN_BC_LEN = 4;

function onSearchKeydown(tid, e) {
    const tab = getTab(tid);
    const now = Date.now();
    if (tab.lastKeyTime > 0) {
        tab.keyIntervals.push(now - tab.lastKeyTime);
        if (tab.keyIntervals.length > 12) tab.keyIntervals.shift();
    }
    tab.lastKeyTime = now;
    if (e.key === 'Enter') {
        e.preventDefault();
        const val = e.target.value.trim();
        if (!val) return;
        if (_isLikelyScanner(tab, val)) handleBarcodeInput(tid, val);
        else { if (tab._results.length > 0) addFromIndex(tid, 0, 0); }
    }
}
function _isLikelyScanner(tab, val) {
    if (tab.keyIntervals.length < 3) return false;
    const avg = tab.keyIntervals.reduce((a,b)=>a+b,0)/tab.keyIntervals.length;
    return avg < SCANNER_MS && val.length >= MIN_BC_LEN;
}

function onSearchInput(tid, val) {
    const tab = getTab(tid);
    clearTimeout(tab.searchTimer);
    if (!val) {
        document.getElementById(`search-results-${tid}`).classList.add('hidden');
        setBadge(tid,'اسم / باركود','bg-gray-100 text-gray-400','fa-search','text-gray-400');
        tab.keyIntervals = [];
        return;
    }
    const looksNum = /^[0-9\-]+$/.test(val) && val.length >= MIN_BC_LEN;
    if (looksNum) {
        setBadge(tid,'باركود 🔍','bg-indigo-100 text-indigo-700','fa-barcode','text-indigo-500');
        const inp = document.getElementById(`product-search-${tid}`);
        inp.classList.add('scanner-active');
        setTimeout(()=>inp.classList.remove('scanner-active'),800);
    } else {
        setBadge(tid,'بحث','bg-gray-100 text-gray-500','fa-search','text-gray-400');
    }
    if (val.length>=2||tab.activeCat!=='') tab.searchTimer=setTimeout(()=>doSearch(tid,val),280);
}

function setBadge(tid, text, cls, ic, icColor) {
    const badge = document.getElementById(`search-badge-${tid}`);
    const icon  = document.getElementById(`search-icon-${tid}`);
    if (badge) { badge.textContent=text; badge.className=`absolute left-2 top-1/2 -translate-y-1/2 text-xs px-2 py-0.5 rounded-lg font-semibold select-none transition-all ${cls}`; }
    if (icon)  icon.className = `fas ${ic} absolute right-3 top-1/2 -translate-y-1/2 transition-all text-sm ${icColor}`;
}

async function handleBarcodeInput(tid, code) {
    setBadge(tid,'بحث...','bg-yellow-100 text-yellow-700','fa-spinner fa-spin','text-yellow-500');
    try {
        const products = await (await fetch(`/products-search?barcode=${encodeURIComponent(code)}`)).json();
        if (products.length===1) {
            const p = products[0];
            const today=new Date(); today.setHours(0,0,0,0);
            const expired = p.expiry_date && new Date(p.expiry_date)<today;
            if (p.quantity<=0||expired) {
                showToast('error','المنتج غير متاح',p.quantity<=0?'الكمية صفر':'منتهي الصلاحية');
            } else {
                const unit=getUnits(p)[0];
                doAddToCart(tid,p,unit);
                showToast('success','✅ تم الإضافة',p.name);
            }
            clearSearchUI(tid);
        } else if (products.length>1) {
            const tab=getTab(tid); tab._results=products;
            renderResults(tid,products);
            showToast('pending','أكثر من منتج','اختر المنتج المناسب');
        } else {
            showToast('error','غير موجود',`"${code}" غير موجود في المخزن`);
        }
    } catch(e) { showToast('error','خطأ في البحث','تحقق من الاتصال'); }
}

function clearSearchUI(tid) {
    const inp=document.getElementById(`product-search-${tid}`);
    if (inp) inp.value='';
    document.getElementById(`search-results-${tid}`)?.classList.add('hidden');
    const tab=getTab(tid); tab.keyIntervals=[]; tab._results=[];
    setBadge(tid,'اسم / باركود','bg-gray-100 text-gray-400','fa-search','text-gray-400');
    setTimeout(()=>inp?.focus(),100);
}

function filterCat(tid, cat) {
    const tab=getTab(tid); tab.activeCat=cat;
    document.querySelectorAll(`.cat-btn-${tid}`).forEach(btn=>{
        const active=btn.dataset.cat===cat;
        btn.className=active
            ?`cat-btn-${tid} text-xs px-3 py-1.5 rounded-full border bg-indigo-600 text-white border-indigo-600 transition whitespace-nowrap`
            :`cat-btn-${tid} text-xs px-3 py-1.5 rounded-full border border-gray-200 bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition whitespace-nowrap`;
    });
    const q=document.getElementById(`product-search-${tid}`).value.trim();
    if (q.length>=2||cat!=='') doSearch(tid,q);
    else document.getElementById(`search-results-${tid}`).classList.add('hidden');
}

async function doSearch(tid, q) {
    try {
        const p=new URLSearchParams();
        if (q) p.append('q',q);
        const tab=getTab(tid);
        if (tab.activeCat) p.append('category',tab.activeCat);
        const products=await (await fetch(`/products-search?${p}`)).json();
        tab._results=products;
        renderResults(tid,products);
    } catch(e) { console.error(e); }
}

function renderResults(tid, products) {
    const tab=getTab(tid); tab._results=products;
    const c=document.getElementById(`search-results-${tid}`);
    if (!products.length) {
        c.innerHTML='<p class="text-gray-400 text-sm text-center py-3">لا توجد نتائج</p>';
        c.classList.remove('hidden'); return;
    }
    const today=new Date(); today.setHours(0,0,0,0);
    const soon=new Date(today); soon.setDate(soon.getDate()+30);
    const unitColors={pack:'border-indigo-300 bg-indigo-50 text-indigo-700 hover:bg-indigo-100',strip:'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100',piece:'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'};
    const unitIcons={pack:'fa-box',strip:'fa-layer-group',piece:'fa-circle'};
    c.innerHTML=products.map((p,pi)=>{
        let eb='', cc='bg-gray-50 hover:bg-indigo-50 border-transparent hover:border-indigo-200';
        if (p.expiry_date) {
            const exp=new Date(p.expiry_date); exp.setHours(0,0,0,0);
            if (exp<today) { eb=`<span class="text-xs font-bold text-white bg-red-500 px-2 py-0.5 rounded-full">منتهي</span>`; cc='bg-red-50 border-red-300 opacity-70 cursor-not-allowed'; }
            else if (exp<=soon) { eb=`<span class="text-xs text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">ينتهي ${fmtDate(p.expiry_date)}</span>`; cc='bg-orange-50 border-orange-200'; }
            else eb=`<span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">حتى ${fmtDate(p.expiry_date)}</span>`;
        }
        const expired=p.expiry_date&&new Date(p.expiry_date)<today;
        const canAdd=p.quantity>0&&!expired;
        const qc=p.quantity===0?'text-red-500 font-bold':p.quantity<=(p.min_quantity??5)?'text-orange-500 font-semibold':'text-gray-400';
        const sell=parseFloat(p.price)||0, cost=parseFloat(p.cost_price)||0, profit=sell-cost;
        const major=p.major_units||1, minor=p.minor_units||1;
        const strip=p.strip_unit_name||'شريط', piece=p.piece_unit_name||'حبة';
        const bcBadge=p.barcode?`<span class="font-mono text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-full"><i class="fas fa-barcode ml-0.5"></i>${p.barcode}</span>`:'';
        const priceRow=cost>0?`<div class="flex items-center gap-2 mt-1 pt-1 border-t border-gray-100 text-xs flex-wrap"><span class="text-gray-400">شراء: <span class="font-bold text-gray-600">${cost.toFixed(2)} ج.م</span></span><span class="${profit>=0?'text-green-600':'text-red-500'}">كسب: <span class="font-bold">${profit>=0?'+':''}${profit.toFixed(2)} ج.م</span></span></div>`:'';
        const qtyStrips=Math.floor(p.quantity*major), qtyPieces=Math.floor(p.quantity*major*minor);
        if (!canAdd) return `<div class="p-3 rounded-xl border ${cc}"><div class="flex items-start justify-between gap-2"><div class="flex-1 min-w-0"><div class="font-semibold text-sm text-gray-800">${p.name}</div><div class="flex flex-wrap items-center gap-1.5 mt-1">${p.category?`<span class="text-xs text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">${p.category}</span>`:''}<span class="text-xs ${qc}">${p.quantity} علبة</span>${bcBadge} ${eb}</div></div><div class="text-right flex-shrink-0"><div class="text-indigo-600 font-bold text-sm">${sell.toFixed(2)} ج.م</div><div class="text-red-400 text-xs">غير متاح</div></div></div></div>`;
        const units=getUnits(p);
        if (units.length===1) return `<div onclick="addFromIndex(${tid},${pi},0)" class="p-3 rounded-xl border cursor-pointer transition ${cc}"><div class="flex items-start justify-between gap-2"><div class="flex-1 min-w-0"><div class="font-semibold text-sm text-gray-800">${p.name}</div><div class="flex flex-wrap items-center gap-1.5 mt-1">${p.category?`<span class="text-xs text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">${p.category}</span>`:''}<span class="text-xs ${qc}">${p.quantity} علبة</span>${bcBadge} ${eb}</div>${priceRow}</div><div class="text-right flex-shrink-0"><div class="text-indigo-600 font-bold text-sm">${sell.toFixed(2)} ج.م</div></div></div></div>`;
        const unitBtns=units.map((u,ui)=>{
            let availTxt='';
            if (u.key==='pack') availTxt=`${p.quantity} علبة`;
            if (u.key==='strip') availTxt=`${qtyStrips} ${strip}`;
            if (u.key==='piece') availTxt=`${qtyPieces} ${piece}`;
            const icon=unitIcons[u.key]||'fa-box', color=unitColors[u.key]||unitColors.pack;
            return `<button type="button" onclick="event.stopPropagation();addFromIndex(${tid},${pi},${ui})" class="text-xs px-2 py-2 rounded-xl border-2 font-semibold transition flex-1 ${color}"><i class="fas ${icon} ml-0.5"></i>${u.name}<span class="block font-bold mt-0.5">${parseFloat(u.price).toFixed(2)} ج.م</span><span class="block text-xs opacity-70 mt-0.5">${availTxt}</span></button>`;
        }).join('');
        return `<div class="p-3 rounded-xl border ${cc}"><div class="flex items-start justify-between gap-2 mb-2"><div class="flex-1 min-w-0"><div class="font-semibold text-sm text-gray-800">${p.name}</div><div class="flex flex-wrap items-center gap-1.5 mt-1">${p.category?`<span class="text-xs text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full">${p.category}</span>`:''}<span class="text-xs text-gray-400">📦=${major} ${strip}×${minor} ${piece}</span>${bcBadge} ${eb}</div>${priceRow}</div><div class="text-right flex-shrink-0"><div class="text-indigo-600 font-bold text-sm">${sell.toFixed(2)} ج.م</div><div class="text-gray-400 text-xs">/ علبة</div></div></div><div class="flex gap-1.5 pt-2 border-t border-gray-100"><span class="text-xs text-gray-400 self-center flex-shrink-0">بيع بـ:</span>${unitBtns}</div></div>`;
    }).join('');
    c.classList.remove('hidden');
}

function addFromIndex(tid, pi, ui) {
    const tab=getTab(tid);
    const p=tab._results[pi];
    if (!p) { showError('حدث خطأ، أعد البحث'); return; }
    const units=getUnits(p);
    doAddToCart(tid, p, units[ui]||units[0]);
}

function doAddToCart(tid, p, unit) {
    const tab=getTab(tid);
    if (isExpired(p)) { showToast('error','منتهي الصلاحية','لا يمكن بيع منتج منتهي الصلاحية'); return; }
    if (p.quantity<=0) { showToast('error','نفدت الكمية','لا يوجد مخزون كافٍ'); return; }
    const qtyFactor=parseFloat(unit.qty_factor)||1;
    const cartKey=`${p.id}_${unit.key}`;
    const usedQty=tab.cart.filter(i=>i.productId===p.id).reduce((s,i)=>s+i.qty*i.qtyFactor,0);
    if (usedQty+qtyFactor>parseFloat(p.quantity)) {
        const major=p.major_units||1,minor=p.minor_units||1;
        const strip=p.strip_unit_name||'شريط',piece=p.piece_unit_name||'حبة';
        const remaining=parseFloat(p.quantity)-usedQty;
        let remTxt='';
        if (unit.key==='piece') remTxt=`${Math.floor(remaining*major*minor)} ${piece}`;
        else if (unit.key==='strip') remTxt=`${Math.floor(remaining*major)} ${strip}`;
        else remTxt=`${remaining.toFixed(2)} علبة`;
        showToast('error','كمية غير كافية',`المتبقي: ${remTxt} فقط`); return;
    }
    const ex=tab.cart.find(i=>i.cartKey===cartKey);
    if (ex) ex.qty++;
    else tab.cart.push({ cartKey, productId:p.id, id:p.id, name:p.name, unitKey:unit.key, unitName:unit.name, price:parseFloat(unit.price), qtyFactor, cost_price:parseFloat(p.cost_price)||0, qty:1, maxQty:parseFloat(p.quantity) });
    // تحديث لاعبة التبويب بعدد الأصناف
    const total=tab.cart.reduce((s,i)=>s+i.qty,0);
    updateTabLabel(tid, `فاتورة ${tid} (${total})`);
    renderCartForTab(tid);
    clearSearchUI(tid);
}

function renderCartForTab(tid) {
    const tab=getTab(tid);
    const c=document.getElementById(`cart-items-${tid}`);
    const b=document.getElementById(`cart-badge-${tid}`);
    const summary=document.getElementById(`cart-summary-${tid}`);
    if (!tab.cart.length) {
        c.innerHTML='<p class="text-gray-400 text-center py-6 text-sm"><i class="fas fa-cart-plus text-2xl block mb-2"></i>ابحث عن منتج وأضفه للفاتورة</p>';
        b.classList.add('hidden'); summary.classList.add('hidden');
        updateTabLabel(tid,`فاتورة ${tid}`);
        calcTotal(tid); return;
    }
    b.textContent=tab.cart.reduce((s,i)=>s+i.qty,0)+' صنف';
    b.classList.remove('hidden');
    const unitColors={pack:'bg-indigo-100 text-indigo-700',strip:'bg-orange-100 text-orange-700',piece:'bg-green-100 text-green-700'};
    c.innerHTML=tab.cart.map((item,idx)=>{
        const costPerUnit=item.cost_price*item.qtyFactor;
        const profit=(item.price-costPerUnit)*item.qty;
        const uc=unitColors[item.unitKey]||unitColors.pack;
        return `<div class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-100">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-semibold text-xs text-gray-800">${item.name}</span>
                    <span class="text-xs px-1.5 py-0.5 rounded-lg font-semibold ${uc}">${item.unitName}</span>
                </div>
                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    <span class="text-xs text-gray-400">${item.price.toFixed(2)} ج.م</span>
                    ${item.cost_price>0?`<span class="text-xs ${profit>=0?'text-green-600':'text-red-500'} font-semibold">ربح: ${profit.toFixed(2)}</span>`:''}
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button onclick="changeQty(${tid},${idx},-1)" class="w-7 h-7 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-bold flex items-center justify-center">−</button>
                <span class="w-7 text-center font-bold text-sm">${item.qty}</span>
                <button onclick="changeQty(${tid},${idx},1)"  class="w-7 h-7 bg-indigo-100 hover:bg-indigo-200 rounded-lg text-sm font-bold text-indigo-600 flex items-center justify-center">+</button>
            </div>
            <div class="text-xs font-bold text-gray-700 w-16 text-left">${(item.price*item.qty).toFixed(2)} ج.م</div>
            <button onclick="removeItem(${tid},${idx})" class="text-red-400 hover:text-red-600 text-xs"><i class="fas fa-times"></i></button>
        </div>`;
    }).join('');
    const rev=tab.cart.reduce((s,i)=>s+i.price*i.qty,0);
    const cst=tab.cart.reduce((s,i)=>s+i.cost_price*i.qtyFactor*i.qty,0);
    const pft=rev-cst, mrg=rev>0?(pft/rev*100):0;
    if (tab.cart.some(i=>i.cost_price>0)) {
        document.getElementById(`summary-cost-${tid}`).textContent=cst.toFixed(2)+' ج.م';
        document.getElementById(`summary-profit-${tid}`).textContent=pft.toFixed(2)+' ج.م';
        document.getElementById(`summary-margin-${tid}`).textContent=mrg.toFixed(1)+'%';
        summary.classList.remove('hidden');
    } else summary.classList.add('hidden');
    calcTotal(tid);
}

function changeQty(tid, idx, delta) {
    const tab=getTab(tid), item=tab.cart[idx];
    const nq=item.qty+delta;
    if (nq<1) { removeItem(tid,idx); return; }
    const usedOthers=tab.cart.filter((_,j)=>j!==idx&&tab.cart[j].productId===item.productId).reduce((s,i)=>s+i.qty*i.qtyFactor,0);
    if (usedOthers+nq*item.qtyFactor>item.maxQty) { showToast('error','كمية غير كافية','لا يوجد مخزون كافٍ'); return; }
    tab.cart[idx].qty=nq;
    renderCartForTab(tid);
}
function removeItem(tid, idx) { getTab(tid).cart.splice(idx,1); renderCartForTab(tid); }
function clearCart(tid)       { getTab(tid).cart=[]; renderCartForTab(tid); }

/* =====================================================
   DISCOUNT
   ===================================================== */
function setDiscountType(tid, type) {
    const tab=getTab(tid); tab.discountType=type;
    const a='px-3 py-1 font-semibold transition bg-indigo-600 text-white';
    const i='px-3 py-1 font-semibold transition text-gray-500 hover:bg-gray-50';
    document.getElementById(`disc-type-amount-${tid}`).className=type==='amount'?a:i;
    document.getElementById(`disc-type-percent-${tid}`).className=type==='percent'?a:i;
    calcTotal(tid);
}
function onDiscountAmountChange(tid) {
    const sub=getTab(tid).cart.reduce((s,i)=>s+i.price*i.qty,0);
    const amt=parseFloat(document.getElementById(`discount-${tid}`).value)||0;
    if (sub>0) document.getElementById(`discount-percent-${tid}`).value=((amt/sub)*100).toFixed(1);
    setDiscountType(tid,'amount'); calcTotal(tid);
}
function onDiscountPercentChange(tid) {
    const sub=getTab(tid).cart.reduce((s,i)=>s+i.price*i.qty,0);
    const pct=parseFloat(document.getElementById(`discount-percent-${tid}`).value)||0;
    document.getElementById(`discount-${tid}`).value=((pct/100)*sub).toFixed(2);
    setDiscountType(tid,'percent'); calcTotal(tid);
}

/* =====================================================
   DELIVERY & PAYMENT
   ===================================================== */
function selectDelivery(tid, type) {
    const tab=getTab(tid); tab.selectedDelivery=type;
    _selectDeliveryUI(tid,type);
}
function _selectDeliveryUI(tid,type) {
    document.querySelectorAll(`.del-btn-${tid}`).forEach(btn=>{
        const val=btn.id.replace(`del-btn-`,``).replace(`-${tid}`,'');
        btn.className=`del-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 font-semibold transition ${
            val===type?(type==='delivery'?'border-indigo-500 bg-indigo-50 text-indigo-700':'border-gray-400 bg-gray-100 text-gray-700'):'border-gray-200 bg-gray-50 text-gray-600 hover:border-indigo-300'
        }`;
    });
    document.getElementById(`delivery-section-${tid}`).classList.toggle('hidden',type!=='delivery');
    if (type==='delivery') {
        const tab=getTab(tid);
        if (tab.selectedCustomer) {
            const ph=document.getElementById(`delivery-phone-${tid}`);
            const ad=document.getElementById(`delivery-address-${tid}`);
            if (tab.selectedCustomer.phone&&!ph.value.trim()) ph.value=tab.selectedCustomer.phone;
            if (tab.selectedCustomer.address&&!ad.value.trim()) ad.value=tab.selectedCustomer.address;
        }
    }
}

const payColors={cash:'border-green-500 bg-green-50 text-green-700',card:'border-blue-500 bg-blue-50 text-blue-700',insurance:'border-purple-500 bg-purple-50 text-purple-700',deferred:'border-red-500 bg-red-50 text-red-700'};

function selectPayment(tid, method) {
    const tab=getTab(tid); tab.selectedPayment=method;
    _selectPaymentUI(tid,method);
}
function _selectPaymentUI(tid,method) {
    document.querySelectorAll(`.pay-method-btn-${tid}`).forEach(btn=>{
        const val=btn.id.replace(`pay-btn-`,``).replace(`-${tid}`,'');
        btn.className=`pay-method-btn-${tid} text-xs sm:text-sm py-2.5 rounded-xl border-2 transition font-semibold ${val===method?payColors[method]:'border-gray-200 bg-gray-50 text-gray-600'}`;
    });
    document.getElementById(`card-type-section-${tid}`).classList.toggle('hidden',method!=='card');
    document.getElementById(`deferred-info-${tid}`).classList.toggle('hidden',method!=='deferred');
    document.getElementById(`customer-required-label-${tid}`).classList.toggle('hidden',method!=='deferred');
    document.getElementById(`insurance-section-${tid}`).classList.toggle('hidden',method!=='insurance');
    if (method!=='insurance') { clearPatient(tid); const cs=document.getElementById(`insurance-contract-${tid}`); if(cs){cs.value='';} getTab(tid).selectedContract=null; }
    if (method==='deferred') document.getElementById(`paid-${tid}`).value=0;
    calcChange(tid);
}

/* =====================================================
   INSURANCE (contract + patient)
   ===================================================== */
function onContractChange(tid) {
    const tab=getTab(tid);
    const val=document.getElementById(`insurance-contract-${tid}`).value;
    tab.selectedContract = val ? INSURANCE_CONTRACTS.find(c=>String(c.id)===String(val)) : null;
    clearPatient(tid); // المريض مرتبط بالعقد
}
function searchPatients(tid, q) {
    const tab=getTab(tid);
    clearTimeout(tab.patientTimer);
    const rb=document.getElementById(`patient-results-${tid}`);
    if (!tab.selectedContract) { rb.innerHTML=`<div class="p-3 text-center text-xs text-amber-600">اختر عقد التأمين أولاً</div>`; rb.classList.remove('hidden'); return; }
    if (q.length<1) { rb.classList.add('hidden'); return; }
    tab.patientTimer=setTimeout(async ()=>{
        try {
            tab._patients=await (await fetch(`/insured-patients/search?contract_id=${tab.selectedContract.id}&q=${encodeURIComponent(q)}`)).json();
            if (!tab._patients.length) {
                rb.innerHTML=`<div class="p-4 text-center"><div class="text-gray-400 text-sm">لا يوجد مرضى</div><a href="/insured-patients/create" target="_blank" class="text-purple-500 text-xs hover:underline mt-1 block">+ إضافة مريض</a></div>`;
            } else {
                rb.innerHTML=tab._patients.map((p,pi)=>`
                    <div onclick="pickPatientByIndex(${tid},${pi})" class="p-3 hover:bg-purple-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                        <div class="font-semibold text-sm text-gray-800">${p.name}</div>
                        <div class="text-xs text-gray-400">${p.card_number?('بطاقة: '+p.card_number):''} ${p.membership_number?(' • عضوية: '+p.membership_number):''}</div>
                    </div>`).join('');
            }
            rb.classList.remove('hidden');
        } catch(e){ rb.classList.add('hidden'); }
    },250);
}
function pickPatientByIndex(tid, pi) {
    const tab=getTab(tid); const p=tab._patients[pi]; if(!p) return;
    tab.selectedPatient=p;
    document.getElementById(`selected-patient-name-${tid}`).textContent=p.name;
    document.getElementById(`selected-patient-${tid}`).classList.remove('hidden');
    document.getElementById(`patient-results-${tid}`).classList.add('hidden');
    document.getElementById(`patient-search-${tid}`).value='';
}
function clearPatient(tid) {
    const tab=getTab(tid); tab.selectedPatient=null;
    const box=document.getElementById(`selected-patient-${tid}`); if(box) box.classList.add('hidden');
    const rb=document.getElementById(`patient-results-${tid}`); if(rb) rb.classList.add('hidden');
    const inp=document.getElementById(`patient-search-${tid}`); if(inp) inp.value='';
}
function selectCard(tid, type) {
    getTab(tid).selectedCard=type;
    document.querySelectorAll(`.card-type-btn-${tid}`).forEach(btn=>{
        const val=btn.id.replace(`card-btn-`,``).replace(`-${tid}`,'');
        btn.className=`card-type-btn-${tid} text-xs py-2 rounded-xl border-2 transition font-semibold ${val===type?'border-blue-500 bg-blue-50 text-blue-700':'border-gray-200 bg-gray-50 text-gray-600 hover:border-blue-300'}`;
    });
}

/* =====================================================
   CUSTOMER SEARCH
   ===================================================== */
function searchCustomers(tid, q) {
    const tab=getTab(tid);
    clearTimeout(tab.customerTimer);
    const rb=document.getElementById(`customer-results-${tid}`);
    if (q.length<1) { rb.classList.add('hidden'); return; }
    tab.customerTimer=setTimeout(async ()=>{
        try {
            tab._customers=await (await fetch(`/customers/search?q=${encodeURIComponent(q)}`)).json();
            if (!tab._customers.length) {
                rb.innerHTML=`<div class="p-4 text-center"><div class="text-gray-400 text-sm">لا يوجد عملاء</div><a href="/customers/create" target="_blank" class="text-indigo-500 text-xs hover:underline mt-1 block">+ إضافة عميل جديد</a></div>`;
            } else {
                rb.innerHTML=tab._customers.map((c,ci)=>`
                    <div onclick="pickCustomerByIndex(${tid},${ci})"
                         class="flex items-center justify-between p-3 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0 transition">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-sm text-gray-800">${c.name}</span>
                                <span class="font-mono text-xs bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded">${c.code}</span>
                            </div>
                            ${c.phone?`<div class="text-xs text-gray-400 mt-0.5">${c.phone}</div>`:''}
                        </div>
                        <div class="text-right flex-shrink-0 mr-2">
                            ${c.balance>0?`<span class="text-xs font-bold text-red-500">${parseFloat(c.balance).toFixed(2)} ج.م</span>`:`<span class="text-xs text-green-500 font-semibold">✅</span>`}
                        </div>
                    </div>`).join('');
            }
            rb.classList.remove('hidden');
        } catch(e) { console.error(e); }
    }, 300);
}
function pickCustomerByIndex(tid, ci) {
    const tab=getTab(tid); const c=tab._customers[ci];
    if (!c) return;
    tab.selectedCustomer=c;
    document.getElementById(`customer-results-${tid}`).classList.add('hidden');
    document.getElementById(`customer-search-${tid}`).value='';
    document.getElementById(`selected-customer-${tid}`).classList.remove('hidden');
    document.getElementById(`selected-customer-name-${tid}`).textContent=c.name;
    document.getElementById(`selected-customer-code-${tid}`).textContent=c.code;
    document.getElementById(`selected-customer-debt-${tid}`).textContent=c.balance>0?`⚠️ دين: ${parseFloat(c.balance).toFixed(2)} ج.م`:'';
    if (tab.selectedDelivery==='delivery') {
        const ph=document.getElementById(`delivery-phone-${tid}`);
        const ad=document.getElementById(`delivery-address-${tid}`);
        if (c.phone&&!ph.value.trim()) ph.value=c.phone;
        if (c.address&&!ad.value.trim()) ad.value=c.address;
    }
}
function clearCustomer(tid) {
    const tab=getTab(tid); tab.selectedCustomer=null;
    document.getElementById(`selected-customer-${tid}`).classList.add('hidden');
    document.getElementById(`customer-search-${tid}`).value='';
}
document.addEventListener('click', e => {
    tabs.forEach(tab => {
        const w=document.getElementById(`customer-wrapper-${tab.id}`);
        if (w&&!w.contains(e.target)) document.getElementById(`customer-results-${tab.id}`)?.classList.add('hidden');
    });
});

/* =====================================================
   TOTALS
   ===================================================== */
function calcTotal(tid) {
    const tab=getTab(tid);
    const sub=tab.cart.reduce((s,i)=>s+i.price*i.qty,0);
    const disc=parseFloat(document.getElementById(`discount-${tid}`)?.value)||0;
    const pct=parseFloat(document.getElementById(`discount-percent-${tid}`)?.value)||0;
    const net=Math.max(0,sub-disc);
    const totalEl=document.getElementById(`total-${tid}`);
    const netEl=document.getElementById(`net-total-${tid}`);
    const fabEl=document.getElementById(`fab-net-${tid}`);
    if (totalEl) totalEl.textContent=sub.toFixed(2)+' ج.م';
    if (netEl)   netEl.textContent=net.toFixed(2)+' ج.م';
    if (fabEl)   fabEl.textContent=net.toFixed(0)+' ج.م';
    const hint=document.getElementById(`discount-hint-${tid}`);
    if (hint) { if (disc>0) { hint.textContent=`خصم ${disc.toFixed(2)} ج.م (${pct.toFixed(1)}%)`; hint.className='text-xs text-indigo-500 text-center'; } else { hint.textContent='لا يوجد خصم'; hint.className='text-xs text-gray-400 text-center'; } }
    calcChange(tid);
}
function calcChange(tid) {
    const tab=getTab(tid);
    const net=parseFloat(document.getElementById(`net-total-${tid}`)?.textContent)||0;
    const paid=parseFloat(document.getElementById(`paid-${tid}`)?.value)||0;
    const diff=paid-net;
    const el=document.getElementById(`change-${tid}`);
    const lbl=document.getElementById(`change-label-${tid}`);
    if (!el||!lbl) return;
    if (tab.selectedPayment==='deferred') {
        el.textContent=net.toFixed(2)+' ج.م'; el.className='font-bold text-red-500';
        lbl.textContent='المبلغ الآجل';
    } else {
        el.textContent=diff.toFixed(2)+' ج.م';
        el.className=`font-bold ${diff>=0?'text-green-600':'text-red-500'}`;
        lbl.textContent=diff>=0?'الباقي للعميل':'متبقي على العميل';
    }
}

/* =====================================================
   SAVE
   ===================================================== */
function buildPayload(tid, token) {
    const tab=getTab(tid);
    return {
        items: tab.cart.map(i=>({ id:i.id, qty:i.qty, qty_factor:i.qtyFactor, unit_key:i.unitKey, unit_name:i.unitName, unit_price:i.price })),
        discount: document.getElementById(`discount-${tid}`).value||0,
        customer_id: tab.selectedCustomer?.id||null,
        contract_id: tab.selectedPayment==='insurance'?(tab.selectedContract?.id||null):null,
        insured_patient_id: tab.selectedPayment==='insurance'?(tab.selectedPatient?.id||null):null,
        notes: document.getElementById(`notes-${tid}`).value,
        delivery_type: tab.selectedDelivery,
        delivery_address: document.getElementById(`delivery-address-${tid}`)?.value||null,
        delivery_phone: document.getElementById(`delivery-phone-${tid}`)?.value||null,
        _token: token,
    };
}

function resetForm(tid) {
    const tab=getTab(tid);
    clearCart(tid);
    clearCustomer(tid);
    tab.selectedCard=null;
    ['discount','discount-percent','paid','notes'].forEach(id=>{
        const el=document.getElementById(`${id}-${tid}`);
        if (el) el.value='';
    });
    ['delivery-address','delivery-phone'].forEach(id=>{
        const el=document.getElementById(`${id}-${tid}`);
        if (el) el.value='';
    });
    document.getElementById(`discount-hint-${tid}`).textContent='لا يوجد خصم';
    selectPayment(tid,'cash');
    selectDelivery(tid,'store');
    calcTotal(tid);
    updateTabLabel(tid,`فاتورة ${tid}`);
}

async function completeSale(tid) {
    const tab=getTab(tid);
    if (!tab.cart.length) { showToast('error','السلة فارغة','أضف منتجات للفاتورة أولاً'); return; }
    if (tab.selectedPayment==='deferred'&&!tab.selectedCustomer) { showToast('error','العميل مطلوب','يجب اختيار عميل للبيع الآجل'); return; }
    if (tab.selectedPayment==='card'&&!tab.selectedCard) { showToast('error','نوع البطاقة مطلوب','يجب اختيار نوع البطاقة'); return; }
    if (tab.selectedPayment==='insurance'&&!tab.selectedContract) { showToast('error','عقد التأمين مطلوب','يجب اختيار عقد التأمين'); return; }
    if (tab.selectedDelivery==='delivery'&&!document.getElementById(`delivery-address-${tid}`).value.trim()) { showToast('error','العنوان مطلوب','يجب إدخال عنوان التوصيل'); return; }
    const net=parseFloat(document.getElementById(`net-total-${tid}`).textContent)||0;
    const paidEl=document.getElementById(`paid-${tid}`);
    const paid=parseFloat(paidEl.value);
    if (tab.selectedPayment!=='deferred') {
        if (isNaN(paid)||paidEl.value.trim()==='') { showToast('error','المبلغ المدفوع مطلوب','ادخل المبلغ المدفوع أولاً'); paidEl.focus(); return; }
        if (paid<0) { showToast('error','مبلغ غير صحيح','المبلغ المدفوع لا يمكن أن يكون سالباً'); paidEl.focus(); return; }
    }
    const token=document.querySelector('meta[name=csrf-token]').content;
    const btns=[`sell-btn-${tid}`];
    btns.forEach(id=>{ const b=document.getElementById(id); if(b){b.disabled=true;b.innerHTML='<i class="fas fa-spinner fa-spin ml-2"></i> جاري...';} });
    try {
        const d=await (await fetch('/sales',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({...buildPayload(tid,token),paid:tab.selectedPayment==='deferred'?0:(paid||0),payment_method:tab.selectedPayment,card_type:tab.selectedCard})})).json();
        if (d.success) { showToast('success','تمت عملية البيع!',d.message); resetForm(tid); }
        else showError(d.message||'حدث خطأ غير متوقع');
    } catch(e) { showError('تأكد من الاتصال وحاول مرة أخرى'); }
    finally { btns.forEach(id=>{ const b=document.getElementById(id); if(b){b.disabled=false;b.innerHTML='<i class="fas fa-check-circle"></i> إتمام البيع مباشرة';} }); }
}

async function savePending(tid) {
    const tab=getTab(tid);
    if (!tab.cart.length) { showToast('error','السلة فارغة','أضف منتجات أولاً'); return; }
    if (tab.selectedDelivery==='delivery'&&!document.getElementById(`delivery-address-${tid}`).value.trim()) { showToast('error','العنوان مطلوب','يجب إدخال عنوان التوصيل'); return; }
    const token=document.querySelector('meta[name=csrf-token]').content;
    try {
        const d=await (await fetch('/pending-orders',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(buildPayload(tid,token))})).json();
        if (d.success) { showToast('pending','تم حفظ الطلب!',d.message,true); resetForm(tid); }
        else showError(d.message||'حدث خطأ غير متوقع');
    } catch(e) { showError('تأكد من الاتصال وحاول مرة أخرى'); }
}

/* =====================================================
   INIT — فاتورة أولى تلقائية
   ===================================================== */
document.addEventListener('DOMContentLoaded', () => {
    addTab();  // ابدأ بتبويب واحد
});
</script>
@endsection