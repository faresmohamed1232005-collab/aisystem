@extends('layouts.app')

@section('title', 'المصروفات')

@section('styles')
<style>
/* ══ Tokens ══════════════════════════════════════════ */
:root {
    --navy:   #0f172a;
    --navy2:  #1e293b;
    --indigo: #6366f1;
    --cyan:   #22d3ee;
    --green:  #10b981;
    --red:    #f43f5e;
    --amber:  #f59e0b;
    --surface:#ffffff;
    --muted:  #64748b;
    --border: #e2e8f0;
}

/* ══ Stat Cards ══════════════════════════════════════ */
.xcard {
    position: relative;
    border-radius: 20px;
    padding: 24px 22px 20px;
    color: #fff;
    overflow: hidden;
    isolation: isolate;
}
.xcard::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    padding: 1.5px;
    background: var(--border-g);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    pointer-events: none;
}
.xcard-bg {
    position: absolute;
    inset: 0;
    border-radius: 20px;
    z-index: -1;
}
.xcard-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(32px);
    opacity: .55;
    pointer-events: none;
}
.xcard-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    margin-bottom: 14px;
    position: relative;
}
.xcard-num {
    font-size: clamp(22px, 4vw, 32px);
    font-weight: 800;
    line-height: 1;
    letter-spacing: -1px;
}
.xcard-unit {
    font-size: 11px;
    font-weight: 600;
    opacity: .55;
    margin-top: 3px;
}
.xcard-label {
    font-size: 12px;
    font-weight: 600;
    opacity: .65;
    margin-bottom: 6px;
}
.xcard-delta {
    margin-top: 12px;
    font-size: 11px;
    opacity: .6;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* ══ Panel ═══════════════════════════════════════════ */
.panel {
    background: var(--surface);
    border-radius: 20px;
    border: 1.5px solid var(--border);
    overflow: hidden;
}
.panel-head {
    padding: 18px 22px;
    border-bottom: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}
.panel-head-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.panel-title {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    flex: 1;
}

/* ══ Form inputs ═════════════════════════════════════ */
.xinput {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 10px 14px;
    font-size: 13px;
    font-family: 'Cairo', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    transition: border-color .2s, box-shadow .2s, background .2s;
    outline: none;
}
.xinput:focus {
    border-color: var(--indigo);
    box-shadow: 0 0 0 3px rgba(99,102,241,.12);
    background: #fff;
}
.xinput::placeholder { color: #94a3b8; }

.xlabel {
    display: block;
    font-size: 11.5px;
    font-weight: 700;
    color: var(--muted);
    margin-bottom: 6px;
    letter-spacing: .03em;
}

.xbtn-primary {
    width: 100%;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #fff;
    font-family: 'Cairo', sans-serif;
    font-size: 14px;
    font-weight: 700;
    padding: 12px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: transform .15s, box-shadow .15s, filter .15s;
    box-shadow: 0 4px 16px rgba(99,102,241,.35);
}
.xbtn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 24px rgba(99,102,241,.45);
    filter: brightness(1.08);
}
.xbtn-primary:active { transform: translateY(0); }

/* ══ Category bars ═══════════════════════════════════ */
.cat-track { height: 7px; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.cat-fill  { height: 100%; border-radius: 999px; transition: width .8s cubic-bezier(.4,0,.2,1); }

/* ══ Table ═══════════════════════════════════════════ */
.xtable th {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 12px 18px;
    text-align: right;
}
.xtable td {
    padding: 13px 18px;
    font-size: 13px;
    color: #1e293b;
    border-bottom: 1px solid #f1f5f9;
}
.xtable tbody tr:hover td { background: #fafaff; }
.xtable tbody tr:last-child td { border-bottom: none; }

.badge-cat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(99,102,241,.1);
    color: #4f46e5;
}

.xbtn-del {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #f43f5e;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background .15s, transform .1s;
    margin: 0 auto;
}
.xbtn-del:hover { background: #ffe4e6; transform: scale(1.1); }

/* ══ Counter animation ═══════════════════════════════ */
.count-up { display: inline-block; }

/* ══ Filter bar ══════════════════════════════════════ */
.xfilter-select {
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 7px 12px;
    font-size: 12px;
    font-family: 'Cairo', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
    transition: border-color .2s;
    cursor: pointer;
}
.xfilter-select:focus { border-color: var(--indigo); }
.xbtn-filter {
    background: var(--navy2);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 7px 14px;
    font-size: 12px;
    font-family: 'Cairo', sans-serif;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: background .15s;
}
.xbtn-filter:hover { background: var(--indigo); }

/* ══ Empty state ══════════════════════════════════════ */
.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #94a3b8;
}
.empty-state i { font-size: 48px; opacity: .25; margin-bottom: 12px; }
.empty-state p { font-size: 14px; }

/* ══ Table footer ══════════════════════════════════ */
.xtfoot td {
    background: linear-gradient(90deg, #eef2ff, #f0f9ff);
    font-weight: 800;
    color: #4338ca;
    font-size: 13px;
    padding: 13px 18px;
    border-top: 2px solid #c7d2fe;
}
</style>
@endsection

@section('content')

{{-- ════════════════════════════════════════════════
     ① STAT CARDS
════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- Card 1 — مصروفات الشهر --}}
    <div class="xcard" style="--border-g: linear-gradient(135deg,#6366f1,#22d3ee)">
        <div class="xcard-bg" style="background: linear-gradient(135deg,#1e1b4b 0%,#1e293b 100%)"></div>
        <div class="xcard-orb" style="width:120px;height:120px;top:-30px;left:-30px;background:#6366f1"></div>
        <div class="xcard-icon" style="background:rgba(99,102,241,.18)">
            <i class="fas fa-calendar-alt" style="color:#818cf8"></i>
        </div>
        <div class="xcard-label">مصروفات الشهر</div>
        <div class="xcard-num count-up" data-target="{{ $totalMonth }}">0</div>
        <div class="xcard-unit">جنيه مصري</div>
        <div class="xcard-delta"><i class="fas fa-clock" style="font-size:10px"></i> {{ \Carbon\Carbon::create()->month($month)->locale('ar')->isoFormat('MMMM') }} {{ $year }}</div>
    </div>

    {{-- Card 2 — مصروفات السنة --}}
    <div class="xcard" style="--border-g: linear-gradient(135deg,#0ea5e9,#06b6d4)">
        <div class="xcard-bg" style="background: linear-gradient(135deg,#0c1a2e 0%,#0f2744 100%)"></div>
        <div class="xcard-orb" style="width:110px;height:110px;top:-25px;left:-25px;background:#0ea5e9"></div>
        <div class="xcard-icon" style="background:rgba(14,165,233,.18)">
            <i class="fas fa-chart-line" style="color:#38bdf8"></i>
        </div>
        <div class="xcard-label">مصروفات {{ $year }}</div>
        <div class="xcard-num count-up" data-target="{{ $totalYear }}">0</div>
        <div class="xcard-unit">جنيه مصري</div>
        <div class="xcard-delta"><i class="fas fa-layer-group" style="font-size:10px"></i> إجمالي السنة</div>
    </div>

    {{-- Card 3 — عدد المصروفات --}}
    <div class="xcard" style="--border-g: linear-gradient(135deg,#f59e0b,#ef4444)">
        <div class="xcard-bg" style="background: linear-gradient(135deg,#1c1007 0%,#1f1510 100%)"></div>
        <div class="xcard-orb" style="width:100px;height:100px;top:-20px;left:-20px;background:#f59e0b"></div>
        <div class="xcard-icon" style="background:rgba(245,158,11,.18)">
            <i class="fas fa-receipt" style="color:#fbbf24"></i>
        </div>
        <div class="xcard-label">عدد العمليات</div>
        <div class="xcard-num count-up" data-target="{{ $expenses->count() }}" data-decimals="0">0</div>
        <div class="xcard-unit">عملية هذا الشهر</div>
        <div class="xcard-delta"><i class="fas fa-tag" style="font-size:10px"></i> {{ $byCategory->count() }} فئة مختلفة</div>
    </div>

    {{-- Card 4 — الإجمالي الكلي --}}
    <div class="xcard" style="--border-g: linear-gradient(135deg,#10b981,#06b6d4)">
        <div class="xcard-bg" style="background: linear-gradient(135deg,#052e16 0%,#0c1a1a 100%)"></div>
        <div class="xcard-orb" style="width:120px;height:120px;top:-30px;left:-30px;background:#10b981"></div>
        <div class="xcard-icon" style="background:rgba(16,185,129,.18)">
            <i class="fas fa-coins" style="color:#34d399"></i>
        </div>
        <div class="xcard-label">إجمالي كل الأوقات</div>
        <div class="xcard-num count-up" data-target="{{ $totalAll }}">0</div>
        <div class="xcard-unit">جنيه مصري</div>
        <div class="xcard-delta"><i class="fas fa-database" style="font-size:10px"></i> منذ بداية الاستخدام</div>
    </div>
</div>

{{-- ════════════════════════════════════════════════
     ② MAIN GRID — فورم + توزيع | جدول
════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- ── العمود الأيمن ── --}}
    <div class="xl:col-span-1 space-y-4">

        {{-- فورم الإضافة --}}
        <div class="panel">
            <div class="panel-head">
                <div class="panel-head-icon" style="background:#eef2ff">
                    <i class="fas fa-plus" style="color:#6366f1"></i>
                </div>
                <span class="panel-title">إضافة مصروف جديد</span>
            </div>

            <div class="p-5">
                @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center gap-1.5"><i class="fas fa-exclamation-circle"></i>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <form action="{{ route('expenses.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="xlabel">اسم المصروف <span style="color:#f43f5e">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required
                               placeholder="مثال: إيجار شهر يونيو"
                               class="xinput">
                    </div>

                    <div>
                        <label class="xlabel">الفئة <span style="color:#f43f5e">*</span></label>
                        <select name="category" required class="xinput" style="cursor:pointer">
                            <option value="" disabled {{ old('category') ? '' : 'selected' }}>اختر الفئة...</option>
                            @foreach($categories as $val => $label)
                                <option value="{{ $val }}" {{ old('category') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="xlabel">المبلغ (جنيه) <span style="color:#f43f5e">*</span></label>
                        <input type="number" name="amount" value="{{ old('amount') }}" required
                               min="0.01" step="0.01" placeholder="0.00" class="xinput">
                    </div>

                    <div>
                        <label class="xlabel">التاريخ <span style="color:#f43f5e">*</span></label>
                        <input type="date" name="expense_date"
                               value="{{ old('expense_date', now()->toDateString()) }}"
                               required class="xinput">
                    </div>

                    <div>
                        <label class="xlabel">ملاحظات <span style="color:#94a3b8">(اختياري)</span></label>
                        <textarea name="notes" rows="2" placeholder="أي تفاصيل إضافية..."
                                  class="xinput resize-none">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="xbtn-primary">
                        <i class="fas fa-plus-circle"></i> إضافة المصروف
                    </button>
                </form>
            </div>
        </div>

        {{-- توزيع الفئات --}}
        @if($byCategory->isNotEmpty())
        <div class="panel">
            <div class="panel-head">
                <div class="panel-head-icon" style="background:#f5f3ff">
                    <i class="fas fa-chart-pie" style="color:#8b5cf6"></i>
                </div>
                <span class="panel-title">توزيع حسب الفئة</span>
                <span style="font-size:11px;color:#94a3b8;font-weight:600">{{ \Carbon\Carbon::create()->month($month)->locale('ar')->isoFormat('MMMM') }}</span>
            </div>

            <div class="p-5 space-y-4">
                @php
                    $palette = ['#6366f1','#0ea5e9','#f59e0b','#10b981','#f43f5e','#8b5cf6','#06b6d4','#84cc16','#ec4899','#14b8a6'];
                    $ci = 0;
                @endphp
                @foreach($byCategory as $cat => $amt)
                @php
                    $pct   = $totalMonth > 0 ? round($amt / $totalMonth * 100) : 0;
                    $color = $palette[$ci % count($palette)];
                    $ci++;
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <span style="width:8px;height:8px;border-radius:50%;background:{{ $color }};display:inline-block;flex-shrink:0"></span>
                            <span style="font-size:12px;font-weight:600;color:#374151">{{ $cat }}</span>
                        </div>
                        <div style="text-align:left">
                            <span style="font-size:13px;font-weight:800;color:#1e293b">{{ number_format($amt, 0) }}</span>
                            <span style="font-size:10px;color:#94a3b8"> جنيه</span>
                            <span style="font-size:10px;color:{{ $color }};font-weight:700;margin-right:4px">{{ $pct }}%</span>
                        </div>
                    </div>
                    <div class="cat-track">
                        <div class="cat-fill" style="width:{{ $pct }}%;background:{{ $color }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- ── الجدول ── --}}
    <div class="xl:col-span-2">
        <div class="panel">
            <div class="panel-head flex-wrap gap-3">
                <div class="panel-head-icon" style="background:#f0fdf4">
                    <i class="fas fa-list-ul" style="color:#10b981"></i>
                </div>
                <span class="panel-title">سجل المصروفات</span>

                <form method="GET" action="{{ route('expenses.index') }}" class="flex items-center gap-2 mr-auto flex-wrap">
                    <select name="month" class="xfilter-select">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->locale('ar')->isoFormat('MMMM') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="xfilter-select">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="xbtn-filter">
                        <i class="fas fa-filter"></i> عرض
                    </button>
                </form>
            </div>

            @if($expenses->isEmpty())
                <div class="empty-state">
                    <div><i class="fas fa-receipt"></i></div>
                    <p>لا توجد مصروفات مسجّلة لهذا الشهر</p>
                    <p style="font-size:12px;margin-top:4px;color:#cbd5e1">أضف أول مصروف من النموذج على اليسار</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="xtable w-full">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>المصروف</th>
                            <th>الفئة</th>
                            <th>المبلغ</th>
                            <th>ملاحظات</th>
                            <th style="text-align:center">حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        <tr>
                            <td>
                                <div style="font-size:12px;color:#64748b;font-weight:600">
                                    {{ $expense->expense_date->format('d') }}
                                    <span style="color:#94a3b8">/ {{ $expense->expense_date->format('m/Y') }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:700;color:#1e293b">{{ $expense->title }}</div>
                            </td>
                            <td>
                                <span class="badge-cat">
                                    <i class="fas fa-tag" style="font-size:9px"></i>
                                    {{ $expense->category }}
                                </span>
                            </td>
                            <td>
                                <div style="font-weight:800;color:#1e293b;font-size:14px">
                                    {{ number_format($expense->amount, 2) }}
                                    <span style="font-size:10px;color:#94a3b8;font-weight:400">جنيه</span>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:12px;color:#94a3b8;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    {{ $expense->notes ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <form action="{{ route('expenses.destroy', $expense) }}" method="POST"
                                      onsubmit="return confirm('تأكيد حذف هذا المصروف؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="xbtn-del">
                                        <i class="fas fa-trash-alt" style="font-size:11px"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="xtfoot">
                        <tr>
                            <td colspan="3">
                                <i class="fas fa-sigma me-1"></i> إجمالي الشهر
                            </td>
                            <td colspan="3">
                                {{ number_format($totalMonth, 2) }} <span style="font-size:11px;font-weight:400">جنيه</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
/* ── Counter-up animation ── */
function animateCount(el) {
    const target   = parseFloat(el.dataset.target) || 0;
    const decimals = parseInt(el.dataset.decimals ?? '2');
    const duration = 1200;
    const steps    = 60;
    const step     = target / steps;
    let current    = 0;
    let count       = 0;
    const timer = setInterval(() => {
        count++;
        current = count >= steps ? target : current + step;
        el.textContent = current.toLocaleString('ar-EG', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
        if (count >= steps) clearInterval(timer);
    }, duration / steps);
}

document.querySelectorAll('.count-up').forEach(el => {
    const obs = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) {
            animateCount(el);
            obs.disconnect();
        }
    }, { threshold: 0.3 });
    obs.observe(el);
});
</script>
@endsection