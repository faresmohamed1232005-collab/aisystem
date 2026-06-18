{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║         الصيدلي الذكي – AI Pharmacy Assistant Widget            ║
    ║  ضيف @include('components.ai-assistant') قبل </body> في layout  ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

<style>
/* ═══════════════════════════════════════════
   Floating Robot Button
═══════════════════════════════════════════ */
#ai-fab {
    position: fixed;
    bottom: 28px;
    left: 28px;
    z-index: 9999;
    cursor: pointer;
    animation: fabFloat 3.5s ease-in-out infinite;
    filter: drop-shadow(0 8px 24px rgba(99,102,241,0.55));
    transition: filter 0.3s;
}
#ai-fab:hover {
    filter: drop-shadow(0 12px 32px rgba(99,102,241,0.85));
    animation-play-state: paused;
}
@keyframes fabFloat {
    0%,100% { transform: translateY(0) rotate(-1deg); }
    50%      { transform: translateY(-10px) rotate(1deg); }
}

/* Pulse ring */
#ai-fab::before {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 2px solid rgba(99,102,241,0.4);
    animation: pulseRing 2.4s ease-out infinite;
}
#ai-fab::after {
    content: '';
    position: absolute;
    inset: -16px;
    border-radius: 50%;
    border: 1.5px solid rgba(99,102,241,0.2);
    animation: pulseRing 2.4s ease-out infinite 0.5s;
}
@keyframes pulseRing {
    0%   { transform: scale(0.9); opacity: 1; }
    100% { transform: scale(1.4); opacity: 0; }
}

/* Tooltip */
#ai-fab .fab-tooltip {
    position: absolute;
    bottom: 110%;
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #e0e7ff;
    font-family: 'Cairo', sans-serif;
    font-size: 12px;
    padding: 5px 12px;
    border-radius: 20px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
    border: 1px solid rgba(99,102,241,0.3);
}
#ai-fab:hover .fab-tooltip { opacity: 1; }

/* ═══════════════════════════════════════════
   Chat Panel
═══════════════════════════════════════════ */
#ai-panel {
    position: fixed;
    bottom: 100px;
    left: 24px;
    width: 380px;
    max-height: 600px;
    z-index: 9998;
    display: flex;
    flex-direction: column;
    border-radius: 24px;
    overflow: hidden;
    background: #0f172a;
    border: 1px solid rgba(99,102,241,0.3);
    box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(99,102,241,0.15);
    transform: scale(0.85) translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.25s ease;
    font-family: 'Cairo', sans-serif;
}
#ai-panel.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: all;
}

/* Panel Header */
.ai-panel-header {
    background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 100%);
    border-bottom: 1px solid rgba(99,102,241,0.2);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.ai-panel-header .ai-header-info { flex: 1; }
.ai-panel-header h3 {
    color: #e0e7ff;
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ai-panel-header p {
    color: #64748b;
    font-size: 11px;
    margin: 2px 0 0;
}
.ai-status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #22c55e;
    animation: statusPulse 2s infinite;
    flex-shrink: 0;
}
@keyframes statusPulse {
    0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34,197,94,0.4); }
    50%      { opacity: 0.8; box-shadow: 0 0 0 5px rgba(34,197,94,0); }
}

.ai-close-btn {
    background: rgba(255,255,255,0.05);
    border: none;
    color: #64748b;
    width: 30px; height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
    font-size: 13px;
}
.ai-close-btn:hover { background: rgba(239,68,68,0.15); color: #ef4444; }

/* Quick Actions */
.ai-quick-actions {
    padding: 10px 12px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    border-bottom: 1px solid rgba(99,102,241,0.1);
    background: rgba(15,23,42,0.8);
    flex-shrink: 0;
}
.ai-quick-btn {
    background: rgba(99,102,241,0.1);
    border: 1px solid rgba(99,102,241,0.25);
    color: #a5b4fc;
    font-family: 'Cairo', sans-serif;
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.ai-quick-btn:hover {
    background: rgba(99,102,241,0.25);
    border-color: #6366f1;
    color: #e0e7ff;
}

/* Messages */
.ai-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
    min-height: 0;
}
.ai-messages::-webkit-scrollbar { width: 4px; }
.ai-messages::-webkit-scrollbar-track { background: transparent; }
.ai-messages::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.3); border-radius: 4px; }

/* Message Bubbles */
.ai-msg {
    display: flex;
    gap: 8px;
    animation: msgIn 0.3s ease;
}
@keyframes msgIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.ai-msg.user { flex-direction: row-reverse; }

.ai-bubble {
    max-width: 80%;
    padding: 10px 14px;
    border-radius: 18px;
    font-size: 13px;
    line-height: 1.6;
    direction: rtl;
}
.ai-msg.assistant .ai-bubble {
    background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(30,27,75,0.6));
    border: 1px solid rgba(99,102,241,0.2);
    color: #e2e8f0;
    border-bottom-right-radius: 4px;
}
.ai-msg.user .ai-bubble {
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: white;
    border-bottom-left-radius: 4px;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
}

.ai-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: rgba(99,102,241,0.1);
    border: 1px solid rgba(99,102,241,0.2);
    font-size: 14px;
}

/* Typing indicator */
.ai-typing {
    display: flex; gap: 4px; align-items: center; padding: 4px 0;
}
.ai-typing span {
    width: 6px; height: 6px;
    background: #6366f1;
    border-radius: 50%;
    animation: typingBounce 1.2s infinite;
}
.ai-typing span:nth-child(2) { animation-delay: 0.2s; }
.ai-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce {
    0%,60%,100% { transform: translateY(0); opacity: 0.5; }
    30%          { transform: translateY(-6px); opacity: 1; }
}

/* Input Area */
.ai-input-area {
    padding: 12px;
    border-top: 1px solid rgba(99,102,241,0.15);
    background: rgba(15,23,42,0.9);
    flex-shrink: 0;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}
.ai-input-area textarea {
    flex: 1;
    background: rgba(30,27,75,0.5);
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: 14px;
    padding: 10px 14px;
    color: #e2e8f0;
    font-family: 'Cairo', sans-serif;
    font-size: 13px;
    resize: none;
    outline: none;
    direction: rtl;
    min-height: 42px;
    max-height: 100px;
    transition: border-color 0.2s;
    line-height: 1.5;
}
.ai-input-area textarea:focus { border-color: rgba(99,102,241,0.6); }
.ai-input-area textarea::placeholder { color: #475569; }
.ai-send-btn {
    width: 42px; height: 42px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(99,102,241,0.35);
}
.ai-send-btn:hover { transform: scale(1.08); box-shadow: 0 6px 20px rgba(99,102,241,0.5); }
.ai-send-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

/* Sidebar link */
#ai-sidebar-link {
    display: flex; align-items: center; gap: 3px; padding: 4px 10px;
    border-radius: 10px; cursor: pointer; transition: background 0.2s;
    border: none; background: transparent; width: 100%; text-align: right;
}
#ai-sidebar-link:hover { background: rgba(99,102,241,0.15); }
.ai-sb-dot { width:7px; height:7px; background:#22c55e; border-radius:50%; margin-right:2px; animation: statusPulse 2s infinite; }

/* Mobile */
@media (max-width: 480px) {
    #ai-panel {
        left: 8px; right: 8px; width: auto;
        bottom: 90px;
        max-height: 75vh;
    }
    #ai-fab { left: 16px; bottom: 20px; }
}
</style>

{{-- ═══ Floating Robot Button ═══ --}}
<div id="ai-fab" onclick="toggleAiPanel()" title="الصيدلي الذكي">
    <div class="fab-tooltip">🤖 الصيدلي الذكي</div>
    <svg width="62" height="76" viewBox="-12 -5 184 268" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="lg-fab" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#1e293b"/>
                <stop offset="100%" stop-color="#0d1526"/>
            </linearGradient>
            <radialGradient id="rg-eye-fab" cx="35%" cy="35%" r="60%">
                <stop offset="0%" stop-color="#818cf8"/>
                <stop offset="100%" stop-color="#4338ca"/>
            </radialGradient>
            <radialGradient id="rg-chest-fab" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#22d3ee"/>
                <stop offset="60%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#4338ca" stop-opacity="0.4"/>
            </radialGradient>
            <filter id="glow-fab">
                <feGaussianBlur stdDeviation="3" result="blur"/>
                <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
        </defs>
        <!-- Antenna -->
        <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
        <circle cx="80" cy="3" r="7" fill="#6366f1" filter="url(#glow-fab)" style="animation: antGlowMini 2.2s ease-in-out infinite"/>
        <circle cx="80" cy="3" r="3.5" fill="#e0e7ff"/>
        <!-- Head -->
        <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <!-- Eyes -->
        <g style="animation: eyeBlinkMini 5s ease-in-out infinite; transform-origin: center">
            <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-fab)" filter="url(#glow-fab)"/>
            <circle cx="57" cy="60" r="11" fill="#0d1526"/>
            <circle cx="57" cy="60" r="6.5" fill="#6366f1"/>
            <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
            <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-fab)" filter="url(#glow-fab)"/>
            <circle cx="103" cy="60" r="11" fill="#0d1526"/>
            <circle cx="103" cy="60" r="6.5" fill="#6366f1"/>
            <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
        </g>
        <!-- Mouth / Keys -->
        <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526" stroke="#6366f1" stroke-width="1.2"/>
        <rect x="58" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
        <rect x="71" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
        <rect x="84" y="89.5" width="9" height="8" rx="2.5" fill="rgba(99,102,241,0.35)"/>
        <!-- Neck -->
        <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526" stroke="#4f46e5" stroke-width="1"/>
        <!-- Body -->
        <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <!-- Arms -->
        <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="-10" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="142" y="185" width="28" height="14" rx="7" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
        <!-- Chest Core -->
        <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)" stroke="rgba(99,102,241,0.35)" stroke-width="1"/>
        <circle cx="80" cy="162" r="10" fill="url(#rg-chest-fab)" filter="url(#glow-fab)" style="animation: chestPulseMini 2.4s ease-in-out infinite"/>
        <!-- Legs -->
        <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-fab)" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
        <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
    </svg>
</div>

{{-- ═══ Chat Panel ═══ --}}
<div id="ai-panel">
    {{-- Header --}}
    <div class="ai-panel-header">
        <svg width="34" height="44" viewBox="-12 -5 184 268" fill="none" style="flex-shrink:0">
            <defs>
                <linearGradient id="lg-ph" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#1e293b"/>
                    <stop offset="100%" stop-color="#0d1526"/>
                </linearGradient>
                <radialGradient id="rg-eye-ph" cx="35%" cy="35%" r="60%">
                    <stop offset="0%" stop-color="#818cf8"/>
                    <stop offset="100%" stop-color="#4338ca"/>
                </radialGradient>
                <radialGradient id="rg-chest-ph" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#22d3ee"/>
                    <stop offset="60%" stop-color="#6366f1"/>
                    <stop offset="100%" stop-color="#4338ca" stop-opacity="0.4"/>
                </radialGradient>
                <filter id="glow-ph">
                    <feGaussianBlur stdDeviation="2.5" result="blur"/>
                    <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
            </defs>
            <line x1="80" y1="28" x2="80" y2="5" stroke="#6366f1" stroke-width="2.5" stroke-linecap="round"/>
            <circle class="r-ant-mini" cx="80" cy="3" r="7" fill="#6366f1" filter="url(#glow-ph)"/>
            <circle cx="80" cy="3" r="3.5" fill="#e0e7ff"/>
            <rect x="28" y="28" width="104" height="82" rx="20" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <g class="r-eye-mini">
                <rect x="40" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-ph)" filter="url(#glow-ph)"/>
                <circle cx="57" cy="60" r="11" fill="#0d1526"/>
                <circle cx="57" cy="60" r="6.5" fill="#6366f1"/>
                <circle cx="60" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
                <rect x="86" y="46" width="34" height="28" rx="9" fill="url(#rg-eye-ph)" filter="url(#glow-ph)"/>
                <circle cx="103" cy="60" r="11" fill="#0d1526"/>
                <circle cx="103" cy="60" r="6.5" fill="#6366f1"/>
                <circle cx="106" cy="57" r="2.8" fill="rgba(255,255,255,0.92)"/>
            </g>
            <rect x="52" y="88" width="56" height="11" rx="5.5" fill="#0d1526" stroke="#6366f1" stroke-width="1.2"/>
            <rect x="68" y="110" width="24" height="14" rx="5" fill="#0d1526" stroke="#4f46e5" stroke-width="1"/>
            <rect x="18" y="122" width="124" height="88" rx="18" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <rect x="-8" y="125" width="26" height="60" rx="13" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <rect x="142" y="125" width="26" height="60" rx="13" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <circle cx="80" cy="162" r="16" fill="rgba(99,102,241,0.10)" stroke="rgba(99,102,241,0.35)" stroke-width="1"/>
            <circle class="r-chest-mini" cx="80" cy="162" r="10" fill="url(#rg-chest-ph)" filter="url(#glow-ph)"/>
            <rect x="30" y="208" width="38" height="46" rx="13" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <rect x="92" y="208" width="38" height="46" rx="13" fill="url(#lg-ph)" stroke="#4f46e5" stroke-width="1.5"/>
            <rect x="24" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
            <rect x="88" y="250" width="48" height="13" rx="6.5" fill="#0d1526" stroke="#4f46e5" stroke-width="1.5"/>
        </svg>
        <div class="ai-header-info">
            <h3>
                <span class="ai-status-dot"></span>
                الصيدلي الذكي
            </h3>
            <p>مساعدك الشخصي لإدارة الصيدلية</p>
        </div>
        <button class="ai-close-btn" onclick="toggleAiPanel()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- Quick Actions --}}
    <div class="ai-quick-actions">
        <button class="ai-quick-btn" onclick="quickAsk('كم كمية المنتجات المنخفضة في المخزن؟')">📦 مخزون منخفض</button>
        <button class="ai-quick-btn" onclick="quickAsk('أظهر لي آخر 5 فواتير مبيعات')">🛒 آخر المبيعات</button>
        <button class="ai-quick-btn" onclick="quickAsk('أظهر لي آخر فواتير الشراء')">📋 فواتير الشراء</button>
        <button class="ai-quick-btn" onclick="quickAsk('ما هي الأدوية التي ستنتهي صلاحيتها قريباً؟')">⚠️ انتهاء الصلاحية</button>
    </div>

    {{-- Messages --}}
    <div class="ai-messages" id="ai-messages">
        <div class="ai-msg assistant">
            <div class="ai-avatar">🤖</div>
            <div class="ai-bubble">
                أهلاً! أنا الصيدلي الذكي 👋<br>
                أستطيع مساعدتك في:
                <br>• كميات المخزن والمنتجات
                <br>• فواتير المبيعات والمشتريات
                <br>• جرعات الأدوية وبدائلها
                <br><br>اسألني أي شيء! 💊
            </div>
        </div>
    </div>

    {{-- Input --}}
    <div class="ai-input-area">
        <button class="ai-send-btn" id="ai-send-btn" onclick="sendMessage()">
            <i class="fas fa-paper-plane" style="transform: rotate(180deg)"></i>
        </button>
        <textarea
            id="ai-input"
            placeholder="اسأل عن دواء، مخزون، فاتورة..."
            rows="1"
            onkeydown="handleInputKey(event)"
            oninput="autoResize(this)"
        ></textarea>
    </div>
</div>

{{-- ═══ JavaScript ═══ --}}
<script>
// ─── State ───
let aiOpen       = false;
let aiMessages   = [];   // conversation history for context
let aiThinking   = false;

// ─── Toggle Panel ───
function toggleAiPanel() {
    aiOpen = !aiOpen;
    document.getElementById('ai-panel').classList.toggle('open', aiOpen);
    if (aiOpen) {
        document.getElementById('ai-input').focus();
        scrollToBottom();
    }
}

// ─── Sidebar link (injected below) ───
function openAiFromSidebar() {
    if (!aiOpen) toggleAiPanel();
    // close mobile sidebar if open
    if (typeof closeSidebar === 'function') closeSidebar();
}

function quickAsk(text) {
    document.getElementById('ai-input').value = text;
    sendMessage();
}

function handleInputKey(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

function scrollToBottom() {
    const msgs = document.getElementById('ai-messages');
    setTimeout(() => msgs.scrollTop = msgs.scrollHeight, 50);
}

function appendMessage(role, html, isHtml = false) {
    const msgs = document.getElementById('ai-messages');
    const wrap = document.createElement('div');
    wrap.className = `ai-msg ${role}`;

    const avatar = document.createElement('div');
    avatar.className = 'ai-avatar';
    avatar.textContent = role === 'assistant' ? '🤖' : '👤';

    const bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    if (isHtml) bubble.innerHTML = html;
    else         bubble.textContent = html;

    wrap.appendChild(avatar);
    wrap.appendChild(bubble);
    msgs.appendChild(wrap);
    scrollToBottom();
    return bubble;
}

function showTyping() {
    const msgs = document.getElementById('ai-messages');
    const wrap = document.createElement('div');
    wrap.className = 'ai-msg assistant';
    wrap.id = 'ai-typing-indicator';

    const avatar = document.createElement('div');
    avatar.className = 'ai-avatar';
    avatar.textContent = '🤖';

    const bubble = document.createElement('div');
    bubble.className = 'ai-bubble';
    bubble.innerHTML = '<div class="ai-typing"><span></span><span></span><span></span></div>';

    wrap.appendChild(avatar);
    wrap.appendChild(bubble);
    msgs.appendChild(wrap);
    scrollToBottom();
}

function removeTyping() {
    const el = document.getElementById('ai-typing-indicator');
    if (el) el.remove();
}

// ─── Main Send Function ───
async function sendMessage() {
    if (aiThinking) return;

    const input   = document.getElementById('ai-input');
    const sendBtn = document.getElementById('ai-send-btn');
    const text    = input.value.trim();
    if (!text) return;

    // Show user message
    appendMessage('user', text);
    aiMessages.push({ role: 'user', content: text });

    input.value = '';
    input.style.height = 'auto';
    aiThinking = true;
    sendBtn.disabled = true;
    showTyping();

    try {
        const res = await fetch('{{ route("ai-assistant.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                message:  text,
                history:  aiMessages.slice(-10), // last 10 messages for context
            })
        });

        const data = await res.json();
        removeTyping();

        if (data.reply) {
            // Format reply (newlines → <br>)
            const formatted = data.reply
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br>');
            appendMessage('assistant', formatted, true);
            aiMessages.push({ role: 'assistant', content: data.reply });
        } else {
            appendMessage('assistant', data.error || 'حدث خطأ. حاول مرة أخرى.');
        }
    } catch (err) {
        removeTyping();
        appendMessage('assistant', '⚠️ تعذر الاتصال بالخادم. تحقق من الاتصال.');
        console.error('AI Assistant error:', err);
    } finally {
        aiThinking = false;
        sendBtn.disabled = false;
        scrollToBottom();
    }
}
</script>

{{-- ═══ Sidebar Injection ═══
     هذا الكود يضيف رابط "الصيدلي الذكي" في السايدبار ديناميكياً
     لو حبيت تضيفه يدوياً في layout، شوف التعليق في الـ README
--}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nav = document.querySelector('#sidebar nav');
    if (!nav) return;

    // Add section + link
    const section = document.createElement('div');
    section.className = 'nav-section';
    section.textContent = 'الذكاء الاصطناعي';

    const link = document.createElement('button');
    link.id = 'ai-sidebar-link';
    link.className = 'nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg transition text-sm w-full text-right text-white';
    link.onclick = openAiFromSidebar;
    link.innerHTML = `
        <svg width="18" height="22" viewBox="-12 -5 184 268" fill="none" style="flex-shrink:0">
            <rect x="28" y="28" width="104" height="82" rx="20" fill="#1e293b" stroke="#4f46e5" stroke-width="3"/>
            <circle cx="57" cy="60" r="11" fill="#6366f1"/>
            <circle cx="103" cy="60" r="11" fill="#6366f1"/>
            <rect x="18" y="122" width="124" height="88" rx="18" fill="#1e293b" stroke="#4f46e5" stroke-width="3"/>
            <circle cx="80" cy="162" r="10" fill="#22d3ee"/>
        </svg>
        الصيدلي الذكي
        <span class="mr-auto flex items-center gap-1">
            <span class="ai-sb-dot"></span>
        </span>
    `;

    nav.appendChild(section);
    nav.appendChild(link);
});
</script>