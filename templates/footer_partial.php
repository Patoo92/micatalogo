<footer class="border-top mt-5 py-4" style="background:#f8fafc;">
    <div class="container">
        <div class="row g-3 align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <small class="text-muted">
                    &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($tienda['nombre_tienda'] ?? 'Mi Tienda'); ?>
                </small>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <small class="text-muted">
                    <a href="privacidad.php" class="text-decoration-none text-muted me-2">Privacidad</a>
                    &middot;
                    <a href="#" onclick="if(window.__cookiesBanner)window.__cookiesBanner();return false;" class="text-decoration-none text-muted ms-2">Cookies</a>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Cookies Consent Banner -->
<div id="cookiesBanner" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9999;background:rgba(15,23,42,0.95);backdrop-filter:blur(8px);padding:16px 20px;border-top:1px solid rgba(255,255,255,0.1);">
    <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
        <p class="text-white small mb-0" style="max-width:700px;">
            Usamos cookies propias para el funcionamiento de la tienda. No compartimos datos con terceros.
            Al navegar, aceptas nuestra <a href="privacidad.php" class="text-decoration-none" style="color:#60a5fa;">política de privacidad</a>.
        </p>
        <div class="d-flex gap-2 flex-shrink-0">
            <button id="cookiesAccept" class="btn btn-sm px-4 fw-bold" style="background:#10b981;color:#fff;border:none;">Aceptar</button>
            <button id="cookiesDecline" class="btn btn-sm px-4 fw-bold" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);">Rechazar</button>
        </div>
    </div>
</div>

<script nonce="<?= $csp_nonce ?>">
(function() {
    if (localStorage.getItem('cookies_consent')) return;
    var banner = document.getElementById('cookiesBanner');
    banner.style.display = 'block';
    window.__cookiesBanner = function() { banner.style.display = 'block'; };
    document.getElementById('cookiesAccept').addEventListener('click', function() {
        localStorage.setItem('cookies_consent', 'accepted');
        banner.style.display = 'none';
    });
    document.getElementById('cookiesDecline').addEventListener('click', function() {
        localStorage.setItem('cookies_consent', 'declined');
        banner.style.display = 'none';
    });
})();
</script>
