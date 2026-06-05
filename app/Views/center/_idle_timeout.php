<!-- ═══════════════════════════════════════════════════════════════
     Idle Timeout Warning Modal
     - Tracks real user activity (mouse, keyboard, touch)
     - Shows warning at 25 minutes idle
     - Auto-logs out at 30 minutes idle
     - Auto-refresh polling uses /center/ping which does NOT reset idle timer
     ═══════════════════════════════════════════════════════════════ -->

<!-- Warning Modal -->
<div class="modal fade" id="idleWarningModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content" style="border:2px solid #f59e0b;">
            <div class="modal-header py-2" style="background:#fffbeb;border-bottom:1px solid #fde68a;">
                <h6 class="modal-title fw-semibold" style="color:#92400e;">
                    <i class="bi bi-clock-history me-2 text-warning"></i>Session Expiring Soon
                </h6>
            </div>
            <div class="modal-body text-center py-3">
                <p class="mb-1" style="font-size:.88rem;color:#374151;">You've been idle. You'll be logged out in</p>
                <div id="idleCountdown" style="font-size:2rem;font-weight:700;color:#dc2626;line-height:1.2;">5:00</div>
                <p class="mb-0 mt-1" style="font-size:.78rem;color:#6b7280;">Move your mouse or press any key to stay logged in.</p>
            </div>
            <div class="modal-footer py-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-primary px-4" id="idleStayBtn">
                    <i class="bi bi-check-circle me-1"></i>Stay Logged In
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var IDLE_WARN_MS    = 25 * 60 * 1000; // 25 minutes — show warning
    var IDLE_LOGOUT_MS  = 30 * 60 * 1000; // 30 minutes — auto logout
    var LOGOUT_URL      = '<?= site_url('logout') ?>';
    var PING_URL        = '<?= site_url('center/ping') ?>';

    var lastActivity    = Date.now();
    var warningShown    = false;
    var warnTimer       = null;
    var logoutTimer     = null;
    var countdownTimer  = null;
    var warningModal    = null;

    // ── Reset idle clock on real user activity ──────────────
    function resetIdle() {
        lastActivity = Date.now();
        if (warningShown) {
            hideWarning();
        }
        clearTimers();
        scheduleTimers();
    }

    ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetIdle, { passive: true });
    });

    // ── Timer scheduling ─────────────────────────────────────
    function scheduleTimers() {
        warnTimer   = setTimeout(showWarning,   IDLE_WARN_MS);
        logoutTimer = setTimeout(doLogout,      IDLE_LOGOUT_MS);
    }

    function clearTimers() {
        clearTimeout(warnTimer);
        clearTimeout(logoutTimer);
        clearInterval(countdownTimer);
    }

    // ── Warning modal ─────────────────────────────────────────
    function showWarning() {
        warningShown = true;
        if (! warningModal) {
            warningModal = new bootstrap.Modal(document.getElementById('idleWarningModal'));
        }
        warningModal.show();
        startCountdown(5 * 60); // 5 minute countdown
    }

    function hideWarning() {
        warningShown = false;
        clearInterval(countdownTimer);
        if (warningModal) {
            warningModal.hide();
        }
    }

    function startCountdown(seconds) {
        var remaining = seconds;
        updateCountdownDisplay(remaining);
        countdownTimer = setInterval(function () {
            remaining--;
            updateCountdownDisplay(remaining);
            if (remaining <= 0) {
                clearInterval(countdownTimer);
            }
        }, 1000);
    }

    function updateCountdownDisplay(seconds) {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        var el = document.getElementById('idleCountdown');
        if (el) {
            el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            el.style.color = seconds <= 60 ? '#dc2626' : '#f59e0b';
        }
    }

    // ── Stay logged in button ─────────────────────────────────
    document.getElementById('idleStayBtn').addEventListener('click', function () {
        resetIdle();
    });

    // ── Auto logout ───────────────────────────────────────────
    function doLogout() {
        clearTimers();
        // POST to logout using a hidden form to respect CSRF
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = LOGOUT_URL;
        var csrf = document.createElement('input');
        csrf.type  = 'hidden';
        csrf.name  = '<?= csrf_token() ?>';
        csrf.value = '<?= csrf_hash() ?>';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }

    // ── Kick off ──────────────────────────────────────────────
    scheduleTimers();

})();
</script>
