<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Super Admin — AI Pharmacy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: 'Cairo', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #f0f3fb;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eceef5;
            box-shadow: 0 2px 12px rgba(100, 80, 240, .06);
            transition: transform .2s, box-shadow .2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(100, 80, 240, .10);
        }

        .badge-approved {
            background: #e3faf0;
            color: #17a85a;
        }

        .badge-pending {
            background: #fef9e7;
            color: #d97706;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 10px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            border: 1px solid transparent;
        }

        .btn-approve {
            background: #e3faf0;
            color: #17a85a;
            border-color: #bbf7d0;
        }

        .btn-approve:hover {
            background: #dcfce7;
        }

        .btn-revoke {
            background: #fdecea;
            color: #e33535;
            border-color: #fecaca;
        }

        .btn-revoke:hover {
            background: #fee2e2;
        }

        .btn-delete {
            background: #f1f5f9;
            color: #64748b;
            border-color: #e2e8f0;
            padding: 6px 10px;
        }

        .btn-delete:hover {
            background: #fdecea;
            color: #e33535;
            border-color: #fecaca;
        }

        .btn-card {
            background: #eee9fe;
            color: #6c5fe6;
            border-color: #ddd6fe;
        }

        .btn-card:hover {
            background: #ede9fe;
        }

        .btn-primary {
            background: #6c5fe6;
            color: #fff;
        }

        .btn-primary:hover {
            background: #5b4fd4;
        }

        .btn-wa {
            background: #e7fdf0;
            color: #25d366;
            border-color: #b7f5d4;
            padding: 5px 9px;
        }

        .btn-wa:hover {
            background: #d1fae5;
        }

        .tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .tbl th {
            font-size: 11px;
            color: #a0a9c0;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 1px solid #f0f2f8;
            text-align: right;
            white-space: nowrap;
        }

        .tbl td {
            font-size: 12px;
            color: #3d4460;
            padding: 12px 14px;
            border-bottom: 1px solid #f8f9fd;
            vertical-align: middle;
        }

        .tbl tr:last-child td {
            border: none;
        }

        .tbl tbody tr:hover td {
            background: #fafbff;
        }

        .modal-bg {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .modal-box {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        }

        .flash-success {
            background: #e3faf0;
            border: 1px solid #bbf7d0;
            color: #17a85a;
        }

        .flash-error {
            background: #fdecea;
            border: 1px solid #fecaca;
            color: #e33535;
        }

        .tbl-wrap {
            overflow-x: auto;
        }

        .tbl {
            min-width: 900px;
        }

        input[type=text],
        select {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 9px 14px;
            font-size: 13px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            font-family: 'Cairo', sans-serif;
        }

        input[type=text]:focus,
        select:focus {
            border-color: #6c5fe6;
            box-shadow: 0 0 0 3px rgba(108, 95, 230, .12);
        }

        /* حقل الباسورد */
        .pass-wrap {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 3px 8px;
            max-width: 160px;
        }

        .pass-val {
            font-family: monospace;
            font-size: 12px;
            color: #475569;
            letter-spacing: 1px;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pass-toggle {
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 2px;
            flex-shrink: 0;
            transition: color .2s;
        }

        .pass-toggle:hover {
            color: #6c5fe6;
        }

        .pass-copy {
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 2px;
            flex-shrink: 0;
            transition: color .2s;
        }

        .pass-copy:hover {
            color: #17a85a;
        }
    </style>
</head>

<body>

    {{-- ══ HEADER ══ --}}
    <div class="header text-white px-5 py-4 flex items-center justify-between shadow-xl sticky top-0 z-10">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600/80 rounded-xl flex items-center justify-center text-lg shadow-inner">🛡️
            </div>
            <div>
                <div class="font-bold text-base leading-tight">Super Admin Panel</div>
                <div class="text-xs text-indigo-300">AI Pharmacy System</div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:block text-xs text-gray-400">{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn text-xs bg-white/10 hover:bg-white/20 text-white border-white/20">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="hidden sm:inline">خروج</span>
                </button>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

        {{-- ══ FLASH ══ --}}
        @if (session('success'))
            <div class="flash-success rounded-xl px-4 py-3 text-sm flex items-center gap-2">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="flash-error rounded-xl px-4 py-3 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            </div>
        @endif

        {{-- ══ STATS ══ --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ([['إجمالي المسجلين', $stats['total'], 'text-gray-800', 'fa-users', '#eee9fe', '#6c5fe6'], ['موافق عليهم', $stats['approved'], 'text-green-600', 'fa-check-circle', '#e3faf0', '#17a85a'], ['في الانتظار', $stats['pending'], 'text-yellow-500', 'fa-clock', '#fef9e7', '#d97706'], ['مسجلين اليوم', $stats['today'], 'text-indigo-600', 'fa-calendar-day', '#eee9fe', '#6c5fe6']] as [$label, $val, $cls, $icon, $iconBg, $iconColor])
                <div class="card p-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-2xl font-black {{ $cls }}">{{ $val }}</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $label }}</div>
                        </div>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                            style="background:{{ $iconBg }}">
                            <i class="fas {{ $icon }} text-sm" style="color:{{ $iconColor }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══ ADS MANAGEMENT ══ --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <div class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-bullhorn text-indigo-500"></i> إدارة الإعلانات
                </div>
                <div class="text-xs text-gray-400 mt-0.5">الإعلانات تظهر في صفحة البيع لجميع الصيادل</div>
            </div>
            <div class="p-5 space-y-4">
                <form method="POST" action="{{ route('super.ads.store') }}" enctype="multipart/form-data"
                    class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 space-y-3">
                    @csrf
                    <div class="text-sm font-bold text-indigo-700 flex items-center gap-2">
                        <i class="fas fa-upload"></i> رفع إعلان جديد
                    </div>
                    <div class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-[180px]">
                            <label class="block text-xs text-gray-500 mb-1">عنوان الإعلان (اختياري)</label>
                            <input type="text" name="title" placeholder="مثلاً: عرض رمضان..."
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-indigo-400 bg-white">
                        </div>
                        <div class="flex-1 min-w-[220px]">
                            <label class="block text-xs text-gray-500 mb-1">صورة الإعلان <span
                                    class="text-red-400">*</span></label>
                            <input type="file" name="image" accept="image/*" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:border-indigo-400"
                                onchange="previewAd(this)">
                        </div>
                        <button type="submit" class="btn btn-primary px-5 py-2.5 text-sm whitespace-nowrap">
                            <i class="fas fa-plus ml-1"></i> رفع الإعلان
                        </button>
                    </div>
                    <div id="adPreviewWrap" class="hidden">
                        <div class="text-xs text-gray-400 mb-1">معاينة:</div>
                        <img id="adPreview" src="" alt="preview"
                            class="rounded-xl border border-indigo-200 max-h-32 object-cover w-full">
                    </div>
                </form>

                @forelse($ads as $ad)
                    <div
                        class="flex flex-wrap items-center gap-3 p-3 rounded-2xl border {{ $ad->is_active ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }}">
                        <img src="{{ asset('storage/' . $ad->image_path) }}" alt="ad"
                            class="w-28 h-16 object-cover rounded-xl border border-gray-200 flex-shrink-0">
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-gray-800">{{ $ad->title ?: 'بدون عنوان' }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $ad->created_at->format('Y/m/d — h:i A') }}
                            </div>
                            <span
                                class="inline-block mt-1 text-xs font-bold px-2 py-0.5 rounded-full {{ $ad->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">
                                {{ $ad->is_active ? '✅ مفعّل' : '⏸ موقوف' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <form method="POST" action="{{ route('super.ads.toggle', $ad) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="btn {{ $ad->is_active ? 'btn-revoke' : 'btn-approve' }} text-xs">
                                    {{ $ad->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('super.ads.destroy', $ad) }}"
                                onsubmit="return confirm('حذف الإعلان؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-delete"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-300 py-8 text-sm">
                        <i class="fas fa-image text-4xl block mb-2"></i> لا توجد إعلانات بعد
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ══ FILTERS ══ --}}
        <div class="card p-4">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="🔍  بحث بالاسم / إيميل / صيدلية / تليفون..." class="flex-1 min-w-[200px]">
                <select name="status">
                    <option value="">كل الحالات</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>✓ موافق عليهم
                    </option>
                    <option value="unapproved" {{ request('status') == 'unapproved' ? 'selected' : '' }}>⏳ في الانتظار
                    </option>
                </select>
                <select name="gov" id="govSelect" onchange="updateCities()">
                    <option value="">كل المحافظات</option>
                    @foreach ($govs as $g)
                        <option value="{{ $g }}" {{ request('gov') == $g ? 'selected' : '' }}>
                            {{ $g }}</option>
                    @endforeach
                </select>
                <select name="city" id="citySelect" style="{{ request('gov') ? '' : 'display:none' }}">
                    <option value="">كل المراكز</option>
                    @foreach ($cities as $c)
                        <option value="{{ $c }}" {{ request('city') == $c ? 'selected' : '' }}>
                            {{ $c }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                @if (request()->hasAny(['search', 'status', 'gov', 'city']))
                    <a href="{{ route('super.admin.index') }}" class="btn"
                        style="background:#f1f5f9;color:#64748b;border-color:#e2e8f0">
                        <i class="fas fa-times"></i> مسح
                    </a>
                @endif
                <button type="submit" formaction="{{ route('super.admin.sales-report') }}" formtarget="_blank"
                    class="btn" style="background:#fdecea;color:#e33535;border-color:#fecaca">
                    <i class="fas fa-file-pdf"></i> تقرير PDF
                </button>
            </form>
        </div>

        {{-- ══ TABLE ══ --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <div class="font-bold text-gray-800 text-sm">المستخدمون المسجلون</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ $users->total() }} مستخدم</div>
                </div>
                <div class="text-xs text-gray-300">صفحة {{ $users->currentPage() }} / {{ $users->lastPage() }}</div>
            </div>

            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>المستخدم</th>

                            <th>الصيدلية</th>
                            <th>المحافظة / المدينة</th>
                            <th>العنوان</th>
                            <th>التليفون</th>
                            <th style="text-align:center">الكارنيه</th>
                            <th style="text-align:center">الحالة</th>
                            <th style="text-align:center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="text-gray-300 text-xs">{{ $user->id }}</td>

                                {{-- المستخدم --}}
                                <td>
                                    <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-300 mt-0.5">
                                        <i class="fas fa-calendar-alt ml-1"></i>
                                        {{ $user->created_at->format('Y/m/d — h:i A') }}
                                    </div>
                                </td>


                                {{-- الصيدلية --}}
                                <td>
                                    <div class="font-semibold text-gray-700">{{ $user->pharmacy_name }}</div>
                                </td>

                                {{-- المحافظة --}}
                                <td>
                                    <div class="text-sm text-gray-700">{{ $user->governorate }}</div>
                                    <div class="text-xs text-gray-400">{{ $user->city }}</div>
                                </td>

                                {{-- العنوان --}}
                                <td>
                                    <div class="text-xs text-gray-500 max-w-[160px]">{{ $user->address }}</div>
                                </td>

                                {{-- ✅ التليفون + واتساب --}}
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-sm text-gray-700">{{ $user->phone }}</span>
                                        @if ($user->phone)
                                            @php
                                                // تنظيف الرقم وتحويله لصيغة دولية مصر 20
                                                $waPhone = preg_replace('/\D/', '', $user->phone);
                                                if (str_starts_with($waPhone, '0')) {
                                                    $waPhone = '2' . $waPhone; // مصر
                                                } elseif (!str_starts_with($waPhone, '20')) {
                                                    $waPhone = '20' . $waPhone;
                                                }
                                            @endphp
                                            <a href="https://wa.me/{{ $waPhone }}" target="_blank"
                                                class="btn btn-wa" title="فتح واتساب">
                                                {{-- أيقونة واتساب SVG --}}
                                                <svg width="14" height="14" viewBox="0 0 24 24"
                                                    fill="currentColor">
                                                    <path
                                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                {{-- الكارنيه --}}
                                <td style="text-align:center">
                                    @if ($user->syndicate_card)
                                        <button class="btn btn-card"
                                            onclick="showCard('{{ asset('storage/' . $user->syndicate_card) }}','{{ addslashes($user->name) }}')">
                                            <i class="fas fa-id-card"></i> عرض
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300">— لا يوجد —</span>
                                    @endif
                                </td>

                                {{-- الحالة --}}
                                <td style="text-align:center">
                                    <span
                                        class="inline-block px-3 py-1 rounded-full text-xs font-bold {{ $user->is_approved ? 'badge-approved' : 'badge-pending' }}">
                                        {{ $user->is_approved ? '✓ موافق' : '⏳ انتظار' }}
                                    </span>
                                </td>

                                {{-- الإجراءات --}}
                                <td style="text-align:center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('super.admin.pharmacy', $user) }}" target="_blank"
                                            class="btn"
                                            style="background:#f0f9ff;color:#0284c7;border-color:#bae6fd"
                                            title="تقرير المبيعات">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="{{ route('super.admin.toggle', $user) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                class="btn {{ $user->is_approved ? 'btn-revoke' : 'btn-approve' }}">
                                                {{ $user->is_approved ? 'إيقاف' : 'موافقة' }}
                                            </button>
                                        </form>
                                        @if ($user->email !== config('superadmin.email'))
                                            <form method="POST" action="{{ route('super.admin.destroy', $user) }}"
                                                onsubmit="return confirm('هتحذف {{ addslashes($user->name) }} وكل بياناته؟\nالعملية مش قابلة للتراجع!')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-delete" title="حذف">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endif

                                        {{-- زر تغيير الباسورد --}}
                                        <button
                                            onclick="openResetModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                            class="btn"
                                            style="background:#fef9e7;color:#d97706;border-color:#fde68a"
                                            title="تعيين باسورد جديد">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" style="text-align:center;padding:48px;color:#c5c9d8">
                                    <i class="fas fa-users text-5xl mb-4 block"></i>
                                    لا يوجد مستخدمون مطابقون للبحث
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="px-5 py-4 border-t border-gray-100">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- ══ CARD MODAL ══ --}}
    <div id="cardModal" class="modal-bg" style="display:none" onclick="closeCardOnBg(event)">
        <div class="modal-box" style="max-width:500px">
            <div class="px-6 pt-5 pb-4 flex items-center justify-between border-b border-gray-100">
                <div>
                    <div class="font-bold text-gray-800" id="cardUserName"></div>
                    <div class="text-xs text-gray-400">كارنيه النقابة</div>
                </div>
                <button onclick="closeCard()"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <div class="p-5">
                <img id="cardImg" src="" alt="كارنيه النقابة"
                    class="w-full rounded-xl border border-gray-200 shadow-sm">
                <a id="cardDownload" href="" download target="_blank"
                    class="btn btn-primary mt-4 w-full justify-center text-sm">
                    <i class="fas fa-download"></i> تحميل الصورة
                </a>
            </div>
        </div>
    </div>

    {{-- ══ Toast للنسخ ══ --}}
    <div id="copyToast"
        style="
    position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(60px);
    background:#1e293b; color:#fff; padding:10px 20px; border-radius:12px;
    font-size:13px; font-weight:600; z-index:9999;
    transition:transform .3s ease, opacity .3s ease; opacity:0; pointer-events:none;">
        ✅ تم النسخ
    </div>
    <div id="resetModal" class="modal-bg" style="display:none" onclick="closeResetOnBg(event)">
        <div class="modal-box" style="max-width:420px">
            <div class="px-6 pt-5 pb-4 flex items-center justify-between border-b border-gray-100">
                <div>
                    <div class="font-bold text-gray-800">🔑 تعيين باسورد جديد</div>
                    <div class="text-xs text-gray-400 mt-0.5" id="resetUserName"></div>
                </div>
                <button onclick="closeReset()"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <form id="resetForm" method="POST" class="p-6 space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-xs text-gray-500 mb-1">الباسورد الجديد</label>
                    <div class="relative">
                        <input type="text" name="new_password" id="newPassInput"
                            placeholder="ادخل الباسورد الجديد..."
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-indigo-400 font-mono">
                        {{-- زر توليد باسورد تلقائي --}}
                        <button type="button" onclick="generatePass()"
                            class="absolute left-2 top-1/2 -translate-y-1/2 text-xs text-indigo-500 hover:text-indigo-700 font-bold">
                            🎲 عشوائي
                        </button>
                    </div>
                </div>
                {{-- عرض الباسورد للنسخ --}}
                <div id="passPreview"
                    class="hidden bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex items-center justify-between">
                    <span class="font-mono text-sm text-indigo-700 font-bold" id="passPreviewVal"></span>
                    <button type="button" onclick="copyGenerated()"
                        class="text-xs text-indigo-500 hover:text-indigo-700">
                        <i class="fas fa-copy"></i> نسخ
                    </button>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn btn-primary flex-1 justify-center py-2.5">
                        <i class="fas fa-save"></i> حفظ الباسورد
                    </button>
                    <button type="button" onclick="closeReset()" class="btn flex-1 justify-center py-2.5"
                        style="background:#f1f5f9;color:#64748b;border-color:#e2e8f0">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // ── الباسورد toggle ──
        const passState = {}; // تتبع حالة كل باسورد

        function togglePass(id, plain) {
            const val = document.getElementById('pw-val-' + id);
            const icon = document.getElementById('pw-icon-' + id);
            passState[id] = !passState[id];
            if (passState[id]) {
                val.textContent = plain;
                icon.className = 'fas fa-eye-slash text-xs';
            } else {
                val.textContent = '••••••••';
                icon.className = 'fas fa-eye text-xs';
            }
        }

        function copyPass(plain, btn) {
            navigator.clipboard.writeText(plain).then(() => {
                showToast();
                const icon = btn.querySelector('i');
                icon.className = 'fas fa-check text-xs';
                setTimeout(() => icon.className = 'fas fa-copy text-xs', 2000);
            });
        }

        function showToast() {
            const t = document.getElementById('copyToast');
            t.style.transform = 'translateX(-50%) translateY(0)';
            t.style.opacity = '1';
            setTimeout(() => {
                t.style.transform = 'translateX(-50%) translateY(60px)';
                t.style.opacity = '0';
            }, 2000);
        }

        // ── الكارنيه ──
        function showCard(url, name) {
            document.getElementById('cardImg').src = url;
            document.getElementById('cardUserName').textContent = name;
            document.getElementById('cardDownload').href = url;
            document.getElementById('cardModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCard() {
            document.getElementById('cardModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function closeCardOnBg(e) {
            if (e.target.id === 'cardModal') closeCard();
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeCard();
        });

        // ── المدن ──
        const govCities = @json($govCities);

        function updateCities() {
            const gov = document.getElementById('govSelect').value;
            const citySel = document.getElementById('citySelect');
            const cities = govCities[gov] || [];
            citySel.innerHTML = '<option value="">كل المراكز</option>';
            cities.forEach(c => {
                const opt = document.createElement('option');
                opt.value = opt.textContent = c;
                citySel.appendChild(opt);
            });
            citySel.style.display = gov ? 'block' : 'none';
            if (gov) citySel.value = '';
        }

        // ── معاينة الإعلان ──
        function previewAd(input) {
            const wrap = document.getElementById('adPreviewWrap');
            const img = document.getElementById('adPreview');
            if (input.files && input.files[0]) {
                img.src = URL.createObjectURL(input.files[0]);
                wrap.classList.remove('hidden');
            }
        }

        function openResetModal(userId, userName) {
            document.getElementById('resetForm').action = `/super/users/${userId}/reset-password`;
            document.getElementById('resetUserName').textContent = userName;
            document.getElementById('newPassInput').value = '';
            document.getElementById('passPreview').classList.add('hidden');
            document.getElementById('resetModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeReset() {
            document.getElementById('resetModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        function closeResetOnBg(e) {
            if (e.target.id === 'resetModal') closeReset();
        }

        // توليد باسورد عشوائي
        function generatePass() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP0123456789@#';
            let pass = '';
            for (let i = 0; i < 10; i++) {
                pass += chars[Math.floor(Math.random() * chars.length)];
            }
            document.getElementById('newPassInput').value = pass;
            document.getElementById('passPreviewVal').textContent = pass;
            document.getElementById('passPreview').classList.remove('hidden');
        }

        function copyGenerated() {
            const val = document.getElementById('passPreviewVal').textContent;
            navigator.clipboard.writeText(val).then(() => showToast());
        }

        // لما اليوزر بيكتب الباسورد يدوياً يظهر الـ preview
        document.getElementById('newPassInput').addEventListener('input', function() {
            const val = this.value;
            if (val.length > 0) {
                document.getElementById('passPreviewVal').textContent = val;
                document.getElementById('passPreview').classList.remove('hidden');
            } else {
                document.getElementById('passPreview').classList.add('hidden');
            }
        });
    </script>

</body>

</html>

