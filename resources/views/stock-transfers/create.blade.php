@extends('layouts.app')
@section('title', 'تحويل جديد')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('stock-transfers.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-right"></i></a>
        <h2 class="text-xl font-bold text-gray-800">تحويل مخزون جديد</h2>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-sm">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
    @endif

    @if($destinations->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-700 text-sm">
        <i class="fas fa-triangle-exclamation"></i> لا توجد فروع أخرى نشطة للتحويل إليها. تُضاف الفروع تلقائياً عند تسجيلها.
    </div>
    @endif

    <form action="{{ route('stock-transfers.store') }}" method="POST" id="transfer-form" class="space-y-6">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الفرع الوجهة *</label>
                <select name="to_branch_id" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right bg-white">
                    <option value="">— اختر الفرع —</option>
                    @foreach($destinations as $d)
                        <option value="{{ $d->branch_id }}">{{ $d->name ?? $d->code }} ({{ $d->code }})</option>
                    @endforeach
                </select>
            </div>

            {{-- بحث دواء --}}
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">أضف صنفاً من مخزونك</label>
                <i class="fas fa-search absolute right-3 top-[42px] text-gray-300 text-xs"></i>
                <input type="text" id="drug-search" autocomplete="off" placeholder="ابحث باسم الدواء..."
                       class="w-full border border-gray-200 rounded-xl pr-8 pl-3 py-2.5 text-sm text-right focus:outline-none focus:border-indigo-400"
                       oninput="searchDrugs(this.value)">
                <div id="drug-results" class="hidden absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden z-20 max-h-64 overflow-y-auto"></div>
            </div>
        </div>

        {{-- الأصناف المختارة --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b font-bold text-gray-800">أصناف التحويل</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-right">
                    <tr>
                        <th class="px-3 py-2 font-medium">الدواء</th>
                        <th class="px-3 py-2 font-medium">الصلاحية</th>
                        <th class="px-3 py-2 font-medium">المتاح</th>
                        <th class="px-3 py-2 font-medium">كمية التحويل</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody id="cart-body" class="divide-y divide-gray-50">
                    <tr id="cart-empty"><td colspan="5" class="text-center py-8 text-gray-300">لم تُضف أصناف بعد</td></tr>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-indigo-400 text-right"></textarea>
            </div>
            <button type="submit" id="submit-btn"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2 disabled:opacity-50">
                <i class="fas fa-paper-plane"></i> إنشاء وإرسال التحويل
            </button>
        </div>
    </form>
</div>

<script>
    let searchTimer = null;
    const cart = new Map(); // inventory_id => {name, expiry, available, qty}

    function searchDrugs(q) {
        clearTimeout(searchTimer);
        const box = document.getElementById('drug-results');
        if (q.length < 1) { box.classList.add('hidden'); return; }
        searchTimer = setTimeout(async () => {
            try {
                const drugs = await (await fetch(`/products/search?q=${encodeURIComponent(q)}`)).json();
                if (!drugs.length) { box.innerHTML = `<div class="p-4 text-center text-gray-400 text-sm">لا نتائج</div>`; }
                else {
                    box.innerHTML = drugs.slice(0, 12).map(d =>
                        `<div onclick="pickDrug(${d.id}, ${JSON.stringify((d.name_ar || d.name_en || '').replace(/"/g,'&quot;'))})"
                              class="p-2.5 hover:bg-indigo-50 cursor-pointer border-b border-gray-50 last:border-0 text-sm">
                            <span class="font-semibold text-gray-800">${d.name_ar || d.name_en}</span>
                        </div>`).join('');
                }
                box.classList.remove('hidden');
            } catch (e) { box.classList.add('hidden'); }
        }, 250);
    }

    async function pickDrug(drugId, name) {
        document.getElementById('drug-results').classList.add('hidden');
        document.getElementById('drug-search').value = '';
        try {
            const batches = await (await fetch(`/stock-transfers/drug-batches?drug_id=${drugId}`)).json();
            if (!batches.length) { await showAlternatives(drugId, name); return; }
            batches.forEach(b => addBatch(b, name));
        } catch (e) { alert('تعذّر جلب الباتشات'); }
    }

    // مفيش مخزون محلي → اعرض فروع أخرى عندها الصنف (من لقطات المخزون).
    async function showAlternatives(drugId, name) {
        let msg = `لا يوجد مخزون من "${name}" في فرعك.`;
        try {
            const alts = await (await fetch(`/stock-transfers/alternatives?drug_id=${drugId}`)).json();
            if (alts.length) {
                msg += `\n\nمتوفر في فروع أخرى:\n` + alts.map(a =>
                    `• ${a.branch_name || a.branch_code || a.snapshot_branch_id}: ${parseFloat(a.quantity)}`).join('\n');
            } else {
                msg += `\nولا يتوفر في أي فرع آخر.`;
            }
        } catch (e) {}
        alert(msg);
    }

    function addBatch(b, name) {
        if (cart.has(b.id)) return; // موجود بالفعل
        cart.set(b.id, { name, expiry: b.expiry_date || '—', available: parseFloat(b.quantity), qty: parseFloat(b.quantity) });
        renderCart();
    }

    function removeBatch(id) { cart.delete(id); renderCart(); }

    function setQty(id, val) {
        const row = cart.get(id); if (!row) return;
        let q = parseFloat(val) || 0;
        if (q > row.available) q = row.available;
        if (q < 0) q = 0;
        row.qty = q;
    }

    function renderCart() {
        const body = document.getElementById('cart-body');
        document.getElementById('submit-btn').disabled = cart.size === 0;
        if (cart.size === 0) {
            body.innerHTML = `<tr id="cart-empty"><td colspan="5" class="text-center py-8 text-gray-300">لم تُضف أصناف بعد</td></tr>`;
            return;
        }
        let i = 0, html = '';
        for (const [id, row] of cart) {
            html += `<tr>
                <td class="px-3 py-2 font-semibold text-gray-800">${row.name}
                    <input type="hidden" name="items[${i}][inventory_id]" value="${id}">
                </td>
                <td class="px-3 py-2 text-gray-500">${row.expiry}</td>
                <td class="px-3 py-2 text-gray-600">${row.available}</td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${i}][quantity]" value="${row.qty}" min="0.0001" max="${row.available}" step="any"
                           oninput="setQty(${id}, this.value)"
                           class="w-24 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:border-indigo-400">
                </td>
                <td class="px-3 py-2">
                    <button type="button" onclick="removeBatch(${id})" class="text-red-500 hover:text-red-700 text-xs px-2 py-1 bg-red-50 rounded-lg"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
            i++;
        }
        body.innerHTML = html;
    }
</script>
@endsection
