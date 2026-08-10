<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'مركز التشخيص') — AI Pharmacy System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>body{font-family:'Cairo','Segoe UI',sans-serif}</style>
    @yield('styles')
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <header class="border-b border-white/10 bg-slate-900/90 px-5 py-4">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div>
                <div class="font-black">AI Pharmacy System</div>
                <div class="text-xs text-slate-400">وضع تشخيص سطح المكتب</div>
            </div>
            <a href="{{ route('login') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm hover:bg-white/10">العودة لتسجيل الدخول</a>
        </div>
    </header>
    <main class="mx-auto max-w-7xl p-4 sm:p-6">@yield('content')</main>
</body>
</html>
