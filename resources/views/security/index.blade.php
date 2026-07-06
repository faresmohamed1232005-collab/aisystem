@extends('layouts.app')
@section('title', 'أمان الحساب')

@section('content')
<div class="max-w-xl mx-auto space-y-6">

    <h2 class="text-xl font-bold text-gray-800">أمان الحساب</h2>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-red-600 text-sm">
        @foreach($errors->all() as $e) <div>• {{ $e }}</div> @endforeach
    </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="font-bold text-gray-800">المصادقة الثنائية (2FA)</div>
                    <div class="text-xs text-gray-400">حماية إضافية بكود من تطبيق المصادقة عند الدخول</div>
                </div>
            </div>
            @if($enabled)
                <span class="bg-green-50 text-green-600 text-xs px-3 py-1.5 rounded-full font-semibold"><i class="fas fa-check"></i> مُفعّلة</span>
            @else
                <span class="bg-gray-100 text-gray-500 text-xs px-3 py-1.5 rounded-full font-semibold">غير مُفعّلة</span>
            @endif
        </div>

        {{-- وضع الإعداد: عرض السرّ وتأكيد أول كود --}}
        @if($secret && ! $enabled)
        <div class="border-t pt-4 space-y-4">
            <div class="bg-indigo-50 rounded-xl p-4 text-sm">
                <div class="text-gray-600 mb-2">١) أضف الحساب في تطبيق المصادقة (Google Authenticator أو ما يماثله) بإدخال هذا المفتاح يدوياً:</div>
                <div class="font-mono text-lg font-bold tracking-widest text-indigo-700 bg-white rounded-lg px-4 py-2 text-center select-all break-all">{{ $secret }}</div>
                <a href="{{ $uri }}" class="text-xs text-indigo-500 hover:underline block mt-2 break-all">otpauth:// رابط الإضافة المباشرة</a>
            </div>
            <form action="{{ route('security.2fa.confirm') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-end">
                @csrf
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">٢) أدخل الكود لتأكيد التفعيل</label>
                    <input type="text" name="code" inputmode="numeric" maxlength="6" placeholder="______"
                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-center text-lg tracking-widest focus:outline-none focus:border-indigo-400" style="direction:ltr">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition">تأكيد التفعيل</button>
            </form>
        </div>

        {{-- مُفعّلة: زر تعطيل --}}
        @elseif($enabled)
        <div class="border-t pt-4">
            <form action="{{ route('security.2fa.disable') }}" method="POST" class="flex flex-col sm:flex-row gap-3 items-end"
                  onsubmit="return confirm('تعطيل المصادقة الثنائية؟')">
                @csrf
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">لتعطيلها، أدخل كلمة المرور</label>
                    <input type="password" name="password" class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-red-400 text-right">
                </div>
                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold px-6 py-3 rounded-xl transition">تعطيل</button>
            </form>
        </div>

        {{-- غير مُفعّلة: زر بدء التفعيل --}}
        @else
        <div class="border-t pt-4">
            <form action="{{ route('security.2fa.enable') }}" method="POST">
                @csrf
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-xl transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> تفعيل المصادقة الثنائية
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
