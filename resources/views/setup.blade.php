<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إعداد الفرع — نظام إدارة الصيدلية</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: #0f172a;
        }
        .card {
            background: #fff; width: 100%; max-width: 460px; margin: 20px;
            border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.25); padding: 32px;
        }
        .card h1 { font-size: 22px; margin: 0 0 4px; color: #0f766e; }
        .card p.sub { margin: 0 0 24px; color: #64748b; font-size: 14px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 14px 0 6px; color: #334155; }
        input {
            width: 100%; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 9px;
            font-size: 15px; outline: none; transition: border-color .15s;
        }
        input:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,.15); }
        .hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }
        button {
            width: 100%; margin-top: 24px; padding: 13px; border: 0; border-radius: 9px;
            background: #0f766e; color: #fff; font-size: 16px; font-weight: 700; cursor: pointer;
        }
        button:hover { background: #115e59; }
        button:disabled { background: #94a3b8; cursor: wait; }
        .alert { padding: 11px 14px; border-radius: 9px; font-size: 14px; margin-bottom: 16px; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .row { display: flex; gap: 12px; }
        .row > div { flex: 1; }
        .diagnostics-link { display:block; margin-top:16px; text-align:center; color:#0f766e; font-size:13px; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>ربط الجهاز بفرع</h1>
        <p class="sub">وصّل هذا الجهاز بفرع مُعرَّف مسبقاً على السيرفر. أدخل <b>كود الفرع</b> (لازم يكون موجوداً على السيرفر) — والجهاز هيسحب بيانات الفرع تلقائياً. يمكن ربط أكتر من جهاز بنفس الفرع (بنفس الكود) ليشاركوا بياناته.</p>

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('setup.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="code">كود الفرع</label>
                    <input type="text" id="code" name="code" value="{{ old('code', $code) }}"
                           placeholder="A" maxlength="16" required autofocus>
                    <div class="hint">كود الفرع الموجود على السيرفر (بادئة الفواتير).</div>
                </div>
                <div>
                    <label for="device_no">رقم الجهاز (اختياري)</label>
                    <input type="number" id="device_no" name="device_no" value="{{ old('device_no') }}"
                           placeholder="1" min="1">
                    <div class="hint">لو الفرع عليه أكتر من جهاز.</div>
                </div>
            </div>

            <label for="name">اسم الفرع (اختياري)</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="فرع المعادي">

            <label for="server_url">رابط السيرفر المركزي</label>
            <input type="url" id="server_url" name="server_url" value="{{ old('server_url', $serverUrl) }}"
                   placeholder="https://server.example.com" required>

            <label for="token">مفتاح المزامنة (Token)</label>
            <input type="password" id="token" name="token" value="{{ old('token') }}"
                   placeholder="••••••••••••" required>
            <div class="hint">المفتاح السرّي المشترك المضبوط على السيرفر.</div>

            <hr style="margin:22px 0;border:0;border-top:1px solid #e2e8f0">
            <div style="font-size:13px;color:#64748b;margin-bottom:6px">
                بيانات دخول حساب الصيدلية (لربط الفرع بالحساب وتنزيل المستخدمين للعمل بدون نت):
            </div>

            <label for="owner_login">بريد/اسم مستخدم الصيدلية</label>
            <input type="text" id="owner_login" name="owner_login" value="{{ old('owner_login') }}"
                   placeholder="pharmacy@example.com" required>

            <label for="owner_password">كلمة مرور الصيدلية</label>
            <input type="password" id="owner_password" name="owner_password" placeholder="••••••••" required>

            <button id="submit-button" type="submit">تسجيل الفرع وبدء المزامنة</button>
        </form>
        <a class="diagnostics-link" href="{{ route('diagnostics.page') }}">فتح مركز التشخيص والإعدادات</a>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function () {
            const button = document.getElementById('submit-button');
            button.disabled = true;
            button.textContent = 'جارٍ تسجيل الفرع...';
        });
    </script>
</body>
</html>
