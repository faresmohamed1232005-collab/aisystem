@extends(auth()->check() ? 'layouts.app' : 'layouts.diagnostics')

@section('title', 'مركز التشخيص والإعدادات')

@section('styles')
<style>
    .diag-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; box-shadow:0 8px 24px rgba(15,23,42,.05); }
    .diag-label { color:#64748b; font-size:.78rem; }
    .diag-value { color:#0f172a; font-weight:800; }
    .status-dot { width:.65rem; height:.65rem; border-radius:999px; display:inline-block; }
    .status-healthy,.status-success,.status-online,.status-registered,.status-completed,.status-downloaded { background:#10b981; color:#047857; }
    .status-warning,.status-offline,.status-failed,.status-unhealthy,.status-missing,.status-not_registered { background:#ef4444; color:#b91c1c; }
    .status-idle,.status-none,.status-not_applicable,.status-unknown { background:#94a3b8; color:#475569; }
    .status-syncing,.status-downloading,.status-checking,.status-running { background:#6366f1; color:#4338ca; }
</style>
@endsection

@section('content')
@php
    $db = $report['database'] ?? [];
    $sync = $report['sync'] ?? [];
    $registration = $report['registration'] ?? [];
    $runtime = $sync['runtime'] ?? [];
    $progressRows = $sync['table_progress'] ?? [];
    $pendingTables = $sync['pending_push']['tables'] ?? [];
    $updateProgress = $update['progress'] ?? [];
    $labels = [
        'healthy'=>'سليم','unhealthy'=>'يحتاج تدخلاً','warning'=>'تحذير','failed'=>'فشل','missing'=>'مفقود',
        'registered'=>'مسجّل','not_registered'=>'غير مسجّل','unknown'=>'غير معروف','online'=>'متصل','offline'=>'غير متصل',
        'idle'=>'خامل','syncing'=>'تجري المزامنة','success'=>'نجاح','none'=>'لا يوجد','checking'=>'جارٍ الفحص',
        'available'=>'متاح','downloading'=>'جارٍ التنزيل','downloaded'=>'جاهز للتثبيت','cancelled'=>'ملغي',
        'not_applicable'=>'غير منطبق','running'=>'جارٍ','completed'=>'مكتمل','pending'=>'معلّق',
    ];
    $statusLabel = fn ($status) => $labels[$status] ?? ($status ?: 'غير معروف');
@endphp

<div class="space-y-5" id="diagnostics-root">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-900">مركز التشخيص والإعدادات</h2>
            <p class="mt-1 text-sm text-slate-500">حالة محلية للقراءة فقط؛ لا تُرسل هذه الصفحة طلبات إلى السيرفر ولا تعرض مفاتيح أو كلمات مرور.</p>
        </div>
        <button type="button" onclick="location.reload()" class="self-start rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
            <i class="fas fa-rotate ml-1"></i> تحديث الحالة
        </button>
    </div>

    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm leading-7 text-amber-900">
        <strong><i class="fas fa-triangle-exclamation ml-1"></i> تنبيه واضح:</strong>
        لا يوجد مسح كامل هنا. إجراءات التعافي تنشئ نسخة SQLite موثوقة أولاً وتحافظ على صفوف العمل والعلامات المعلّقة.
    </div>

    @if (\App\Support\Runtime::isDesktop() && config('database.default') === 'sqlite' && \App\Support\Branch::isRegistered())
    <section class="diag-card p-5">
        <div class="mb-5">
            <h3 class="font-black text-slate-900"><i class="fas fa-screwdriver-wrench ml-2 text-indigo-500"></i>إصلاح وإعادة تهيئة التطبيق</h3>
            <p class="mt-1 text-sm text-slate-500">ثلاث درجات مستقلة. ابدأ دائماً بالإصلاح غير المدمر، ولا تستخدم إعادة ضبط المصنع إلا إذا أردت قاعدة محلية جديدة تماماً.</p>
        </div>
        <div class="grid gap-4 xl:grid-cols-3">
            <form method="POST" action="{{ route('diagnostics.repair') }}" class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">@csrf
                <strong class="text-emerald-900">1. إصلاح المزامنة وحساب الدخول</strong>
                <p class="my-2 text-xs leading-6 text-slate-600">ينشئ Backup، ويتحقق من مالك الفرع، ويعيد تنزيل حساب الدخول ويكمل المزامنة. لا يحذف مبيعات أو مخزون أو موظفين.</p>
                <input name="owner_login" required autocomplete="username" placeholder="بريد أو اسم مستخدم مالك الويب" class="w-full rounded-lg border p-2 text-sm">
                <input name="owner_password" type="password" required autocomplete="current-password" placeholder="كلمة مرور مالك الويب" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <button class="mt-3 rounded-lg bg-emerald-700 px-3 py-2 text-sm font-bold text-white">إصلاح بدون حذف بيانات</button>
            </form>

            <form method="POST" action="{{ route('diagnostics.reconfigure') }}" class="rounded-xl border border-amber-200 bg-amber-50/50 p-4">@csrf
                <strong class="text-amber-900">2. إعادة إعداد الاتصال</strong>
                <p class="my-2 text-xs leading-6 text-slate-600">يحفظ بيانات العمل، ويمسح إعدادات الربط ومؤشرات السحب فقط، ثم يعيدك إلى شاشة Setup. يجب استخدام نفس الفرع والمالك.</p>
                <input name="owner_login" required autocomplete="username" placeholder="بريد أو اسم مستخدم مالك الويب" class="w-full rounded-lg border p-2 text-sm">
                <input name="owner_password" type="password" required autocomplete="current-password" placeholder="كلمة مرور مالك الويب" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <input name="confirmation" required placeholder="اكتب: {{ \App\Services\Diagnostics\RecoveryService::DISCONNECT_PHRASE }}" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <button onclick="return confirm('سيتم إنشاء Backup وفصل إعدادات الاتصال فقط. بيانات العمل ستبقى. متابعة؟')" class="mt-3 rounded-lg bg-amber-600 px-3 py-2 text-sm font-bold text-white">Backup ثم إعادة الإعداد</button>
            </form>

            <form method="POST" action="{{ route('diagnostics.factory-reset') }}" class="rounded-xl border border-red-300 bg-red-50/60 p-4">@csrf
                <strong class="text-red-900">3. إعادة ضبط المصنع</strong>
                <p class="my-2 text-xs font-bold leading-6 text-red-800">يجهّز قاعدة محلية جديدة عند إعادة فتح التطبيق. تُؤرشف القاعدة الحالية ويُنشأ Backup أولاً، لكن البيانات غير المرفوعة لن تظهر في القاعدة الجديدة.</p>
                <input name="owner_login" required autocomplete="username" placeholder="بريد أو اسم مستخدم مالك الويب" class="w-full rounded-lg border p-2 text-sm">
                <input name="owner_password" type="password" required autocomplete="current-password" placeholder="كلمة مرور مالك الويب" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <input name="confirmation" required placeholder="اكتب: {{ \App\Services\Diagnostics\RecoveryService::FACTORY_RESET_PHRASE }}" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <label class="mt-3 flex items-start gap-2 text-xs leading-5 text-red-900"><input type="checkbox" name="acknowledge_pending" value="1" required class="mt-1"> أفهم أن أي بيانات محلية غير مرفوعة قد لا تكون موجودة على السيرفر، وأن التطبيق سيبدأ Setup بعد إعادة فتحه.</label>
                <button onclick="return confirm('إجراء منفصل وخطير: سيتم أرشفة القاعدة الحالية وتجهيز قاعدة جديدة عند إعادة فتح التطبيق. هل أنت متأكد؟')" class="mt-3 rounded-lg bg-red-700 px-3 py-2 text-sm font-bold text-white">إنشاء Backup وتجهيز الضبط</button>
            </form>
        </div>
    </section>
    @endif

    @if (auth()->check() && \App\Support\Actor::isOwner())
    <section class="diag-card p-5">
        <h3 class="font-black text-slate-900">إجراءات المالك الآمنة</h3>
        <p class="mt-1 text-sm text-slate-500">هذه الأزرار للمالك فقط. إعادة محاولة جدول أو فصل الاتصال تتطلب كلمة المرور.</p>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('diagnostics.actions.sync') }}" class="rounded-xl border p-4">@csrf
                <strong>مزامنة يدوية</strong><p class="my-2 text-xs text-slate-500">تشغيل دورة الرفع ثم السحب المعتادة الآن.</p>
                <button class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-bold text-white">تشغيل المزامنة</button>
            </form>
            <form method="POST" action="{{ route('diagnostics.actions.backup') }}" class="rounded-xl border p-4">@csrf
                <strong>نسخة احتياطية محلية</strong><p class="my-2 text-xs text-slate-500">لقطة WAL-safe مع quick_check وبصمة SHA256.</p>
                <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white">إنشاء نسخة موثوقة</button>
            </form>
            <form method="POST" action="{{ route('diagnostics.actions.retry') }}" class="rounded-xl border p-4">@csrf
                <strong>إعادة محاولة جدول سحب واحد</strong>
                <p class="my-2 text-xs text-slate-500">يمسح مؤشر السحب والتقدم الأولي لهذا الجدول فقط، ولا يغيّر synced_at أو الصفوف المعلّقة.</p>
                <select name="table" required class="w-full rounded-lg border p-2 text-sm">@foreach($pullableTables as $table)<option value="{{ $table }}">{{ $table }}</option>@endforeach</select>
                <input name="password" type="password" required placeholder="كلمة مرور المالك" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <button onclick="return confirm('سيتم إنشاء نسخة احتياطية ثم إعادة مؤشر هذا الجدول فقط. متابعة؟')" class="mt-2 rounded-lg bg-amber-600 px-3 py-2 text-sm font-bold text-white">نسخة ثم إعادة المحاولة</button>
            </form>
            @if (\App\Support\Runtime::isDesktop() && config('database.default') === 'sqlite')
            <form method="POST" action="{{ route('diagnostics.actions.disconnect') }}" class="rounded-xl border border-red-200 p-4">@csrf
                <strong class="text-red-800">فصل الاتصال وإعادة الإعداد</strong>
                <p class="my-2 text-xs text-slate-500">يحفظ بيانات العمل، ويمسح مفاتيح الاتصال والتقدم الأولي فقط. لا يمسح قاعدة البيانات.</p>
                <input name="password" type="password" required placeholder="كلمة مرور المالك" class="w-full rounded-lg border p-2 text-sm">
                <input name="confirmation" required placeholder="اكتب: {{ \App\Services\Diagnostics\RecoveryService::DISCONNECT_PHRASE }}" class="mt-2 w-full rounded-lg border p-2 text-sm">
                <button onclick="return confirm('سيتم فصل الاتصال فقط مع الاحتفاظ ببيانات العمل. متابعة؟')" class="mt-2 rounded-lg bg-red-700 px-3 py-2 text-sm font-bold text-white">فصل آمن وإعادة الإعداد</button>
            </form>
            @endif
        </div>
    </section>
    @endif

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['قاعدة البيانات', $db['status'] ?? 'unknown', 'fa-database'],
            ['تسجيل الفرع', $registration['status'] ?? 'unknown', 'fa-code-branch'],
            ['المزامنة', $sync['status'] ?? 'unknown', 'fa-arrows-rotate'],
            ['التحديث', $update['status'] ?? 'none', 'fa-download'],
        ] as [$title, $status, $icon])
            <div class="diag-card p-5">
                <div class="flex items-center justify-between"><i class="fas {{ $icon }} text-indigo-500"></i><span class="status-dot status-{{ $status }}"></span></div>
                <div class="mt-4 diag-label">{{ $title }}</div>
                <div class="diag-value mt-1">{{ $statusLabel($status) }}</div>
            </div>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-2">
        <div class="diag-card p-5">
            <h3 class="font-black text-slate-900"><i class="fas fa-code-branch ml-2 text-indigo-500"></i>التسجيل والتشغيل</h3>
            <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div><dt class="diag-label">بيئة التشغيل</dt><dd class="diag-value">{{ ($report['application']['runtime'] ?? '') === 'desktop' ? 'سطح المكتب' : 'الويب' }}</dd></div>
                <div><dt class="diag-label">الإصدار</dt><dd class="diag-value">{{ $report['application']['version'] ?? 'غير معروف' }}</dd></div>
                <div><dt class="diag-label">كود الفرع</dt><dd class="diag-value">{{ $registration['branch_code'] ?: 'غير محدد' }}</dd></div>
                <div><dt class="diag-label">معرّف الفرع</dt><dd class="diag-value break-all">{{ $registration['branch_id'] ?: 'غير محدد' }}</dd></div>
                <div><dt class="diag-label">السيرفر مضبوط</dt><dd class="diag-value">{{ !empty($registration['server_configured']) ? 'نعم' : 'لا' }}</dd></div>
                <div><dt class="diag-label">مفتاح المزامنة</dt><dd class="diag-value">{{ !empty($registration['token_present']) ? 'موجود ومحجوب' : 'غير موجود' }}</dd></div>
            </dl>
        </div>

        <div class="diag-card p-5">
            <h3 class="font-black text-slate-900"><i class="fas fa-database ml-2 text-indigo-500"></i>فحوص قاعدة البيانات</h3>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse (($db['checks'] ?? []) as $name => $check)
                    <div class="flex items-center justify-between gap-4 py-3 text-sm">
                        <span class="text-slate-600">{{ ['connection'=>'الاتصال','integrity'=>'سلامة الملف','foreign_keys'=>'العلاقات المرجعية','schema'=>'الجداول الأساسية','owner'=>'حساب المالك'][$name] ?? $name }}</span>
                        <span class="flex items-center gap-2 font-bold text-slate-800"><span class="status-dot status-{{ $check['status'] ?? 'unknown' }}"></span>{{ $statusLabel($check['status'] ?? 'unknown') }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-slate-500">تعذّر تحميل الفحوص.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="diag-card p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="font-black text-slate-900"><i class="fas fa-arrows-rotate ml-2 text-indigo-500"></i>تشغيل المزامنة</h3>
            <span class="text-xs text-slate-400">آخر محاولة: {{ $runtime['last_attempt'] ?? 'لا توجد' }}</span>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><div class="diag-label">الحالة</div><div class="diag-value">{{ $statusLabel($runtime['status'] ?? 'idle') }}</div></div>
            <div><div class="diag-label">آخر نجاح</div><div class="diag-value text-sm">{{ $runtime['last_success'] ?? 'لا يوجد' }}</div></div>
            <div><div class="diag-label">إخفاقات متتالية</div><div class="diag-value">{{ (int)($sync['consecutive_failures'] ?? 0) }}</div></div>
            <div><div class="diag-label">سجلات معلّقة للرفع</div><div class="diag-value">{{ number_format((int)($sync['pending_push']['total'] ?? 0)) }}</div></div>
        </div>
        @if (!empty($runtime['identity_conflict']) || !empty($runtime['message']))
            @php($identityConflict = (bool)($runtime['identity_conflict'] ?? false))
            <div class="mt-4 rounded-lg {{ $identityConflict ? 'bg-amber-50 text-amber-900' : 'bg-slate-50 text-slate-600' }} p-3 text-sm leading-7">
                @if ($identityConflict)
                    <strong>الاتصال بالسيرفر يعمل، لكن تنزيل حساب الدخول توقف لحماية البيانات المحلية.</strong>
                    <div>استخدم الخيار الأول «إصلاح المزامنة وحساب الدخول» بالأعلى؛ سيُنشئ Backup ويعيد ربط المالك دون حذف بيانات العمل.</div>
                @else
                    {{ $runtime['message'] }}
                @endif
            </div>
        @endif
        <p class="mt-3 text-xs leading-6 text-slate-500">«السجلات المعلّقة للرفع = 0» تعني فقط أنه لا توجد تغييرات محلية تنتظر الرفع؛ ولا تعني أن تنزيل البيانات من السيرفر اكتمل.</p>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
        <div class="diag-card overflow-hidden xl:col-span-2">
            <div class="border-b border-slate-100 p-5"><h3 class="font-black text-slate-900">تقدم الجداول</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-right text-sm">
                    <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">الجدول</th><th class="p-3">الاتجاه</th><th class="p-3">الحالة</th><th class="p-3">المعالج</th><th class="p-3">الناجح</th><th class="p-3">الفاشل</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($progressRows as $row)
                        <tr><td class="p-3 font-bold text-slate-800">{{ $row['table_name'] ?? '—' }}</td><td class="p-3 text-slate-600">{{ $row['direction'] ?? '—' }}</td><td class="p-3">{{ $statusLabel($row['status'] ?? 'unknown') }}</td><td class="p-3">{{ number_format((int)($row['processed_rows'] ?? 0)) }} / {{ number_format((int)($row['total_rows'] ?? 0)) }}</td><td class="p-3 text-emerald-700">{{ number_format((int)($row['succeeded_rows'] ?? 0)) }}</td><td class="p-3 text-red-700">{{ number_format((int)($row['failed_rows'] ?? 0)) }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-slate-500">لا تتوفر بيانات تقدم بعد. هذا طبيعي قبل تشغيل migration أو قبل أول مزامنة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="diag-card p-5">
            <h3 class="font-black text-slate-900">المعلّق حسب الجدول</h3>
            <div class="mt-4 space-y-3">
                @forelse ($pendingTables as $table => $count)
                    <div class="flex justify-between rounded-lg bg-slate-50 p-3 text-sm"><span class="text-slate-600">{{ $table }}</span><strong class="text-slate-900">{{ number_format($count) }}</strong></div>
                @empty
                    <p class="text-sm leading-7 text-slate-500">لا توجد سجلات معلّقة، أو أن جداول المزامنة غير متاحة بعد.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="diag-card p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div><h3 class="font-black text-slate-900">تقدم تحديث التطبيق</h3><p class="mt-1 text-xs text-slate-500">الحالة: {{ ($update['status'] ?? 'none') === 'none' ? 'لم يتم فحص تحديث بعد' : $statusLabel($update['status']) }}</p></div>
            <div class="flex items-center gap-2">
                @if (auth()->check() && \App\Support\Actor::isOwner() && \App\Support\Runtime::isDesktop())
                    <button type="button" onclick="runUpdateAction('{{ route('update.check') }}')" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-50">فحص الآن</button>
                    @if (($update['status'] ?? 'none') === 'available')
                        <button type="button" onclick="runUpdateAction('{{ route('update.download') }}')" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white">تنزيل التحديث</button>
                    @elseif (($update['status'] ?? 'none') === 'downloaded')
                        <button type="button" onclick="runUpdateAction('{{ route('update.install') }}')" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white">تثبيت وإعادة التشغيل</button>
                    @endif
                @endif
                <span class="text-sm font-bold text-indigo-700">{{ number_format((float)($updateProgress['percent'] ?? 0), 1) }}%</span>
            </div>
        </div>
        @if (($update['status'] ?? 'none') !== 'none')
            <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600" style="width:{{ min(100, max(0, (float)($updateProgress['percent'] ?? 0))) }}%"></div></div>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-500"><span>الإصدار: {{ $update['version'] ?? 'غير محدد' }}</span><span>تم: {{ number_format((int)($updateProgress['transferred_bytes'] ?? 0)) }} بايت</span><span>الإجمالي: {{ number_format((int)($updateProgress['total_bytes'] ?? 0)) }} بايت</span><span>السرعة: {{ number_format((int)($updateProgress['bytes_per_second'] ?? 0)) }} بايت/ث</span></div>
        @else
            <p class="mt-4 rounded-lg bg-slate-50 p-3 text-sm text-slate-500">اضغط «فحص الآن» من حساب المالك لمعرفة إن كان هناك إصدار جديد.</p>
        @endif
        @if (!empty($update['error']))<p class="mt-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">تعذّر إكمال عملية التحديث. أعد الفحص أو راجع سجل التطبيق.</p>@endif
    </section>

    <section class="diag-card overflow-hidden">
        <div class="border-b border-slate-100 p-5"><h3 class="font-black text-slate-900">آخر سجلات المزامنة الآمنة</h3><p class="mt-1 text-xs text-slate-500">الرسائل منقّحة ولا تعرض بيانات اعتماد.</p></div>
        <div class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <div class="grid gap-2 p-4 text-sm sm:grid-cols-[100px_90px_90px_1fr_150px]">
                    <strong class="text-slate-800">{{ $log['direction'] ?: '—' }}</strong><span>{{ $statusLabel($log['status']) }}</span><span>{{ number_format($log['rows']) }} صف</span><span class="break-words text-slate-600">{{ $log['message'] ?: 'بلا رسالة' }}</span><span class="text-xs text-slate-400">{{ $log['created_at'] ?: '—' }}</span>
                </div>
            @empty
                <p class="p-8 text-center text-sm text-slate-500">لا توجد سجلات مزامنة بعد، أو أن جدول السجل لم يُنشأ بعد.</p>
            @endforelse
        </div>
    </section>

    <p class="text-center text-xs text-slate-400">تم إنشاء التقرير محلياً في {{ $report['generated_at'] ?? '—' }}</p>
</div>
@endsection

@section('scripts')
<script>
async function runUpdateAction(url) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'تعذّر تنفيذ العملية');
        if (window.syncNotify) syncNotify(true, data.message || 'تم بدء العملية');
        setTimeout(() => location.reload(), 800);
    } catch (error) {
        if (window.syncNotify) syncNotify(false, error.message || 'تعذّر تنفيذ عملية التحديث');
        else alert(error.message || 'تعذّر تنفيذ عملية التحديث');
    }
}
</script>
@endsection
