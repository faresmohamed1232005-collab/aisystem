@extends('layouts.app')

@section('title', '🔒 تقفيل درج الكاشير')

@section('content')

    @php
        $isSubUser = session()->has('sub_user');
        $isAdmin = !$isSubUser && Auth::check();
    @endphp

    <div class="max-w-5xl mx-auto space-y-8">

        {{-- ══════════════════════════════════════════ --}}
        {{--  فورم تقفيل الدرج --}}
        {{-- ══════════════════════════════════════════ --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-l from-slate-50 to-white px-6 py-5 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 bg-slate-800 rounded-lg flex items-center justify-center text-white text-sm">
                        <i class="fas fa-cash-register"></i>
                    </span>
                    تسجيل تقفيل درج الكاشير
                </h2>
                <p class="text-xs text-gray-500 mt-1 mr-10">يجب إدخال كلمة مرورك لتأكيد العملية</p>
            </div>

            {{-- رسالة نجاح --}}
            <div id="alert-success"
                class="{{ session('drawer_success') ? '' : 'hidden' }} mx-6 mt-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">
                <i class="fas fa-check-circle text-green-500 text-base"></i>
                <span class="font-semibold" id="alert-success-text">{{ session('drawer_success') }}</span>
            </div>

            {{-- رسالة خطأ --}}
            <div id="alert-error"
                class="hidden mx-6 mt-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                <i class="fas fa-times-circle text-red-500 text-base"></i>
                <span class="font-semibold" id="alert-error-text">كلمة المرور غير صحيحة ❌</span>
            </div>

            <form id="drawerForm" class="p-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- اسم المسجِّل --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-user text-indigo-400 ml-1"></i> اسم المسجِّل
                        </label>
                        <input type="text" name="locked_by_name" id="locked_by_name" value="{{ $currentUserName }}"
                            readonly
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none bg-indigo-50/50 font-semibold text-indigo-700 cursor-default">
                        <input type="hidden" name="locked_by_email" value="{{ $currentUserEmail }}">
                    </div>

                    {{-- اسم البائع --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-user-tie text-gray-400 ml-1"></i> اسم البائع <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="text" name="seller_name" id="seller_name" placeholder="اسم البائع أو الكاشير"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition">
                        <p id="err-seller" class="text-red-500 text-xs mt-1 hidden">اسم البائع مطلوب</p>
                    </div>

                    {{-- المبلغ المتوقع من النظام --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-calculator text-blue-400 ml-1"></i> المبلغ المتوقع (من النظام)
                            <span class="text-gray-400 font-normal text-xs">— يُحسب تلقائياً</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" name="expected_amount" id="expected_amount" value="0"
                                min="0" readonly oninput="calcDiff()"
                                class="w-full border border-dashed border-blue-300 rounded-xl px-4 py-2.5 text-sm bg-blue-50/40 font-bold text-blue-700 outline-none cursor-default"
                                dir="ltr">
                            <button type="button" onclick="fetchExpected()"
                                class="absolute left-2 top-1/2 -translate-y-1/2 w-7 h-7 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg flex items-center justify-center transition"
                                title="إعادة الحساب">
                                <i class="fas fa-rotate text-xs" id="fetch-icon"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1" id="expected-info">
                            <i class="fas fa-info-circle ml-0.5"></i> مجموع مبيعات اليوم النقدية تلقائياً
                        </p>
                    </div>

                    {{-- المبلغ الفعلي في الدرج --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-money-bill-wave text-green-400 ml-1"></i> المبلغ الفعلي في الدرج <span
                                class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" name="cash_amount" id="cash_amount" value="0"
                                min="0" oninput="calcDiff()"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-green-300 outline-none transition"
                                dir="ltr">
                        </div>
                        <p id="err-cash" class="text-red-500 text-xs mt-1 hidden">أدخل المبلغ الفعلي</p>
                    </div>

                    {{-- المبلغ المتروك في الدرج --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-vault text-amber-500 ml-1"></i> المبلغ المتروك في الدرج
                            <span class="text-gray-400 font-normal text-xs">— للوردية القادمة</span>
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" name="opening_amount" id="opening_amount" value="0"
                                min="0" oninput="calcDeposit()"
                                class="w-full border border-amber-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-300 outline-none transition bg-amber-50/30 font-semibold text-amber-700"
                                dir="ltr">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold">ج.م</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">المبلغ الذي ستتركه كرصيد افتتاحي</p>
                    </div>

                    {{-- المبلغ للإيداع --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-piggy-bank text-purple-400 ml-1"></i> المبلغ للإيداع
                            <span class="text-gray-400 font-normal text-xs">— الفعلي ناقص المتروك</span>
                        </label>
                        <div id="deposit_display"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm font-bold bg-purple-50/40 border-purple-200 text-purple-700"
                            dir="ltr">
                            0.00 ج.م
                        </div>
                    </div>

                    {{-- الفرق --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-scale-balanced text-orange-400 ml-1"></i> الفرق (فعلي - متوقع)
                        </label>
                        <div id="diff_display"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm font-bold bg-gray-50 border-gray-200 text-gray-500"
                            dir="ltr">
                            0.00
                        </div>
                    </div>

                    {{-- كلمة المرور --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-lock text-red-400 ml-1"></i>
                            كلمة مرور <span class="text-indigo-600 font-bold">{{ $currentUserName }}</span> للتأكيد
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="lock_password" placeholder="أدخل كلمة مرورك"
                                dir="ltr"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-300 outline-none transition">
                            <button type="button" onclick="togglePass('lock_password','eye_lock')"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition">
                                <i id="eye_lock" class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        <p id="err-pass" class="text-red-500 text-xs mt-1 hidden flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> كلمة المرور غير صحيحة
                        </p>
                    </div>

                    {{-- ملاحظات --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            <i class="fas fa-sticky-note text-yellow-400 ml-1"></i> ملاحظات
                        </label>
                        <textarea name="notes" id="notes" rows="2" placeholder="أي ملاحظات إضافية..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-300 outline-none transition resize-none"></textarea>
                    </div>
                </div>

                <div class="flex justify-end mt-6">
                    <button type="submit" id="submitBtn"
                        class="bg-slate-800 hover:bg-slate-900 text-white px-7 py-2.5 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed">
                        <i class="fas fa-lock" id="submitIcon"></i>
                        <span id="submitText">تسجيل التقفيل وحفظ العملية</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════ --}}
        {{--  جدول سجل العمليات — للأدمن فقط (كل user عادي وليس sub_user) --}}
        {{-- ══════════════════════════════════════════ --}}
        @if ($isAdmin)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                            <i class="fas fa-history text-sm"></i>
                        </span>
                        سجل عمليات تقفيل الدرج
                    </h2>
                    <span class="bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1 rounded-full">
                        {{ $locks->total() }} عملية
                    </span>
                </div>

                @if ($locks->isEmpty())
                    <div id="empty-msg" class="py-16 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3 block opacity-30"></i>
                        <p class="text-sm">لا توجد عمليات تقفيل مسجّلة بعد</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm" id="locks-table">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        #</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        التاريخ والوقت</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        المسجِّل</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        البائع</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        الفعلي</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        المتوقع</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        المتروك</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        للإيداع</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        الفرق</th>
                                    <th
                                        class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        ملاحظات</th>
                                    <th
                                        class="text-center px-4 py-3.5 text-xs font-semibold text-gray-500 whitespace-nowrap">
                                        حذف</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50" id="locks-tbody">
                                @foreach ($locks as $i => $lock)
                                    @include('drawer-lock._row', [
                                        'lock' => $lock,
                                        'index' => $locks->firstItem() + $i,
                                    ])
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($locks->hasPages())
                        <div class="px-6 py-4 border-t border-gray-100">
                            {{ $locks->links() }}
                        </div>
                    @endif
                @endif
            </div>
        @endif

    </div>

    {{-- ══════════════════════════════════════════ --}}
    {{--  مودال تأكيد الحذف --}}
    {{-- ══════════════════════════════════════════ --}}
    <div id="deleteModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 border border-gray-100">
            <div class="text-center mb-5">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-trash text-red-500 text-xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 text-base">تأكيد حذف العملية</h3>
                <p class="text-sm text-gray-500 mt-1">هل أنت متأكد؟ لا يمكن التراجع عن هذا الإجراء.</p>
            </div>
            <input type="hidden" id="deleteLockId">
            <div class="flex gap-3">
                <button id="confirmDeleteBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl text-sm font-bold transition flex items-center justify-center gap-2">
                    <i class="fas fa-trash"></i> نعم، احذف
                </button>
                <button onclick="closeDeleteModal()"
                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl text-sm font-bold transition">
                    إلغاء
                </button>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};

        async function fetchExpected() {
            const icon = document.getElementById('fetch-icon');
            icon.classList.add('fa-spin');
            try {
                const r = await fetch('{{ route('drawer-lock.expected') }}', {
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    }
                });
                const d = await r.json();
                document.getElementById('expected_amount').value = d.amount.toFixed(2);

                const info = document.getElementById('expected-info');
                if (info) {
                    // ✅ عرض تفصيلي: مبيعات − مرتجعات
                    let detail = '';
                    if (d.cash_returns > 0) {
                        detail = ` — مبيعات: <strong>${d.cash_sales.toFixed(2)}</strong> ج.م` +
                            ` − مرتجعات: <strong class="text-red-500">${d.cash_returns.toFixed(2)}</strong> ج.م`;
                    }

                    info.innerHTML = d.last_lock_time ?
                        `<i class="fas fa-clock ml-0.5 text-blue-400"></i> مبيعات نقدية منذ آخر تقفيل الساعة <strong>${d.last_lock_time}</strong>${detail}` :
                        `<i class="fas fa-info-circle ml-0.5"></i> مجموع مبيعات اليوم النقدية (لا يوجد تقفيل سابق اليوم)${detail}`;
                }

                calcDiff();
            } catch (e) {
                showError('تعذّر جلب المبلغ المتوقع، حاول مجدداً');
            } finally {
                icon.classList.remove('fa-spin');
            }
        }

        function calcDiff() {
            const cash = parseFloat(document.getElementById('cash_amount').value) || 0;
            const expected = parseFloat(document.getElementById('expected_amount').value) || 0;
            const diff = cash - expected;
            const el = document.getElementById('diff_display');

            el.textContent = (diff >= 0 ? '+' : '') + diff.toFixed(2) + ' ج.م';
            el.className = 'w-full border rounded-xl px-4 py-2.5 text-sm font-bold ';
            if (diff > 0) el.className += 'bg-green-50 border-green-200 text-green-700';
            else if (diff < 0) el.className += 'bg-red-50 border-red-200 text-red-600';
            else el.className += 'bg-gray-50 border-gray-200 text-gray-500';

            calcDeposit();
        }

        function calcDeposit() {
            const cash = parseFloat(document.getElementById('cash_amount').value) || 0;
            const opening = parseFloat(document.getElementById('opening_amount').value) || 0;
            document.getElementById('deposit_display').textContent = Math.max(0, cash - opening).toFixed(2) + ' ج.م';
        }

        function togglePass(inputId, eyeId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                eye.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function showError(msg) {
            const el = document.getElementById('alert-error');
            document.getElementById('alert-error-text').textContent = msg;
            el.classList.remove('hidden');
            document.getElementById('alert-success').classList.add('hidden');
            el.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function showSuccess(msg) {
            const el = document.getElementById('alert-success');
            document.getElementById('alert-success-text').textContent = msg;
            el.classList.remove('hidden');
            document.getElementById('alert-error').classList.add('hidden');
            el.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }

        function hideAlerts() {
            ['alert-error', 'alert-success', 'err-pass', 'err-seller', 'err-cash'].forEach(id => {
                document.getElementById(id)?.classList.add('hidden');
            });
        }

        document.getElementById('drawerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlerts();

            let valid = true;
            if (!document.getElementById('seller_name').value.trim()) {
                document.getElementById('err-seller').classList.remove('hidden');
                valid = false;
            }
            if (!document.getElementById('cash_amount').value || parseFloat(document.getElementById(
                    'cash_amount').value) < 0) {
                document.getElementById('err-cash').classList.remove('hidden');
                valid = false;
            }
            if (!document.getElementById('lock_password').value) {
                document.getElementById('err-pass').classList.remove('hidden');
                valid = false;
            }
            if (!valid) return;

            const formData = new FormData();
            formData.append('_token', CSRF);
            formData.append('locked_by_name', document.getElementById('locked_by_name').value);
            formData.append('locked_by_email', document.querySelector('[name=locked_by_email]').value);
            formData.append('seller_name', document.getElementById('seller_name').value);
            formData.append('cash_amount', document.getElementById('cash_amount').value);
            formData.append('expected_amount', document.getElementById('expected_amount').value);
            formData.append('opening_amount', document.getElementById('opening_amount').value);
            formData.append('password', document.getElementById('lock_password').value);
            formData.append('notes', document.getElementById('notes').value);

            const btn = document.getElementById('submitBtn');
            const icon = document.getElementById('submitIcon');
            const txt = document.getElementById('submitText');
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';
            txt.textContent = 'جارٍ الحفظ...';

            try {
                const response = await fetch('{{ route('drawer-lock.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    showSuccess(data.message ?? 'تم تسجيل تقفيل الدرج بنجاح ✅');
                    document.getElementById('seller_name').value = '';
                    document.getElementById('cash_amount').value = '0';
                    document.getElementById('opening_amount').value = '0';
                    document.getElementById('lock_password').value = '';
                    document.getElementById('notes').value = '';

                    await fetchExpected();

                    if (isAdmin && data.row) {
                        document.getElementById('empty-msg')?.remove();

                        let tbody = document.getElementById('locks-tbody');
                        if (!tbody) {
                            document.querySelector('.overflow-x-auto')?.remove();
                            const wrapper = document.createElement('div');
                            wrapper.className = 'overflow-x-auto';
                            wrapper.innerHTML = `
                        <table class="w-full text-sm" id="locks-table">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">#</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">التاريخ والوقت</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">المسجِّل</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">البائع</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">الفعلي</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">المتوقع</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">المتروك</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">للإيداع</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">الفرق</th>
                                    <th class="text-right px-4 py-3.5 text-xs font-semibold text-gray-500">ملاحظات</th>
                                    <th class="text-center px-4 py-3.5 text-xs font-semibold text-gray-500">حذف</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50" id="locks-tbody"></tbody>
                        </table>`;
                            document.querySelector('.bg-white.rounded-2xl:last-of-type').appendChild(wrapper);
                            tbody = document.getElementById('locks-tbody');
                        }
                        tbody.insertAdjacentHTML('afterbegin', data.row);
                    }
                } else if (response.status === 422) {
                    if (data.errors?.password) {
                        const errEl = document.getElementById('err-pass');
                        errEl.classList.remove('hidden');
                        errEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.errors.password[
                            0];
                        showError('❌ ' + data.errors.password[0]);
                        document.getElementById('lock_password').focus();
                        document.getElementById('lock_password').classList.add('border-red-400');
                    } else {
                        showError(Object.values(data.errors)[0][0]);
                    }
                } else {
                    showError(data.message ?? 'حدث خطأ غير متوقع، حاول مجدداً');
                }
            } catch (err) {
                showError('حدث خطأ في الاتصال، تحقق من الشبكة وحاول مجدداً');
            } finally {
                btn.disabled = false;
                icon.className = 'fas fa-lock';
                txt.textContent = 'تسجيل التقفيل وحفظ العملية';
            }
        });

        document.getElementById('lock_password').addEventListener('input', function() {
            this.classList.remove('border-red-400');
            document.getElementById('err-pass').classList.add('hidden');
            document.getElementById('alert-error').classList.add('hidden');
        });

        function openDeleteModal(id) {
            document.getElementById('deleteLockId').value = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            const id = document.getElementById('deleteLockId').value;
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جارٍ الحذف...';

            try {
                const res = await fetch(`/drawer-lock/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    const row = document.getElementById(`lock-row-${id}`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s, transform 0.3s';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => row.remove(), 300);
                    }
                    closeDeleteModal();
                    showSuccess('تم حذف العملية بنجاح ✅');
                    await fetchExpected();
                } else {
                    closeDeleteModal();
                    showError(data.message ?? 'حدث خطأ أثناء الحذف');
                }
            } catch (e) {
                closeDeleteModal();
                showError('حدث خطأ في الاتصال');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i> نعم، احذف';
            }
        });

        window.addEventListener('load', fetchExpected);
    </script>
@endsection
