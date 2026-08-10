<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول — AI Pharmacy System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* ── Background Video / Image ── */
        #bg-video {
            position: fixed;
            inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        /* ── Overlay ── */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.72);
            z-index: -1;
        }

        /* ── Stars ── */
        .star {
            position: absolute;
            border-radius: 50%;
            animation: twinkle var(--d, 3s) ease-in-out infinite;
            animation-delay: var(--delay, 0s);
        }
        @keyframes twinkle {
            0%, 100% { opacity: 1;   transform: scale(1); }
            50%       { opacity: 0.15; transform: scale(0.5); }
        }
        #stars-wrap { position: fixed; inset: 0; z-index: -1; pointer-events: none; overflow: hidden; }

        /* ── Layout ── */
        .page {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5rem;
            min-height: 100vh;
        }

        /* ══════════════════════════════
           ROBOT SIDE
        ══════════════════════════════ */
        .robot-side {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.4rem;
        }

        .robot-float {
            animation: robotFloat 3.6s ease-in-out infinite;
            filter: drop-shadow(0 0 18px rgba(99,102,241,0.45));
        }
        @keyframes robotFloat {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-14px); }
        }

        /* Robot SVG animations */
        .r-eye       { animation: eyeBlink 5s ease-in-out infinite; transform-origin: 80px 65px; }
        @keyframes eyeBlink {
            0%,90%,100% { transform: scaleY(1); }
            95%          { transform: scaleY(0.08); }
        }
        .r-antenna   { animation: antGlow 2.2s ease-in-out infinite; }
        @keyframes antGlow {
            0%,100% { fill: #6366f1; filter: drop-shadow(0 0 5px #6366f1); }
            50%      { fill: #22d3ee; filter: drop-shadow(0 0 12px #22d3ee); }
        }
        .r-chest     { animation: chestPulse 2.4s ease-in-out infinite; }
        @keyframes chestPulse {
            0%,100% { opacity: 0.55; }
            50%      { opacity: 1; }
        }
        .r-scanline  { animation: scan 3s linear infinite; }
        @keyframes scan {
            0%   { transform: translateY(-40px); opacity: 0; }
            20%  { opacity: 0.6; }
            80%  { opacity: 0.6; }
            100% { transform: translateY(40px); opacity: 0; }
        }

        .robot-brand { text-align: center; color: #fff; }
        .robot-brand .name { font-size: 1.25rem; font-weight: 900; letter-spacing: 0.5px; }
        .robot-brand .tag  {
            font-size: 0.78rem; color: #818cf8; margin-top: 3px;
        }
        .online-badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(34,211,238,0.1);
            border: 1px solid rgba(34,211,238,0.28);
            border-radius: 30px;
            padding: 5px 14px;
            font-size: 0.72rem;
            color: #22d3ee;
            margin-top: 8px;
        }
        .online-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #22d3ee;
            box-shadow: 0 0 8px #22d3ee;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.2} }

        /* ══════════════════════════════
           GLASS CARD
        ══════════════════════════════ */
        .card {
            width: 100%;
            max-width: 420px;
            background: rgba(10, 18, 38, 0.70);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid rgba(99,102,241,0.22);
            border-radius: 24px;
            padding: 2.4rem 2rem;
            color: #e2e8f0;
            position: relative;
            box-shadow:
                0 25px 70px rgba(0,0,0,0.55),
                0 0 0 1px rgba(255,255,255,0.04) inset,
                0 0 50px rgba(99,102,241,0.08);
            animation: cardIn 0.7s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn { from { opacity:0; transform:translateY(22px); } to { opacity:1; transform:translateY(0); } }

        /* top shimmer line */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 50%; transform: translateX(-50%);
            width: 62%; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(129,140,248,0.7), transparent);
            border-radius: 999px;
        }

        .card-head { text-align: center; margin-bottom: 2rem; }
        .card-icon {
            width: 54px; height: 54px;
            margin: 0 auto 1rem;
            border-radius: 15px;
            background: linear-gradient(135deg,#4f46e5,#7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #fff;
            box-shadow: 0 8px 24px rgba(99,102,241,0.45);
        }
        .card-title { font-size: 1.5rem; font-weight: 900; color: #fff; }
        .card-sub   { font-size: 0.8rem; color: #64748b; margin-top: 5px; }

        /* Error block */
        .alert {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 12px;
            padding: 0.7rem 1rem;
            margin-bottom: 1.2rem;
            color: #fca5a5;
            font-size: 0.8rem;
            display: flex; align-items: center; gap: 8px;
        }

        /* Form */
        .field { margin-bottom: 1.1rem; }
        .label {
            display: block;
            font-size: 0.77rem;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .input-box { position: relative; }
        .input-ico {
            position: absolute;
            right: 13px; top: 50%; transform: translateY(-50%);
            color: #475569; font-size: 0.82rem;
            pointer-events: none;
            transition: color 0.25s;
        }
        .input-box:focus-within .input-ico { color: #818cf8; }

        .inp {
            width: 100%;
            background: rgba(99,102,241,0.07);
            border: 1px solid rgba(99,102,241,0.18);
            border-radius: 12px;
            padding: 0.78rem 2.7rem 0.78rem 0.95rem;
            color: #f1f5f9;
            font-family: 'Cairo', sans-serif;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
        }
        .inp::placeholder { color: #334155; }
        .inp:focus {
            border-color: #6366f1;
            background: rgba(99,102,241,0.12);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }

        .eye-btn {
            position: absolute;
            left: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: #475569; font-size: 0.82rem; padding: 3px;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: #818cf8; }

        .remember {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 1.3rem;
        }
        .remember input { accent-color: #6366f1; width: 15px; height: 15px; cursor: pointer; }
        .remember label { font-size: 0.8rem; color: #64748b; cursor: pointer; }

        /* Submit button */
        .btn {
            width: 100%;
            padding: 0.88rem;
            border: none;
            border-radius: 13px;
            background: linear-gradient(135deg, #4338ca, #6366f1, #7c3aed);
            background-size: 200% 100%;
            color: #fff;
            font-family: 'Cairo', sans-serif;
            font-size: 0.97rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s, background-position 0.4s;
            box-shadow: 0 5px 22px rgba(99,102,241,0.45);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(99,102,241,0.6);
            background-position: right center;
        }
        .btn:active { transform: translateY(0); }
        .btn::after {
            content: '';
            position: absolute;
            top: -60%; left: -60%;
            width: 45%; height: 220%;
            background: rgba(255,255,255,0.12);
            transform: skewX(-20deg);
            transition: left 0.55s ease;
        }
        .btn:hover::after { left: 130%; }

        .card-foot {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #475569;
        }
        .card-foot a {
            color: #818cf8;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.2s;
        }
        .card-foot a:hover { color: #22d3ee; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .robot-side { display: none; }
            .page { padding: 1.5rem 1rem; }
        }
        @media (max-width: 440px) {
            .card { padding: 2rem 1.3rem; }
        }
    </style>
</head>
<body>

<!-- ── Space Background ── -->
<video id="bg-video" autoplay muted loop playsinline>
    <source src="" type="video/mp4">
</video>
<div class="overlay"></div>
<div id="stars-wrap"></div>

<div class="page">

    <!-- ══════════ ROBOT ══════════ -->
    <div class="robot-side">
        <div class="robot-float">
            <!--
                viewBox="-12 -5 184 268"
                Robot fits fully: arms go from x=-8 to x=168, body y=0 to y=258
                No clipping on any side.
            -->
            <svg width="184" height="268" viewBox="-12 -5 184 268"
                 fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="lg-body" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%"   stop-color="#1e293b"/>
                        <stop offset="100%" stop-color="#0d1526"/>
                    </linearGradient>
                    <radialGradient id="rg-eye" cx="35%" cy="35%" r="60%">
                        <stop offset="0%"   stop-color="#818cf8"/>
                        <stop offset="100%" stop-color="#4338ca"/>
                    </radialGradient>
                    <radialGradient id="rg-chest" cx="50%" cy="50%" r="50%">
                        <stop offset="0%"   stop-color="#22d3ee"/>
                        <stop offset="60%"  stop-color="#6366f1"/>
                        <stop offset="100%" stop-color="#4338ca" stop-opacity="0.4"/>
                    </radialGradient>
                    <filter id="glow-soft">
                        <feGaussianBlur stdDeviation="2.5" result="blur"/>
                        <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                    </filter>
                    <clipPath id="clip-body">
                        <rect x="18" y="118" width="124" height="90" rx="18"/>
                    </clipPath>
                </defs>

                <!-- ── Antenna ── -->
                <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
                <circle class="r-antenna" cx="80" cy="3" r="7" fill="#6366f1" filter="url(#glow-soft)"/>
                <circle cx="80" cy="3" r="3.5" fill="#e0e7ff"/>

                <!-- ── Head ── -->
                <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                <!-- head inner shine -->
                <rect x="34" y="30" width="92" height="8" rx="0" fill="rgba(129,140,248,0.08)"
                      style="border-radius:20px 20px 0 0"/>

                <!-- ── Eyes (blink group) ── -->
                <g class="r-eye">
                    <!-- Left eye housing -->
                    <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#rg-eye)" filter="url(#glow-soft)"/>
                    <circle cx="57" cy="60" r="11" fill="#0d1526"/>
                    <circle cx="57" cy="60" r="6.5" fill="#6366f1"/>
                    <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
                    <circle cx="54" cy="63" r="1.2" fill="rgba(255,255,255,0.3)"/>
                    <!-- Right eye housing -->
                    <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#rg-eye)" filter="url(#glow-soft)"/>
                    <circle cx="103" cy="60" r="11" fill="#0d1526"/>
                    <circle cx="103" cy="60" r="6.5" fill="#6366f1"/>
                    <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
                    <circle cx="100" cy="63" r="1.2" fill="rgba(255,255,255,0.3)"/>
                </g>

                <!-- ── Nose ── -->
                <circle cx="75" cy="82" r="2.5" fill="rgba(99,102,241,0.55)"/>
                <circle cx="85" cy="82" r="2.5" fill="rgba(99,102,241,0.55)"/>

                <!-- ── Mouth ── -->
                <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526" stroke="#6366f1" stroke-width="1.2"/>
                <rect x="58" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
                <rect x="71" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
                <rect x="84" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>

                <!-- ── Neck ── -->
                <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526" stroke="#4f46e5" stroke-width="1"/>
                <line x1="68" y1="115" x2="92" y2="115" stroke="rgba(99,102,241,0.4)" stroke-width="1"/>

                <!-- ── BODY ── -->
                <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>

                <!-- Scanline inside body -->
                <rect x="19" y="122" width="122" height="88" rx="17" fill="none" clip-path="url(#clip-body)"/>
                <rect class="r-scanline" x="20" y="130" width="120" height="2" rx="1" fill="rgba(99,102,241,0.18)" clip-path="url(#clip-body)"/>

                <!-- ── LEFT ARM ── x from -8 to 18, no gap/overlap with body -->
                <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                <!-- shoulder rivet -->
                <circle cx="9" cy="125" r="4.5" fill="#4f46e5" opacity="0.7"/>
                <!-- left hand -->
                <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>

                <!-- ── RIGHT ARM ── x from 142 to 168, no gap/overlap -->
                <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                <!-- shoulder rivet -->
                <circle cx="151" cy="125" r="4.5" fill="#4f46e5" opacity="0.7"/>
                <!-- right hand -->
                <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>

                <!-- ── Chest ── -->
                <circle cx="80" cy="162" r="24" fill="rgba(99,102,241,0.07)" stroke="rgba(99,102,241,0.25)" stroke-width="1"/>
                <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)" stroke="rgba(99,102,241,0.35)" stroke-width="1"/>
                <circle class="r-chest" cx="80" cy="162" r="10" fill="url(#rg-chest)" filter="url(#glow-soft)"/>
                <line x1="80" y1="153" x2="80" y2="171" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" stroke-linecap="round"/>
                <line x1="71" y1="162" x2="89" y2="162" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" stroke-linecap="round"/>

                <!-- Side vents left -->
                <rect x="24" y="143" width="18" height="4" rx="2" fill="rgba(99,102,241,0.28)"/>
                <rect x="24" y="151" width="13" height="4" rx="2" fill="rgba(99,102,241,0.18)"/>
                <!-- Side vents right -->
                <rect x="118" y="143" width="18" height="4" rx="2" fill="rgba(99,102,241,0.28)"/>
                <rect x="125" y="151" width="13" height="4" rx="2" fill="rgba(99,102,241,0.18)"/>

                <!-- ── LEGS ── -->
                <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                <!-- leg detail stripe -->
                <rect x="38" y="220" width="22" height="3" rx="1.5" fill="rgba(99,102,241,0.3)"/>
                <rect x="100" y="220" width="22" height="3" rx="1.5" fill="rgba(99,102,241,0.3)"/>

                <!-- ── FEET ── -->
                <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
            </svg>
        </div>

        <div class="robot-brand">
            <div class="name">AI Pharmacy System</div>
            <div class="tag">نظام الصيدلية الذكي</div>
            <div class="online-badge">
                <span class="online-dot"></span>
                النظام يعمل بكفاءة
            </div>
        </div>
    </div><!-- /robot-side -->


    <!-- ══════════ CARD ══════════ -->
    <div class="card">
        <div class="card-head">
            <div class="card-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="card-title">تسجيل الدخول</div>
            <div class="card-sub">مرحباً بك — أدخل بياناتك للمتابعة</div>
        </div>

        @if($errors->any())
        <div class="alert">
            <i class="fas fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="field">
                <label class="label">الإيميل أو اسم المستخدم</label>
                <div class="input-box">
                    <i class="fas fa-user input-ico"></i>
                    <input type="text" name="login" value="{{ old('login') }}" required
                           class="inp" placeholder="أدخل الإيميل أو اليوزرنيم">
                </div>
            </div>

            <div class="field">
                <label class="label">كلمة السر</label>
                <div class="input-box">
                    <i class="fas fa-lock input-ico"></i>
                    <input type="password" name="password" id="passInp" required
                           class="inp" placeholder="أدخل كلمة السر">
                    <button type="button" class="eye-btn" onclick="togglePass()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">تذكرني على هذا الجهاز</label>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-arrow-right-to-bracket"></i>
                دخول
            </button>
        </form>

        @unless (\App\Support\Runtime::isDesktop())
            <div class="card-foot">
                مش عندك حساب؟
                <a href="{{ route('register') }}"> انشاء حساب </a>
            </div>
        @endunless
        @if (\App\Support\Runtime::isDesktop())
            <div class="card-foot">
                <a href="{{ route('diagnostics.page') }}"><i class="fas fa-stethoscope"></i> مركز التشخيص والإعدادات</a>
            </div>
        @endif
    </div>

</div><!-- /page -->

<script>
/* ── Stars ── */
const wrap = document.getElementById('stars-wrap');
for (let i = 0; i < 130; i++) {
    const s = document.createElement('div');
    const size = Math.random() * 2.5 + 0.5;
    s.className = 'star';
    s.style.cssText = `
        width:${size}px; height:${size}px;
        background:${Math.random()>.88?'#a5b4fc':'white'};
        left:${Math.random()*100}%; top:${Math.random()*100}%;
        --d:${2+Math.random()*5}s;
        --delay:${Math.random()*5}s;
        ${size>1.8?'box-shadow:0 0 4px rgba(165,180,252,0.6)':''}
    `;
    wrap.appendChild(s);
}

/* ── Password toggle ── */
function togglePass() {
    const inp  = document.getElementById('passInp');
    const icon = document.getElementById('eyeIcon');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    icon.className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye';
}
</script>
</body>
</html>