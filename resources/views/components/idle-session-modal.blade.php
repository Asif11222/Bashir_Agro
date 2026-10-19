<style>
#idle-timeout-modal {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(12, 38, 27, 0.65) !important;
    backdrop-filter: blur(5px) !important;
    -webkit-backdrop-filter: blur(5px) !important;
    z-index: 999999 !important;
    display: none;
    place-items: center !important;
    padding: 18px !important;
}
#idle-timeout-modal.show {
    display: grid !important;
}
</style>

<div class="modal-wrap" id="idle-timeout-modal">
    <div class="modal" style="width: min(460px, 92vw); border-radius: 14px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); background:#fff;">
        <div class="modal-head" style="background: #fff8f6; border-bottom: 1px solid #fee2e2; padding: 14px 18px; display:flex; align-items: center;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:#fee2e2;color:#dc2626;display:grid;place-items:center;font-size:18px;">
                    ⏱
                </div>
                <div>
                    <h3 style="margin:0;font-size:15px;font-weight:700;color:#991b1b;">সেশন মেয়াদোত্তীর্ণ সতর্কতা</h3>
                    <div style="font-size:11px;color:#b91c1c;">নিষ্ক্রিয়তা শনাক্তকরণ ও অ্যাকাউন্ট সুরক্ষা</div>
                </div>
            </div>
        </div>

        <div class="modal-body" style="padding: 20px 18px; font-size: 13.5px; line-height: 1.5; color: var(--text, #333);">
            <p style="margin: 0 0 14px;">
                আপনি বেশ কিছুক্ষণ যাবত নিষ্ক্রিয় রয়েছেন। অ্যাকাউন্ট সুরক্ষার স্বার্থে আগামী 
                <b id="idle-countdown-text" style="color:#dc2626; font-size: 18px; font-weight: 700;">120</b> সেকেন্ডের মধ্যে সেশনটি স্বয়ংক্রিয়ভাবে বন্ধ হয়ে যাবে।
            </p>

            <div style="background: #f1f5f9; border-radius: 8px; height: 7px; overflow: hidden; margin-bottom: 14px;">
                <div id="idle-progress-bar" style="height: 100%; width: 100%; background: #dc2626; transition: width 1s linear;"></div>
            </div>

            <p style="margin: 0; font-size: 12.5px; color: var(--muted, #666);">
                আপনি কি আপনার কাজ অব্যাহত রাখতে সেশন চালু রাখতে চান?
            </p>
        </div>

        <div class="modal-foot" style="padding: 12px 18px; background: #f8fafc; border-top: 1px solid var(--line, #e2e8f0); display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn" id="btn-idle-logout" style="font-size: 13px; padding: 8px 16px; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; border-radius: 6px;">
                এখনই লগআউট
            </button>
            <button type="button" class="btn primary" id="btn-idle-keep-alive" style="font-size: 13px; padding: 8px 18px; background: #059669; border-color: #059669; color: #fff; border-radius: 6px; cursor: pointer; font-weight: 600;">
                ✓ সেশন চালু রাখুন
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const isInstantTest = urlParams.get('test_warning') === '1' || urlParams.get('test_modal') === '1';
    const isTestMode = isInstantTest || urlParams.get('test_idle') === '1';

    // Normal: 18 minutes idle + 2 minutes countdown = 20 minutes
    // Test mode: 5s idle + 20s countdown for rapid verification
    const IDLE_THRESHOLD = isTestMode ? (isInstantTest ? 0 : 5 * 1000) : 18 * 60 * 1000;
    const WARNING_DURATION = isTestMode ? (isInstantTest ? 60 * 1000 : 20 * 1000) : 2 * 60 * 1000;
    const STORAGE_KEY = 'bashir_agro_last_activity';
    const LOGOUT_SYNC_KEY = 'bashir_agro_idle_logout_trigger';

    let lastActivity = Date.now();
    let isWarningOpen = false;
    let isLoggingOut = false;

    // Initialize lastActivity from localStorage only if recent (less than IDLE_THRESHOLD)
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && !isInstantTest) {
            const parsed = parseInt(stored, 10);
            if (!isNaN(parsed) && (Date.now() - parsed) < IDLE_THRESHOLD && parsed <= Date.now()) {
                lastActivity = parsed;
            }
        }
        if (isInstantTest) {
            lastActivity = Date.now() - IDLE_THRESHOLD;
        } else {
            localStorage.setItem(STORAGE_KEY, lastActivity.toString());
        }
    } catch(e) {}

    console.log(`[IdleSession] Initialized. Idle threshold: ${Math.round(IDLE_THRESHOLD/1000)}s, Warning duration: ${Math.round(WARNING_DURATION/1000)}s.`);

    // Expose global helper for background syncing pages (e.g. monitoring)
    window.isUserSessionIdle = function() {
        return (Date.now() - lastActivity) > (5 * 60 * 1000) || isWarningOpen;
    };

    function recordActivity() {
        if (isWarningOpen || isLoggingOut) return;
        lastActivity = Date.now();
        try {
            localStorage.setItem(STORAGE_KEY, lastActivity.toString());
        } catch(e) {}
    }

    // Attach passive activity listeners (throttled mousemove to ignore minor trackpad/sensor jitter)
    let lastMouseX = -1;
    let lastMouseY = -1;
    window.addEventListener('mousemove', function(e) {
        if (lastMouseX === -1 || Math.abs(e.clientX - lastMouseX) > 6 || Math.abs(e.clientY - lastMouseY) > 6) {
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
            recordActivity();
        }
    }, { passive: true });

    ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function(evt) {
        window.addEventListener(evt, recordActivity, { passive: true });
    });

    // Multi-tab sync via storage events
    window.addEventListener('storage', function(e) {
        if (e.key === STORAGE_KEY && e.newValue) {
            const parsed = parseInt(e.newValue, 10);
            if (!isNaN(parsed) && parsed > lastActivity) {
                lastActivity = parsed;
                if (isWarningOpen) {
                    hideWarningModal();
                }
            }
        } else if (e.key === LOGOUT_SYNC_KEY) {
            performLogout();
        }
    });

    const modal = document.getElementById('idle-timeout-modal');
    const countdownText = document.getElementById('idle-countdown-text');
    const progressBar = document.getElementById('idle-progress-bar');
    const keepAliveBtn = document.getElementById('btn-idle-keep-alive');
    const logoutBtn = document.getElementById('btn-idle-logout');

    function showWarningModal() {
        if (isWarningOpen || !modal) return;
        isWarningOpen = true;
        modal.classList.add('show');
        modal.style.display = 'grid';
        console.log('[IdleSession] Warning modal opened.');
    }

    function hideWarningModal() {
        if (!isWarningOpen || !modal) return;
        isWarningOpen = false;
        modal.classList.remove('show');
        modal.style.display = 'none';
        console.log('[IdleSession] Warning modal closed.');
    }

    // Expose manual test function in browser console
    window.showIdleWarningTest = function() {
        lastActivity = Date.now() - IDLE_THRESHOLD;
        showWarningModal();
        if (countdownText) countdownText.textContent = Math.round(WARNING_DURATION / 1000);
        if (progressBar) progressBar.style.width = '100%';
        console.log('[IdleSession] Manual test warning modal triggered.');
    };

    async function keepSessionAlive() {
        hideWarningModal();
        recordActivity();
        console.log('[IdleSession] Sending keep-alive request...');

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('{{ route("admin.session.keep-alive") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (res.ok) {
                console.log('[IdleSession] Keep-alive success.');
                if (window.showToast) {
                    window.showToast('সেশন সফলভাবে চালু রাখা হয়েছে।');
                }
            }
        } catch(err) {
            console.warn('[IdleSession] Keep-alive ping failed:', err);
        }
    }

    async function performLogout() {
        if (isLoggingOut) return;
        isLoggingOut = true;
        console.log('[IdleSession] Logging out due to inactivity...');

        try {
            localStorage.setItem(LOGOUT_SYNC_KEY, Date.now().toString());
        } catch(e) {}

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        try {
            await fetch('{{ route("admin.logout") }}?reason=idle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ reason: 'idle' })
            });
        } catch(e) {}

        window.location.href = '{{ route("login") }}?reason=idle';
    }

    if (keepAliveBtn) keepAliveBtn.addEventListener('click', keepSessionAlive);
    if (logoutBtn) logoutBtn.addEventListener('click', performLogout);

    // Main ticker checking every 1 second
    setInterval(function() {
        if (isLoggingOut) return;

        const idleElapsed = Date.now() - lastActivity;

        if (idleElapsed >= IDLE_THRESHOLD) {
            const remainingMs = (IDLE_THRESHOLD + WARNING_DURATION) - idleElapsed;
            const remainingSec = Math.max(0, Math.ceil(remainingMs / 1000));

            showWarningModal();

            if (countdownText) {
                countdownText.textContent = remainingSec;
            }
            if (progressBar) {
                const percent = Math.max(0, (remainingMs / WARNING_DURATION) * 100);
                progressBar.style.width = percent + '%';
            }

            if (remainingSec <= 0) {
                performLogout();
            }
        } else {
            if (isWarningOpen) {
                hideWarningModal();
            }
        }
    }, 1000);
})();
</script>
