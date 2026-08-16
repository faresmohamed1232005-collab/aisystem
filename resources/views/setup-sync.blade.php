<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>المزامنة الأولى — نظام إدارة الصيدلية</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: #0f172a;
        }
        .card {
            background: #fff; width: 100%; max-width: 560px; margin: 20px;
            border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.25); padding: 32px;
        }
        h1 { font-size: 22px; margin: 0 0 4px; color: #0f766e; }
        .sub { margin: 0 0 22px; color: #64748b; font-size: 14px; line-height: 1.7; }
        .alert { padding: 11px 14px; border-radius: 9px; font-size: 14px; margin-bottom: 16px; }
        .alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .progress-track { height: 12px; overflow: hidden; border-radius: 999px; background: #e2e8f0; }
        .progress-bar { width: 0; height: 100%; background: #0f766e; transition: width .2s ease; }
        .status { margin: 14px 0 6px; font-weight: 700; color: #334155; }
        .details { color: #64748b; font-size: 13px; min-height: 22px; }
        button, .continue {
            width: 100%; margin-top: 22px; padding: 13px; border: 0; border-radius: 9px;
            background: #0f766e; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer;
            text-align: center; text-decoration: none; display: block;
        }
        button:hover, .continue:hover { background: #115e59; }
        button:disabled { background: #94a3b8; cursor: wait; }
        .continue { display: none; }
        .diagnostics-link { display:block; margin-top:16px; text-align:center; color:#0f766e; font-size:13px; font-weight:700; text-decoration:none; }
        .table-list { margin-top:16px; max-height:280px; overflow:auto; border:1px solid #e2e8f0; border-radius:9px; }
        .table-row { display:grid; grid-template-columns:1fr auto; gap:10px; padding:9px 12px; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .table-row:last-child { border-bottom:0; }
        .table-error { grid-column:1/-1; color:#b91c1c; font-size:12px; }
        .muted { color:#64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>المزامنة الأولى — فرع {{ $branchCode }}</h1>
        <p class="sub">تم تسجيل الفرع. سيُنزل التطبيق البيانات على دفعات قصيرة حتى لا تتوقف الشاشة، ويمكن إعادة المحاولة بأمان إذا انقطع الاتصال.</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div id="error" class="alert alert-error" @if(!$manifestError) hidden @endif>{{ $manifestError }}</div>
        <div class="progress-track"><div id="progress" class="progress-bar"></div></div>
        <div id="status" class="status">جاهز لبدء تنزيل البيانات</div>
        <div id="details" class="details"></div>
        <div id="table-list" class="table-list"></div>

        <button id="start" type="button">بدء تنزيل البيانات</button>
        <a id="continue" class="continue" href="{{ url('/') }}">فتح التطبيق</a>
        <a class="diagnostics-link" href="{{ route('diagnostics.page') }}">فتح مركز التشخيص والإعدادات</a>

        {{-- مخرج فكّ القفل: لو الإعداد الأول غلط (رابط/توكن) والتنزيل بيفشل باستمرار، يمسح
             إعدادات الاتصال محلياً (بدون سيرفر ولا دخول) ويرجّع لشاشة الإعداد. البيانات تبقى. --}}
        <form method="POST" action="{{ route('setup.reset-connection') }}"
              onsubmit="return confirm('سيتم مسح إعدادات الاتصال (رابط السيرفر والمفتاح) والعودة لشاشة الإعداد. بياناتك المحلية تبقى وتُحفظ نسخة احتياطية. متابعة؟');">
            @csrf
            <input type="hidden" name="confirmation" value="{{ \App\Services\Diagnostics\RecoveryService::DISCONNECT_PHRASE }}">
            <button type="submit" style="background:#b91c1c; margin-top:10px;">الإعداد غير صحيح؟ إعادة ضبط الاتصال محلياً</button>
        </form>
    </div>

    <script>
        const tables = @json($tables);
        const progressRows = @json($progressRows);
        const labels = {
            users: 'حساب الصيدلية', drugs: 'كتالوج الأدوية', ads: 'الإعلانات',
            sub_users: 'حسابات الموظفين', branches: 'الفروع', contracts: 'التعاقدات',
            insurance_rules: 'قواعد التأمين', pricing_rules: 'قواعد التسعير',
            insured_patients: 'المرضى المؤمن عليهم', stock_transfers: 'تحويلات المخزون',
            stock_transfer_items: 'بنود التحويلات', branch_inventory_snapshots: 'أرصدة الفروع'
        };
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const start = document.getElementById('start');
        const status = document.getElementById('status');
        const details = document.getElementById('details');
        const progress = document.getElementById('progress');
        const error = document.getElementById('error');
        const continueLink = document.getElementById('continue');

        const rows = Object.fromEntries(progressRows.map(row => [row.table_name, row]));
        let ownerExists = @json($ownerExists);

        const isKnown = () => progressRows.some(row => row.manifest_id);
        const nextTable = () => tables.find(table => !rows[table] || rows[table].status !== 'completed');
        const downloadedCount = () => Object.values(rows).reduce((sum, row) => sum + Number(row.succeeded_rows || 0), 0);

        const render = () => {
            const all = tables.map(table => rows[table] || { table_name: table, status: 'pending', total_rows: 0, succeeded_rows: 0 });
            const known = isKnown();
            const total = all.reduce((sum, row) => sum + Number(row.total_rows || 0), 0);
            const done = all.reduce((sum, row) => sum + Math.min(Number(row.succeeded_rows || 0), Number(row.total_rows || 0)), 0);
            const terminal = all.every(row => row.status === 'completed');
            const percent = known ? (total === 0 ? (terminal ? 100 : 0) : Math.round(done * 100 / total)) : null;
            progress.style.width = (percent ?? 100) + '%';
            progress.style.opacity = percent === null ? '.45' : '1';
            details.textContent = known
                ? `تم تنزيل ${done.toLocaleString('ar-EG')} من ${total.toLocaleString('ar-EG')} سجل (${percent}٪).`
                : `تم تنزيل ${downloadedCount().toLocaleString('ar-EG')} سجل؛ الخادم القديم لا يوفّر إجماليًا مسبقًا.`;
            document.getElementById('table-list').innerHTML = all.map(row => {
                const count = known ? `${Number(row.succeeded_rows || 0).toLocaleString('ar-EG')} / ${Number(row.total_rows || 0).toLocaleString('ar-EG')}` : Number(row.succeeded_rows || 0).toLocaleString('ar-EG');
                const state = {pending:'معلّق', running:'جارٍ', failed:'فشل', completed:'مكتمل'}[row.status] || row.status;
                return `<div class="table-row"><span>${labels[row.table_name] || row.table_name}</span><span class="muted">${count} — ${state}</span>${row.last_error ? `<span class="table-error">${row.last_error}</span>` : ''}</div>`;
            }).join('');
            return terminal;
        };

        // يؤكّد الاكتمال سيرفر-سايد ويضبط علَم الدخول قبل إظهار «فتح التطبيق».
        // البوابة تمنع الدخول قبل ضبط هذا العلَم، فلا يدخل المستخدم بقاعدة نصف محمّلة.
        const finishSetup = async () => {
            try {
                const res = await fetch('{{ route('setup.sync.complete') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'تعذّر تأكيد اكتمال التنزيل.');
                }
                status.textContent = 'اكتملت المزامنة الأولى بنجاح';
                error.hidden = true;
                start.style.display = 'none';
                continueLink.style.display = 'block';
            } catch (e) {
                error.textContent = e.message + ' اضغط «إعادة المحاولة» لإكمال ما تبقّى.';
                error.hidden = false;
                status.textContent = 'لم يكتمل التنزيل بعد';
                start.textContent = 'إعادة المحاولة';
                start.disabled = false;
                start.style.display = 'block';
            }
        };

        const syncNext = async () => {
            const table = nextTable();
            if (!table) {
                const complete = render();
                if (!complete || !ownerExists) {
                    throw new Error(ownerExists ? 'بعض الجداول لم تصل إلى حالة مكتملة.' : 'اكتملت الجداول لكن حساب مالك الصيدلية غير موجود محلياً.');
                }
                await finishSetup();
                return;
            }
            status.textContent = 'جارٍ تنزيل: ' + (labels[table] || table);
            details.textContent = 'تم تنزيل ' + downloadedCount().toLocaleString('ar-EG') + ' سجل حتى الآن...';
            error.hidden = true;

            try {
                const response = await fetch('{{ route('setup.sync.step') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ table })
                });
                const result = await response.json().catch(() => ({}));

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'تعذّر إكمال التنزيل.');
                }

                ownerExists = Boolean(result.owner_exists);
                if (result.progress) rows[table] = result.progress;
                render();
                await syncNext();
            } catch (exception) {
                error.textContent = exception.message + ' اضغط «إعادة المحاولة» للمتابعة من آخر دفعة ناجحة.';
                error.hidden = false;
                status.textContent = 'توقفت المزامنة مؤقتاً';
                start.textContent = 'إعادة المحاولة';
                start.disabled = false;
                start.style.display = 'block';
            }
        };

        start.addEventListener('click', () => {
            start.disabled = true;
            start.textContent = 'جارٍ التنزيل...';
            syncNext();
        });

        const initiallyTerminal = render();
        if (initiallyTerminal && ownerExists) {
            finishSetup();
        } else {
            setTimeout(() => start.click(), 150);
        }
    </script>
</body>
</html>
