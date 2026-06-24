<footer class="border-top mt-5 py-4" style="background:#f8fafc;">
    <div class="container">
        <?php if (!empty($tienda['direccion']) || !empty($tienda['horario'])): ?>
        <div class="row g-2 mb-3 text-center text-md-start">
            <?php if (!empty($tienda['direccion'])): ?>
            <div class="col-md-6">
                <small class="text-muted d-block"><iconify-icon icon="mdi:map-marker" width="14"></iconify-icon> <?php echo htmlspecialchars($tienda['direccion']); ?></small>
            </div>
            <?php endif; ?>
            <?php if (!empty($tienda['horario'])): ?>
            <div class="col-md-6">
                <small class="text-muted d-block"><iconify-icon icon="mdi:clock-outline" width="14"></iconify-icon> <?php echo htmlspecialchars($tienda['horario']); ?></small>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php
        $fp_plan = $tienda['plan'] ?? 'starter';
        $fp_pers = in_array($fp_plan, ['pro', 'business', 'enterprise']);
        ?>
        <?php if ($fp_pers && (!empty($tienda['instagram_url']) || !empty($tienda['facebook_url']) || !empty($tienda['tiktok_url']) || !empty($tienda['twitter_url']))): ?>
        <div class="row mb-3">
            <div class="col text-center">
                <?php if (!empty($tienda['instagram_url'])): ?><a href="<?php echo htmlspecialchars($tienda['instagram_url']); ?>" target="_blank" class="text-decoration-none mx-2" style="color:#64748b;" title="Instagram"><iconify-icon icon="mdi:instagram" width="20"></iconify-icon></a><?php endif; ?>
                <?php if (!empty($tienda['facebook_url'])): ?><a href="<?php echo htmlspecialchars($tienda['facebook_url']); ?>" target="_blank" class="text-decoration-none mx-2" style="color:#64748b;" title="Facebook"><iconify-icon icon="mdi:facebook" width="20"></iconify-icon></a><?php endif; ?>
                <?php if (!empty($tienda['tiktok_url'])): ?><a href="<?php echo htmlspecialchars($tienda['tiktok_url']); ?>" target="_blank" class="text-decoration-none mx-2" style="color:#64748b;" title="TikTok"><iconify-icon icon="mdi:tiktok" width="20"></iconify-icon></a><?php endif; ?>
                <?php if (!empty($tienda['twitter_url'])): ?><a href="<?php echo htmlspecialchars($tienda['twitter_url']); ?>" target="_blank" class="text-decoration-none mx-2" style="color:#64748b;" title="X / Twitter"><iconify-icon icon="mdi:twitter" width="20"></iconify-icon></a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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
