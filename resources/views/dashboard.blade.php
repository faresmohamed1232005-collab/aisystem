@extends('layouts.app')
@section('title', 'لوحة التحكم')

@section('content')

    <style>
        /* ============================= RESET & BASE ============================= */
        .db * {
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif !important;
        }

        .db {
            background: #f0f3fb;
            padding: 22px;
            direction: rtl;
            min-height: 100vh;
        }

        /* ============================= GRID ============================= */
        .db-grid {
            display: grid;
            gap: 16px;
            margin-bottom: 16px;
        }

        .cols-4 {
            grid-template-columns: repeat(4, 1fr);
        }

        .cols-2-1 {
            grid-template-columns: 2fr 1fr;
        }

        .cols-1-2 {
            grid-template-columns: 1fr 2fr;
        }

        .cols-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .cols-1-1-2 {
            grid-template-columns: 1fr 1fr 2fr;
        }

        @media(max-width:1100px) {
            .cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }

            .cols-2-1,
            .cols-1-2 {
                grid-template-columns: 1fr;
            }

            .cols-3,
            .cols-1-1-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media(max-width:640px) {

            .cols-4,
            .cols-3,
            .cols-1-1-2 {
                grid-template-columns: 1fr 1fr;
            }
        }

        /* ============================= CARD ============================= */
        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eceef5;
            padding: 20px;
            position: relative;
            transition: transform .2s, box-shadow .2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(100, 80, 240, .09);
        }

        /* ============================= STAT CARD ============================= */
        .sc-label {
            font-size: 11px;
            color: #a0a9c0;
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
        }

        .sc-value {
            font-size: 28px;
            font-weight: 800;
            color: #1c2039;
            line-height: 1;
            display: block;
        }

        .sc-unit {
            font-size: 14px;
            font-weight: 400;
            color: #a0a9c0;
        }

        .sc-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            font-size: 11px;
            color: #a0a9c0;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            border-radius: 20px;
            padding: 3px 9px;
            font-size: 10px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-up {
            background: #e3faf0;
            color: #17a85a;
        }

        .badge-down {
            background: #fdecea;
            color: #e33535;
        }

        .badge-neu {
            background: #f1f2f6;
            color: #6b7390;
        }

        /* ============================= SECTION HEADER ============================= */
        .sh {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .sh-title {
            font-size: 13px;
            font-weight: 700;
            color: #1c2039;
            display: block;
        }

        .sh-sub {
            font-size: 11px;
            color: #a0a9c0;
            display: block;
            margin-top: 2px;
        }

        .sh-link {
            font-size: 11px;
            color: #6c5fe6;
            text-decoration: none;
            white-space: nowrap;
        }

        .sh-link:hover {
            text-decoration: underline;
        }

        /* ============================= PRODUCT BARS ============================= */
        .pbar-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .pbar-name {
            font-size: 11px;
            color: #3d4460;
            width: 100px;
            flex-shrink: 0;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pbar-bg {
            flex: 1;
            background: #f0f2fb;
            border-radius: 20px;
            height: 8px;
            overflow: hidden;
        }

        .pbar-fill {
            height: 100%;
            border-radius: 20px;
        }

        .pbar-num {
            font-size: 11px;
            color: #a0a9c0;
            width: 30px;
            text-align: left;
            flex-shrink: 0;
        }

        /* ============================= TABLE ============================= */
        .db-tbl {
            width: 100%;
            border-collapse: collapse;
        }

        .db-tbl th {
            font-size: 10px;
            color: #a0a9c0;
            font-weight: 600;
            padding: 6px 10px;
            border-bottom: 1px solid #f0f2f8;
            text-align: right;
            white-space: nowrap;
        }

        .db-tbl td {
            font-size: 11px;
            color: #3d4460;
            padding: 9px 10px;
            border-bottom: 1px solid #f8f9fd;
            vertical-align: middle;
        }

        .db-tbl tr:last-child td {
            border: none;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-r {
            background: #ef4444;
        }

        .dot-o {
            background: #f97316;
        }

        /* ============================= INVOICE ROWS ============================= */
        .inv-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 0;
            border-bottom: 1px solid #f8f9fd;
        }

        .inv-row:last-child {
            border: none;
        }

        .inv-av {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* ============================= STATUS PILLS ============================= */
        .pill {
            border-radius: 20px;
            padding: 2px 9px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
        }

        .pill-paid {
            background: #e3faf0;
            color: #17a85a;
        }

        .pill-partial {
            background: #fef9e7;
            color: #d97706;
        }

        .pill-defer {
            background: #fdecea;
            color: #e33535;
        }

        /* ============================= LEGEND ============================= */
        .leg {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .leg-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #6b7390;
        }

        .leg-sq {
            width: 10px;
            height: 10px;
            border-radius: 2px;
            flex-shrink: 0;
            display: inline-block;
        }

        /* ============================= DELIVERY BOX ============================= */
        .del-box {
            background: #f7f8ff;
            border-radius: 10px;
            padding: 13px;
            text-align: center;
        }

        /* ============================= DONUT WRAP ============================= */
        .donut-w {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }

        /* ============================= CHART WRAP ============================= */
        .ch-w {
            position: relative;
            width: 100%;
        }

        /* ============================= AVATAR COLORS ============================= */
        .av-v {
            background: #eee9fe;
            color: #7c3aed
        }

        .av-b {
            background: #dbeafe;
            color: #1d4ed8
        }

        .av-g {
            background: #dcfce7;
            color: #15803d
        }

        .av-r {
            background: #fdecea;
            color: #b91c1c
        }

        .av-y {
            background: #fef9c3;
            color: #854d0e
        }

        .av-t {
            background: #ccfbf1;
            color: #0f766e
        }

        .av-p {
            background: #fdf4ff;
            color: #9333ea
        }

        .av-o {
            background: #fff7ed;
            color: #c2410c
        }

        /* ============================= QUICK ACTIONS ============================= */
        .qa-card {
            border-radius: 16px;
            border: 1px solid #eceef5;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none !important;
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
        }

        .qa-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(100, 80, 240, .13);
        }

        .qa-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
        }

        .qa-card .qa-title {
            font-size: 13px;
            font-weight: 700;
        }

        .qa-card .qa-sub {
            font-size: 11px;
            margin-top: 2px;
        }

        .qa-notif-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    @php
        /* ── مقارنات ── */
        $dayDiff =
            $yesterdaySales > 0
                ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100
                : ($todaySales > 0
                    ? 100
                    : 0);
        $weekDiff =
            $lastWeekSales > 0
                ? (($thisWeekSales - $lastWeekSales) / $lastWeekSales) * 100
                : ($thisWeekSales > 0
                    ? 100
                    : 0);
        $dch =
            $deliveryCountLastWeek > 0
                ? (($deliveryCountThisWeek - $deliveryCountLastWeek) / $deliveryCountLastWeek) * 100
                : ($deliveryCountThisWeek > 0
                    ? 100
                    : 0);
        $sch =
            $storeCountLastWeek > 0
                ? (($storeCountThisWeek - $storeCountLastWeek) / $storeCountLastWeek) * 100
                : ($storeCountThisWeek > 0
                    ? 100
                    : 0);

        /* ── ثوابت ── */
        $avCls = ['av-v', 'av-b', 'av-g', 'av-r', 'av-y', 'av-t', 'av-p', 'av-o'];
        $barCols = ['#6c5fe6', '#9d8df1', '#b8aefd', '#d1cbfe', '#e6e3ff'];
        $maxQty = $topProducts->max('total_qty') ?: 1;

        /* ── JSON للرسوم البيانية ── */
        $jLabels = $chartDayLabels->toJson();
        $jData = $chartDayTotals->toJson();
        $jCatLbl = $topCategories->pluck('category')->toJson();
        $jCatData = $topCategories->pluck('total_qty')->toJson();

        /* ── مبيعات حسب طريقة الدفع ── */
        $paymentBreakdown = \App\Models\Sale::where('user_id', Auth::id())
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('payment_method, COUNT(*) as cnt, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();
    @endphp

    <div class="db">

        {{-- ════════════════════════════════════════
     ROW 1 — Stat Cards (4) + Top Products
════════════════════════════════════════ --}}
        <div class="db-grid" style="grid-template-columns: 1fr 1fr 1fr 1fr 1.6fr">

            {{-- مبيعات اليوم --}}
            <div class="card">
                <span class="sc-label">مبيعات اليوم</span>
                <span class="sc-value">{{ number_format($todaySales, 0) }} <span class="sc-unit">ج.م</span></span>
                <div class="sc-meta">
                    <span class="badge {{ $dayDiff >= 0 ? 'badge-up' : 'badge-down' }}">
                        @if ($dayDiff >= 0)
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#17a85a"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="18 15 12 9 6 15" />
                            </svg>
                        @else
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#e33535"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        @endif
                        {{ number_format(abs($dayDiff), 1) }}%
                    </span>
                    مقارنةً بالأمس
                </div>
            </div>

            {{-- مبيعات الأسبوع --}}
            <div class="card">
                <span class="sc-label">مبيعات الأسبوع</span>
                <span class="sc-value">{{ number_format($thisWeekSales, 0) }} <span class="sc-unit">ج.م</span></span>
                <div class="sc-meta">
                    <span class="badge {{ $weekDiff >= 0 ? 'badge-up' : 'badge-down' }}">
                        @if ($weekDiff >= 0)
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#17a85a"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="18 15 12 9 6 15" />
                            </svg>
                        @else
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#e33535"
                                stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        @endif
                        {{ number_format(abs($weekDiff), 1) }}%
                    </span>
                    مقارنةً بالأسبوع الماضي
                </div>
            </div>

            {{-- مخزون منخفض --}}
            <div class="card">
                <span class="sc-label">مخزون منخفض</span>
                <span class="sc-value" style="color:#ef4444">{{ $lowStock }}</span>
                <div class="sc-meta">
                    <span class="badge badge-neu">{{ $expiringSoon }} قارب الانتهاء</span>
                </div>
            </div>

            {{-- طلبات معلقة --}}
            <div class="card">
                <span class="sc-label">طلبات معلقة</span>
                <span class="sc-value" style="color:#6c5fe6">{{ $pendingOrders }}</span>
                <div class="sc-meta">
                    <span class="badge badge-neu">{{ $totalCustomers }} عميل</span>
                </div>
            </div>

            {{-- أعلى المنتجات مبيعاً --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">أعلى المنتجات مبيعاً</span>
                        <span class="sh-sub">حسب الكمية المباعة</span>
                    </div>

                </div>
                @forelse($topProducts as $i => $tp)
                    @php
                        $pct = round(($tp->total_qty / $maxQty) * 100);
                        $nm = $tp->drug?->name_ar ?? ($tp->drug?->name_en ?? 'منتج');
                        $short = mb_strlen($nm) > 17 ? mb_substr($nm, 0, 17) . '…' : $nm;
                    @endphp
                    <div class="pbar-row">
                        <div class="pbar-name">{{ $short }}</div>
                        <div class="pbar-bg">
                            <div class="pbar-fill"
                                style="width:{{ $pct }}%;background:{{ $barCols[$i] ?? '#6c5fe6' }}"></div>
                        </div>
                        <div class="pbar-num">{{ number_format($tp->total_qty) }}</div>
                    </div>
                @empty
                    <p style="text-align:center;color:#c5c9d8;font-size:12px;padding:18px 0">لا توجد بيانات حتى الآن</p>
                @endforelse
            </div>

        </div>

        {{-- ════════════════════════════════════════
     ROW 2 — Area Chart + Low Stock Table
════════════════════════════════════════ --}}
        <div class="db-grid cols-2-1">

            {{-- رسم بياني المبيعات --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">إجمالي المبيعات</span>
                        <span class="sh-sub">آخر 7 أيام</span>
                    </div>
                    <div style="text-align:left">
                        <div style="font-size:22px;font-weight:800;color:#1c2039;line-height:1">
                            {{ number_format($monthSales, 0) }}
                            <span style="font-size:13px;font-weight:400;color:#a0a9c0">ج.م</span>
                        </div>
                        <div style="font-size:10px;color:#a0a9c0;margin-top:3px">إجمالي الشهر</div>
                    </div>
                </div>
                <div class="ch-w" style="height:160px">
                    <canvas id="salesLineChart"></canvas>
                </div>
            </div>

            {{-- منتجات منخفضة المخزون --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">مخزون منخفض</span>
                        <span class="sh-sub">{{ $lowStock }} منتج يحتاج تجديد</span>
                    </div>
                    <a href="{{ route('products.index') }}" class="sh-link">عرض الكل</a>
                </div>
                <table class="db-tbl">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style="text-align:center">الكمية</th>
                            <th style="text-align:center">الحد الأدنى</th>
                            <th style="text-align:center">●</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(\App\Models\Product::where('user_id', Auth::id())
                        ->whereColumn('quantity','<=','min_quantity')
                        ->orderBy('quantity')->limit(6)->get() as $p)
                            <tr>
                                <td style="font-weight:600">
                                    {{ mb_strlen($p->name) > 20 ? mb_substr($p->name, 0, 20) . '…' : $p->name }}</td>
                                <td
                                    style="text-align:center;font-weight:700;color:{{ $p->quantity == 0 ? '#ef4444' : '#f97316' }}">
                                    {{ $p->quantity }}</td>
                                <td style="text-align:center;color:#a0a9c0">{{ $p->min_quantity }}</td>
                                <td style="text-align:center"><span
                                        class="dot {{ $p->quantity == 0 ? 'dot-r' : 'dot-o' }}"></span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:#c5c9d8;padding:18px;font-size:12px">🎉
                                    المخزون ممتاز!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        {{-- ════════════════════════════════════════
     ROW 3 — Recent Sales | Sales by Channel | Users (Delivery)
════════════════════════════════════════ --}}
        <div class="db-grid cols-3">

            {{-- آخر المبيعات --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">آخر المبيعات</span>
                        <span class="sh-sub">{{ $todayCount }} فاتورة اليوم</span>
                    </div>

                </div>
                @forelse($recentSales as $sale)
                    @php
                        $av = $avCls[$loop->index % count($avCls)];
                        $init = mb_substr($sale->customer?->name ?? 'ع', 0, 1);
                        $pCls = match ($sale->payment_status) {
                            'paid' => 'pill-paid',
                            'partial' => 'pill-partial',
                            default => 'pill-defer',
                        };
                        $pLbl = match ($sale->payment_status) {
                            'paid' => 'مدفوع',
                            'partial' => 'جزئي',
                            default => 'آجل',
                        };
                    @endphp
                    <div class="inv-row">
                        <div style="display:flex;align-items:center;gap:9px">
                            <div class="inv-av {{ $av }}">{{ $init }}</div>
                            <div>
                                <div style="font-size:12px;font-weight:600;color:#1c2039">
                                    {{ $sale->customer?->name ?? 'عميل عام' }}</div>
                                <div style="font-size:10px;color:#a0a9c0">{{ $sale->created_at->format('h:i A') }}</div>
                            </div>
                        </div>
                        <div style="text-align:left">
                            <div style="font-size:12px;font-weight:700;color:#1c2039">{{ number_format($sale->total, 0) }}
                                ج.م</div>
                            <span class="pill {{ $pCls }}">{{ $pLbl }}</span>
                        </div>
                    </div>
                @empty
                    <p style="text-align:center;color:#c5c9d8;font-size:12px;padding:24px 0">لا توجد فواتير</p>
                @endforelse
            </div>

            {{-- المبيعات حسب الفئة - Pie Chart --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">المبيعات حسب الفئة</span>
                        <span class="sh-sub">الكميات الإجمالية</span>
                    </div>
                </div>
                <div style="display:flex;justify-content:center;margin-bottom:14px">
                    <div class="ch-w" style="height:170px;max-width:200px">
                        <canvas id="catPieChart"></canvas>
                    </div>
                </div>
                <div class="leg" style="justify-content:center">
                    @foreach ($topCategories as $i => $cat)
                        <span class="leg-item">
                            <span class="leg-sq"
                                style="background:{{ $barCols[$i] ?? '#6c5fe6' }};border-radius:50%"></span>
                            {{ mb_substr($cat->category, 0, 10) }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- التوصيل - Donut + مقارنة --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">نوع الطلبات</span>
                        <span class="sh-sub">هذا الأسبوع مقابل الأسبوع الماضي</span>
                    </div>
                </div>
                <div style="display:flex;justify-content:center;gap:20px;margin:4px 0 10px">
                    <div style="text-align:center">
                        <div style="font-size:10px;color:#a0a9c0;margin-bottom:6px">هذا الأسبوع</div>
                        <div class="donut-w"><canvas id="del1"></canvas></div>
                    </div>
                    <div style="text-align:center">
                        <div style="font-size:10px;color:#a0a9c0;margin-bottom:6px">الأسبوع الماضي</div>
                        <div class="donut-w"><canvas id="del2"></canvas></div>
                    </div>
                </div>
                <div class="leg" style="justify-content:center;margin:6px 0 12px">
                    <span class="leg-item"><span class="leg-sq"
                            style="background:#6c5fe6;border-radius:50%"></span>توصيل</span>
                    <span class="leg-item"><span class="leg-sq" style="background:#9d8df1;border-radius:50%"></span>من
                        الصيدلية</span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div class="del-box">
                        <div style="font-size:10px;color:#a0a9c0">توصيل الأسبوع</div>
                        <div style="font-size:20px;font-weight:800;color:#6c5fe6">{{ $deliveryCountThisWeek }}</div>
                        <span class="badge {{ $dch >= 0 ? 'badge-up' : 'badge-down' }}">
                            @if ($dch >= 0)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#17a85a"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15" />
                                </svg>
                            @else
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#e33535"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            @endif
                            {{ number_format(abs($dch), 0) }}%
                        </span>
                    </div>
                    <div class="del-box">
                        <div style="font-size:10px;color:#a0a9c0">استلام الأسبوع</div>
                        <div style="font-size:20px;font-weight:800;color:#9d8df1">{{ $storeCountThisWeek }}</div>
                        <span class="badge {{ $sch >= 0 ? 'badge-up' : 'badge-down' }}">
                            @if ($sch >= 0)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#17a85a"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="18 15 12 9 6 15" />
                                </svg>
                            @else
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#e33535"
                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            @endif
                            {{ number_format(abs($sch), 0) }}%
                        </span>
                    </div>
                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════
     ROW 4 — Recent Purchases + Quick Actions
════════════════════════════════════════ --}}
        <div class="db-grid cols-2-1">

            {{-- آخر المشتريات --}}
            <div class="card">
                <div class="sh">
                    <div>
                        <span class="sh-title">آخر المشتريات</span>
                        <span class="sh-sub">آخر 5 فواتير شراء</span>
                    </div>
                    <a href="{{ route('purchases.index') }}" class="sh-link">الكل</a>
                </div>
                <table class="db-tbl">
                    <thead>
                        <tr>
                            <th>المورد</th>
                            <th>رقم الفاتورة</th>
                            <th style="text-align:center">الإجمالي</th>
                            <th style="text-align:center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPurchases as $inv)
                            @php
                                $pCls = match ($inv->payment_status) {
                                    'paid' => 'pill-paid',
                                    'partial' => 'pill-partial',
                                    default => 'pill-defer',
                                };
                                $pLbl = match ($inv->payment_status) {
                                    'paid' => 'مدفوع',
                                    'partial' => 'جزئي',
                                    default => 'غير مدفوع',
                                };
                                $sn = $inv->supplier?->name ?? '—';
                                $sn = mb_strlen($sn) > 18 ? mb_substr($sn, 0, 18) . '…' : $sn;
                            @endphp
                            <tr>
                                <td style="font-weight:600">{{ $sn }}</td>
                                <td style="color:#a0a9c0">{{ $inv->invoice_number }}</td>
                                <td style="text-align:center;font-weight:700;color:#1c2039">
                                    {{ number_format($inv->net_total, 0) }} ج.م</td>
                                <td style="text-align:center"><span
                                        class="pill {{ $pCls }}">{{ $pLbl }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:#c5c9d8;padding:18px;font-size:12px">لا
                                    توجد فواتير بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- إجراءات سريعة --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-content:start">

                {{-- بيع جديد --}}
                <a href="{{ route('sales.create') }}" class="qa-card" style="background:#6c5fe6;border-color:#6c5fe6">
                    <div class="qa-icon" style="background:rgba(255,255,255,.2)">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" />
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#fff">بيع جديد</div>
                        <div class="qa-sub" style="color:rgba(255,255,255,.65)">فاتورة مباشرة</div>
                    </div>
                </a>

                {{-- شراء جديد --}}
                <a href="{{ route('purchases.create') }}" class="qa-card" style="background:#fff">
                    <div class="qa-icon" style="background:#eee9fe">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6c5fe6"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13" />
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#1c2039">شراء جديد</div>
                        <div class="qa-sub" style="color:#a0a9c0">فاتورة مورد</div>
                    </div>
                </a>

                {{-- منتج جديد --}}
                <a href="{{ route('products.create') }}" class="qa-card" style="background:#fff">
                    <div class="qa-icon" style="background:#dcfce7">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#15803d"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="16" />
                            <line x1="8" y1="12" x2="16" y2="12" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#1c2039">منتج جديد</div>
                        <div class="qa-sub" style="color:#a0a9c0">إضافة للمخزن</div>
                    </div>
                </a>

                {{-- الإشعارات --}}
                <a href="{{ route('notifications.index') }}" class="qa-card" style="background:#fff;position:relative">
                    @if ($notifications->count() > 0)
                        <span class="qa-notif-badge">{{ $notifications->count() }}</span>
                    @endif
                    <div class="qa-icon" style="background:#fff7ed">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#c2410c"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#1c2039">الإشعارات</div>
                        <div class="qa-sub" style="color:#a0a9c0">{{ $notifications->count() }} جديد</div>
                    </div>
                </a>

                {{-- العملاء --}}
                <a href="{{ route('customers.index') }}" class="qa-card" style="background:#fff">
                    <div class="qa-icon" style="background:#dbeafe">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#1c2039">العملاء</div>
                        <div class="qa-sub" style="color:#a0a9c0">{{ $totalCustomers }} عميل</div>
                    </div>
                </a>

                {{-- الموردين --}}
                <a href="{{ route('suppliers.index') }}" class="qa-card" style="background:#fff">
                    <div class="qa-icon" style="background:#fdf4ff">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9333ea"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="15" rx="2" />
                            <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                            <line x1="12" y1="12" x2="12" y2="16" />
                            <line x1="10" y1="14" x2="14" y2="14" />
                        </svg>
                    </div>
                    <div>
                        <div class="qa-title" style="color:#1c2039">الموردين</div>
                        <div class="qa-sub" style="color:#a0a9c0">{{ $totalSuppliers }} مورد</div>
                    </div>
                </a>

            </div>
        </div>

    </div>{{-- end .db --}}

    {{-- ════════ Chart.js ════════ --}}
    <script>
        document.addEventListener('chart-ready', function () {
            Chart.defaults.font.family = 'Cairo, sans-serif';

            var V1 = '#6c5fe6';
            var V2 = '#9d8df1';
            var COLS = ['#6c5fe6', '#9d8df1', '#b8aefd', '#d1cbfe', '#e6e3ff'];

            /* ── Area / Line Chart ── */
            (function() {
                var el = document.getElementById('salesLineChart');
                if (!el) return;
                var g = el.getContext('2d').createLinearGradient(0, 0, 0, 155);
                g.addColorStop(0, 'rgba(108,95,230,.20)');
                g.addColorStop(1, 'rgba(108,95,230,0)');
                new Chart(el, {
                    type: 'line',
                    data: {
                        labels: {!! $jLabels !!},
                        datasets: [{
                            data: {!! $jData !!},
                            borderColor: V1,
                            borderWidth: 2.5,
                            pointBackgroundColor: V1,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            fill: true,
                            backgroundColor: g,
                            tension: 0.42
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#1c2039',
                                titleColor: V2,
                                bodyColor: '#fff',
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(c) {
                                        return ' ' + Number(c.parsed.y).toLocaleString() + ' ج.م';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#f0f2f8'
                                },
                                ticks: {
                                    color: '#a0a9c0',
                                    font: {
                                        size: 10
                                    },
                                    callback: function(v) {
                                        return v >= 1000 ? (v / 1000) + 'k' : v;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#a0a9c0',
                                    font: {
                                        size: 10
                                    },
                                    autoSkip: false,
                                    maxRotation: 0
                                }
                            }
                        }
                    }
                });
            })();

            /* ── Category Pie Chart ── */
            (function() {
                var el = document.getElementById('catPieChart');
                if (!el) return;
                new Chart(el, {
                    type: 'doughnut',
                    data: {
                        labels: {!! $jCatLbl !!},
                        datasets: [{
                            data: {!! $jCatData !!},
                            backgroundColor: COLS,
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '55%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#1c2039',
                                titleColor: V2,
                                bodyColor: '#fff',
                                padding: 10,
                                cornerRadius: 8
                            }
                        }
                    }
                });
            })();

            /* ── Donut helper ── */
            function makeDonut(id, d, s) {
                var el = document.getElementById(id);
                if (!el) return;
                var t = d + s;
                new Chart(el, {
                    type: 'doughnut',
                    data: {
                        labels: ['توصيل', 'صيدلية'],
                        datasets: [{
                            data: t > 0 ? [d, s] : [1, 1],
                            backgroundColor: t > 0 ? [V1, V2] : ['#e5e7eb', '#e5e7eb'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: t > 0,
                                backgroundColor: '#1c2039',
                                titleColor: V2,
                                bodyColor: '#fff',
                                padding: 8,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(c) {
                                        return ' ' + c.label + ': ' + c.parsed;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            makeDonut('del1', {{ (int) $deliveryCountThisWeek }}, {{ (int) $storeCountThisWeek }});
            makeDonut('del2', {{ (int) $deliveryCountLastWeek }}, {{ (int) $storeCountLastWeek }});
        });
    </script>

@endsection
