@extends('layouts.app')

@section('title', 'إدارة الموظفين')

@section('styles')
<style>
/* ══ Tokens ══════════════════════════════════════════ */
:root {
    --navy:    #0f172a;
    --navy2:   #1e293b;
    --indigo:  #6366f1;
    --cyan:    #22d3ee;
    --green:   #10b981;
    --red:     #f43f5e;
    --amber:   #f59e0b;
    --violet:  #8b5cf6;
    --border:  #e2e8f0;
    --surface: #ffffff;
    --muted:   #64748b;
    --bg:      #f8fafc;
}

/* ══ Stat Cards (نفس نمط المصروفات) ══════════════════ */
.xcard {
    position: relative; border-radius: 20px;
    padding: 22px 20px 18px; color: #fff; overflow: hidden; isolation: isolate;
}
.xcard::before {
    content: ''; position: absolute; inset: 0; border-radius: 20px;
    padding: 1.5px; background: var(--border-g);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none;
}
.xcard-bg   { position: absolute; inset: 0; border-radius: 20px; z-index: -1; }
.xcard-orb  { position: absolute; border-radius: 50%; filter: blur(36px); opacity: .5; pointer-events: none; }
.xcard-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 17px; margin-bottom: 12px; position: relative; }
.xcard-num  { font-size: clamp(20px,3.5vw,30px); font-weight: 800; line-height: 1; letter-spacing: -1px; }
.xcard-unit { font-size: 11px; font-weight: 600; opacity: .5; margin-top: 3px; }
.xcard-label{ font-size: 11.5px; font-weight: 600; opacity: .6; margin-bottom: 6px; }
.xcard-delta{ margin-top: 10px; font-size: 11px; opacity: .55; display: flex; align-items: center; gap: 5px; }
.count-up   { display: inline-block; }

/* ══ Panel ═══════════════════════════════════════════ */
.panel       { background: var(--surface); border-radius: 20px; border: 1.5px solid var(--border); overflow: hidden; }
.panel-head  { padding: 16px 20px; border-bottom: 1.5px solid var(--border); display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.panel-icon  { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.panel-title { font-size: 14px; font-weight: 700; color: #1e293b; flex: 1; min-width: 80px; }

/* ══ Form ════════════════════════════════════════════ */
.xinput {
    width: 100%; border: 1.5px solid var(--border); border-radius: 12px;
    padding: 9px 13px; font-size: 13px; font-family: 'Cairo',sans-serif;
    color: #1e293b; background: #f8fafc; transition: all .2s; outline: none;
}
.xinput:focus { border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(99,102,241,.12); background: #fff; }
.xinput::placeholder { color: #94a3b8; }
.xlabel { display: block; font-size: 11px; font-weight: 700; color: var(--muted); margin-bottom: 5px; letter-spacing:.03em; }
.xbtn {
    font-family: 'Cairo',sans-serif; font-size: 13px; font-weight: 700;
    padding: 10px 16px; border-radius: 12px; border: none; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    transition: all .15s;
}
.xbtn-primary { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; box-shadow: 0 4px 14px rgba(99,102,241,.3); }
.xbtn-primary:hover { transform: translateY(-1px); box-shadow: 0 7px 20px rgba(99,102,241,.4); filter: brightness(1.07); }
.xbtn-sm { padding: 6px 12px; font-size: 12px; border-radius: 9px; }

/* ══ Employee Card ═══════════════════════════════════ */
.emp-card {
    background: #fff;
    border: 1.5px solid var(--border);
    border-radius: 18px;
    overflow: hidden;
    transition: box-shadow .2s, transform .2s;
}
.emp-card:hover { box-shadow: 0 8px 32px rgba(99,102,241,.1); transform: translateY(-2px); }

.emp-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 16px 18px;
    display: flex; align-items: center; gap: 12px;
    position: relative; overflow: hidden;
}
.emp-header::after {
    content: ''; position: absolute; top: -20px; left: -20px;
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(99,102,241,.15); pointer-events: none;
}
.emp-avatar {
    width: 46px; height: 46px; border-radius: 14px;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 800; color: #fff;
    flex-shrink: 0; position: relative; z-index: 1;
    box-shadow: 0 4px 12px rgba(99,102,241,.4);
}
.emp-name    { color: #fff; font-weight: 800; font-size: 14px; line-height: 1.2; }
.emp-title   { color: #94a3b8; font-size: 11px; font-weight: 600; margin-top: 2px; }
.emp-status  {
    margin-right: auto; position: relative; z-index: 1;
    background: rgba(16,185,129,.15); color: #34d399;
    border: 1px solid rgba(16,185,129,.3);
    border-radius: 999px; font-size: 10px; font-weight: 700;
    padding: 3px 9px; display: flex; align-items: center; gap: 4px;
}
.emp-status::before { content:''; width:6px; height:6px; border-radius:50%; background:#34d399; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ══ Salary breakdown row ═══════════════════════════ */
.sal-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 18px; font-size: 12px; border-bottom: 1px solid #f1f5f9;
}
.sal-row:last-child { border-bottom: none; }
.sal-row-label { color: var(--muted); display: flex; align-items: center; gap: 6px; font-weight: 600; }
.sal-row-val   { font-weight: 700; color: #1e293b; }
.sal-row-val.plus  { color: #10b981; }
.sal-row-val.minus { color: #f43f5e; }

.sal-net {
    background: linear-gradient(90deg,#eef2ff,#f0fdf4);
    border-top: 2px solid #c7d2fe;
    padding: 10px 18px;
    display: flex; align-items: center; justify-content: space-between;
}
.sal-net-label { font-size: 12px; font-weight: 800; color: #4338ca; }
.sal-net-val   { font-size: 18px; font-weight: 900; color: #4338ca; letter-spacing: -.5px; }

/* ══ Transaction badges ══════════════════════════════ */
.txn-row {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 18px; font-size: 11.5px;
    border-bottom: 1px solid #f8fafc;
}
.txn-row:last-child { border-bottom: none; }
.txn-type {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700;
}
.txn-note  { color: #94a3b8; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.txn-amount{ font-weight: 800; color: #1e293b; white-space: nowrap; font-size: 12px; }
.txn-del   { width: 22px; height: 22px; border-radius: 6px; background: #fff1f2; border: 1px solid #fecdd3; color: #f43f5e; display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0; transition: all .15s; }
.txn-del:hover { background: #ffe4e6; transform: scale(1.1); }

/* ══ Pay button ══════════════════════════════════════ */
.pay-btn {
    width: 100%; background: linear-gradient(135deg,#10b981,#059669);
    color: #fff; font-family: 'Cairo',sans-serif; font-size: 13px; font-weight: 700;
    padding: 11px; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 7px;
    transition: all .15s; border-radius: 0 0 17px 17px;
    box-shadow: inset 0 2px 0 rgba(255,255,255,.12);
}
.pay-btn:hover { filter: brightness(1.08); box-shadow: 0 4px 14px rgba(16,185,129,.35); }
.pay-btn.paid  { background: linear-gradient(135deg,#64748b,#475569); cursor: default; }

/* ══ Add action modal ════════════════════════════════ */
.modal-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,.6);
    backdrop-filter: blur(4px); z-index: 100;
    display: none; align-items: center; justify-content: center; padding: 16px;
}
.modal-backdrop.open { display: flex; }
.modal-box {
    background: #fff; border-radius: 22px; width: 100%; max-width: 420px;
    overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,.25);
    animation: modalPop .25s cubic-bezier(.34,1.56,.64,1);
}
@keyframes modalPop { from{transform:scale(.88);opacity:0} to{transform:scale(1);opacity:1} }
.modal-head { background: linear-gradient(135deg,#1e293b,#0f172a); padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; }
.modal-head h3 { color: #fff; font-size: 15px; font-weight: 800; }
.modal-close { color: #64748b; background: none; border: none; font-size: 18px; cursor: pointer; transition: color .15s; padding: 4px; }
.modal-close:hover { color: #fff; }

/* ══ Filter bar (محسّن — تصميم غامق بنفسجي) ══════════ */
.filter-panel {
    background: linear-gradient(135deg, #2e1065 0%, #4c1d95 45%, #5b21b6 100%);
    border-radius: 20px;
    padding: 18px 22px;
    position: relative; overflow: hidden; isolation: isolate;
    box-shadow: 0 10px 32px rgba(91,33,182,.28);
    border: 1.5px solid rgba(167,139,250,.25);
}
.filter-panel::before {
    content: ''; position: absolute; top: -50px; right: -40px;
    width: 180px; height: 180px; border-radius: 50%;
    background: rgba(139,92,246,.35); filter: blur(55px); pointer-events: none;
}
.filter-panel::after {
    content: ''; position: absolute; bottom: -60px; left: -30px;
    width: 150px; height: 150px; border-radius: 50%;
    background: rgba(34,211,238,.18); filter: blur(55px); pointer-events: none;
}
.filter-panel-row { position: relative; z-index: 1; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.filter-panel .panel-icon { background: rgba(255,255,255,.12); }
.filter-panel .panel-title { color: #fff; font-size: 14px; font-weight: 800; }
.filter-panel .panel-title small { display:block; font-size:11px; font-weight:600; color:#c4b5fd; margin-top:2px; }

.xfilter-select {
    border: 1.5px solid rgba(255,255,255,.18); border-radius: 10px; padding: 8px 12px;
    font-size: 12.5px; font-family: 'Cairo',sans-serif; font-weight: 600;
    color: #fff; background: rgba(255,255,255,.08); outline: none; cursor: pointer;
    transition: all .2s;
}
.xfilter-select option { color: #1e293b; }
.xfilter-select:focus, .xfilter-select:hover { border-color: #c4b5fd; background: rgba(255,255,255,.16); }

.xbtn-filter {
    background: linear-gradient(135deg,#a78bfa,#7c3aed); color: #fff; border: none;
    border-radius: 10px; padding: 8px 16px; font-size: 12.5px; font-family: 'Cairo',sans-serif;
    font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
    transition: all .15s;
    box-shadow: 0 4px 14px rgba(124,58,237,.4);
}
.xbtn-filter:hover { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(124,58,237,.5); }

.filter-panel .xbtn-primary {
    background: rgba(255,255,255,.95); color: #5b21b6;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.filter-panel .xbtn-primary:hover { background: #fff; filter: none; box-shadow: 0 6px 18px rgba(0,0,0,.2); }

/* ══ Add Employee Form Toggle ════════════════════════ */
.add-form-wrap { overflow: hidden; transition: max-height .35s ease, opacity .3s; max-height: 0; opacity: 0; }
.add-form-wrap.open { max-height: 700px; opacity: 1; }

/* ══ Empty state ══════════════════════════════════════ */
.empty-state { padding: 60px 20px; text-align: center; color: #94a3b8; }
.empty-state i { font-size: 48px; opacity: .2; margin-bottom: 14px; display: block; }

/* ══ Alert ════════════════════════════════════════════ */
.alert { border-radius: 12px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; display: flex; align-items: center; gap: 9px; }
.alert-success { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
.alert-error   { background: #fff1f2; border: 1.5px solid #fecdd3; color: #9f1239; }
</style>
@endsection

@section('content')

{{-- Alerts --}}
@if(session('success'))
<div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

{{-- ════════════════════════════════════════════════
     ① STAT CARDS
════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    {{-- إجمالي الموظفين --}}
    <div class="xcard" style="--border-g:linear-gradient(135deg,#6366f1,#22d3ee)">
        <div class="xcard-bg" style="background:linear-gradient(135deg,#1e1b4b,#1e293b)"></div>
        <div class="xcard-orb" style="width:110px;height:110px;top:-28px;left:-28px;background:#6366f1"></div>
        <div class="xcard-icon" style="background:rgba(99,102,241,.18)"><i class="fas fa-users" style="color:#818cf8"></i></div>
        <div class="xcard-label">إجمالي الموظفين</div>
        <div class="xcard-num count-up" data-target="{{ $totalEmployees }}" data-decimals="0">0</div>
        <div class="xcard-unit">موظف نشط</div>
        <div class="xcard-delta"><i class="fas fa-user-check" style="font-size:10px"></i> {{ $totalPaid }} تم صرف رواتبهم</div>
    </div>

    {{-- إجمالي الرواتب الأساسية --}}
    <div class="xcard" style="--border-g:linear-gradient(135deg,#0ea5e9,#06b6d4)">
        <div class="xcard-bg" style="background:linear-gradient(135deg,#0c1a2e,#0f2744)"></div>
        <div class="xcard-orb" style="width:110px;height:110px;top:-25px;left:-25px;background:#0ea5e9"></div>
        <div class="xcard-icon" style="background:rgba(14,165,233,.18)"><i class="fas fa-money-bill-wave" style="color:#38bdf8"></i></div>
        <div class="xcard-label">الرواتب الأساسية</div>
        <div class="xcard-num count-up" data-target="{{ $totalBaseSalaries }}">0</div>
        <div class="xcard-unit">جنيه / شهر</div>
        <div class="xcard-delta"><i class="fas fa-calendar" style="font-size:10px"></i> قبل الخصومات</div>
    </div>

    {{-- صافي الرواتب --}}
    <div class="xcard" style="--border-g:linear-gradient(135deg,#10b981,#06b6d4)">
        <div class="xcard-bg" style="background:linear-gradient(135deg,#052e16,#0c1a1a)"></div>
        <div class="xcard-orb" style="width:110px;height:110px;top:-25px;left:-25px;background:#10b981"></div>
        <div class="xcard-icon" style="background:rgba(16,185,129,.18)"><i class="fas fa-coins" style="color:#34d399"></i></div>
        <div class="xcard-label">صافي الرواتب</div>
        <div class="xcard-num count-up" data-target="{{ $totalNetSalaries }}">0</div>
        <div class="xcard-unit">جنيه هذا الشهر</div>
        <div class="xcard-delta"><i class="fas fa-calculator" style="font-size:10px"></i> بعد كل الإجراءات</div>
    </div>

    {{-- المصروف الفعلي --}}
    <div class="xcard" style="--border-g:linear-gradient(135deg,#f59e0b,#ef4444)">
        <div class="xcard-bg" style="background:linear-gradient(135deg,#1c1007,#1f1510)"></div>
        <div class="xcard-orb" style="width:110px;height:110px;top:-25px;left:-25px;background:#f59e0b"></div>
        <div class="xcard-icon" style="background:rgba(245,158,11,.18)"><i class="fas fa-hand-holding-usd" style="color:#fbbf24"></i></div>
        <div class="xcard-label">تم الصرف فعلياً</div>
        <div class="xcard-num count-up" data-target="{{ $totalPaid }}" data-decimals="0">0</div>
        <div class="xcard-unit">موظف تم صرف راتبه</div>
        <div class="xcard-delta"><i class="fas fa-check-double" style="font-size:10px"></i> من أصل {{ $totalEmployees }} موظف</div>
    </div>
</div>

{{-- ════════════════════════════════════════════════
     ② TOOLBAR — فلتر + زرار إضافة موظف (تصميم بنفسجي غامق)
════════════════════════════════════════════════ --}}
<div class="filter-panel mb-5">
    <div class="filter-panel-row">
        <div class="panel-icon" style="background:rgba(255,255,255,.12)"><i class="fas fa-sliders-h" style="color:#c4b5fd"></i></div>
        <div class="panel-title">
            عرض شهري
            <small>اختر الشهر والسنة لعرض رواتب وإجراءات الموظفين</small>
        </div>

        <form method="GET" action="{{ route('employees.index') }}" class="flex items-center gap-2 flex-wrap">
            <select name="month" class="xfilter-select">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $month==$m ? 'selected':'' }}>
                        {{ \Carbon\Carbon::create()->month($m)->locale('ar')->isoFormat('MMMM') }}
                    </option>
                @endforeach
            </select>
            <select name="year" class="xfilter-select">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $year==$y ? 'selected':'' }}>{{ $y }}</option>
                @endforeach
            </select>
            <button type="submit" class="xbtn-filter"><i class="fas fa-filter"></i> عرض</button>
        </form>

        <button onclick="toggleAddForm()"
                class="xbtn xbtn-primary xbtn-sm mr-auto">
            <i class="fas fa-user-plus"></i> موظف جديد
        </button>
    </div>

    {{-- فورم إضافة موظف (قابل للطي) --}}
    <div id="add-form-wrap" class="add-form-wrap" style="position:relative;z-index:1">
        <div class="p-5 mt-4 border-t border-white/15">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <form action="{{ route('employees.store') }}" method="POST"
                      class="contents" id="add-emp-form">
                    @csrf
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">الاسم كاملاً <span style="color:#fda4af">*</span></label>
                        <input type="text" name="name" required placeholder="أحمد محمد..." class="xinput" value="{{ old('name') }}">
                    </div>
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">المسمى الوظيفي</label>
                        <input type="text" name="job_title" placeholder="صيدلاني، كاشير..." class="xinput" value="{{ old('job_title') }}">
                    </div>
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">رقم الموبايل</label>
                        <input type="text" name="phone" placeholder="01xxxxxxxxx" class="xinput" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">الراتب الأساسي (جنيه) <span style="color:#fda4af">*</span></label>
                        <input type="number" name="base_salary" required min="1" step="0.01" placeholder="3000.00" class="xinput" value="{{ old('base_salary') }}">
                    </div>
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">تاريخ التعيين <span style="color:#fda4af">*</span></label>
                        <input type="date" name="hired_at" required class="xinput" value="{{ old('hired_at', now()->toDateString()) }}">
                    </div>
                    <div>
                        <label class="xlabel" style="color:#c4b5fd">ملاحظات</label>
                        <input type="text" name="notes" placeholder="أي ملاحظات..." class="xinput" value="{{ old('notes') }}">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3 flex gap-3 pt-1">
                        <button type="submit" class="xbtn xbtn-primary">
                            <i class="fas fa-plus-circle"></i> حفظ الموظف
                        </button>
                        <button type="button" onclick="toggleAddForm()"
                                class="xbtn" style="background:rgba(255,255,255,.12);color:#fff">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                    </div>
                </form>
            </div>

            @if($errors->any())
            <div class="mt-3 bg-red-50 border border-red-200 rounded-xl p-3 text-xs text-red-600 space-y-1">
                @foreach($errors->all() as $e)<div><i class="fas fa-exclamation-circle me-1"></i>{{ $e }}</div>@endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════
     ③ EMPLOYEES GRID
════════════════════════════════════════════════ --}}
@if($employees->isEmpty())
<div class="panel">
    <div class="empty-state">
        <i class="fas fa-user-slash"></i>
        <p style="font-size:15px;font-weight:700;color:#475569">لا يوجد موظفون مسجّلون</p>
        <p style="font-size:13px;margin-top:6px">اضغط "موظف جديد" لإضافة أول موظف</p>
    </div>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @foreach($employees as $emp)
    <div class="emp-card">

        {{-- Header --}}
        <div class="emp-header">
            <div class="emp-avatar">{{ mb_substr($emp->name, 0, 1) }}</div>
            <div style="flex:1;min-width:0">
                <div class="emp-name truncate">{{ $emp->name }}</div>
                <div class="emp-title">{{ $emp->job_title ?? 'موظف' }}
                    @if($emp->phone) · {{ $emp->phone }} @endif
                </div>
            </div>
            <div class="emp-status">نشط</div>
        </div>

        {{-- Salary Breakdown --}}
        <div class="py-2">
            <div class="sal-row">
                <span class="sal-row-label"><i class="fas fa-money-bill w-4 text-center" style="color:#6366f1"></i> الراتب الأساسي</span>
                <span class="sal-row-val">{{ number_format($emp->base_salary, 0) }} <span style="font-size:10px;color:#94a3b8">جنيه</span></span>
            </div>
            @if($emp->month_bonus > 0)
            <div class="sal-row">
                <span class="sal-row-label"><i class="fas fa-star w-4 text-center" style="color:#10b981"></i> بونص</span>
                <span class="sal-row-val plus">+ {{ number_format($emp->month_bonus, 0) }}</span>
            </div>
            @endif
            @if($emp->month_deduction > 0)
            <div class="sal-row">
                <span class="sal-row-label"><i class="fas fa-minus-circle w-4 text-center" style="color:#f43f5e"></i> خصم</span>
                <span class="sal-row-val minus">- {{ number_format($emp->month_deduction, 0) }}</span>
            </div>
            @endif
            @if($emp->month_advance > 0)
            <div class="sal-row">
                <span class="sal-row-label"><i class="fas fa-hand-holding-usd w-4 text-center" style="color:#f59e0b"></i> سلفة</span>
                <span class="sal-row-val minus">- {{ number_format($emp->month_advance, 0) }}</span>
            </div>
            @endif
            @if($emp->month_absence > 0)
            <div class="sal-row">
                <span class="sal-row-label"><i class="fas fa-user-times w-4 text-center" style="color:#8b5cf6"></i> غياب ({{ $emp->month_absence_days }} يوم)</span>
                <span class="sal-row-val minus">- {{ number_format($emp->month_absence, 0) }}</span>
            </div>
            @endif
        </div>

        {{-- Net Salary --}}
        <div class="sal-net">
            <span class="sal-net-label"><i class="fas fa-sigma me-1"></i> صافي الراتب</span>
            <span class="sal-net-val">{{ number_format($emp->net_salary, 2) }} <span style="font-size:11px;font-weight:600">جنيه</span></span>
        </div>

        {{-- Transactions this month --}}
        @php
            $txns = $emp->monthTransactions($month, $year)->whereNotIn('type',['salary']);
        @endphp
        @if($txns->isNotEmpty())
        <div style="border-top:1.5px solid #f1f5f9;padding:8px 0">
            <div style="font-size:10.5px;font-weight:700;color:#94a3b8;padding:4px 18px 6px;letter-spacing:.04em;text-transform:uppercase">إجراءات الشهر</div>
            @foreach($txns as $txn)
            @php $meta = \App\Models\EmployeeTransaction::$typeLabels[$txn->type]; @endphp
            <div class="txn-row">
                <span class="txn-type" style="background:{{ $meta['color'] }}18;color:{{ $meta['color'] }}">
                    <i class="fas {{ $meta['icon'] }}" style="font-size:9px"></i>
                    {{ $meta['label'] }}
                </span>
                <span class="txn-note">{{ $txn->notes ?: '—' }}</span>
                <span class="txn-amount">
                    {{ $txn->type === 'bonus' ? '+' : '-' }}{{ number_format($txn->amount, 0) }}
                </span>
                <form action="{{ route('employees.transaction.delete', $txn) }}" method="POST"
                      onsubmit="return confirm('حذف هذا الإجراء؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="txn-del"><i class="fas fa-times" style="font-size:9px"></i></button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Actions Footer --}}
        <div style="padding:12px 14px;border-top:1.5px solid #f1f5f9;display:flex;gap:8px;align-items:center">
            {{-- زرار إضافة إجراء --}}
            <button onclick="openActionModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', {{ $month }}, {{ $year }})"
                    class="xbtn xbtn-sm flex-1"
                    style="background:#f8fafc;color:#475569;border:1.5px solid #e2e8f0">
                <i class="fas fa-plus-circle" style="color:#6366f1"></i> إجراء
            </button>

            {{-- زرار حذف الموظف --}}
            <form action="{{ route('employees.destroy', $emp) }}" method="POST"
                  onsubmit="return confirm('إيقاف هذا الموظف؟')">
                @csrf @method('DELETE')
                <button type="submit" class="xbtn xbtn-sm"
                        style="background:#fff1f2;color:#f43f5e;border:1.5px solid #fecdd3">
                    <i class="fas fa-user-slash"></i>
                </button>
            </form>
        </div>

        {{-- Pay Salary Button --}}
        @if($emp->salary_paid)
        <button class="pay-btn paid" disabled>
            <i class="fas fa-check-circle"></i> تم صرف الراتب ✓
        </button>
        @else
        <form action="{{ route('employees.pay', $emp) }}" method="POST"
              onsubmit="return confirm('صرف راتب {{ addslashes($emp->name) }} ({{ number_format($emp->net_salary,2) }} جنيه)؟ سيُسجَّل تلقائياً في المصروفات.')">
            @csrf
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year"  value="{{ $year }}">
            <button type="submit" class="pay-btn">
                <i class="fas fa-money-bill-wave"></i>
                صرف {{ number_format($emp->net_salary, 2) }} جنيه
            </button>
        </form>
        @endif
    </div>
    @endforeach
</div>
@endif

{{-- ════════════════════════════════════════════════
     ④ ADD ACTION MODAL
════════════════════════════════════════════════ --}}
<div id="action-modal" class="modal-backdrop" onclick="closeActionModal(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <div class="modal-head">
            <h3><i class="fas fa-bolt me-2" style="color:#fbbf24"></i> إضافة إجراء — <span id="modal-emp-name"></span></h3>
            <button onclick="closeModal()" class="modal-close"><i class="fas fa-times"></i></button>
        </div>
        <form id="action-form" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="month" id="modal-month">
            <input type="hidden" name="year"  id="modal-year">

            {{-- نوع الإجراء --}}
            <div>
                <label class="xlabel">نوع الإجراء <span style="color:#f43f5e">*</span></label>
                <div class="grid grid-cols-2 gap-2" id="type-btns">
                    @foreach(['bonus'=>['بونص','#10b981','fa-star'],'deduction'=>['خصم','#f43f5e','fa-minus-circle'],'advance'=>['سلفة','#f59e0b','fa-hand-holding-usd'],'absence'=>['غياب','#8b5cf6','fa-user-times']] as $t=>[$lbl,$clr,$ico])
                    <label class="type-btn flex items-center gap-2 border-2 rounded-12px p-3 cursor-pointer transition"
                           style="border-radius:12px;border-color:#e2e8f0"
                           data-type="{{ $t }}" data-color="{{ $clr }}">
                        <input type="radio" name="type" value="{{ $t }}" class="hidden">
                        <span style="width:28px;height:28px;border-radius:8px;background:{{ $clr }}18;display:flex;align-items:center;justify-content:center">
                            <i class="fas {{ $ico }}" style="color:{{ $clr }};font-size:12px"></i>
                        </span>
                        <span style="font-size:13px;font-weight:700;color:#1e293b">{{ $lbl }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- المبلغ --}}
            <div id="amount-wrap">
                <label class="xlabel">المبلغ (جنيه) <span style="color:#f43f5e">*</span></label>
                <input type="number" name="amount" min="0.01" step="0.01" placeholder="0.00" class="xinput" id="modal-amount">
            </div>

            {{-- أيام الغياب (يظهر بس لو غياب) --}}
            <div id="absence-wrap" style="display:none">
                <label class="xlabel">عدد أيام الغياب <span style="color:#f43f5e">*</span></label>
                <input type="number" name="absence_days" min="1" max="31" placeholder="1" class="xinput" id="modal-absence-days">
                <p style="font-size:11px;color:#94a3b8;margin-top:5px"><i class="fas fa-info-circle me-1"></i>سيتم احتساب المبلغ تلقائياً (راتب يومي × الأيام)</p>
            </div>

            {{-- ملاحظات --}}
            <div>
                <label class="xlabel">ملاحظات</label>
                <input type="text" name="notes" placeholder="سبب الخصم / البونص ..." class="xinput">
            </div>

            <button type="submit" class="xbtn xbtn-primary w-full">
                <i class="fas fa-check-circle"></i> تأكيد الإجراء
            </button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
/* ── Counter-up ── */
function animateCount(el) {
    const target   = parseFloat(el.dataset.target) || 0;
    const decimals = parseInt(el.dataset.decimals ?? '2');
    const steps = 55, dur = 1100;
    let c = 0;
    const t = setInterval(() => {
        c++;
        const v = c >= steps ? target : target * (c / steps);
        el.textContent = v.toLocaleString('ar-EG', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        if (c >= steps) clearInterval(t);
    }, dur / steps);
}
document.querySelectorAll('.count-up').forEach(el => {
    const obs = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) { animateCount(el); obs.disconnect(); }
    }, { threshold: 0.3 });
    obs.observe(el);
});

/* ── Add Form Toggle ── */
function toggleAddForm() {
    document.getElementById('add-form-wrap').classList.toggle('open');
}
@if($errors->any()) document.getElementById('add-form-wrap').classList.add('open'); @endif

/* ── Action Modal ── */
function openActionModal(empId, empName, month, year) {
    document.getElementById('modal-emp-name').textContent = empName;
    document.getElementById('modal-month').value = month;
    document.getElementById('modal-year').value  = year;
    document.getElementById('action-form').action = `/employees/${empId}/transaction`;
    document.getElementById('action-modal').classList.add('open');
    // reset
    document.querySelectorAll('.type-btn').forEach(b => b.style.borderColor = '#e2e8f0');
    document.querySelector('input[name="type"]:checked')?.closest('.type-btn') && (document.querySelector('input[name="type"]').checked = false);
    document.getElementById('amount-wrap').style.display  = 'block';
    document.getElementById('absence-wrap').style.display = 'none';
    document.getElementById('modal-amount').value = '';
}
function closeModal() { document.getElementById('action-modal').classList.remove('open'); }
function closeActionModal(e) { if (e.target === document.getElementById('action-modal')) closeModal(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ── Type buttons ── */
document.querySelectorAll('.type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.type-btn').forEach(b => b.style.borderColor = '#e2e8f0');
        this.style.borderColor = this.dataset.color;
        this.querySelector('input[type=radio]').checked = true;

        const isAbsence = this.dataset.type === 'absence';
        document.getElementById('amount-wrap').style.display  = isAbsence ? 'none' : 'block';
        document.getElementById('absence-wrap').style.display = isAbsence ? 'block' : 'none';
        if (isAbsence) {
            document.getElementById('modal-amount').removeAttribute('required');
            document.getElementById('modal-absence-days').setAttribute('required','');
        } else {
            document.getElementById('modal-amount').setAttribute('required','');
            document.getElementById('modal-absence-days').removeAttribute('required');
        }
    });
});
</script>
@endsection