@extends('layouts.app')
@section('title', 'مرتجع شراء جديد')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto" dir="rtl">

    <div class="flex items-center gap-3">
        <a href="{{ route('purchase-returns.index') }}" class="text-gray-400 hover:text-gray-600 transition">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h2 class="text-xl font-bold text-gray-800">مرتجع شراء جديد</h2>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-700 text-sm space-y-1">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
    @endif

    <form action="{{ route('purchase-returns.store') }}" method="POST" id="returnForm">
        @csrf

        {{-- Step 1: اختيار الفاتورة --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-4">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center font-bold">1</span>
                اختر فاتورة الشراء
            </h3>

            {{-- ── فلاتر الفواتير ── --}}
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                <p class="text-xs font-bold text-gray-500 mb-3">🔍 فلترة الفواتير</p>
                <div class="flex flex-wrap gap-3">
                    {{-- بحث باسم المورد --}}
                    <div class="flex-1 min-w-44">
                        <label class="text-xs text-gray-500 mb-1 block">اسم المورد</label>
                        <input type="text" id="filterSupplier"
                            placeholder="اكتب اسم المورد..."
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-400 text-right"
                            oninput="filterInvoices()">
                    </div>
                    {{-- من تاريخ --}}
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">من تاريخ</label>
                        <input type="date" id="filterDateFrom"
                            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                            onchange="filterInvoices()">
                    </div>
                    {{-- إلى تاريخ --}}
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">إلى تاريخ</label>
                        <input type="date" id="filterDateTo"
                            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-400"
                            onchange="filterInvoices()">
                    </div>
                    {{-- زر مسح --}}
                    <div class="flex items-end">
                        <button type="button" onclick="clearFilters()"
                            class="border border-gray-200 bg-white hover:bg-gray-50 text-gray-500 px-3 py-2 rounded-xl text-sm transition">
                            ✕ مسح
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2" id="filterCount"></p>
            </div>

            {{-- ── بحث مباشر + dropdown ── --}}
            <div class="flex gap-3 flex-wrap">
                <div class="flex-1 min-w-60">
                    <label class="text-xs text-gray-500 mb-1 block">رقم الفاتورة أو ID</label>
                    <input type="text" id="invSearch"
                        placeholder="اكتب رقم الفاتورة واضغط بحث..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right"
                        value="{{ old('purchase_invoice_id', $invoice?->invoice_number) }}">
                </div>
                <button type="button" onclick="searchInvoice()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition mt-5">
                    بحث
                </button>
                <div class="flex-1 min-w-60">
                    <label class="text-xs text-gray-500 mb-1 block">أو اختر من القائمة</label>
                    <select id="invoiceSelect" onchange="loadInvById(this.value)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right">
                        <option value="">— اختر فاتورة —</option>
                        @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}"
                            data-supplier="{{ strtolower($inv->supplier?->name ?? '') }}"
                            data-date="{{ optional($inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date) : $inv->created_at)->format('Y-m-d') }}"
                            {{ $invoice?->id == $inv->id ? 'selected' : '' }}>
                            {{ $inv->invoice_number }} — {{ $inv->supplier?->name ?? 'بدون مورد' }} — {{ optional($inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date) : $inv->created_at)->format('d/m/Y') }} — {{ number_format($inv->net_total,0) }} ج.م
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="invInfo" class="hidden mt-4 p-4 bg-blue-50 rounded-xl grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div><span class="text-gray-400 text-xs">رقم الفاتورة</span><div class="font-bold" id="ii-num">—</div></div>
                <div><span class="text-gray-400 text-xs">المورد</span><div class="font-bold" id="ii-sup">—</div></div>
                <div><span class="text-gray-400 text-xs">إجمالي الفاتورة</span><div class="font-bold text-blue-600" id="ii-total">—</div></div>
                <div><span class="text-gray-400 text-xs">تاريخ الفاتورة</span><div class="font-bold" id="ii-date">—</div></div>
            </div>
            <div id="invError" class="hidden mt-3 p-3 bg-red-50 text-red-600 rounded-xl text-sm"></div>
            <input type="hidden" name="purchase_invoice_id" id="invIdInput" value="{{ old('purchase_invoice_id', $invoice?->id) }}">
        </div>

        {{-- Step 2: الأصناف --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-4" id="itemsSection" style="{{ $invoice ? '' : 'display:none' }}">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center font-bold">2</span>
                اختر الأصناف والوحدات المراد إرجاعها للمورد
            </h3>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 text-xs text-blue-700">
                <i class="fas fa-info-circle ml-1"></i>
                ممكن ترجع بأي وحدة — اشتريت علبة؟ ترجع شريط أو حبة والسعر هيتحسب تلقائي
            </div>
            <div id="itemsCards" class="space-y-4"></div>
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-xl p-4 flex justify-between items-center">
                <span class="font-bold text-gray-700">إجمالي المرتجع</span>
                <span class="text-xl font-bold text-blue-600" id="returnTotal">0.00 ج.م</span>
            </div>
            <div id="stockPreview" class="hidden mt-3 bg-red-50 border border-red-200 rounded-xl p-3">
                <p class="text-xs font-bold text-red-700 mb-2"><i class="fas fa-boxes ml-1"></i>سيُخصم من المخزن (إرجاع للمورد)</p>
                <div id="stockPreviewItems" class="space-y-1 text-xs text-red-600"></div>
            </div>
        </div>

        {{-- Step 3: بيانات --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-4" id="detailsSection" style="{{ $invoice ? '' : 'display:none' }}">
            <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                <span class="w-6 h-6 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center font-bold">3</span>
                بيانات الاسترداد
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الاسترداد <span class="text-red-500">*</span></label>
                    <select name="refund_method" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right">
                        <option value="balance" {{ old('refund_method','balance')=='balance' ? 'selected' : '' }}>💳 خصم من رصيد المورد</option>
                        <option value="cash"    {{ old('refund_method')=='cash'    ? 'selected' : '' }}>💵 رد نقدي من المورد</option>
                        <option value="none"    {{ old('refund_method')=='none'    ? 'selected' : '' }}>🚫 بدون استرداد</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">سبب الإرجاع</label>
                    <input type="text" name="reason" value="{{ old('reason') }}"
                        placeholder="مثال: منتج تالف، خطأ في الشحن..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                    <textarea name="notes" rows="2" placeholder="ملاحظات إضافية..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 text-right resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div id="submitSection" style="{{ $invoice ? '' : 'display:none' }}">
            <button type="submit" onclick="return validateForm()"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-2xl font-bold text-base transition">
                ✅ حفظ مرتجع الشراء
            </button>
        </div>
    </form>
</div>

<script>
var currentItems  = [];
var selectedUnits = {};

var unitColors = {
    pack  : { active: 'border-blue-400 bg-blue-50 text-blue-700',      inactive: 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-blue-50'   },
    strip : { active: 'border-orange-400 bg-orange-50 text-orange-700', inactive: 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-orange-50' },
    piece : { active: 'border-green-400 bg-green-50 text-green-700',    inactive: 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-green-50'  },
};
var unitIcons = { pack: 'fa-box', strip: 'fa-layer-group', piece: 'fa-circle' };

/* ══════ فلترة القائمة المنسدلة ══════ */
function filterInvoices() {
    var supplier = document.getElementById('filterSupplier').value.trim().toLowerCase();
    var dateFrom = document.getElementById('filterDateFrom').value;
    var dateTo   = document.getElementById('filterDateTo').value;
    var select   = document.getElementById('invoiceSelect');
    var options  = select.querySelectorAll('option');
    var visible  = 0;

    options.forEach(function(opt) {
        if (!opt.value) return;
        var optSupplier = opt.getAttribute('data-supplier') || '';
        var optDate     = opt.getAttribute('data-date') || '';

        var matchSupplier = !supplier || optSupplier.includes(supplier);
        var matchFrom     = !dateFrom || optDate >= dateFrom;
        var matchTo       = !dateTo   || optDate <= dateTo;

        var show = matchSupplier && matchFrom && matchTo;
        opt.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    var count = document.getElementById('filterCount');
    if (supplier || dateFrom || dateTo) {
        count.textContent = 'تم عرض ' + visible + ' فاتورة من أصل ' + (options.length - 1);
    } else {
        count.textContent = '';
    }

    // لو الـ option المختارة اتخفت — امسح الاختيار
    if (select.value && select.options[select.selectedIndex]?.style.display === 'none') {
        select.value = '';
    }
}

function clearFilters() {
    document.getElementById('filterSupplier').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value   = '';
    filterInvoices();
}

/* ══════ جلب الفاتورة ══════ */
function loadInvById(id) { if (!id) return; document.getElementById('invSearch').value = id; fetchInvoice(id); }
function searchInvoice() { var q = document.getElementById('invSearch').value.trim(); if (!q) return; fetchInvoice(q); }

function fetchInvoice(query) {
    fetch('/purchase-returns/fetch-invoice?invoice_id=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            document.getElementById('invError').textContent = data.error;
            document.getElementById('invError').classList.remove('hidden');
            document.getElementById('invInfo').classList.add('hidden');
            hideSections(); return;
        }
        document.getElementById('invError').classList.add('hidden');
        document.getElementById('ii-num').textContent   = data.invoice.invoice_number;
        document.getElementById('ii-sup').textContent   = data.invoice.supplier;
        document.getElementById('ii-total').textContent = Number(data.invoice.net_total).toLocaleString('ar-EG') + ' ج.م';
        document.getElementById('ii-date').textContent  = data.invoice.invoice_date;
        document.getElementById('invInfo').classList.remove('hidden');
        document.getElementById('invIdInput').value     = data.invoice.id;
        currentItems  = data.items;
        selectedUnits = {};
        buildItemsCards(data.items);
        showSections();
    })
    .catch(() => {
        document.getElementById('invError').textContent = 'حدث خطأ في الاتصال';
        document.getElementById('invError').classList.remove('hidden');
    });
}

/* ══════ بناء الكروت ══════ */
function buildItemsCards(items) {
    var container = document.getElementById('itemsCards');
    container.innerHTML = '';

    items.forEach(function(item, idx) {
        var canReturn = item.max_return > 0;

        if (canReturn && item.available_units && item.available_units.length > 0) {
            var first = item.available_units[0];
            selectedUnits[idx] = { key: first.key, name: first.name, qty_factor: first.qty_factor, price: first.price, max: first.max };
        }

        var card = document.createElement('div');
        card.className = 'bg-gray-50 rounded-2xl border border-gray-200 p-4';
        card.id = 'card-' + idx;

        var html = '<div class="flex items-start justify-between mb-3">';
        html += '<div><div class="font-bold text-gray-800">' + item.product_name + '</div>';
        if (item.category) html += '<div class="text-xs text-indigo-400 mt-0.5">' + item.category + '</div>';
        html += '<div class="text-xs text-gray-400 mt-0.5">';
        html += 'اشتريت: <span class="font-semibold">' + item.quantity + ' علبة</span>';
        if (item.major_units > 1) html += ' = <span class="font-semibold">' + (item.quantity * item.major_units) + ' ' + item.strip_name + '</span>';
        if (item.already_returned > 0) html += ' — مُرجع: <span class="font-semibold text-red-400">' + item.already_returned + '</span>';
        html += '</div></div>';
        if (!canReturn) html += '<span class="text-xs text-red-400 bg-red-50 px-2 py-1 rounded-lg">مكتمل الإرجاع</span>';
        html += '</div>';

        if (canReturn && item.available_units && item.available_units.length > 0) {
            html += '<div class="mb-3"><div class="text-xs text-gray-500 mb-2 font-medium">اختر وحدة الإرجاع:</div><div class="flex gap-2">';
            item.available_units.forEach(function(unit, ui) {
                var isActive = ui === 0;
                var colors   = unitColors[unit.key] || unitColors.pack;
                var icon     = unitIcons[unit.key]  || 'fa-box';
                html += '<button type="button" id="unit-btn-' + idx + '-' + unit.key + '" ';
                html += 'onclick="selectUnit(' + idx + ', \'' + unit.key + '\', \'' + unit.name + '\', ' + unit.qty_factor + ', ' + unit.price + ', ' + unit.max + ')" ';
                html += 'class="flex-1 text-xs py-2.5 px-2 rounded-xl border-2 font-semibold transition ' + (isActive ? colors.active : colors.inactive) + '">';
                html += '<i class="fas ' + icon + ' block mb-0.5"></i>' + unit.name + '<br>';
                html += '<span class="font-bold">' + parseFloat(unit.price).toFixed(2) + ' ج.م</span><br>';
                html += '<span class="opacity-70">أقصى: ' + unit.max + '</span></button>';
            });
            html += '</div></div>';
        }

        if (canReturn) {
            var firstUnit = item.available_units?.[0];
            html += '<div class="flex items-center gap-3">';
            html += '<label class="text-xs text-gray-500 font-medium">كمية الإرجاع:</label>';
            html += '<div class="flex items-center gap-2">';
            html += '<button type="button" onclick="changeQty(' + idx + ', -1)" class="w-8 h-8 bg-gray-200 hover:bg-gray-300 rounded-lg font-bold flex items-center justify-center">−</button>';
            html += '<input type="number" id="qty-' + idx + '" value="0" min="0" max="' + (firstUnit?.max ?? item.max_return) + '" ';
            html += 'class="w-16 border border-gray-200 bg-white rounded-lg px-2 py-1.5 text-center text-sm font-bold focus:outline-none focus:border-blue-400" oninput="calcTotal()">';
            html += '<button type="button" onclick="changeQty(' + idx + ', 1)" class="w-8 h-8 bg-blue-100 hover:bg-blue-200 rounded-lg font-bold text-blue-600 flex items-center justify-center">+</button>';
            html += '</div>';
            html += '<span id="unit-label-' + idx + '" class="text-xs text-gray-500">' + (firstUnit?.name ?? 'علبة') + '</span>';
            html += '<span class="text-sm font-bold text-blue-600" id="sub-' + idx + '">0.00 ج.م</span></div>';

            html += '<input type="hidden" name="items[' + idx + '][invoice_item_id]"    value="' + item.id + '">';
            html += '<input type="hidden" id="hidden-qty-' + idx + '"         name="items[' + idx + '][quantity]"          value="0">';
            html += '<input type="hidden" id="hidden-unit-key-' + idx + '"    name="items[' + idx + '][return_unit_key]"   value="' + (firstUnit?.key ?? 'pack') + '">';
            html += '<input type="hidden" id="hidden-unit-name-' + idx + '"   name="items[' + idx + '][return_unit_name]"  value="' + (firstUnit?.name ?? 'علبة') + '">';
            html += '<input type="hidden" id="hidden-qty-factor-' + idx + '"  name="items[' + idx + '][return_qty_factor]" value="' + (firstUnit?.qty_factor ?? 1) + '">';
            html += '<input type="hidden" id="hidden-unit-price-' + idx + '"  name="items[' + idx + '][return_unit_price]" value="' + (firstUnit?.price ?? item.purchase_price) + '">';
        }

        card.innerHTML = html;
        container.appendChild(card);
    });

    calcTotal();
}

/* ══════ اختيار الوحدة ══════ */
function selectUnit(idx, key, name, qtyFactor, price, max) {
    selectedUnits[idx] = { key, name, qty_factor: qtyFactor, price, max };

    var item = currentItems[idx];
    if (item && item.available_units) {
        item.available_units.forEach(function(unit) {
            var btn = document.getElementById('unit-btn-' + idx + '-' + unit.key);
            if (!btn) return;
            var colors = unitColors[unit.key] || unitColors.pack;
            btn.className = 'flex-1 text-xs py-2.5 px-2 rounded-xl border-2 font-semibold transition ' +
                (unit.key === key ? colors.active : colors.inactive);
        });
    }

    var qtyInput = document.getElementById('qty-' + idx);
    if (qtyInput) { qtyInput.max = max; if (parseInt(qtyInput.value) > max) qtyInput.value = max; }

    var lbl = document.getElementById('unit-label-' + idx);
    if (lbl) lbl.textContent = name;

    document.getElementById('hidden-unit-key-'   + idx).value = key;
    document.getElementById('hidden-unit-name-'  + idx).value = name;
    document.getElementById('hidden-qty-factor-' + idx).value = qtyFactor;
    document.getElementById('hidden-unit-price-' + idx).value = price;

    calcTotal();
}

/* ══════ تغيير الكمية ══════ */
function changeQty(idx, delta) {
    var input = document.getElementById('qty-' + idx);
    if (!input) return;
    var max = parseInt(input.max) || 9999;
    input.value = Math.max(0, Math.min(max, (parseInt(input.value) || 0) + delta));
    calcTotal();
}

/* ══════ حساب الإجمالي ══════ */
function calcTotal() {
    var total = 0, stockLines = [];

    currentItems.forEach(function(item, idx) {
        var qtyInput = document.getElementById('qty-' + idx);
        if (!qtyInput) return;

        var qty  = parseInt(qtyInput.value) || 0;
        var unit = selectedUnits[idx] || { price: item.purchase_price, qty_factor: 1, name: 'علبة' };
        var sub  = qty * unit.price;
        total   += sub;

        var hiddenQty = document.getElementById('hidden-qty-' + idx);
        if (hiddenQty) hiddenQty.value = qty;

        var subEl = document.getElementById('sub-' + idx);
        if (subEl) subEl.textContent = sub.toFixed(2) + ' ج.م';

        if (qty > 0) {
            stockLines.push(
                '<div class="flex justify-between"><span>- ' + item.product_name + '</span>' +
                '<span class="font-bold">' + qty + ' ' + unit.name + ' = ' + (qty * unit.qty_factor).toFixed(4) + ' علبة</span></div>'
            );
        }
    });

    document.getElementById('returnTotal').textContent = total.toFixed(2) + ' ج.م';

    var preview = document.getElementById('stockPreview');
    var previewItems = document.getElementById('stockPreviewItems');
    if (stockLines.length > 0) { previewItems.innerHTML = stockLines.join(''); preview.classList.remove('hidden'); }
    else { preview.classList.add('hidden'); }
}

/* ══════ التحقق ══════ */
function validateForm() {
    var hasItem = false;
    currentItems.forEach(function(_, idx) {
        var q = document.getElementById('qty-' + idx);
        if (q && parseInt(q.value) > 0) hasItem = true;
    });
    if (!hasItem) { alert('يجب إدخال كمية إرجاع لصنف واحد على الأقل'); return false; }
    currentItems.forEach(function(_, idx) {
        var q = document.getElementById('qty-' + idx);
        if (!q || parseInt(q.value) <= 0) {
            var h = document.querySelector('input[name="items[' + idx + '][invoice_item_id]"]');
            if (h) h.disabled = true;
        }
    });
    return true;
}

function showSections() { ['itemsSection','detailsSection','submitSection'].forEach(id => document.getElementById(id).style.display = ''); }
function hideSections()  { ['itemsSection','detailsSection','submitSection'].forEach(id => document.getElementById(id).style.display = 'none'); }

@if($invoice)
window.addEventListener('DOMContentLoaded', function() { fetchInvoice({{ $invoice->id }}); });
@endif
</script>
@endsection