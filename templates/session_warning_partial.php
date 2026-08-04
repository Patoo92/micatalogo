<?php
$sesion_warning_lead    = 60;
$sesion_warning_timeout = 1800;
$sesion_warning_minutos = (int)(($sesion_warning_timeout - $sesion_warning_lead) / 60);
$sesion_logout_url      = isset($_SESSION['admin_id']) ? 'logout-admin.php' : 'logout.php';
?>
<div class="modal fade" id="sessionWarningModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><iconify-icon icon="mdi:clock-alert-outline" width="22" class="me-1"></iconify-icon> Sesión por expirar</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mb-1 text-muted">Por inactividad, tu sesión se cerrará en:</p>
                <h2 class="fw-bold mb-3" id="sessionCountdown" style="font-size:2.5rem;color:var(--tblr-primary,#2f6fed);">01:00</h2>
                <p class="small text-muted mb-0">Tu sesión caduca tras <?php echo $sesion_warning_minutos; ?> minutos sin actividad. Hacé clic en «Continuar sesión» para seguir trabajando.</p>
            </div>
            <div class="modal-footer">
                <a href="<?php echo $sesion_logout_url; ?>?token=<?php echo htmlspecialchars($_SESSION['_logout_token'] ?? ''); ?>" class="btn btn-outline-secondary">Cerrar sesión</a>
                <button type="button" class="btn btn-primary" id="sessionKeepAlive">Continuar sesión</button>
            </div>
        </div>
    </div>
</div>
<script nonce="<?= $csp_nonce ?>">
(function() {
    var lead = 60;
    var timeout = 1800;
    var modalEl = document.getElementById('sessionWarningModal');
    var countEl = document.getElementById('sessionCountdown');
    var keepBtn = document.getElementById('sessionKeepAlive');
    if (!modalEl || !countEl || !keepBtn) return;

    var bsModal = null;
    if (typeof bootstrap !== 'undefined') {
        bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
    }

    var shown = false;
    var warnAt = Date.now() + (timeout - lead) * 1000;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function render() {
        var m = Math.floor(remaining / 60);
        countEl.textContent = pad(m) + ':' + pad(remaining % 60);
    }
    var remaining = lead;

    function showModal() {
        if (shown) return;
        shown = true;
        remaining = lead;
        render();
        if (bsModal) bsModal.show();
        else modalEl.style.display = 'block';
    }
    function hideModal() {
        shown = false;
        if (bsModal) bsModal.hide();
        else modalEl.style.display = 'none';
    }
    function irLogin() {
        window.location.href = 'login.php';
    }
    function keepAlive() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'keepalive.php', true);
        xhr.timeout = 10000;
        xhr.onload = function() {
            var ok = false;
            try {
                ok = xhr.status === 200 && (xhr.getResponseHeader('Content-Type') || '').indexOf('json') !== -1;
            } catch (e) {}
            if (ok) {
                warnAt = Date.now() + (timeout - lead) * 1000;
                hideModal();
            } else {
                irLogin();
            }
        };
        xhr.onerror = function() { irLogin(); };
        xhr.ontimeout = function() { irLogin(); };
        xhr.send();
    }

    keepBtn.addEventListener('click', keepAlive);

    setInterval(function() {
        var now = Date.now();
        if (!shown && now >= warnAt) {
            showModal();
        } else if (shown) {
            remaining = Math.max(0, lead - Math.floor((now - warnAt) / 1000));
            render();
            if (remaining <= 0) irLogin();
        }
    }, 500);
})();
</script>
