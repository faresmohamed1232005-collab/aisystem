<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب — AI Pharmacy System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Background ── */
        #bg-video {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; z-index: -2;
        }
        .overlay {
            position: fixed; inset: 0;
            background: rgba(2, 6, 23, 0.75);
            z-index: -1;
        }
        .star {
            position: absolute; border-radius: 50%;
            animation: twinkle var(--d, 3s) ease-in-out infinite;
            animation-delay: var(--delay, 0s);
        }
        @keyframes twinkle {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:.15; transform:scale(.5); }
        }
        #stars-wrap { position:fixed; inset:0; z-index:-1; pointer-events:none; overflow:hidden; }

        /* ── Page ── */
        .page {
            position: relative; z-index: 1;
            padding: 2.5rem 1.2rem 3rem;
            display: flex; flex-direction: column;
            align-items: center;
        }

        /* ── Card ── */
        .card {
            width: 100%; max-width: 800px;
            background: rgba(10, 18, 38, 0.72);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid rgba(99,102,241,0.22);
            border-radius: 26px;
            padding: 2.5rem 2.2rem;
            color: #e2e8f0;
            position: relative;
            box-shadow:
                0 28px 80px rgba(0,0,0,0.55),
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 0 60px rgba(99,102,241,0.08);
            animation: cardIn 0.7s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(22px)} to{opacity:1;transform:translateY(0)} }
        .card::before {
            content:'';
            position:absolute; top:0; left:50%; transform:translateX(-50%);
            width:58%; height:1px;
            background:linear-gradient(90deg,transparent,rgba(129,140,248,.7),transparent);
            border-radius:999px;
        }

        /* ── Header ── */
        .card-head {
            display: flex; align-items: center; gap: 1.4rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(99,102,241,0.14);
        }
        .head-robot { flex-shrink: 0; animation: robotBob 3.5s ease-in-out infinite; }
        @keyframes robotBob { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-9px)} }
        .head-text .title { font-size: 1.5rem; font-weight: 900; color: #fff; }
        .head-text .sub   { font-size: 0.8rem; color: #64748b; margin-top: 3px; }

        /* Online badge */
        .online-badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(34,211,238,0.09);
            border: 1px solid rgba(34,211,238,0.25);
            border-radius: 30px;
            padding: 4px 12px;
            font-size: 0.7rem; color: #22d3ee;
            margin-top: 7px;
        }
        .online-dot { width:6px; height:6px; border-radius:50%; background:#22d3ee;
            box-shadow:0 0 7px #22d3ee; animation:blink 2s ease-in-out infinite; }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.2} }

        /* Error */
        .alert {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.28);
            border-radius: 12px;
            padding: .75rem 1rem;
            margin-bottom: 1.4rem;
            color: #fca5a5; font-size: .8rem;
        }
        .alert ul { list-style: none; }
        .alert li { display:flex; align-items:center; gap:7px; padding:2px 0; }

        /* ── Section labels ── */
        .sec-label {
            display: flex; align-items: center; gap: 9px;
            font-size: 0.74rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: .9px;
            color: #818cf8;
            margin: 1.5rem 0 1rem;
        }
        .sec-label i { font-size: 0.8rem; }
        .sec-label::after {
            content:''; flex:1; height:1px;
            background: linear-gradient(90deg, rgba(99,102,241,.3), transparent);
        }

        /* ── Grid ── */
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .col-2 { grid-column: 1 / -1; }

        /* ── Fields ── */
        .field { display:flex; flex-direction:column; gap:5px; }
        .label {
            font-size: .76rem; font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase; letter-spacing: .45px;
        }
        .input-box { position: relative; }
        .input-ico {
            position:absolute; right:13px; top:50%; transform:translateY(-50%);
            color:#475569; font-size:.8rem; pointer-events:none;
            transition:color .25s;
        }
        .input-box:focus-within .input-ico { color:#818cf8; }

        .inp, .sel, .ta {
            width: 100%;
            background: rgba(99,102,241,0.07);
            border: 1px solid rgba(99,102,241,0.18);
            border-radius: 12px;
            padding: .75rem 2.6rem .75rem .9rem;
            color: #f1f5f9;
            font-family: 'Cairo', sans-serif;
            font-size: .88rem;
            outline: none;
            appearance: none;
            transition: border-color .25s, box-shadow .25s, background .25s;
        }
        .inp::placeholder, .ta::placeholder { color:#334155; }
        .inp:focus, .sel:focus, .ta:focus {
            border-color:#6366f1;
            background:rgba(99,102,241,0.12);
            box-shadow:0 0 0 3px rgba(99,102,241,0.14);
        }
        .sel option { background:#1e293b; color:#fff; }
        .ta { padding:.75rem .9rem; resize:none; }

        /* ── Upload ── */
        .upload-zone {
            border: 2px dashed rgba(99,102,241,.28);
            border-radius: 14px;
            padding: 1.6rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .25s, background .25s;
        }
        .upload-zone:hover { border-color:#6366f1; background:rgba(99,102,241,0.06); }
        .upload-zone.done  { border-color:rgba(34,211,238,.45); background:rgba(34,211,238,0.05); }
        .u-icon   { font-size:1.9rem; color:#818cf8; display:block; margin-bottom:7px; }
        .u-text   { font-size:.82rem; color:#94a3b8; }
        .u-text strong { color:#818cf8; }
        .u-sub    { font-size:.7rem; color:#475569; margin-top:3px; }
        .u-done   { display:none; align-items:center; justify-content:center; gap:9px; font-size:.82rem; color:#22d3ee; }
        .upload-zone.done .u-main { display:none; }
        .upload-zone.done .u-done { display:flex; }

        /* ── Password strength ── */
        .pass-bar { height:3px; border-radius:2px; background:rgba(255,255,255,.07); margin-top:6px; overflow:hidden; }
        .pass-fill { height:100%; width:0; border-radius:2px; transition:width .35s,background .35s; }
        .pass-hint { font-size:.68rem; color:#64748b; margin-top:3px; }

        /* ── Submit ── */
        .btn-submit {
            width:100%; padding:.92rem;
            border:none; border-radius:14px;
            background: linear-gradient(135deg,#4338ca,#6366f1,#7c3aed);
            color:#fff; font-family:'Cairo',sans-serif;
            font-size:1rem; font-weight:700;
            cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:9px;
            position:relative; overflow:hidden;
            transition:transform .2s, box-shadow .2s;
            box-shadow:0 6px 26px rgba(99,102,241,.45);
            letter-spacing:.3px;
        }
        .btn-submit:hover { transform:translateY(-2px); box-shadow:0 12px 34px rgba(99,102,241,.6); }
        .btn-submit:active { transform:translateY(0); }
        .btn-submit::after {
            content:''; position:absolute;
            top:-60%; left:-60%; width:45%; height:220%;
            background:rgba(255,255,255,.11); transform:skewX(-20deg);
            transition:left .55s ease;
        }
        .btn-submit:hover::after { left:130%; }

        .card-foot { text-align:center; margin-top:1.5rem; font-size:.8rem; color:#475569; }
        .card-foot a { color:#818cf8; font-weight:700; text-decoration:none; transition:color .2s; }
        .card-foot a:hover { color:#22d3ee; }

        /* Robot anims */
        .r-eye     { animation:eyeBlink 5s ease-in-out infinite; transform-origin:80px 60px; }
        @keyframes eyeBlink { 0%,90%,100%{transform:scaleY(1)} 95%{transform:scaleY(.08)} }
        .r-antenna { animation:antGlow 2.2s ease-in-out infinite; }
        @keyframes antGlow {
            0%,100%{fill:#6366f1;filter:drop-shadow(0 0 5px #6366f1)}
            50%{fill:#22d3ee;filter:drop-shadow(0 0 11px #22d3ee)}
        }
        .r-chest   { animation:chestPulse 2.4s ease-in-out infinite; }
        @keyframes chestPulse { 0%,100%{opacity:.55} 50%{opacity:1} }

        @media (max-width:640px) {
            .grid { grid-template-columns:1fr; }
            .col-2 { grid-column:1; }
            .card { padding:2rem 1.2rem; }
            .card-head { gap:.9rem; }
            .head-robot svg { width:55px; height:75px; }
        }
    </style>
</head>
<body>

<video id="bg-video" autoplay muted loop playsinline
       poster="https://images.unsplash.com/photo-1462331940025-496dfbfc7564?w=1920&q=85">
    <source src="" type="video/mp4">
</video>
<div class="overlay"></div>
<div id="stars-wrap"></div>

<div class="page">
    <div class="card">

        <!-- ── Header ── -->
        <div class="card-head">
            <div class="head-robot">
                <!-- Same robot, scaled to 72px wide -->
                <svg width="72" height="96" viewBox="-12 -5 184 268" fill="none">
                    <defs>
                        <linearGradient id="lg2" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%"  stop-color="#1e293b"/>
                            <stop offset="100%" stop-color="#0d1526"/>
                        </linearGradient>
                        <radialGradient id="re2" cx="35%" cy="35%" r="60%">
                            <stop offset="0%"  stop-color="#818cf8"/>
                            <stop offset="100%" stop-color="#4338ca"/>
                        </radialGradient>
                        <radialGradient id="rc2" cx="50%" cy="50%" r="50%">
                            <stop offset="0%"  stop-color="#22d3ee"/>
                            <stop offset="100%" stop-color="#4338ca" stop-opacity=".4"/>
                        </radialGradient>
                        <filter id="gs2">
                            <feGaussianBlur stdDeviation="2.5" result="b"/>
                            <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
                    <circle class="r-antenna" cx="80" cy="3" r="7" fill="#6366f1" filter="url(#gs2)"/>
                    <circle cx="80" cy="3" r="3.5" fill="#e0e7ff"/>
                    <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <g class="r-eye">
                        <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#re2)" filter="url(#gs2)"/>
                        <circle cx="57" cy="60" r="11" fill="#0d1526"/>
                        <circle cx="57" cy="60" r="6.5" fill="#6366f1"/>
                        <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,.92)"/>
                        <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#re2)" filter="url(#gs2)"/>
                        <circle cx="103" cy="60" r="11" fill="#0d1526"/>
                        <circle cx="103" cy="60" r="6.5" fill="#6366f1"/>
                        <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,.92)"/>
                    </g>
                    <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526" stroke="#6366f1" stroke-width="1.2"/>
                    <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526" stroke="#4f46e5" stroke-width="1"/>
                    <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="-8"  y="125" width="26" height="60" rx="13" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <circle cx="80" cy="162" r="18" fill="rgba(99,102,241,.1)" stroke="rgba(99,102,241,.3)" stroke-width="1"/>
                    <circle class="r-chest" cx="80" cy="162" r="10" fill="url(#rc2)" filter="url(#gs2)"/>
                    <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg2)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                </svg>
            </div>
            <div class="head-text">
                <div class="title">إنشاء حساب جديد</div>
                <div class="sub">سجّل بياناتك للوصول لنظام الصيدلية الذكي</div>
                <div class="online-badge">
                    <span class="online-dot"></span>
                    AI Pharmacy System — نظام ذكي
                </div>
            </div>
        </div>

        @if($errors->any())
        <div class="alert">
            <ul>@foreach($errors->all() as $e)<li><i class="fas fa-circle-exclamation"></i>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Section: Personal -->
            <div class="sec-label"><i class="fas fa-user"></i> البيانات الشخصية</div>
            <div class="grid">
                <div class="field">
                    <label class="label">الاسم الكامل *</label>
                    <div class="input-box">
                        <i class="fas fa-id-card input-ico"></i>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="inp" placeholder="الاسم بالكامل">
                    </div>
                </div>
                <div class="field">
                    <label class="label">البريد الإلكتروني *</label>
                    <div class="input-box">
                        <i class="fas fa-envelope input-ico"></i>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="inp" placeholder="example@email.com">
                    </div>
                </div>
                <div class="field">
                    <label class="label">رقم التلفون *</label>
                    <div class="input-box">
                        <i class="fas fa-phone input-ico"></i>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="inp" placeholder="01xxxxxxxxx">
                    </div>
                </div>
                <div class="field">
                    <label class="label">اسم الصيدلية *</label>
                    <div class="input-box">
                        <i class="fas fa-hospital input-ico"></i>
                        <input type="text" name="pharmacy_name" value="{{ old('pharmacy_name') }}" required
                               class="inp" placeholder="اسم الصيدلية">
                    </div>
                </div>
            </div>

            <!-- Section: Location -->
            <div class="sec-label"><i class="fas fa-location-dot"></i> الموقع الجغرافي</div>
            <div class="grid">
                <div class="field">
                    <label class="label">المحافظة *</label>
                    <div class="input-box">
                        <i class="fas fa-map input-ico"></i>
                        <select name="governorate" id="gov-sel" required class="sel"
                                onchange="loadCities(this.value)">
                            <option value="">اختر المحافظة</option>
                            @foreach(config('egypt_locations') as $gov => $cities)
                                <option value="{{ $gov }}" {{ old('governorate')==$gov?'selected':'' }}>{{ $gov }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label class="label">المركز *</label>
                    <div class="input-box">
                        <i class="fas fa-city input-ico"></i>
                        <select name="city" id="city-sel" required class="sel">
                            <option value="">اختر المحافظة أولاً</option>
                        </select>
                    </div>
                </div>
                <div class="field col-2">
                    <label class="label">العنوان التفصيلي *</label>
                    <textarea name="address" required rows="2" class="ta"
                              placeholder="الشارع، الحي، المدينة...">{{ old('address') }}</textarea>
                </div>
            </div>

            <!-- Section: Documents -->
            <div class="sec-label"><i class="fas fa-file-shield"></i> المستندات</div>
            <div class="field">
                <label class="label">صورة كارنيه النقابة *</label>
                <div class="upload-zone" id="upload-zone"
                     onclick="document.getElementById('syn-file').click()"
                     ondragover="ev.preventDefault();this.classList.add('hover')"
                     ondragleave="this.classList.remove('hover')"
                     ondrop="dropFile(event)">
                    <div class="u-main">
                        <i class="fas fa-id-badge u-icon"></i>
                        <div class="u-text"><strong>اضغط لرفع الصورة</strong> أو اسحب وأفلت</div>
                        <div class="u-sub">JPG، PNG — حد أقصى 5MB</div>
                    </div>
                    <div class="u-done">
                        <i class="fas fa-circle-check" style="color:#22d3ee;font-size:1.1rem"></i>
                        <span id="fname">—</span>
                    </div>
                </div>
                <input type="file" id="syn-file" name="syndicate_card" required
                       accept="image/*" style="display:none" onchange="handleFile(this)">
            </div>

            <!-- Section: Security -->
            <div class="sec-label"><i class="fas fa-lock"></i> الأمان</div>
            <div class="grid">
                <div class="field">
                    <label class="label">كلمة السر *</label>
                    <div class="input-box">
                        <i class="fas fa-key input-ico"></i>
                        <input type="password" name="password" id="p1" required
                               class="inp" placeholder="8 أحرف على الأقل"
                               oninput="checkStrength(this.value)">
                    </div>
                    <div class="pass-bar"><div class="pass-fill" id="pbar"></div></div>
                    <div class="pass-hint" id="phint">أدخل كلمة السر</div>
                </div>
                <div class="field">
                    <label class="label">تأكيد كلمة السر *</label>
                    <div class="input-box">
                        <i class="fas fa-key input-ico"></i>
                        <input type="password" name="password_confirmation" id="p2" required
                               class="inp" placeholder="أعد كتابة كلمة السر"
                               oninput="checkMatch()">
                    </div>
                    <div class="pass-hint" id="mhint"></div>
                </div>

                <div class="col-2" style="margin-top:.4rem">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-astronaut"></i>
                        إنشاء الحساب والانطلاق
                    </button>
                </div>
            </div>
        </form>

        <div class="card-foot">
            عندك حساب؟ <a href="{{ route('login') }}">سجّل دخول</a>
        </div>
    </div>
</div>

<script>
/* ── Stars ── */
const wrap = document.getElementById('stars-wrap');
for (let i = 0; i < 130; i++) {
    const s = document.createElement('div');
    const sz = Math.random() * 2.5 + 0.5;
    s.className = 'star';
    s.style.cssText = `
        width:${sz}px;height:${sz}px;
        background:${Math.random()>.88?'#a5b4fc':'white'};
        left:${Math.random()*100}%;top:${Math.random()*100}%;
        --d:${2+Math.random()*5}s;--delay:${Math.random()*5}s;
        ${sz>1.8?'box-shadow:0 0 4px rgba(165,180,252,0.6)':''}
    `;
    wrap.appendChild(s);
}

/* ── Cities ── */
const egyptLocations = @json(config('egypt_locations'));
function loadCities(gov) {
    const sel = document.getElementById('city-sel');
    sel.innerHTML = '<option value="">اختر المركز</option>';
    (egyptLocations[gov] || []).forEach(c => {
        const o = document.createElement('option');
        o.value = c; o.textContent = c; sel.appendChild(o);
    });
}
const og = "{{ old('governorate') }}";
if (og) { loadCities(og); document.getElementById('city-sel').value = "{{ old('city') }}"; }

/* ── File upload ── */
function handleFile(inp) {
    if (!inp.files[0]) return;
    const zone = document.getElementById('upload-zone');
    zone.classList.add('done');
    document.getElementById('fname').textContent = inp.files[0].name;
}
function dropFile(e) {
    e.preventDefault();
    const inp = document.getElementById('syn-file');
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    inp.files = dt.files;
    handleFile(inp);
}

/* ── Password strength ── */
function checkStrength(v) {
    const bar = document.getElementById('pbar');
    const hint = document.getElementById('phint');
    let sc = 0;
    if (v.length >= 8) sc++;
    if (/[A-Z]/.test(v)) sc++;
    if (/[0-9]/.test(v)) sc++;
    if (/[^A-Za-z0-9]/.test(v)) sc++;
    const pct = sc / 4 * 100;
    const colors = ['#ef4444','#f97316','#eab308','#22c55e'];
    const labels = ['ضعيفة جداً','ضعيفة','مقبولة','قوية 💪'];
    bar.style.width = pct + '%';
    bar.style.background = colors[Math.max(0, sc-1)];
    hint.textContent = v.length ? labels[Math.max(0, sc-1)] : 'أدخل كلمة السر';
    hint.style.color  = v.length ? colors[Math.max(0, sc-1)] : '#64748b';
}
function checkMatch() {
    const p1 = document.getElementById('p1').value;
    const p2 = document.getElementById('p2').value;
    const h  = document.getElementById('mhint');
    if (!p2) { h.textContent = ''; return; }
    if (p1 === p2) { h.textContent = '✓ كلمتا السر متطابقتان'; h.style.color = '#22c55e'; }
    else           { h.textContent = '✗ كلمتا السر مختلفتان'; h.style.color = '#ef4444'; }
}
</script>
</body>
</html>