<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق بخطوتين — AI Pharmacy System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 1rem;
        }
        .card { background: #fff; border-radius: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,.4); padding: 2.5rem; width: 100%; max-width: 26rem; text-align: center; }
        .icon { width: 4rem; height: 4rem; border-radius: 1rem; background: #eef2ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 1.25rem; }
        h1 { font-size: 1.25rem; color: #1f2937; margin-bottom: .4rem; }
        p { color: #6b7280; font-size: .875rem; margin-bottom: 1.5rem; }
        input[name=code] { width: 100%; border: 1px solid #e5e7eb; border-radius: .9rem; padding: .9rem; font-size: 1.5rem; text-align: center; letter-spacing: .5rem; direction: ltr; outline: none; transition: border-color .2s; }
        input[name=code]:focus { border-color: #6366f1; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: .75rem; padding: .75rem; font-size: .8rem; margin-bottom: 1rem; }
        button { width: 100%; background: #4f46e5; color: #fff; font-weight: 700; border: none; border-radius: .9rem; padding: .9rem; font-size: 1rem; margin-top: 1rem; cursor: pointer; transition: background .2s; }
        button:hover { background: #4338ca; }
        .back { display: inline-block; margin-top: 1rem; color: #9ca3af; font-size: .8rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-shield-halved"></i></div>
        <h1>التحقق بخطوتين</h1>
        <p>أدخل الكود المكوّن من 6 أرقام من تطبيق المصادقة</p>

        @if($errors->any())
        <div class="err">@foreach($errors->all() as $e){{ $e }}@endforeach</div>
        @endif

        <form method="POST" action="{{ route('login.2fa.verify') }}">
            @csrf
            <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" placeholder="______" autofocus required>
            <button type="submit"><i class="fas fa-unlock"></i> تأكيد الدخول</button>
        </form>

        <a href="{{ route('login') }}" class="back">إلغاء والرجوع لتسجيل الدخول</a>
    </div>
</body>
</html>
