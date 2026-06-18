<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مبيعات — {{ $user->pharmacy_name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * {
            font-family: 'Cairo', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #f0f3fb;
            min-height: 100vh;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #eceef5;
            box-shadow: 0 2px 12px rgba(100, 80, 240, .06);
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
            padding: 11px 14px;
            border-bottom: 1px solid #f8f9fd;
            vertical-align: middle;
        }

        .tbl tbody tr:last-child td {
            border: none;
        }

        .tbl tbody tr:hover td {
            background: #fafbff;
        }

        .period-btn {
            font-size: 13px;
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: all .2s;
            font-family: 'Cairo', sans-serif;
        }

        .period-btn.active {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
        }

        .period-btn:hover:not(.active) {
            background: #eef0ff;
            border-color: #c7d2fe;
        }

        input[type=date] {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 13px;
            font-family: 'Cairo', sans-serif;
            outline: none;
        }

        input[type=date]:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .12);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .card {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
        }
    </style>
</head>

<body class="p-4 md:p-6 space-y-5">
    <div id="pdfHeader"
        style="display:none; direction:rtl; font-family:'Cairo',sans-serif;
     background:#fff; padding:20px 24px; width:1280px; border-bottom:2px solid #e0e4f5;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <div style="font-size:20px; font-weight:900; color:#1e1b4b;">
                    🏥 {{ $user->pharmacy_name }}
                </div>
                <div style="font-size:13px; color:#64748b; margin-top:4px;">
                    👤 {{ $user->name }}
                    &nbsp;|&nbsp;
                    📍 {{ $user->governorate }} / {{ $user->city }}
                    &nbsp;|&nbsp;
                    📞 {{ $user->phone }}
                </div>
            </div>
            <div style="text-align:left;">
                <div style="font-size:13px; color:#6366f1; font-weight:700;">
                    📅 {{ $periodLabel }}
                </div>
                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">
                    {{ $dateFrom->format('Y/m/d') }} ← {{ $dateTo->format('Y/m/d') }}
                </div>
            </div>
        </div>

        {{-- ملخص الأرقام --}}
        <div style="display:flex; gap:16px; margin-top:14px; flex-wrap:wrap;">
            <div style="background:#eef2ff; border-radius:10px; padding:10px 18px; flex:1; text-align:center;">
                <div style="font-size:11px; color:#818cf8;">إجمالي الإيرادات</div>
                <div style="font-size:16px; font-weight:900; color:#4338ca;">{{ number_format($totalRevenue, 2) }} ج.م
                </div>
            </div>
            <div style="background:#f0fdf4; border-radius:10px; padding:10px 18px; flex:1; text-align:center;">
                <div style="font-size:11px; color:#86efac;">صافي الأرباح</div>
                <div style="font-size:16px; font-weight:900; color:{{ $totalProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                    {{ number_format($totalProfit, 2) }} ج.م
                </div>
            </div>
            <div style="background:#eff6ff; border-radius:10px; padding:10px 18px; flex:1; text-align:center;">
                <div style="font-size:11px; color:#93c5fd;">عدد الفواتير</div>
                <div style="font-size:16px; font-weight:900; color:#1d4ed8;">{{ $totalCount }}</div>
            </div>
            <div style="background:#fff7ed; border-radius:10px; padding:10px 18px; flex:1; text-align:center;">
                <div style="font-size:11px; color:#fdba74;">التكلفة الإجمالية</div>
                <div style="font-size:16px; font-weight:900; color:#c2410c;">{{ number_format($totalCost, 2) }} ج.م
                </div>
            </div>
            <div style="background:#fdf4ff; border-radius:10px; padding:10px 18px; flex:1; text-align:center;">
                <div style="font-size:11px; color:#d8b4fe;">هامش الربح</div>
                <div
                    style="font-size:16px; font-weight:900;
                color:{{ $profitMargin >= 30 ? '#7c3aed' : ($profitMargin >= 15 ? '#d97706' : '#dc2626') }};">
                    {{ number_format($profitMargin, 1) }}%
                </div>
            </div>
        </div>
    </div>
    {{-- ══ HEADER ══ --}}
    <div class="card p-5 flex flex-wrap items-center justify-between gap-4 no-print">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-2xl">🏥</div>
            <div>
                <div class="text-xl font-black text-gray-800">{{ $user->pharmacy_name }}</div>
                <div class="text-sm text-gray-400 mt-0.5">
                    <i class="fas fa-user ml-1"></i>{{ $user->name }}
                    &nbsp;|&nbsp;
                    <i class="fas fa-map-marker-alt ml-1"></i>{{ $user->governorate }} / {{ $user->city }}
                    &nbsp;|&nbsp;
                    <i class="fas fa-phone ml-1"></i>{{ $user->phone }}
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="exportPDF()"
                class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-bold transition">
                <i class="fas fa-file-pdf"></i> تحميل PDF
            </button>
            <a href="{{ route('super.admin.index') }}"
                class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm transition">
                <i class="fas fa-arrow-right"></i> العودة
            </a>
        </div>
    </div>

    {{-- ══ PERIOD FILTER ══ --}}
    <div class="card p-4 no-print">
        <form method="GET" id="filterForm" class="flex flex-wrap items-center gap-2">
            @foreach ([
        'today' => 'اليوم',
        'week' => 'هذا الأسبوع',
        'month' => 'هذا الشهر',
        'last10' => 'آخر 10 أيام',
        'custom' => 'تاريخ مخصص',
    ] as $val => $label)
                <button type="submit" name="period" value="{{ $val }}"
                    class="period-btn {{ $period === $val ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach

            <div id="customRange" class="flex items-center gap-2 {{ $period === 'custom' ? '' : 'hidden' }}">
                <input type="date" name="from" value="{{ request('from', $dateFrom->format('Y-m-d')) }}">
                <span class="text-gray-400 text-sm">إلى</span>
                <input type="date" name="to" value="{{ request('to', $dateTo->format('Y-m-d')) }}">
                <button type="submit" name="period" value="custom"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-bold">
                    <i class="fas fa-filter ml-1"></i> تطبيق
                </button>
            </div>
        </form>
    </div>

    {{-- ══ STATS ══ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([['إجمالي الإيرادات', number_format($totalRevenue, 2) . ' ج.م', 'text-indigo-600', 'fa-dollar-sign', '#eee9fe', '#6c5fe6'], ['صافي الأرباح', number_format($totalProfit, 2) . ' ج.م', $totalProfit >= 0 ? 'text-green-600' : 'text-red-500', 'fa-chart-line', '#e3faf0', '#17a85a'], ['عدد الفواتير', $totalCount . ' فاتورة', 'text-gray-800', 'fa-receipt', '#eff6ff', '#3b82f6'], ['إجمالي الخصومات', number_format($totalDiscount, 2) . ' ج.م', 'text-orange-500', 'fa-tags', '#fff7ed', '#f97316']] as [$lbl, $val, $cls, $icon, $bg, $ic])
            <div class="card p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">{{ $lbl }}</div>
                        <div class="text-xl font-black {{ $cls }}">{{ $val }}</div>
                    </div>
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                        style="background:{{ $bg }}">
                        <i class="fas {{ $icon }} text-sm" style="color:{{ $ic }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- هامش الربح --}}
    <div class="card p-4 flex items-center gap-4">
        <div class="text-sm text-gray-500 whitespace-nowrap">هامش الربح الإجمالي:</div>
        <div class="flex-1 h-3 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all"
                style="width:{{ min($profitMargin, 100) }}%;
                    background:{{ $profitMargin >= 30 ? '#22c55e' : ($profitMargin >= 15 ? '#f59e0b' : '#ef4444') }}">
            </div>
        </div>
        <div
            class="text-sm font-bold {{ $profitMargin >= 30 ? 'text-green-600' : ($profitMargin >= 15 ? 'text-yellow-500' : 'text-red-500') }}">
            {{ number_format($profitMargin, 1) }}%
        </div>
        <div class="text-xs text-gray-400">
            التكلفة: <span class="font-semibold text-gray-600">{{ number_format($totalCost, 2) }} ج.م</span>
        </div>
    </div>

    {{-- ══ CHART ══ --}}
    <div class="card p-5 no-print">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-chart-area text-indigo-500"></i>
            المبيعات اليومية —
            <span class="text-indigo-600">{{ $periodLabel }}</span>
            <span class="text-xs text-gray-400 font-normal mr-1">
                ({{ $dateFrom->format('Y/m/d') }} → {{ $dateTo->format('Y/m/d') }})
            </span>
        </h3>
        @if (array_sum($chartRevenue) == 0)
            <div class="flex items-center justify-center h-32 text-gray-300 text-sm">
                <div class="text-center">
                    <i class="fas fa-chart-area text-4xl block mb-2"></i>
                    لا توجد مبيعات في هذه الفترة
                </div>
            </div>
        @else
            <canvas id="lineChart" height="80"></canvas>
        @endif
    </div>

    {{-- ══ DRUGS TABLE ══ --}}
    <div class="card overflow-hidden" id="drugsSectionCard">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-pills text-indigo-500"></i>
                    مبيعات الصيدلية — تفصيلي
                </div>
                <div class="text-xs text-gray-400 mt-0.5">
                    {{ $allDrugs->total() }} دواء مختلف
                    &nbsp;|&nbsp; صفحة {{ $allDrugs->currentPage() }} من {{ $allDrugs->lastPage() }}
                </div>
            </div>
            <button onclick="exportPDF()"
                class="no-print flex items-center gap-2 bg-red-50 hover:bg-red-100 text-red-600
                       border border-red-200 px-4 py-2 rounded-xl text-sm font-bold transition">
                <i class="fas fa-file-pdf"></i> تصدير PDF كامل
            </button>
        </div>

        {{-- Table --}}
        @if ($allDrugs->isEmpty())
            <div class="text-center text-gray-300 py-16">
                <i class="fas fa-box-open text-5xl block mb-3"></i>
                <div class="text-sm">لا توجد مبيعات في هذه الفترة</div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="tbl w-full" id="drugsTable" style="min-width:700px">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>اسم الدواء</th>
                            <th>الفئة</th>
                            <th style="text-align:center">الكمية المباعة</th>
                            <th style="text-align:left">متوسط السعر</th>
                            <th style="text-align:left">إجمالي الإيراد</th>
                            <th style="text-align:left">التكلفة</th>
                            <th style="text-align:left">الربح</th>
                            <th style="text-align:center">الهامش %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $startIndex = ($allDrugs->currentPage()-1) * $allDrugs->perPage() @endphp
                        @foreach ($allDrugs as $i => $drug)
                            @php
                                $margin =
                                    $drug->total_revenue > 0 ? ($drug->total_profit / $drug->total_revenue) * 100 : 0;
                                $marginColor =
                                    $margin >= 30
                                        ? 'text-green-600 bg-green-50'
                                        : ($margin >= 10
                                            ? 'text-yellow-600 bg-yellow-50'
                                            : 'text-red-500 bg-red-50');
                            @endphp
                            <tr>
                                <td class="text-gray-300 text-xs font-mono">{{ $startIndex + $i + 1 }}</td>

                                <td>
                                    <div class="font-semibold text-gray-800">{{ $drug->name }}</div>
                                </td>

                                <td>
                                    @if ($drug->category)
                                        <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">
                                            {{ $drug->category }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>

                                <td style="text-align:center">
                                    <span class="font-bold text-gray-800 text-sm">
                                        {{ number_format($drug->total_qty) }}
                                    </span>
                                    <span class="text-xs text-gray-400">وحدة</span>
                                </td>

                                <td style="text-align:left" class="text-gray-600 text-xs font-mono">
                                    {{ number_format($drug->avg_price, 2) }} ج.م
                                </td>

                                <td style="text-align:left">
                                    <span
                                        class="font-bold text-gray-800">{{ number_format($drug->total_revenue, 2) }}</span>
                                    <span class="text-xs text-gray-400 mr-0.5">ج.م</span>
                                </td>

                                <td style="text-align:left" class="text-gray-500 text-xs font-mono">
                                    {{ number_format($drug->total_cost, 2) }} ج.م
                                </td>

                                <td style="text-align:left">
                                    <span
                                        class="font-bold {{ $drug->total_profit >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                        {{ number_format($drug->total_profit, 2) }}
                                    </span>
                                    <span class="text-xs text-gray-400 mr-0.5">ج.م</span>
                                </td>

                                <td style="text-align:center">
                                    <span class="text-xs font-bold px-2 py-1 rounded-lg {{ $marginColor }}">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    {{-- Totals Row --}}
                    <tfoot>
                        <tr style="background:#f8f9ff;border-top:2px solid #e0e4f5">
                            <td colspan="3" class="px-4 py-3 font-black text-indigo-700 text-sm">
                                الإجمالي ({{ $periodLabel }})
                            </td>
                            <td style="text-align:center" class="font-black text-gray-800 text-sm">
                                —
                            </td>
                            <td></td>
                            <td style="text-align:left" class="font-black text-gray-800">
                                {{ number_format($totalRevenue, 2) }} ج.م
                            </td>
                            <td style="text-align:left" class="font-bold text-gray-500 text-xs">
                                {{ number_format($totalCost, 2) }} ج.م
                            </td>
                            <td style="text-align:left"
                                class="font-black {{ $totalProfit >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                {{ number_format($totalProfit, 2) }} ج.م
                            </td>
                            <td style="text-align:center"
                                class="font-black {{ $profitMargin >= 30 ? 'text-green-600' : ($profitMargin >= 15 ? 'text-yellow-600' : 'text-red-500') }}">
                                {{ number_format($profitMargin, 1) }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($allDrugs->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 no-print">
                    {{ $allDrugs->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ══ PAYMENT ══ --}}
    <div class="card p-5">
        <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-credit-card text-indigo-500"></i> طرق الدفع
        </h3>
        @php
            $payLabels = ['cash' => '💵 كاش', 'card' => '💳 بطاقة', 'insurance' => '🏥 تأمين', 'deferred' => '📋 آجل'];
            $barColors = ['cash' => '#22c55e', 'card' => '#3b82f6', 'insurance' => '#a855f7', 'deferred' => '#ef4444'];
        @endphp
        @forelse($paymentChart as $pm)
            @php $pct = $totalRevenue > 0 ? ($pm->revenue/$totalRevenue)*100 : 0; @endphp
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="font-semibold text-gray-700">
                        {{ $payLabels[$pm->payment_method] ?? $pm->payment_method }}
                    </span>
                    <span class="text-gray-500 text-xs">
                        {{ $pm->count }} فاتورة &nbsp;|&nbsp;
                        <span class="font-bold text-gray-700">{{ number_format($pm->revenue, 2) }} ج.م</span>
                        &nbsp;({{ number_format($pct, 1) }}%)
                    </span>
                </div>
                <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full"
                        style="width:{{ $pct }}%; background:{{ $barColors[$pm->payment_method] ?? '#6366f1' }}">
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-gray-300 py-6 text-sm">لا توجد بيانات</div>
        @endforelse
    </div>

    {{-- ══ SCRIPTS ══ --}}
    <script>
        // ── Chart ──
        @if (array_sum($chartRevenue) > 0)
            new Chart(document.getElementById('lineChart'), {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [{
                            label: 'الإيرادات (ج.م)',
                            data: @json($chartRevenue),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.08)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#6366f1',
                            pointRadius: 4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'عدد الفواتير',
                            data: @json($chartCount),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.05)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4,
                            pointBackgroundColor: '#10b981',
                            pointRadius: 3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Cairo'
                                },
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.datasetIndex === 0 ?
                                    ` ${ctx.parsed.y.toFixed(2)} ج.م` : ` ${ctx.parsed.y} فاتورة`
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Cairo',
                                    size: 11
                                }
                            }
                        },
                        y: {
                            position: 'right',
                            ticks: {
                                font: {
                                    family: 'Cairo',
                                    size: 11
                                },
                                callback: v => v + ' ج.م'
                            }
                        },
                        y1: {
                            position: 'left',
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Cairo',
                                    size: 11
                                },
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        @endif

        // ── Show/hide custom date range ──
        document.querySelectorAll('button[name=period]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('customRange').classList.toggle('hidden', btn.value !== 'custom');
            });
        });

        // ── PDF Export (all pages data passed as JSON) ──
        @php
            $drugsForExport = $allDrugs->map(function ($d) {
                return [
                    'name' => $d->name,
                    'category' => $d->category ?? '—',
                    'total_qty' => $d->total_qty,
                    'avg_price' => round($d->avg_price, 2),
                    'total_revenue' => round($d->total_revenue, 2),
                    'total_cost' => round($d->total_cost, 2),
                    'total_profit' => round($d->total_profit, 2),
                    'margin' => $d->total_revenue > 0 ? round(($d->total_profit / $d->total_revenue) * 100, 1) : 0,
                ];
            });
        @endphp

        const allDrugsData = @json($drugsForExport);

        async function exportPDF() {
            const loader = document.createElement('div');
            loader.innerHTML = `<div style="position:fixed;inset:0;background:rgba(0,0,0,.5);
        display:flex;align-items:center;justify-content:center;z-index:9999;
        font-family:Cairo,sans-serif;color:#fff;font-size:18px;gap:12px">
        <i class="fas fa-spinner fa-spin"></i> جاري إنشاء الـ PDF...
    </div>`;
            document.body.appendChild(loader);

            try {
                const {
                    jsPDF
                } = window.jspdf;

                // ① إظهار الهيدر مؤقتاً
                const headerEl = document.getElementById('pdfHeader');
                headerEl.style.display = 'block';

                const tableEl = document.getElementById('drugsSectionCard');
                const btns = tableEl.querySelectorAll('.no-print');
                btns.forEach(el => el.style.display = 'none');

                // ② تصوير الهيدر والجدول
                const [headerCanvas, tableCanvas] = await Promise.all([
                    html2canvas(headerEl, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        scrollX: 0,
                        scrollY: 0
                    }),
                    html2canvas(tableEl, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        scrollX: 0,
                        scrollY: -window.scrollY
                    }),
                ]);

                // ③ أخفِ الهيدر وأرجع الأزرار
                headerEl.style.display = 'none';
                btns.forEach(el => el.style.display = '');

                // ④ إنشاء PDF
                const pdf = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });
                const pageW = pdf.internal.pageSize.getWidth();
                const pageH = pdf.internal.pageSize.getHeight();
                const margin = 8;
                const usableW = pageW - margin * 2;

                // رسم الهيدر
                const hRatio = headerCanvas.height / headerCanvas.width;
                const hDrawH = usableW * hRatio;
                pdf.addImage(headerCanvas.toDataURL('image/jpeg', 0.97), 'JPEG', margin, margin, usableW, hDrawH);

                // رسم الجدول تحت الهيدر مع دعم تعدد الصفحات
                const tRatio = tableCanvas.height / tableCanvas.width;
                const tDrawH = usableW * tRatio;
                const tableTop = margin + hDrawH + 4;
                const maxH = pageH - tableTop - margin;

                if (tDrawH <= maxH) {
                    pdf.addImage(tableCanvas.toDataURL('image/jpeg', 0.97), 'JPEG', margin, tableTop, usableW, tDrawH);
                } else {
                    const srcW = tableCanvas.width;
                    const srcH = tableCanvas.height;
                    const sliceRatio = maxH / tDrawH;
                    let srcY = 0;
                    let isFirst = true;
                    let startY = tableTop;

                    while (srcY < srcH) {
                        if (!isFirst) {
                            pdf.addPage();
                            startY = margin;
                        }
                        isFirst = false;

                        const sliceH = Math.min(srcH - srcY, Math.round(srcH * sliceRatio));
                        const sliceCanvas = document.createElement('canvas');
                        sliceCanvas.width = srcW;
                        sliceCanvas.height = sliceH;
                        sliceCanvas.getContext('2d').drawImage(tableCanvas, 0, srcY, srcW, sliceH, 0, 0, srcW, sliceH);

                        const sliceDrawH = (sliceH / srcH) * tDrawH;
                        pdf.addImage(sliceCanvas.toDataURL('image/jpeg', 0.97), 'JPEG', margin, startY, usableW,
                            sliceDrawH);
                        srcY += sliceH;
                    }
                }

                // ⑤ Footer
                const pageCount = pdf.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    pdf.setPage(i);
                    pdf.setFontSize(8);
                    pdf.setTextColor(160);
                    pdf.text(`Page ${i} of ${pageCount}`, margin, pageH - 4);
                    pdf.text('AI Pharmacy System', pageW - margin - 38, pageH - 4);
                }

                pdf.save('pharmacy-report-{{ $user->id }}-{{ $dateFrom->format('Ymd') }}.pdf');

            } finally {
                document.body.removeChild(loader);
            }
        }
    </script>

</body>

</html>
