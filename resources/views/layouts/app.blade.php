<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Pharmacy System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(99, 102, 241, 0.2);
            border-right: 3px solid #6366f1;
        }

        .nav-section {
            font-size: 10px;
            color: #475569;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            padding: 12px 16px 4px;
        }

        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (max-width: 1023px) {
            #sidebar {
                position: fixed;
                top: 0;
                right: 0;
                height: 100vh;
                z-index: 50;
                transform: translateX(100%);
                box-shadow: -8px 0 32px rgba(0, 0, 0, 0.4);
            }

            #sidebar.open {
                transform: translateX(0);
            }
        }

        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            z-index: 40;
            backdrop-filter: blur(2px);
        }

        #sidebar-overlay.show {
            display: block;
        }

        .mini-robot {
            animation: robotBob 3.5s ease-in-out infinite;
        }

        @keyframes robotBob {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }

        .r-eye-mini {
            animation: eyeBlinkMini 5s ease-in-out infinite;
            transform-origin: center;
        }

        @keyframes eyeBlinkMini {

            0%,
            90%,
            100% {
                transform: scaleY(1);
            }

            95% {
                transform: scaleY(0.08);
            }
        }

        .r-ant-mini {
            animation: antGlowMini 2.2s ease-in-out infinite;
        }

        @keyframes antGlowMini {

            0%,
            100% {
                fill: #6366f1;
                filter: drop-shadow(0 0 3px #6366f1);
            }

            50% {
                fill: #22d3ee;
                filter: drop-shadow(0 0 7px #22d3ee);
            }
        }

        .r-chest-mini {
            animation: chestPulseMini 2.4s ease-in-out infinite;
        }

        @keyframes chestPulseMini {

            0%,
            100% {
                opacity: 0.55;
            }

            50% {
                opacity: 1;
            }
        }

        .header-robot-wrap {
            cursor: default;
            filter: drop-shadow(0 0 6px rgba(99, 102, 241, 0.35));
            transition: filter 0.3s;
        }

        .header-robot-wrap:hover {
            filter: drop-shadow(0 0 12px rgba(99, 102, 241, 0.7));
        }

        #hamburger-btn {
            display: none;
        }

        @media (max-width: 1023px) {
            #hamburger-btn {
                display: flex;
            }
        }

        @media (max-width: 1023px) {
            #main-wrapper {
                flex-direction: column;
            }
        }

        @yield('styles')
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

    @php
        $isSubUser = session()->has('sub_user');
        $isAdmin = !$isSubUser && Auth::check();
        $displayName = $isSubUser ? session('sub_user.name') : Auth::user()->name;
    @endphp

    <div id="sidebar-overlay" onclick="closeSidebar()"></div>

    <div id="main-wrapper" class="flex h-screen overflow-hidden">

        {{-- ════════ Sidebar ════════ --}}
        <div id="sidebar" class="sidebar w-64 flex-shrink-0 text-white flex flex-col overflow-y-auto">

            <div class="p-4 border-b border-white/10">
                <div class="flex items-center gap-3">
                    <button onclick="closeSidebar()"
                        class="lg:hidden text-gray-400 hover:text-white p-1 ml-auto order-last">
                        <i class="fas fa-times text-lg"></i>
                    </button>

                    <div class="mini-robot flex-shrink-0">
                        <svg width="40" height="52" viewBox="-12 -5 184 268" fill="none">
                            <defs>
                                <linearGradient id="lg-sb" x1="0" y1="0" x2="0"
                                    y2="1">
                                    <stop offset="0%" stop-color="#1e293b" />
                                    <stop offset="100%" stop-color="#0d1526" />
                                </linearGradient>
                                <radialGradient id="rg-eye-sb" cx="35%" cy="35%" r="60%">
                                    <stop offset="0%" stop-color="#818cf8" />
                                    <stop offset="100%" stop-color="#4338ca" />
                                </radialGradient>
                                <radialGradient id="rg-chest-sb" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" stop-color="#22d3ee" />
                                    <stop offset="60%" stop-color="#6366f1" />
                                    <stop offset="100%" stop-color="#4338ca" stop-opacity="0.4" />
                                </radialGradient>
                                <filter id="glow-sb">
                                    <feGaussianBlur stdDeviation="2.5" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1"
                                stroke-width="2.5" stroke-linecap="round" />
                            <circle class="r-ant-mini" cx="80" cy="3" r="7" fill="#6366f1"
                                filter="url(#glow-sb)" />
                            <circle cx="80" cy="3" r="3.5" fill="#e0e7ff" />
                            <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <g class="r-eye-mini">
                                <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-sb)"
                                    filter="url(#glow-sb)" />
                                <circle cx="57" cy="60" r="11" fill="#0d1526" />
                                <circle cx="57" cy="60" r="6.5" fill="#6366f1" />
                                <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)" />
                                <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-sb)"
                                    filter="url(#glow-sb)" />
                                <circle cx="103" cy="60" r="11" fill="#0d1526" />
                                <circle cx="103" cy="60" r="6.5" fill="#6366f1" />
                                <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)" />
                            </g>
                            <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526"
                                stroke="#6366f1" stroke-width="1.2" />
                            <rect x="58" y="89.5" width="9" height="8" rx="2.5"
                                fill="rgba(99,102,241,0.35)" />
                            <rect x="71" y="89.5" width="9" height="8" rx="2.5"
                                fill="rgba(99,102,241,0.35)" />
                            <rect x="84" y="89.5" width="9" height="8" rx="2.5"
                                fill="rgba(99,102,241,0.35)" />
                            <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1" />
                            <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)"
                                stroke="rgba(99,102,241,0.35)" stroke-width="1" />
                            <circle class="r-chest-mini" cx="80" cy="162" r="10"
                                fill="url(#rg-chest-sb)" filter="url(#glow-sb)" />
                            <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-sb)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                        </svg>
                    </div>

                    <div>
                        <div class="font-bold text-sm">AI Pharmacy System</div>
                        <div class="text-xs text-gray-400 truncate max-w-[120px]">{{ Auth::user()->pharmacy_name }}
                        </div>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">

                <div class="nav-section">عام</div>
                <a href="{{ route('dashboard') }}"
                    class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-tachometer-alt w-5 text-center"></i> لوحة التحكم
                </a>
                <a href="{{ route('sales.create') }}"
                    class="nav-item {{ request()->routeIs('sales.create') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-cash-register w-5 text-center"></i> البيع المباشر
                </a>
                <a href="{{ route('pending.index') }}"
                    class="nav-item {{ request()->routeIs('pending.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-clock w-5 text-center"></i> الطلبات المعلقة
                    <span id="pending-badge"
                        class="mr-auto hidden bg-orange-500 text-white text-xs px-2 py-0.5 rounded-full">0</span>
                </a>
                @can('manage_employees')
                <div class="nav-section">المصروفات</div>
                <a href="{{ route('expenses.index') }}"
                    class="nav-item {{ request()->routeIs('expenses.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-money-bill-wave w-5 text-center"></i> المصروفات
                </a>
                <a href="{{ route('employees.index') }}"
                    class="nav-item {{ request()->routeIs('employees.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-user-tie w-5 text-center"></i> الموظفين
                </a>
                @endcan
                <div class="nav-section">المخزن</div>
                <a href="{{ route('products.index') }}"
                    class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-boxes w-5 text-center"></i> المخزن
                </a>

                <div class="nav-section">المبيعات</div>
                @can('view_reports')
                <a href="{{ route('sales.report') }}"
                    class="nav-item {{ request()->routeIs('sales.report') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-chart-bar w-5 text-center"></i> تقارير المبيعات
                </a>
                <a href="{{ route('forecast.index') }}"
                    class="nav-item {{ request()->routeIs('forecast.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-robot w-5 text-center text-indigo-400"></i> التوقعات و الطلب
                </a>
                @endcan
                <a href="{{ route('sale-returns.index') }}"
                    class="nav-item {{ request()->routeIs('sale-returns.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-undo-alt w-5 text-center"></i> مرتجعات المبيعات
                </a>

                <div class="nav-section">المشتريات</div>
                <a href="{{ route('purchases.index') }}"
                    class="nav-item {{ request()->routeIs('purchases.index') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-file-invoice-dollar w-5 text-center"></i> فواتير الشراء
                </a>
            
                <a href="{{ route('purchases.report') }}"
                    class="nav-item {{ request()->routeIs('purchases.report') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-chart-line w-5 text-center"></i> تقارير المشتريات
                </a>
                <a href="{{ route('purchase-returns.index') }}"
                    class="nav-item {{ request()->routeIs('purchase-returns.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-undo w-5 text-center"></i> مرتجعات المشتريات
                </a>

                <div class="nav-section">العلاقات</div>
                <a href="{{ route('customers.index') }}"
                    class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-users w-5 text-center"></i> العملاء
                </a>
                <a href="{{ route('suppliers.index') }}"
                    class="nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-truck w-5 text-center"></i> الموردين
                </a>

                @can('manage_contracts')
                <div class="nav-section">التأمين والتعاقدات</div>
                <a href="{{ route('contracts.index') }}"
                    class="nav-item {{ request()->routeIs('contracts.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-file-contract w-5 text-center"></i> التعاقدات
                </a>
                <a href="{{ route('insured-patients.index') }}"
                    class="nav-item {{ request()->routeIs('insured-patients.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-user-injured w-5 text-center"></i> المرضى المؤمّن عليهم
                </a>
                <a href="{{ route('insurance-claims.index') }}"
                    class="nav-item {{ request()->routeIs('insurance-claims.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-file-invoice-dollar w-5 text-center"></i> مطالبات التأمين
                </a>
                @endcan

                @canany(['manage_branches', 'create_transfers', 'receive_transfers'])
                <div class="nav-section">الفروع والتحويلات</div>
                @can('manage_branches')
                <a href="{{ route('branches.index') }}"
                    class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-code-branch w-5 text-center"></i> الفروع
                </a>
                @endcan
                <a href="{{ route('stock-transfers.index') }}"
                    class="nav-item {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-right-left w-5 text-center"></i> تحويلات المخزون
                </a>
                @endcanany

                <div class="nav-section">الكاشير</div>
                <a href="{{ route('drawer-lock.index') }}"
                    class="nav-item {{ request()->routeIs('drawer-lock.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                    onclick="closeSidebar()">
                    <i class="fas fa-cash-register w-5 text-center"></i> تقفيل درج الكاشير
                </a>

                @can('manage_sub_users')
                    <div class="nav-section">الإدارة</div>
                    <a href="{{ route('sub-users.index') }}"
                        class="nav-item {{ request()->routeIs('sub-users.*') ? 'active' : '' }} flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm"
                        onclick="closeSidebar()">
                        <i class="fas fa-shield-halved w-5 text-center"></i> الصلاحيات
                    </a>
                @endcan
            </nav>

            <div class="p-4 border-t border-white/10">
                <div class="text-xs text-gray-300 font-semibold mb-0.5 truncate">{{ $displayName }}</div>
                @if ($isSubUser)
                    <div class="text-xs text-indigo-400 mb-2">{{ \App\Support\Roles::label(session('sub_user.role')) }}</div>
                @else
                    <div class="text-xs text-indigo-400 mb-2">👑 المالك</div>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        class="w-full text-right text-sm text-gray-400 hover:text-white transition flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>

        {{-- ════════ Main Content ════════ --}}
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">

            {{-- Header --}}
            <header class="bg-white border-b px-4 py-3 flex items-center justify-between shadow-sm flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button id="hamburger-btn" onclick="openSidebar()"
                        class="lg:hidden items-center justify-center w-9 h-9 rounded-lg bg-gray-100 hover:bg-indigo-100 text-gray-600 hover:text-indigo-600 transition">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-base font-bold text-gray-700 truncate">@yield('title', 'لوحة التحكم')</h1>
                </div>

                <div class="flex items-center gap-3">
                    @if (session('success'))
                        <div class="hidden sm:block bg-green-100 text-green-700 px-3 py-1 rounded-lg text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- زر تحميل تطبيق سطح المكتب: في المتصفح فقط (ليس داخل تطبيق الديسكتوب نفسه) --}}
                    @if (config('sync.desktop_download_url') && ! config('nativephp-internal.running'))
                        <a href="{{ config('sync.desktop_download_url') }}" target="_blank" rel="noopener"
                            title="حمّل تطبيق سطح المكتب (يعمل بدون إنترنت)"
                            class="hidden sm:flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-3 h-9 rounded-lg text-sm font-semibold transition">
                            <i class="fas fa-desktop"></i>
                            <span class="hidden md:inline">تطبيق سطح المكتب</span>
                        </a>
                    @endif

                    @if (config('sync.enabled'))
                        {{-- زر المزامنة اليدوي مع السيرفر --}}
                        <button id="sync-btn" onclick="runSync()" title="مزامنة مع السيرفر"
                            class="relative w-9 h-9 rounded-lg bg-gray-100 hover:bg-indigo-100 text-gray-600 hover:text-indigo-600 transition flex items-center justify-center">
                            <i id="sync-icon" class="fas fa-sync-alt"></i>
                        </button>
                    @endif

                    {{-- جرس الإشعارات --}}
                    <a href="{{ route('notifications.index') }}"
                        class="relative w-9 h-9 rounded-lg bg-gray-100 hover:bg-indigo-100 text-gray-600 hover:text-indigo-600 transition flex items-center justify-center">
                        <i class="fas fa-bell"></i>
                        <span id="notif-badge"
                            class="absolute -top-1 -left-1 bg-red-500 text-white text-[10px] min-w-[18px] h-[18px] flex items-center justify-center rounded-full px-1 hidden">0</span>
                    </a>

                    <div class="header-robot-wrap mini-robot" title="{{ $displayName }}">
                        <svg width="32" height="40" viewBox="-12 -5 184 268" fill="none">
                            <defs>
                                <linearGradient id="lg-hdr" x1="0" y1="0" x2="0"
                                    y2="1">
                                    <stop offset="0%" stop-color="#1e293b" />
                                    <stop offset="100%" stop-color="#0d1526" />
                                </linearGradient>
                                <radialGradient id="rg-eye-hdr" cx="35%" cy="35%" r="60%">
                                    <stop offset="0%" stop-color="#818cf8" />
                                    <stop offset="100%" stop-color="#4338ca" />
                                </radialGradient>
                                <radialGradient id="rg-chest-hdr" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" stop-color="#22d3ee" />
                                    <stop offset="60%" stop-color="#6366f1" />
                                    <stop offset="100%" stop-color="#4338ca" stop-opacity="0.4" />
                                </radialGradient>
                                <filter id="glow-hdr">
                                    <feGaussianBlur stdDeviation="2.5" result="blur" />
                                    <feMerge>
                                        <feMergeNode in="blur" />
                                        <feMergeNode in="SourceGraphic" />
                                    </feMerge>
                                </filter>
                            </defs>
                            <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1"
                                stroke-width="2.5" stroke-linecap="round" />
                            <circle class="r-ant-mini" cx="80" cy="3" r="7" fill="#6366f1"
                                filter="url(#glow-hdr)" />
                            <circle cx="80" cy="3" r="3.5" fill="#e0e7ff" />
                            <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <g class="r-eye-mini">
                                <rect x="40" y="46" width="34" height="28" rx="9"
                                    fill="url(#rg-eye-hdr)" filter="url(#glow-hdr)" />
                                <circle cx="57" cy="60" r="11" fill="#0d1526" />
                                <circle cx="57" cy="60" r="6.5" fill="#6366f1" />
                                <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)" />
                                <rect x="86" y="46" width="34" height="28" rx="9"
                                    fill="url(#rg-eye-hdr)" filter="url(#glow-hdr)" />
                                <circle cx="103" cy="60" r="11" fill="#0d1526" />
                                <circle cx="103" cy="60" r="6.5" fill="#6366f1" />
                                <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)" />
                            </g>
                            <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526"
                                stroke="#6366f1" stroke-width="1.2" />
                            <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1" />
                            <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)"
                                stroke="rgba(99,102,241,0.35)" stroke-width="1" />
                            <circle class="r-chest-mini" cx="80" cy="162" r="10"
                                fill="url(#rg-chest-hdr)" filter="url(#glow-hdr)" />
                            <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-hdr)"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                            <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526"
                                stroke="#4f46e5" stroke-width="1.5" />
                        </svg>
                    </div>
                </div>
            </header>

            {{-- بانر التحديث: يظهر عند توفّر تحديث جديد (بإذن المستخدم) --}}
            <div id="update-banner" class="hidden bg-indigo-600 text-white px-4 py-2.5 flex items-center justify-between gap-3 text-sm flex-shrink-0">
                <div class="flex items-center gap-2">
                    <i class="fas fa-download"></i>
                    <span id="update-banner-text">يوجد تحديث جديد للتطبيق</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="update-action-btn" onclick="handleUpdateAction()"
                        class="bg-white text-indigo-700 hover:bg-indigo-50 font-bold px-3 py-1 rounded-lg text-xs transition">
                        حدّث الآن
                    </button>
                    <button onclick="document.getElementById('update-banner').classList.add('hidden')"
                        class="text-indigo-200 hover:text-white text-xs px-1" title="إخفاء">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <main class="flex-1 overflow-y-auto p-3 sm:p-4 lg:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebar-overlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('show');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeSidebar();
        });

        async function loadNotifCount() {
            try {
                const r = await fetch('{{ route('notifications.count') }}');
                const d = await r.json();
                const badge = document.getElementById('notif-badge');
                if (d.count > 0) {
                    badge.textContent = d.count;
                    badge.classList.remove('hidden');
                } else badge.classList.add('hidden');
            } catch (e) {}
        }
        async function loadPendingCount() {
            try {
                const r = await fetch('{{ route('pending.count') }}');
                const d = await r.json();
                const badge = document.getElementById('pending-badge');
                if (d.count > 0) {
                    badge.textContent = d.count;
                    badge.classList.remove('hidden');
                } else badge.classList.add('hidden');
            } catch (e) {}
        }
        loadNotifCount();
        setInterval(loadNotifCount, 30000);
        loadPendingCount();
        setInterval(loadPendingCount, 30000);

        @if (config('sync.enabled'))
        // ===== المزامنة مع السيرفر (يدوي + تلقائي دوري) =====
        let syncing = false;
        async function runSync(silent = false) {
            if (syncing) return;
            syncing = true;
            const icon = document.getElementById('sync-icon');
            const btn = document.getElementById('sync-btn');
            if (icon) icon.classList.add('fa-spin');
            try {
                const r = await fetch('{{ route('sync.run') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json',
                    },
                });
                const d = await r.json();
                if (!silent) syncNotify(d.success, d.message || (d.success ? 'تمت المزامنة' : 'تعذّرت المزامنة'));
                if (btn) btn.title = (d.message || 'مزامنة مع السيرفر');
            } catch (e) {
                if (!silent) syncNotify(false, 'تعذّر الاتصال بالمزامنة');
            } finally {
                if (icon) icon.classList.remove('fa-spin');
                syncing = false;
                refreshSyncStatus();
            }
        }
        window.runSync = runSync;

        // مؤشر الاتصال: يحدّث لون أيقونة الزر حسب آخر حالة مزامنة.
        async function refreshSyncStatus() {
            try {
                const r = await fetch('{{ route('sync.status') }}', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                const btn = document.getElementById('sync-btn');
                const icon = document.getElementById('sync-icon');
                if (!btn || !icon || !d.state) return;
                const st = d.state.status;
                btn.classList.remove('text-green-600', 'text-red-500', 'text-gray-600');
                if (st === 'online') btn.classList.add('text-green-600');
                else if (st === 'offline') btn.classList.add('text-red-500');
                else btn.classList.add('text-gray-600');
                if (st === 'syncing') icon.classList.add('fa-spin');
                else if (!syncing) icon.classList.remove('fa-spin');
                const last = d.state.last_success ? ('Last: ' + d.state.last_success) : '';
                btn.title = (st === 'offline' && d.state.message ? d.state.message : 'مزامنة مع السيرفر') + (last ? ' — ' + last : '');
            } catch (e) { /* تجاهل */ }
        }
        window.refreshSyncStatus = refreshSyncStatus;
        // إشعار مزامنة آمن: يستخدم showToast إن وُجد، وإلا بانر بسيط أعلى الشاشة.
        function syncNotify(success, msg) {
            if (window.showToast) { window.showToast(success ? 'success' : 'error', success ? 'تمت المزامنة' : 'تعذّرت المزامنة', msg); return; }
            const el = document.createElement('div');
            el.textContent = msg;
            el.style.cssText = 'position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:9999;'
                + 'padding:10px 18px;border-radius:12px;font-size:13px;color:#fff;box-shadow:0 8px 24px rgba(0,0,0,.15);'
                + 'background:' + (success ? '#16a34a' : '#dc2626');
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3500);
        }
        // مزامنة تلقائية دورية في الخلفية (صامتة)
        const SYNC_INTERVAL = {{ (int) config('sync.interval', 60) }} * 1000;
        if (SYNC_INTERVAL >= 5000) setInterval(() => runSync(true), SYNC_INTERVAL);
        // تحديث مؤشر الاتصال دورياً وعند البداية
        refreshSyncStatus();
        setInterval(refreshSyncStatus, 15000);
        @endif

        @if (config('nativephp.updater.enabled'))
        // ===== تحديث التطبيق (بإذن المستخدم) =====
        let updateStatus = 'none';
        async function checkUpdateStatus() {
            try {
                const r = await fetch('{{ route('update.status') }}', { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                updateStatus = d.status;
                const banner = document.getElementById('update-banner');
                const text = document.getElementById('update-banner-text');
                const actionBtn = document.getElementById('update-action-btn');
                if (!banner) return;
                if (d.status === 'available') {
                    text.textContent = 'يوجد تحديث جديد للتطبيق' + (d.version ? ' (الإصدار ' + d.version + ')' : '');
                    actionBtn.textContent = 'حدّث الآن';
                    actionBtn.disabled = false;
                    banner.classList.remove('hidden');
                } else if (d.status === 'downloading') {
                    text.textContent = 'جارٍ تنزيل التحديث...';
                    actionBtn.disabled = true;
                    actionBtn.textContent = 'يُنزّل...';
                    banner.classList.remove('hidden');
                } else if (d.status === 'downloaded') {
                    text.textContent = 'التحديث جاهز للتثبيت' + (d.version ? ' (الإصدار ' + d.version + ')' : '');
                    actionBtn.textContent = 'أعد التشغيل وثبّت';
                    actionBtn.disabled = false;
                    banner.classList.remove('hidden');
                } else {
                    banner.classList.add('hidden');
                }
            } catch (e) { /* تجاهل (غالباً لا نت أو بيئة ويب) */ }
        }
        async function handleUpdateAction() {
            const route = updateStatus === 'downloaded' ? '{{ route('update.install') }}' : '{{ route('update.download') }}';
            try {
                const r = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        'Accept': 'application/json',
                    },
                });
                const d = await r.json();
                if (window.syncNotify) syncNotify(d.success, d.message || '');
                checkUpdateStatus();
            } catch (e) {
                if (window.syncNotify) syncNotify(false, 'تعذّر تنفيذ التحديث');
            }
        }
        window.handleUpdateAction = handleUpdateAction;
        checkUpdateStatus();
        setInterval(checkUpdateStatus, 60000); // فحص حالة التحديث كل دقيقة
        @endif
    </script>

    @yield('scripts')
    @include('components.ai-assistant')
</body>

</html>
