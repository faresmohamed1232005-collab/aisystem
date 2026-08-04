<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Pharmacy System — مستقبل إدارة الصيدليات</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
/* ══════════════════════════════════════════
   TOKENS — نفس هوية صفحة تسجيل الدخول
   bg: #020617 | glass: rgba(10,18,38,.7) | indigo: #6366f1 | violet: #7c3aed | cyan: #22d3ee
══════════════════════════════════════════ */
:root{
    --bg:#020617;
    --glass:rgba(10,18,38,0.70);
    --glass-soft:rgba(10,18,38,0.45);
    --border:rgba(99,102,241,0.22);
    --border-soft:rgba(99,102,241,0.14);
    --indigo:#6366f1;
    --indigo-deep:#4338ca;
    --violet:#7c3aed;
    --cyan:#22d3ee;
    --ink:#e2e8f0;
    --ink-dim:#94a3b8;
    --ink-mute:#475569;
    --grad: linear-gradient(135deg,#4338ca,#6366f1,#7c3aed);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{
    font-family:'Cairo',sans-serif;
    background:var(--bg);
    color:var(--ink);
    overflow-x:hidden;
    line-height:1.7;
}
::selection{background:rgba(99,102,241,.4);}
a{text-decoration:none;color:inherit;}
ul{list-style:none;}
img,svg{display:block;max-width:100%;}
.container{max-width:1240px;margin:0 auto;padding:0 1.6rem;}
section{position:relative;z-index:1;}
.eyebrow{
    display:inline-flex;align-items:center;gap:8px;
    font-size:.78rem;font-weight:700;color:var(--cyan);
    background:rgba(34,211,238,.08);border:1px solid rgba(34,211,238,.25);
    padding:6px 16px;border-radius:30px;margin-bottom:1.1rem;letter-spacing:.3px;
}
.eyebrow .dot{width:6px;height:6px;border-radius:50%;background:var(--cyan);box-shadow:0 0 8px var(--cyan);animation:blink 2s ease-in-out infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.25}}
.sec-head{text-align:center;max-width:680px;margin:0 auto 3.4rem;}
.sec-head h2{font-size:clamp(1.8rem,4vw,2.7rem);font-weight:900;color:#fff;letter-spacing:-.5px;}
.sec-head p{color:var(--ink-dim);margin-top:.9rem;font-size:1rem;}
.reveal{opacity:0;transform:translateY(28px);transition:opacity .8s cubic-bezier(.16,1,.3,1),transform .8s cubic-bezier(.16,1,.3,1);}
.reveal.in{opacity:1;transform:translateY(0);}

/* ── background layers (fixed, reused across whole page) ── */
.bg-fixed{position:fixed;inset:0;z-index:-3;background:
    radial-gradient(ellipse 60% 45% at 18% 8%, rgba(99,102,241,.16), transparent 60%),
    radial-gradient(ellipse 55% 40% at 88% 18%, rgba(124,58,237,.14), transparent 60%),
    radial-gradient(ellipse 50% 40% at 50% 95%, rgba(34,211,238,.08), transparent 60%),
    var(--bg);
}
#stars-wrap{position:fixed;inset:0;z-index:-2;pointer-events:none;overflow:hidden;}
.star{position:absolute;border-radius:50%;animation:twinkle var(--d,3s) ease-in-out infinite;animation-delay:var(--delay,0s);}
@keyframes twinkle{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.15;transform:scale(.5);}}

/* ══════════ LOADER ══════════ */
#loader{
    position:fixed;inset:0;z-index:999;background:var(--bg);
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.6rem;
    transition:opacity .6s ease, visibility .6s ease;
}
#loader.hide{opacity:0;visibility:hidden;pointer-events:none;}
.loader-ring{position:relative;width:110px;height:110px;}
.loader-ring svg{width:100%;height:100%;transform:rotate(-90deg);}
.loader-ring circle{fill:none;stroke-width:4;}
.loader-ring .track{stroke:rgba(99,102,241,.15);}
.loader-ring .bar{stroke:var(--cyan);stroke-linecap:round;stroke-dasharray:296;stroke-dashoffset:296;filter:drop-shadow(0 0 6px var(--cyan));transition:stroke-dashoffset .2s linear;}
.loader-ring .cross{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:var(--indigo);}
#loader-pct{font-size:.85rem;color:var(--ink-dim);font-weight:700;letter-spacing:1px;}
#loader-txt{font-size:.78rem;color:var(--ink-mute);}
.loader-pill{position:absolute;border-radius:50px;background:linear-gradient(135deg,#818cf8,#22d3ee);opacity:.7;animation:pillFloat 2.4s ease-in-out infinite;}
@keyframes pillFloat{0%,100%{transform:translateY(0) rotate(0deg);}50%{transform:translateY(-18px) rotate(20deg);}}

/* ══════════ NAV ══════════ */
header.nav{
    position:fixed;top:0;left:0;right:0;z-index:90;
    background:rgba(2,6,23,.55);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    border-bottom:1px solid rgba(99,102,241,.12);
    transition:background .3s;
}
.nav-inner{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.6rem;max-width:1240px;margin:0 auto;}
.brand{display:flex;align-items:center;gap:10px;font-weight:900;color:#fff;font-size:1.05rem;}
.brand .mark{width:36px;height:36px;border-radius:10px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.95rem;box-shadow:0 6px 18px rgba(99,102,241,.4);flex-shrink:0;}
.nav-links{display:flex;gap:2rem;font-size:.88rem;color:var(--ink-dim);}
.nav-links a{transition:color .2s;position:relative;}
.nav-links a:hover{color:#fff;}
.nav-cta{display:flex;gap:.7rem;}
.btn{
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:.7rem 1.5rem;border-radius:12px;font-weight:700;font-size:.88rem;
    border:none;cursor:pointer;font-family:'Cairo',sans-serif;
    position:relative;overflow:hidden;transition:transform .2s,box-shadow .2s;
}
.btn::after{content:'';position:absolute;top:-60%;left:-60%;width:45%;height:220%;background:rgba(255,255,255,.14);transform:skewX(-20deg);transition:left .55s ease;}
.btn:hover::after{left:130%;}
.btn:hover{transform:translateY(-2px);}
.btn-primary{background:var(--grad);background-size:200% 100%;color:#fff;box-shadow:0 6px 20px rgba(99,102,241,.4);}
.btn-primary:hover{box-shadow:0 10px 28px rgba(99,102,241,.55);background-position:right center;}
.btn-ghost{background:rgba(99,102,241,.08);color:var(--ink);border:1px solid var(--border);}
.btn-ghost:hover{background:rgba(99,102,241,.15);}
.btn-lg{padding:1rem 2.1rem;font-size:1rem;border-radius:14px;}
.burger{display:none;color:#fff;font-size:1.3rem;background:none;border:none;cursor:pointer;z-index:95;}
.mobile-nav{
    position:fixed;top:0;left:0;right:0;bottom:0;z-index:99;
    background:rgba(2,6,23,.97);backdrop-filter:blur(10px);
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.8rem;
    opacity:0;visibility:hidden;transform:translateY(-14px);
    transition:opacity .35s ease,visibility .35s ease,transform .35s ease;
}
.mobile-nav.open{opacity:1;visibility:visible;transform:translateY(0);}
.mobile-nav a{font-size:1.15rem;font-weight:700;color:var(--ink);}
.mobile-nav a:hover{color:var(--cyan);}
.mobile-nav .btn{margin-top:.8rem;width:80%;max-width:280px;}
.mobile-nav-close{position:absolute;top:1.4rem;left:1.6rem;font-size:1.4rem;color:#fff;background:none;border:none;cursor:pointer;}

/* ══════════ HERO ══════════ */
.hero{min-height:100vh;display:flex;align-items:center;padding:9rem 0 5rem;}
.hero-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:3rem;align-items:center;}
.hero-copy h1{font-size:clamp(2.2rem,4.6vw,3.6rem);font-weight:900;color:#fff;letter-spacing:-1px;line-height:1.25;}
.hero-copy h1 span{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent;}
.hero-copy p{color:var(--ink-dim);font-size:1.05rem;margin:1.4rem 0 2rem;max-width:520px;}
.hero-btns{display:flex;gap:1rem;flex-wrap:wrap;}
.hero-stats{display:flex;gap:1.8rem;margin-top:2.6rem;flex-wrap:wrap;}
.hstat b{display:block;font-size:1.5rem;font-weight:900;color:#fff;}
.hstat span{font-size:.78rem;color:var(--ink-mute);}

/* robot stage in hero */
.hero-robot{position:relative;display:flex;align-items:center;justify-content:center;max-width:100%;}
.robot-float{animation:robotFloat 3.6s ease-in-out infinite;filter:drop-shadow(0 0 22px rgba(99,102,241,.5));}
@keyframes robotFloat{0%,100%{transform:translateY(0px);}50%{transform:translateY(-16px);}}
.r-eye{animation:eyeBlink 5s ease-in-out infinite;transform-origin:80px 65px;}
@keyframes eyeBlink{0%,90%,100%{transform:scaleY(1);}95%{transform:scaleY(.08);}}
.r-antenna{animation:antGlow 2.2s ease-in-out infinite;}
@keyframes antGlow{0%,100%{fill:#6366f1;filter:drop-shadow(0 0 5px #6366f1);}50%{fill:#22d3ee;filter:drop-shadow(0 0 12px #22d3ee);}}
.r-chest{animation:chestPulse 2.4s ease-in-out infinite;}
@keyframes chestPulse{0%,100%{opacity:.55;}50%{opacity:1;}}
.r-scanline{animation:scan 3s linear infinite;}
@keyframes scan{0%{transform:translateY(-40px);opacity:0;}20%{opacity:.6;}80%{opacity:.6;}100%{transform:translateY(40px);opacity:0;}}
.orbit-ring{position:absolute;border:1px dashed rgba(99,102,241,.25);border-radius:50%;animation:spin 26s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
.orbit-chip{
    position:absolute;display:flex;align-items:center;gap:7px;
    background:var(--glass);backdrop-filter:blur(14px);border:1px solid var(--border);
    padding:9px 13px;border-radius:12px;font-size:.76rem;font-weight:700;color:var(--ink);
    box-shadow:0 10px 30px rgba(0,0,0,.35);animation:chipFloat 4s ease-in-out infinite;
    white-space:nowrap;
}
.orbit-chip i{color:var(--cyan);}
@keyframes chipFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-10px);}}

/* ══════════ MARQUEE ══════════ */
.marquee-wrap{padding:2.4rem 0;border-top:1px solid var(--border-soft);border-bottom:1px solid var(--border-soft);overflow:hidden;}
.marquee-label{text-align:center;font-size:.78rem;color:var(--ink-mute);margin-bottom:1.4rem;letter-spacing:1px;}
.marquee-track{display:flex;gap:3.2rem;width:max-content;animation:mq 26s linear infinite;}
@keyframes mq{from{transform:translateX(0);}to{transform:translateX(-50%);}}
.mq-item{display:flex;align-items:center;gap:10px;color:var(--ink-mute);font-weight:700;font-size:1.05rem;white-space:nowrap;}
.mq-item i{color:var(--indigo);}

/* ══════════ CARD BASE (glass) ══════════ */
.glass{
    background:var(--glass);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
    border:1px solid var(--border);border-radius:22px;
    box-shadow:0 20px 50px rgba(0,0,0,.35),0 0 0 1px rgba(255,255,255,.03) inset;
}

/* ══════════ FEATURES ══════════ */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.4rem;}
.feat-card{
    padding:1.9rem 1.7rem;position:relative;overflow:hidden;cursor:default;
    transition:transform .35s cubic-bezier(.16,1,.3,1),border-color .35s, box-shadow .35s;
}
.feat-card:hover{transform:translateY(-8px);border-color:rgba(99,102,241,.5);box-shadow:0 26px 60px rgba(99,102,241,.18);}
.feat-ico{
    width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;
    background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.25);color:var(--indigo);
    font-size:1.15rem;margin-bottom:1.1rem;transition:transform .35s, background .35s, color .35s;
}
.feat-card:hover .feat-ico{background:var(--grad);color:#fff;transform:rotate(-8deg) scale(1.08);}
.feat-card h3{font-size:1.05rem;font-weight:800;color:#fff;margin-bottom:.5rem;}
.feat-card p{font-size:.86rem;color:var(--ink-dim);}
.feat-card .glow{position:absolute;inset:auto -30% -30% auto;width:140px;height:140px;background:radial-gradient(circle,rgba(99,102,241,.25),transparent 70%);opacity:0;transition:opacity .35s;}
.feat-card:hover .glow{opacity:1;}

/* ══════════ AI SECTION ══════════ */
.ai-sec{padding:7rem 0;}
.ai-grid{display:grid;grid-template-columns:.85fr 1.15fr;gap:3.2rem;align-items:center;}
.ai-brain-wrap{position:relative;display:flex;align-items:center;justify-content:center;min-height:380px;}
.brain-core{position:relative;width:230px;height:230px;border-radius:50%;
    background:radial-gradient(circle at 35% 30%,rgba(99,102,241,.35),rgba(124,58,237,.12) 55%,transparent 75%);
    display:flex;align-items:center;justify-content:center;animation:pulseCore 3.2s ease-in-out infinite;}
@keyframes pulseCore{0%,100%{box-shadow:0 0 40px rgba(99,102,241,.25);}50%{box-shadow:0 0 70px rgba(99,102,241,.45);}}
.brain-core i{font-size:3.6rem;color:var(--cyan);filter:drop-shadow(0 0 18px var(--cyan));}
.neural-svg{position:absolute;inset:0;width:100%;height:100%;opacity:.55;}
.neural-svg circle{animation:neuronBlink 2.6s ease-in-out infinite;}
.ai-list{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
.ai-item{
    display:flex;align-items:center;gap:12px;padding:1rem 1.1rem;border-radius:14px;
    background:rgba(99,102,241,.06);border:1px solid var(--border-soft);
    font-size:.86rem;font-weight:700;color:var(--ink);transition:background .25s,border-color .25s,transform .25s;
}
.ai-item:hover{background:rgba(99,102,241,.13);border-color:rgba(99,102,241,.4);transform:translateX(-4px);}
.ai-item i{color:var(--cyan);font-size:1rem;flex-shrink:0;}

/* ══════════ DASHBOARD PREVIEW ══════════ */
.dash-wrap{padding:2rem 2rem 1.4rem;}
.dash-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.4rem;flex-wrap:wrap;gap:10px;}
.dash-dots{display:flex;gap:6px;}
.dash-dots span{width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,.15);}
.dash-dots span:nth-child(1){background:#f87171;}
.dash-dots span:nth-child(2){background:#fbbf24;}
.dash-dots span:nth-child(3){background:#34d399;}
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.4rem;}
.kpi{padding:1rem 1.1rem;border-radius:14px;background:rgba(99,102,241,.06);border:1px solid var(--border-soft);}
.kpi span{font-size:.75rem;color:var(--ink-mute);}
.kpi b{display:block;font-size:1.35rem;color:#fff;font-weight:900;margin-top:2px;}
.kpi em{font-style:normal;font-size:.72rem;color:#34d399;font-weight:700;}
.chart-row{display:grid;grid-template-columns:1.4fr 1fr;gap:1.2rem;}
.chart-box{background:rgba(2,6,23,.4);border:1px solid var(--border-soft);border-radius:16px;padding:1.1rem;height:230px;}
.chart-box h4{font-size:.8rem;color:var(--ink-dim);margin-bottom:.6rem;font-weight:700;}

/* ══════════ COMPARISON ══════════ */
.cmp-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.6rem;}
.cmp-card{padding:2rem 1.8rem;}
.cmp-card h3{font-weight:900;font-size:1.15rem;margin-bottom:1.3rem;display:flex;align-items:center;gap:10px;}
.cmp-card.old{border-color:rgba(148,163,184,.15);}
.cmp-card.old h3{color:var(--ink-mute);}
.cmp-card.new h3{color:#fff;}
.cmp-card.new{border-color:rgba(99,102,241,.5);box-shadow:0 20px 60px rgba(99,102,241,.18);position:relative;}
.cmp-card li{display:flex;align-items:flex-start;gap:10px;font-size:.88rem;padding:.55rem 0;border-bottom:1px dashed rgba(148,163,184,.1);}
.cmp-card.old li{color:var(--ink-mute);}
.cmp-card.new li{color:var(--ink);}
.cmp-card li i{margin-top:3px;flex-shrink:0;}
.cmp-card.old li i{color:#f87171;}
.cmp-card.new li i{color:#34d399;}

/* ══════════ TIMELINE ══════════ */
.timeline{position:relative;max-width:900px;margin:0 auto;}
.timeline::before{content:'';position:absolute;top:0;bottom:0;right:27px;width:2px;background:linear-gradient(180deg,var(--indigo),var(--violet),transparent);}
.tl-step{position:relative;padding-right:70px;margin-bottom:2.2rem;}
.tl-step:last-child{margin-bottom:0;}
.tl-num{position:absolute;right:0;top:0;width:56px;height:56px;border-radius:16px;background:var(--grad);color:#fff;font-weight:900;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 8px 22px rgba(99,102,241,.4);}
.tl-step h4{font-size:1.05rem;font-weight:800;color:#fff;margin-bottom:.4rem;}
.tl-step p{font-size:.86rem;color:var(--ink-dim);max-width:560px;}

/* ══════════ TESTIMONIALS ══════════ */
.test-track{display:flex;gap:1.4rem;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:1rem;}
.test-track::-webkit-scrollbar{height:6px;}
.test-track::-webkit-scrollbar-thumb{background:rgba(99,102,241,.35);border-radius:10px;}
.test-card{min-width:340px;scroll-snap-align:start;padding:1.8rem;flex-shrink:0;}
.test-stars{color:#fbbf24;font-size:.85rem;margin-bottom:.9rem;}
.test-card p{font-size:.9rem;color:var(--ink);margin-bottom:1.3rem;}
.test-who{display:flex;align-items:center;gap:12px;}
.test-avatar{width:42px;height:42px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;}
.test-who b{display:block;font-size:.88rem;color:#fff;}
.test-who span{font-size:.75rem;color:var(--ink-mute);}

/* ══════════ PRICING ══════════ */
.price-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.4rem;align-items:stretch;}
.price-card{padding:2.1rem 1.8rem;display:flex;flex-direction:column;position:relative;transition:transform .3s;}
.price-card:hover{transform:translateY(-6px);}
.price-card.popular{border-color:rgba(99,102,241,.6);box-shadow:0 26px 60px rgba(99,102,241,.22);transform:scale(1.03);}
.price-badge{position:absolute;top:-13px;left:50%;transform:translateX(-50%);background:var(--grad);color:#fff;font-size:.72rem;font-weight:800;padding:5px 16px;border-radius:20px;box-shadow:0 6px 16px rgba(99,102,241,.4);}
.price-card h3{font-size:1.05rem;font-weight:800;color:#fff;}
.price-card .price{font-size:2.2rem;font-weight:900;color:#fff;margin:1rem 0 .2rem;}
.price-card .price span{font-size:.85rem;color:var(--ink-mute);font-weight:600;}
.price-card .desc{font-size:.82rem;color:var(--ink-dim);margin-bottom:1.4rem;}
.price-card ul{margin-bottom:1.6rem;flex-grow:1;}
.price-card li{display:flex;align-items:center;gap:9px;font-size:.85rem;color:var(--ink);padding:.5rem 0;}
.price-card li i{color:var(--cyan);font-size:.85rem;}

/* ══════════ FAQ ══════════ */
.faq-wrap{max-width:800px;margin:0 auto;}
.faq-item{border:1px solid var(--border-soft);border-radius:16px;margin-bottom:.9rem;overflow:hidden;background:rgba(99,102,241,.03);}
.faq-q{display:flex;justify-content:space-between;align-items:center;padding:1.15rem 1.4rem;cursor:pointer;font-weight:700;font-size:.92rem;color:#fff;gap:1rem;}
.faq-q i{transition:transform .3s;color:var(--indigo);flex-shrink:0;}
.faq-item.open .faq-q i{transform:rotate(45deg);}
.faq-a{max-height:0;overflow:hidden;transition:max-height .35s ease;}
.faq-a p{padding:0 1.4rem 1.15rem;font-size:.85rem;color:var(--ink-dim);}

/* ══════════ FINAL CTA ══════════ */
.final-cta{padding:5.5rem 0;}
.cta-box{padding:3.4rem 2rem;text-align:center;position:relative;overflow:hidden;}
.cta-box::before{content:'';position:absolute;inset:-40%;background:radial-gradient(circle,rgba(99,102,241,.18),transparent 65%);animation:spin 20s linear infinite;}
.cta-box h2{position:relative;font-size:clamp(1.7rem,3.8vw,2.5rem);font-weight:900;color:#fff;margin-bottom:1rem;}
.cta-box p{position:relative;color:var(--ink-dim);max-width:480px;margin:0 auto 1.8rem;}
.cta-box .hero-btns{position:relative;justify-content:center;}

/* ══════════ FOOTER ══════════ */
footer{border-top:1px solid var(--border-soft);padding:3rem 0 1.6rem;overflow:hidden;}
.foot-grid{display:flex;justify-content:center;margin-bottom:1.6rem;}
.foot-col{max-width:340px;text-align:center;display:flex;flex-direction:column;align-items:center;}
.foot-col .brand{justify-content:center;}
.foot-col p{color:var(--ink-mute);font-size:.85rem;max-width:280px;}
.foot-social{display:flex;gap:10px;margin-top:1rem;justify-content:center;}
.foot-social a{width:36px;height:36px;border-radius:10px;background:rgba(99,102,241,.08);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--ink-dim);}
.foot-social a:hover{background:var(--grad);color:#fff;}
.foot-bottom{text-align:center;font-size:.8rem;color:var(--ink-mute);border-top:1px solid var(--border-soft);padding-top:1.4rem;}
.foot-credit{margin-top:.5rem;font-size:.8rem;color:var(--ink-mute);}
.foot-credit a{color:var(--cyan);font-weight:800;transition:color .2s;}
.foot-credit a:hover{color:#fff;}

/* ══════════ RESPONSIVE ══════════ */
@media(max-width:1080px){
    .nav-links{display:none;}
    .burger{display:block;}
}
@media(max-width:980px){
    .hero-grid,.ai-grid,.cmp-grid{grid-template-columns:1fr;}
    .feat-grid{grid-template-columns:repeat(2,1fr);}
    .price-grid{grid-template-columns:1fr;}
    .kpi-row{grid-template-columns:repeat(2,1fr);}
    .chart-row{grid-template-columns:1fr;}
    .hero-robot{order:-1;transform:scale(.85);margin-bottom:-1rem;}
    .hero{padding:7.5rem 0 3.5rem;}
}
@media(max-width:760px){
    .nav-cta .btn-ghost{display:none;}
    .nav-cta .btn-primary{padding:.6rem 1.1rem;font-size:.8rem;}
    .hero-robot{transform:scale(.7);}
    .orbit-ring{display:none;}
    .hero-stats{gap:1.2rem;}
    .hstat b{font-size:1.25rem;}
    .sec-head{margin-bottom:2.4rem;}
    .ai-sec, .final-cta, section[style*="7rem"]{padding-top:4.5rem !important;padding-bottom:4.5rem !important;}
}
@media(max-width:600px){
    .feat-grid{grid-template-columns:1fr;}
    .ai-list{grid-template-columns:1fr;}
    .kpi-row{grid-template-columns:1fr 1fr;}
    .dash-wrap{padding:1.4rem 1.2rem 1rem;}
    .hero-copy h1{letter-spacing:-.5px;}
    .hero-btns .btn{width:100%;}
    .hero-btns{flex-direction:column;}
    .test-card{min-width:82vw;}
    .timeline::before{right:23px;}
    .tl-step{padding-right:58px;}
    .tl-num{width:46px;height:46px;font-size:.95rem;}
    .container{padding:0 1.1rem;}
}
@media(max-width:420px){
    .hero-robot{transform:scale(.6);}
    .orbit-chip{display:none;}
    .kpi-row{grid-template-columns:1fr;}
    .chart-box{height:200px;}
    .price-card,.feat-card,.cmp-card,.test-card{padding:1.4rem 1.2rem;}
}
</style>
</head>
<body>

<div class="bg-fixed"></div>
<div id="stars-wrap"></div>

<!-- ══════════ LOADER ══════════ -->
<div id="loader">
    <div class="loader-pill" style="width:30px;height:14px;top:32%;left:38%;"></div>
    <div class="loader-pill" style="width:22px;height:22px;top:60%;left:60%;animation-delay:.6s;"></div>
    <div class="loader-pill" style="width:26px;height:12px;top:65%;left:35%;animation-delay:1.1s;"></div>
    <div class="loader-ring">
        <svg viewBox="0 0 100 100">
            <circle class="track" cx="50" cy="50" r="47"></circle>
            <circle class="bar" id="loaderBar" cx="50" cy="50" r="47"></circle>
        </svg>
        <div class="cross"><i class="fas fa-plus"></i></div>
    </div>
    <div id="loader-pct">0%</div>
    <div id="loader-txt">جارٍ تجهيز الذكاء الاصطناعي...</div>
</div>

<!-- ══════════ NAV ══════════ -->
<header class="nav">
    <div class="nav-inner">
        <div class="brand">
            <span class="mark">
                <svg viewBox="0 0 100 100" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <line x1="50" y1="34" x2="50" y2="16" stroke="#fff" stroke-width="5" stroke-linecap="round"/>
                    <circle cx="50" cy="11" r="6" fill="#22d3ee"/>
                    <rect x="16" y="34" width="68" height="48" rx="15" fill="rgba(255,255,255,0.14)" stroke="#fff" stroke-width="4.5"/>
                    <circle cx="35" cy="57" r="7.5" fill="#fff"/>
                    <circle cx="65" cy="57" r="7.5" fill="#22d3ee"/>
                    <rect x="39" y="72" width="22" height="6" rx="3" fill="#fff"/>
                </svg>
            </span>
            AI Pharmacy System
        </div>
        <nav class="nav-links">
            <a href="#features">المميزات</a>
            <a href="#ai">الذكاء الاصطناعي</a>
            <a href="#dashboard">لوحة التحكم</a>
            <a href="#faq">الأسئلة الشائعة</a>
        </nav>
        <div class="nav-cta">
            <a href="/login" class="btn btn-ghost"><i class="fas fa-globe"></i> حساب ويب</a>
            <a href="#" class="btn btn-primary"><i class="fas fa-download"></i> تحميل البرنامج</a>
        </div>
        <button class="burger" id="burgerBtn"><i class="fas fa-bars"></i></button>
    </div>
</header>

<!-- ══════════ MOBILE NAV ══════════ -->
<div class="mobile-nav" id="mobileNav">
    <button class="mobile-nav-close" id="mobileNavClose"><i class="fas fa-xmark"></i></button>
    <a href="#features">المميزات</a>
    <a href="#ai">الذكاء الاصطناعي</a>
    <a href="#dashboard">لوحة التحكم</a>
    <a href="#faq">الأسئلة الشائعة</a>
    <a href="#" class="btn btn-ghost"><i class="fas fa-globe"></i> حساب ويب</a>
    <a href="#" class="btn btn-primary"><i class="fas fa-download"></i> تحميل البرنامج</a>
</div>

<!-- ══════════ HERO ══════════ -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy reveal">
            <div class="eyebrow"><span class="dot"></span> مدعوم بالذكاء الاصطناعي — الجيل الجديد</div>
            <h1>مستقبل إدارة الصيدليات <span>يُدار بالذكاء الاصطناعي</span></h1>
            <p>إدارة أذكى لصيدليتك من الألف إلى الياء. تحكّم في المخزون والمبيعات والمشتريات والتقارير والموردين والعملاء والتحليل المالي — كل ده من منصة واحدة ذكية.</p>
            <div class="hero-btns">
                <a href="#" class="btn btn-primary btn-lg"><i class="fas fa-arrow-down"></i> تحميل النسخة المكتبية</a>
                <a href="/login" class="btn btn-ghost btn-lg"><i class="fas fa-globe"></i> إنشاء حساب ويب</a>
            </div>
            <div class="hero-stats">
                <div class="hstat"><b data-count="1000" data-suffix="+">0</b><span>صيدلية تستخدم النظام</span></div>
                <div class="hstat"><b data-count="99.99" data-decimals="2" data-suffix="%">0</b><span>نسبة استقرار النظام</span></div>
                <div class="hstat"><b>AI</b><span>محرك ذكاء اصطناعي</span></div>
                <div class="hstat"><b>Cloud</b><span>مزامنة سحابية فورية</span></div>
            </div>
        </div>

        <div class="hero-robot reveal">
            <div class="orbit-ring" style="width:400px;height:400px;"></div>
            <div class="orbit-ring" style="width:320px;height:320px;animation-direction:reverse;animation-duration:34s;"></div>
            <div class="orbit-chip" style="top:6%;right:2%;"><i class="fas fa-chart-line"></i> تنبؤ بالمبيعات</div>
            <div class="orbit-chip" style="bottom:14%;left:-6%;"><i class="fas fa-triangle-exclamation"></i> تنبيه انتهاء صلاحية</div>
            <div class="orbit-chip" style="top:44%;left:-12%;"><i class="fas fa-barcode"></i> مسح فوري بالباركود</div>
            <div class="robot-float">
                <svg width="184" height="268" viewBox="-12 -5 184 268" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="lg-body" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#1e293b"/><stop offset="100%" stop-color="#0d1526"/>
                        </linearGradient>
                        <radialGradient id="rg-eye" cx="35%" cy="35%" r="60%">
                            <stop offset="0%" stop-color="#818cf8"/><stop offset="100%" stop-color="#4338ca"/>
                        </radialGradient>
                        <radialGradient id="rg-chest" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#22d3ee"/><stop offset="60%" stop-color="#6366f1"/><stop offset="100%" stop-color="#4338ca" stop-opacity="0.4"/>
                        </radialGradient>
                        <filter id="glow-soft"><feGaussianBlur stdDeviation="2.5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                        <clipPath id="clip-body"><rect x="18" y="118" width="124" height="90" rx="18"/></clipPath>
                    </defs>
                    <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
                    <circle class="r-antenna" cx="80" cy="3" r="7" fill="#6366f1" filter="url(#glow-soft)"/>
                    <circle cx="80" cy="3" r="3.5" fill="#e0e7ff"/>
                    <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <g class="r-eye">
                        <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#rg-eye)" filter="url(#glow-soft)"/>
                        <circle cx="57" cy="60" r="11" fill="#0d1526"/><circle cx="57" cy="60" r="6.5" fill="#6366f1"/>
                        <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/><circle cx="54" cy="63" r="1.2" fill="rgba(255,255,255,0.3)"/>
                        <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#rg-eye)" filter="url(#glow-soft)"/>
                        <circle cx="103" cy="60" r="11" fill="#0d1526"/><circle cx="103" cy="60" r="6.5" fill="#6366f1"/>
                        <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/><circle cx="100" cy="63" r="1.2" fill="rgba(255,255,255,0.3)"/>
                    </g>
                    <circle cx="75" cy="82" r="2.5" fill="rgba(99,102,241,0.55)"/><circle cx="85" cy="82" r="2.5" fill="rgba(99,102,241,0.55)"/>
                    <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526" stroke="#6366f1" stroke-width="1.2"/>
                    <rect x="58" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
                    <rect x="71" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
                    <rect x="84" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
                    <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526" stroke="#4f46e5" stroke-width="1"/>
                    <line x1="68" y1="115" x2="92" y2="115" stroke="rgba(99,102,241,0.4)" stroke-width="1"/>
                    <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="19" y="122" width="122" height="88" rx="17" fill="none" clip-path="url(#clip-body)"/>
                    <rect class="r-scanline" x="20" y="130" width="120" height="2" rx="1" fill="rgba(99,102,241,0.18)" clip-path="url(#clip-body)"/>
                    <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <circle cx="9" cy="125" r="4.5" fill="#4f46e5" opacity="0.7"/>
                    <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <circle cx="151" cy="125" r="4.5" fill="#4f46e5" opacity="0.7"/>
                    <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <circle cx="80" cy="162" r="24" fill="rgba(99,102,241,0.07)" stroke="rgba(99,102,241,0.25)" stroke-width="1"/>
                    <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)" stroke="rgba(99,102,241,0.35)" stroke-width="1"/>
                    <circle class="r-chest" cx="80" cy="162" r="10" fill="url(#rg-chest)" filter="url(#glow-soft)"/>
                    <line x1="80" y1="153" x2="80" y2="171" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" stroke-linecap="round"/>
                    <line x1="71" y1="162" x2="89" y2="162" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" stroke-linecap="round"/>
                    <rect x="24" y="143" width="18" height="4" rx="2" fill="rgba(99,102,241,0.28)"/>
                    <rect x="24" y="151" width="13" height="4" rx="2" fill="rgba(99,102,241,0.18)"/>
                    <rect x="118" y="143" width="18" height="4" rx="2" fill="rgba(99,102,241,0.28)"/>
                    <rect x="125" y="151" width="13" height="4" rx="2" fill="rgba(99,102,241,0.18)"/>
                    <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-body)" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="38" y="220" width="22" height="3" rx="1.5" fill="rgba(99,102,241,0.3)"/>
                    <rect x="100" y="220" width="22" height="3" rx="1.5" fill="rgba(99,102,241,0.3)"/>
                    <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                    <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ MARQUEE ══════════ -->
<div class="marquee-wrap reveal">
    <div class="marquee-label">موثوق به من صيدليات في</div>
    <div class="marquee-track" id="marqueeTrack">
        <span class="mq-item"><i class="fas fa-location-dot"></i> القاهرة</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> المنصورة</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> الإسكندرية</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> طنطا</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> الزقازيق</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> أسيوط</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> المنيا</span>
        <span class="mq-item"><i class="fas fa-location-dot"></i> بورسعيد</span>
    </div>
</div>

<!-- ══════════ FEATURES ══════════ -->
<section id="features" style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> كل حاجة محتاجها في مكان واحد</div>
            <h2>مميزات تغطي صيدليتك بالكامل</h2>
            <p>من نقطة البيع لحد التقارير المالية — منصة متكاملة مبنية خصيصًا لطبيعة عمل الصيدليات المصرية.</p>
        </div>
        <div class="feat-grid">
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-gauge-high"></i></div><h3>لوحة تحكم ذكية</h3><p>نظرة شاملة على أداء الصيدلية لحظة بلحظة مع مؤشرات مدعومة بالذكاء الاصطناعي.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-boxes-stacked"></i></div><h3>إدارة المخزون</h3><p>تتبّع كل صنف بالكمية والصلاحية والباركود، مع تنبيهات فورية عند نقص الكمية.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-cash-register"></i></div><h3>نقطة بيع (POS)</h3><p>واجهة بيع سريعة تدعم الشرايط والحبات والعلب بضغطة واحدة.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-barcode"></i></div><h3>قارئ باركود</h3><p>مسح فوري للأصناف من الفاتورة أو من على الرف بدقة عالية.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-capsules"></i></div><h3>تتبّع الأدوية</h3><p>مطابقة الأدوية بالاسم أو الباركود مع قاعدة بيانات ضخمة ومحدّثة.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-truck-field"></i></div><h3>إدارة الموردين</h3><p>سجل كامل لكل مورد، فواتيره، ومواعيد التوريد القادمة.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-users"></i></div><h3>إدارة العملاء</h3><p>تاريخ شراء كل عميل ومتابعة احتياجاته الدورية من الأدوية.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-file-invoice"></i></div><h3>طباعة الفواتير</h3><p>فواتير احترافية جاهزة للطباعة أو الإرسال في ثوانٍ.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-cloud-arrow-up"></i></div><h3>نسخ احتياطي سحابي</h3><p>بياناتك محفوظة ومؤمّنة دايمًا، حتى لو حصل أي عطل مفاجئ.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-desktop"></i></div><h3>نسخة مكتبية</h3><p>تعمل بسرعة وبدون إنترنت على أجهزة Windows.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-user-shield"></i></div><h3>صلاحيات المستخدمين</h3><p>تحكم دقيق فيما يقدر كل موظف يشوفه أو يعدّله.</p></div>
            <div class="feat-card glass reveal"><div class="glow"></div><div class="feat-ico"><i class="fas fa-bell"></i></div><h3>تنبيهات فورية</h3><p>إشعارات لحظية لنقص المخزون واقتراب انتهاء الصلاحية.</p></div>
        </div>
    </div>
</section>

<!-- ══════════ AI SECTION ══════════ -->
<section id="ai" class="ai-sec">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> عقل النظام</div>
            <h2>الذكاء الاصطناعي يشتغل معاك مش بدالك</h2>
            <p>يحلل بياناتك يوم بيوم، ويطلعلك قرارات جاهزة قبل ما تسألها.</p>
        </div>
        <div class="ai-grid">
            <div class="ai-brain-wrap reveal">
                <svg class="neural-svg" viewBox="0 0 300 300">
                    <line x1="150" y1="150" x2="60" y2="70" stroke="#6366f1" stroke-width="1"/>
                    <line x1="150" y1="150" x2="240" y2="60" stroke="#6366f1" stroke-width="1"/>
                    <line x1="150" y1="150" x2="40" y2="200" stroke="#6366f1" stroke-width="1"/>
                    <line x1="150" y1="150" x2="260" y2="210" stroke="#6366f1" stroke-width="1"/>
                    <line x1="150" y1="150" x2="150" y2="20" stroke="#6366f1" stroke-width="1"/>
                    <line x1="150" y1="150" x2="150" y2="280" stroke="#6366f1" stroke-width="1"/>
                    <circle cx="60" cy="70" r="4" fill="#22d3ee" style="animation-delay:.2s"/>
                    <circle cx="240" cy="60" r="4" fill="#22d3ee" style="animation-delay:.6s"/>
                    <circle cx="40" cy="200" r="4" fill="#22d3ee" style="animation-delay:1s"/>
                    <circle cx="260" cy="210" r="4" fill="#22d3ee" style="animation-delay:1.4s"/>
                    <circle cx="150" cy="20" r="4" fill="#22d3ee" style="animation-delay:1.8s"/>
                    <circle cx="150" cy="280" r="4" fill="#22d3ee" style="animation-delay:2.2s"/>
                </svg>
                <div class="brain-core"><i class="fas fa-brain"></i></div>
            </div>
            <div class="ai-list reveal">
                <div class="ai-item"><i class="fas fa-chart-simple"></i> توليد تقارير تلقائية</div>
                <div class="ai-item"><i class="fas fa-arrow-trend-up"></i> توقع المبيعات القادمة</div>
                <div class="ai-item"><i class="fas fa-boxes-packing"></i> توقع احتياجات المخزون</div>
                <div class="ai-item"><i class="fas fa-sack-dollar"></i> تحليل الأرباح والمصروفات</div>
                <div class="ai-item"><i class="fas fa-triangle-exclamation"></i> اكتشاف نقص المخزون مبكرًا</div>
                <div class="ai-item"><i class="fas fa-hourglass-end"></i> التنبؤ بالأدوية قريبة الانتهاء</div>
                <div class="ai-item"><i class="fas fa-shuffle"></i> اقتراح بدائل للأدوية</div>
                <div class="ai-item"><i class="fas fa-chart-pie"></i> تحليل ذكاء الأعمال</div>
                <div class="ai-item"><i class="fas fa-users-gear"></i> رؤى حول سلوك العملاء</div>
                <div class="ai-item"><i class="fas fa-lightbulb"></i> توصيات تلقائية للقرارات</div>
                <div class="ai-item"><i class="fas fa-message"></i> استعلامات بلغة طبيعية</div>
                <div class="ai-item"><i class="fas fa-magnifying-glass"></i> بحث ذكي فوري</div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ DASHBOARD PREVIEW ══════════ -->
<section id="dashboard" style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> لوحة التحكم</div>
            <h2>كل أرقام صيدليتك في شاشة واحدة</h2>
            <p>مبيعات، مشتريات، أرباح، ومخزون — يتحدّث لحظيًا وأنت شغّال.</p>
        </div>
        <div class="glass dash-wrap reveal">
            <div class="dash-topbar">
                <div class="dash-dots"><span></span><span></span><span></span></div>
                <span style="font-size:.8rem;color:var(--ink-mute);"><i class="fas fa-circle" style="color:#34d399;font-size:.5rem;"></i> متصل الآن — تحديث مباشر</span>
            </div>
            <div class="kpi-row">
                <div class="kpi"><span>إجمالي المبيعات</span><b>184,320 ج.م</b><em>▲ 12.4%</em></div>
                <div class="kpi"><span>صافي الأرباح</span><b>52,910 ج.م</b><em>▲ 8.1%</em></div>
                <div class="kpi"><span>عدد الأصناف</span><b>3,412</b><em>▲ 2.3%</em></div>
                <div class="kpi"><span>مخزون منخفض</span><b>27 صنف</b><em style="color:#fbbf24;">تنبيه</em></div>
            </div>
            <div class="chart-row">
                <div class="chart-box"><h4>المبيعات مقابل المشتريات — آخر 6 شهور</h4><canvas id="chartSales"></canvas></div>
                <div class="chart-box"><h4>توزيع الفئات الأكثر مبيعًا</h4><canvas id="chartPie"></canvas></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ WHY AI (Comparison) ══════════ -->
<section style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> ليه بالذكاء الاصطناعي</div>
            <h2>الفرق بين الصيدلية التقليدية والصيدلية الذكية</h2>
        </div>
        <div class="cmp-grid">
            <div class="cmp-card glass old reveal">
                <h3><i class="fas fa-book"></i> الطريقة التقليدية</h3>
                <ul>
                    <li><i class="fas fa-xmark"></i> جرد يدوي بياخد ساعات</li>
                    <li><i class="fas fa-xmark"></i> اكتشاف نقص المخزون بعد فوات الأوان</li>
                    <li><i class="fas fa-xmark"></i> تقارير مبعثرة على ورق أو إكسل</li>
                    <li><i class="fas fa-xmark"></i> صعوبة متابعة تواريخ الصلاحية</li>
                    <li><i class="fas fa-xmark"></i> قرارات مبنية على تخمين</li>
                </ul>
            </div>
            <div class="cmp-card glass new reveal">
                <div class="price-badge">الأفضل</div>
                <h3><i class="fas fa-robot" style="color:var(--cyan);"></i> AI Pharmacy System</h3>
                <ul>
                    <li><i class="fas fa-check"></i> جرد فوري ومزامنة لحظية</li>
                    <li><i class="fas fa-check"></i> تنبيهات ذكية قبل نفاد الصنف</li>
                    <li><i class="fas fa-check"></i> تقارير آلية جاهزة بضغطة زرار</li>
                    <li><i class="fas fa-check"></i> متابعة تلقائية لكل تواريخ الصلاحية</li>
                    <li><i class="fas fa-check"></i> قرارات مبنية على تحليل بيانات حقيقي</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ HOW IT WORKS ══════════ -->
<section style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> خطوات بسيطة</div>
            <h2>تبدأ تشتغل في أقل من 10 دقائق</h2>
        </div>
        <div class="timeline reveal">
            <div class="tl-step"><div class="tl-num">1</div><h4>إنشاء حساب</h4><p>سجّل بيانات صيدليتك في دقيقة واحدة.</p></div>
            <div class="tl-step"><div class="tl-num">2</div><h4>تحميل النسخة المكتبية</h4><p>نزّل البرنامج على جهاز الصيدلية بسهولة.</p></div>
            <div class="tl-step"><div class="tl-num">3</div><h4>تسجيل الدخول</h4><p>ادخل بحسابك وابدأ فورًا من غير تعقيد.</p></div>
            <div class="tl-step"><div class="tl-num">4</div><h4>مزامنة البيانات</h4><p>بياناتك تتزامن تلقائيًا بين الجهاز والسحابة.</p></div>
            <div class="tl-step"><div class="tl-num">5</div><h4>إدارة المخزون</h4><p>ابدأ تسجيل أصنافك وكمياتك بسهولة.</p></div>
            <div class="tl-step"><div class="tl-num">6</div><h4>استخدام الذكاء الاصطناعي</h4><p>خلّي النظام يحلل ويقترح ويتوقع بدالك.</p></div>
            <div class="tl-step"><div class="tl-num">7</div><h4>زيادة الأرباح</h4><p>قرارات أدق تعني أرباح أعلى وهدر أقل.</p></div>
        </div>
    </div>
</section>

<!-- ══════════ TESTIMONIALS ══════════ -->
<section style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> آراء العملاء</div>
            <h2>صيادلة بيثقوا في النظام يوميًا</h2>
        </div>
        <div class="test-track reveal">
            <div class="test-card glass"><div class="test-stars">★★★★★</div><p>"النظام غيّر شكل شغلي بالكامل، بقيت أعرف كل حاجة عن مخزوني من غير ما أتعب."</p><div class="test-who"><div class="test-avatar">أ.م</div><div><b>أحمد مصطفى</b><span>صيدلية النور — المنصورة</span></div></div></div>
            <div class="test-card glass"><div class="test-stars">★★★★★</div><p>"تنبيهات انتهاء الصلاحية وحدها وفّرتلي آلاف الجنيهات كانت هتتهدر."</p><div class="test-who"><div class="test-avatar">س.ع</div><div><b>سارة عبد الله</b><span>صيدلية الشفاء — القاهرة</span></div></div></div>
            <div class="test-card glass"><div class="test-stars">★★★★★</div><p>"التقارير المالية بقت جاهزة في ثواني بدل ما كنت أقعد أيام أجمعها يدوي."</p><div class="test-who"><div class="test-avatar">م.ح</div><div><b>محمد حسين</b><span>صيدلية الأمل — طنطا</span></div></div></div>
            <div class="test-card glass"><div class="test-stars">★★★★★</div><p>"الدعم الفني ممتاز والنظام سهل جدًا حتى لموظفين جداد."</p><div class="test-who"><div class="test-avatar">ن.ك</div><div><b>نور الدين كمال</b><span>صيدلية الحياة — الزقازيق</span></div></div></div>
        </div>
    </div>
</section>


<!-- ══════════ FAQ ══════════ -->
<section id="faq" style="padding:7rem 0;">
    <div class="container">
        <div class="sec-head reveal">
            <div class="eyebrow" style="margin-inline:auto;"><span class="dot"></span> أسئلة شائعة</div>
            <h2>عندك سؤال؟ هنجاوبك</h2>
        </div>
        <div class="faq-wrap reveal">
            <div class="faq-item">
                <div class="faq-q">هل النظام يشتغل من غير إنترنت؟ <i class="fas fa-plus"></i></div>
                <div class="faq-a"><p>النسخة المكتبية تشتغل بشكل كامل بدون إنترنت، وتتزامن مع السحابة تلقائيًا لما الاتصال يرجع.</p></div>
            </div>
            <div class="faq-item">
                <div class="faq-q">هل بياناتي آمنة؟ <i class="fas fa-plus"></i></div>
                <div class="faq-a"><p>كل البيانات محفوظة بتشفير كامل مع نسخ احتياطي دوري تلقائي على السحابة.</p></div>
            </div>
            <div class="faq-item">
                <div class="faq-q">هل أقدر أستخدمه على أكتر من فرع؟ <i class="fas fa-plus"></i></div>
                <div class="faq-a"><p>أيوه، خطة المؤسسات مصممة خصيصًا لإدارة فروع متعددة من مكان واحد.</p></div>
            </div>
            <div class="faq-item">
                <div class="faq-q">هل فيه فترة تجربة مجانية؟ <i class="fas fa-plus"></i></div>
                <div class="faq-a"><p>أيوه، بنوفر فترة تجربة مجانية لمدة 14 يوم لجميع الخطط.</p></div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ FINAL CTA ══════════ -->
<section class="final-cta">
    <div class="container">
        <div class="glass cta-box reveal">
            <h2>جاهز ترقّي صيدليتك؟</h2>
            <p>انضم لأكتر من 1000 صيدلية بتدير شغلها بذكاء أكبر وربح أعلى.</p>
            <div class="hero-btns">
                <a href="#" class="btn btn-primary btn-lg"><i class="fas fa-arrow-down"></i> تحميل النسخة المكتبية</a>
                <a href="/login" class="btn btn-ghost btn-lg"><i class="fas fa-globe"></i> إنشاء حساب ويب</a>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ FOOTER ══════════ -->
<footer>
    <div class="container">
        <div class="foot-grid">
            <div class="foot-col">
                <div class="brand" style="margin-bottom:1rem;">
                    <span class="mark">
                        <svg viewBox="0 0 100 100" width="20" height="20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line x1="50" y1="34" x2="50" y2="16" stroke="#fff" stroke-width="5" stroke-linecap="round"/>
                            <circle cx="50" cy="11" r="6" fill="#22d3ee"/>
                            <rect x="16" y="34" width="68" height="48" rx="15" fill="rgba(255,255,255,0.14)" stroke="#fff" stroke-width="4.5"/>
                            <circle cx="35" cy="57" r="7.5" fill="#fff"/>
                            <circle cx="65" cy="57" r="7.5" fill="#22d3ee"/>
                            <rect x="39" y="72" width="22" height="6" rx="3" fill="#fff"/>
                        </svg>
                    </span>
                    AI Pharmacy System
                </div>
                <p>منصة ذكية لإدارة الصيدليات، مبنية لتناسب طبيعة السوق المصري.</p>
                <div class="foot-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/aipharmacysystem?igsh=Y2NvcXhrbjh0Y3Rp&utm_source=qr"><i class="fab fa-instagram"></i></a>
                <a href="https://wa.me/201060942653?text=السلام%20عليكم،%20أرغب%20في%20الاستفسار" target="_blank">
    <i class="fab fa-whatsapp"></i>
</a>
                </div>
            </div>
        </div>
        <div class="foot-bottom">
            © 2026 AI Pharmacy System. جميع الحقوق محفوظة.
            <div class="foot-credit">صنع بواسطة <a href="https://takweda.com" target="_blank" rel="noopener noreferrer">تكويدة</a></div>
        </div>
    </div>
</footer>

<script>
/* ── Stars background ── */
const starsWrap = document.getElementById('stars-wrap');
for(let i=0;i<140;i++){
    const s=document.createElement('div');
    const size=Math.random()*2.4+.5;
    s.className='star';
    s.style.cssText=`width:${size}px;height:${size}px;background:${Math.random()>.88?'#a5b4fc':'white'};left:${Math.random()*100}%;top:${Math.random()*100}%;--d:${2+Math.random()*5}s;--delay:${Math.random()*5}s;${size>1.8?'box-shadow:0 0 4px rgba(165,180,252,.6)':''}`;
    starsWrap.appendChild(s);
}

/* ── Loader ── */
const bar=document.getElementById('loaderBar');
const pctEl=document.getElementById('loader-pct');
const loaderTxt=document.getElementById('loader-txt');
const msgs=['جارٍ تجهيز الذكاء الاصطناعي...','تحميل بيانات المخزون...','تفعيل التحليلات الذكية...','على وشك الانتهاء...'];
let pct=0;
const iv=setInterval(()=>{
    pct+=Math.random()*14+4;
    if(pct>=100){pct=100;clearInterval(iv);setTimeout(()=>document.getElementById('loader').classList.add('hide'),350);}
    bar.style.strokeDashoffset=296-(296*pct/100);
    pctEl.textContent=Math.floor(pct)+'%';
    loaderTxt.textContent=msgs[Math.min(msgs.length-1,Math.floor(pct/28))];
},260);

/* ── Scroll reveal ── */
const io=new IntersectionObserver((entries)=>{
    entries.forEach(e=>{ if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } });
},{threshold:.12});
document.querySelectorAll('.reveal').forEach(el=>io.observe(el));

/* ── Counters ── */
document.querySelectorAll('[data-count]').forEach(el=>{
    const target=parseFloat(el.dataset.count);
    const decimals=parseInt(el.dataset.decimals||0);
    const suffix=el.dataset.suffix||'';
    const io2=new IntersectionObserver((entries)=>{
        entries.forEach(e=>{
            if(e.isIntersecting){
                let cur=0; const step=target/60;
                const t=setInterval(()=>{
                    cur+=step;
                    if(cur>=target){cur=target;clearInterval(t);}
                    el.textContent=cur.toFixed(decimals)+suffix;
                },20);
                io2.unobserve(el);
            }
        });
    },{threshold:.5});
    io2.observe(el);
});

/* ── Marquee duplicate for seamless loop ── */
const track=document.getElementById('marqueeTrack');
track.innerHTML+=track.innerHTML;

/* ── FAQ accordion ── */
document.querySelectorAll('.faq-item').forEach(item=>{
    const q=item.querySelector('.faq-q');
    const a=item.querySelector('.faq-a');
    q.addEventListener('click',()=>{
        const isOpen=item.classList.contains('open');
        document.querySelectorAll('.faq-item').forEach(i=>{i.classList.remove('open');i.querySelector('.faq-a').style.maxHeight=null;});
        if(!isOpen){item.classList.add('open');a.style.maxHeight=a.scrollHeight+'px';}
    });
});

/* ── Nav background on scroll ── */
window.addEventListener('scroll',()=>{
    document.querySelector('header.nav').style.background = window.scrollY>30 ? 'rgba(2,6,23,.85)' : 'rgba(2,6,23,.55)';
});

/* ── Mobile nav toggle ── */
const burgerBtn=document.getElementById('burgerBtn');
const mobileNav=document.getElementById('mobileNav');
const mobileNavClose=document.getElementById('mobileNavClose');
function openMobileNav(){ mobileNav.classList.add('open'); document.body.style.overflow='hidden'; }
function closeMobileNav(){ mobileNav.classList.remove('open'); document.body.style.overflow=''; }
burgerBtn.addEventListener('click',openMobileNav);
mobileNavClose.addEventListener('click',closeMobileNav);
mobileNav.querySelectorAll('a').forEach(a=>a.addEventListener('click',closeMobileNav));

/* ── Dashboard charts ── */
window.addEventListener('load',()=>{
    Chart.defaults.color='#94a3b8';
    Chart.defaults.font.family='Cairo';

    new Chart(document.getElementById('chartSales'),{
        type:'line',
        data:{
            labels:['فبراير','مارس','أبريل','مايو','يونيو','يوليو'],
            datasets:[
                {label:'مبيعات',data:[42,55,48,68,74,88],borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.15)',tension:.4,fill:true,pointRadius:0,borderWidth:2.5},
                {label:'مشتريات',data:[30,34,38,40,44,50],borderColor:'#22d3ee',backgroundColor:'rgba(34,211,238,.08)',tension:.4,fill:true,pointRadius:0,borderWidth:2.5}
            ]
        },
        options:{
            responsive:true,maintainAspectRatio:false,
            plugins:{legend:{labels:{boxWidth:10,font:{size:10}}}},
            scales:{
                x:{grid:{color:'rgba(148,163,184,.08)'}},
                y:{grid:{color:'rgba(148,163,184,.08)'}}
            }
        }
    });

    new Chart(document.getElementById('chartPie'),{
        type:'doughnut',
        data:{
            labels:['مسكنات','فيتامينات','مضادات حيوية','عناية شخصية','أخرى'],
            datasets:[{data:[32,22,18,16,12],backgroundColor:['#6366f1','#22d3ee','#7c3aed','#818cf8','#334155'],borderColor:'#020617',borderWidth:2}]
        },
        options:{
            responsive:true,maintainAspectRatio:false,cutout:'62%',
            plugins:{legend:{position:'bottom',labels:{boxWidth:9,font:{size:9},padding:9}}}
        }
    });
});
</script>
</body>
</html>