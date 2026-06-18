@extends('layouts.app')
@section('title', 'الإشعارات')

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-bold text-gray-800">الإشعارات</h2>
            @if($notifications->total() > 0)
            <span class="bg-indigo-100 text-indigo-700 text-sm px-3 py-1 rounded-full font-semibold">
                {{ $notifications->total() }} إشعار
            </span>
            @endif
        </div>
    </div>

    @forelse($notifications as $notif)
    @php
        $styles = match($notif->type) {
            'danger'  => [
                'border'   => 'border-red-200',
                'iconBg'   => 'bg-red-100',
                'iconColor'=> 'text-red-600',
                'icon'     => 'fa-circle-exclamation',
                'dot'      => 'bg-red-500',
                'badge'    => 'bg-red-100 text-red-700',
                'badgeText'=> 'تحذير',
            ],
            'warning' => [
                'border'   => 'border-orange-200',
                'iconBg'   => 'bg-orange-100',
                'iconColor'=> 'text-orange-500',
                'icon'     => 'fa-triangle-exclamation',
                'dot'      => 'bg-orange-400',
                'badge'    => 'bg-orange-100 text-orange-700',
                'badgeText'=> 'تنبيه',
            ],
            'success' => [
                'border'   => 'border-green-200',
                'iconBg'   => 'bg-green-100',
                'iconColor'=> 'text-green-600',
                'icon'     => 'fa-circle-check',
                'dot'      => 'bg-green-500',
                'badge'    => 'bg-green-100 text-green-700',
                'badgeText'=> 'ناجح',
            ],
            default   => [
                'border'   => 'border-blue-200',
                'iconBg'   => 'bg-blue-100',
                'iconColor'=> 'text-blue-600',
                'icon'     => 'fa-circle-info',
                'dot'      => 'bg-blue-500',
                'badge'    => 'bg-blue-100 text-blue-700',
                'badgeText'=> 'معلومة',
            ],
        };
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border {{ $styles['border'] }} p-4 flex items-start gap-4 hover:shadow-md transition-shadow">

        {{-- أيقون --}}
        <div class="flex-shrink-0 w-11 h-11 {{ $styles['iconBg'] }} rounded-xl flex items-center justify-center">
            <i class="fas {{ $styles['icon'] }} text-lg {{ $styles['iconColor'] }}"></i>
        </div>

        {{-- المحتوى --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2 mb-1">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm text-gray-800">{{ $notif->title }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $styles['badge'] }}">
                        {{ $styles['badgeText'] }}
                    </span>
                </div>
                <span class="text-xs text-gray-400 flex-shrink-0 flex items-center gap-1">
                    <i class="fas fa-clock text-gray-300"></i>
                    {{ $notif->created_at->diffForHumans() }}
                </span>
            </div>
            <p class="text-sm text-gray-500 leading-relaxed">{{ $notif->message }}</p>
        </div>
    </div>

    @empty

    {{-- حالة فارغة --}}
    <div class="bg-white rounded-2xl shadow-sm p-16 text-center">
        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-bell-slash text-2xl text-gray-400"></i>
        </div>
        <div class="font-bold text-gray-600 mb-1">لا توجد إشعارات</div>
        <div class="text-sm text-gray-400">كل حاجة تمام — مفيش إشعارات جديدة دلوقتي</div>
    </div>

    @endforelse

    @if($notifications->hasPages())
    <div class="pt-2">{{ $notifications->links() }}</div>
    @endif

</div>
@endsection