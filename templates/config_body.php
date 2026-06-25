<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Configuración de Tienda</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .form-label-custom { font-weight: 600; font-size: 0.875rem; color: #374151; margin-bottom: 4px; }
        .nav-tabs .nav-link { font-weight: 500; font-size: 0.9rem; border-radius: 10px 10px 0 0; }
        .nav-tabs .nav-link:not(.active) { color: #64748b; }
        .nav-tabs .nav-link.active { font-weight: 600; }
        body.dark-mode .form-label-custom { color: #cbd5e1; }
        body.dark-mode .nav-tabs .nav-link { border-color: rgba(255,255,255,0.1); color: #94a3b8; }
        body.dark-mode .nav-tabs .nav-link.active { background: rgba(30,41,59,0.6); border-color: rgba(255,255,255,0.1); border-bottom-color: transparent; color: #e2e8f0; }
        body.dark-mode .nav-tabs .nav-link:hover:not(.active) { border-color: rgba(255,255,255,0.15); color: #e2e8f0; }
        @media (max-width: 991.98px) {
            .navbar-collapse .d-flex { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .navbar-collapse .d-flex .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-admin sidebar-open <?php echo ($_SESSION['tema_admin'] ?? 'default') !== 'default' ? 'theme-' . $_SESSION['tema_admin'] : ''; ?>">

    <?php require __DIR__ . '/sidebar_partial.php'; ?>

            <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#configNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="configNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-primary btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 600px;">
        <div class="card card-config glass-card p-4">
            <h4 class="mb-4 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:cog" width="28"></iconify-icon>
                Configuración de Tienda
            </h4>

            <?php echo $mensaje; ?>

            <form action="guardar-configuracion.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <ul class="nav nav-tabs nav-fill mb-4" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-negocio" type="button" role="tab">
                            <iconify-icon icon="mdi:store" width="16"></iconify-icon> Negocio
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-apariencia" type="button" role="tab">
                            <iconify-icon icon="mdi:palette" width="16"></iconify-icon> Apariencia
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-redes" type="button" role="tab">
                            <iconify-icon icon="mdi:link-variant" width="16"></iconify-icon> Redes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-avanzado" type="button" role="tab">
                            <iconify-icon icon="mdi:cog-outline" width="16"></iconify-icon> Avanzado
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="tab-negocio" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:store" width="16"></iconify-icon> Nombre de la tienda</label>
                            <input type="text" name="nombre_tienda" value="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:email" width="16"></iconify-icon> Email de contacto / notificaciones</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($tienda['email'] ?? ''); ?>" class="form-control" placeholder="tutienda@ejemplo.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:image" width="16"></iconify-icon> Logo de tu tienda</label>
                            <input type="file" name="logo" class="form-control">
                            <?php if (!empty($tienda['logo_url'])): ?>
                                <small class="text-muted d-block mt-1">Actual: <?php echo htmlspecialchars($tienda['logo_url']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:palette" width="16"></iconify-icon> Color principal de la marca</label>
                            <input type="color" name="color" value="<?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>" class="form-control form-control-color">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:currency-eur" width="16"></iconify-icon> Moneda</label>
                            <input type="text" name="moneda" value="<?php echo htmlspecialchars($tienda['moneda'] ?? '€'); ?>" class="form-control" placeholder="€" maxlength="10" style="max-width:100px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:text" width="16"></iconify-icon> Descripción / Bio de la tienda</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Breve descripción de tu negocio..."><?php echo htmlspecialchars($tienda['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:map-marker" width="16"></iconify-icon> Dirección</label>
                            <input type="text" name="direccion" value="<?php echo htmlspecialchars($tienda['direccion'] ?? ''); ?>" class="form-control" placeholder="Calle, número, ciudad">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:clock-outline" width="16"></iconify-icon> Horario de atención</label>
                            <input type="text" name="horario" value="<?php echo htmlspecialchars($tienda['horario'] ?? ''); ?>" class="form-control" placeholder="Lun-Vie 10:00-19:00, Sáb 10:00-14:00">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">
                                <iconify-icon icon="mdi:whatsapp" width="16"></iconify-icon> Número de WhatsApp
                            </label>
                            <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($tienda['telefono_whatsapp'] ?? ''); ?>" class="form-control" placeholder="+34600123456">
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom">
                                <iconify-icon icon="mdi:message-text" width="16"></iconify-icon> Mensaje predeterminado de WhatsApp
                            </label>
                            <textarea name="mensaje_whatsapp" class="form-control" rows="2" placeholder="Hola, me interesa..."><?php echo htmlspecialchars($tienda['mensaje_whatsapp'] ?? ''); ?></textarea>
                            <small class="text-muted">Texto que se prefija al contactar por WhatsApp.</small>
                        </div>

                        <?php if (plan_limite('personalizacion')): ?>
                        <h6 class="fw-bold mt-4 mb-3"><iconify-icon icon="mdi:bell-outline" width="18"></iconify-icon> Notificaciones</h6>
                        <div class="mb-2 form-check form-switch">
                            <input type="hidden" name="notif_nuevo_pedido" value="0">
                            <input type="checkbox" name="notif_nuevo_pedido" id="notif_pedido" class="form-check-input" value="1" <?php echo ($tienda['notif_nuevo_pedido'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notif_pedido">Notificarme cuando llegue un nuevo pedido</label>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input type="hidden" name="notif_stock_bajo" value="0">
                            <input type="checkbox" name="notif_stock_bajo" id="notif_stock" class="form-check-input" value="1" <?php echo ($tienda['notif_stock_bajo'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notif_stock">Notificarme cuando un producto tenga stock crítico</label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="tab-apariencia" role="tabpanel">
                        <?php if (plan_limite('personalizacion')): ?>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:image-area" width="16"></iconify-icon> Banner / Portada de la tienda</label>
                            <input type="file" name="banner" class="form-control" accept="image/*">
                            <?php if (!empty($tienda['banner_url'])): ?>
                                <small class="text-muted d-block mt-1">Actual: <?php echo htmlspecialchars($tienda['banner_url']); ?></small>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:image-auto" width="16"></iconify-icon> Título del hero</label>
                            <input type="text" name="hero_title" value="<?php echo htmlspecialchars($tienda['hero_title'] ?? ''); ?>" class="form-control" placeholder="Explora nuestra colección">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:image-auto" width="16"></iconify-icon> Subtítulo del hero</label>
                            <input type="text" name="hero_subtitle" value="<?php echo htmlspecialchars($tienda['hero_subtitle'] ?? ''); ?>" class="form-control" placeholder="Agrega productos a tu carrito y envíanos tu orden por WhatsApp">
                        </div>

                        <h6 class="fw-bold mt-4 mb-3"><iconify-icon icon="mdi:palette" width="18"></iconify-icon> Tema visual del panel</h6>
                        <div class="mb-3">
                            <label class="form-label-custom">Tema de administración</label>
                            <select name="tema_admin" class="form-select">
                                <option value="default" <?php echo ($tienda['tema_admin'] ?? 'default') === 'default' ? 'selected' : ''; ?>>Default (Púrpura)</option>
                                <option value="ocean" <?php echo ($tienda['tema_admin'] ?? '') === 'ocean' ? 'selected' : ''; ?>>Océano (Azul)</option>
                                <option value="forest" <?php echo ($tienda['tema_admin'] ?? '') === 'forest' ? 'selected' : ''; ?>>Bosque (Verde)</option>
                                <option value="sunset" <?php echo ($tienda['tema_admin'] ?? '') === 'sunset' ? 'selected' : ''; ?>>Atardecer (Naranja)</option>
                                <option value="midnight" <?php echo ($tienda['tema_admin'] ?? '') === 'midnight' ? 'selected' : ''; ?>>Medianoche (Índigo)</option>
                            </select>
                            <small class="text-muted">Cambiá el esquema de colores del panel de administración.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:code-tags" width="16"></iconify-icon> CSS personalizado</label>
                            <textarea name="css_personalizado" class="form-control" rows="4" placeholder=".clase { color: red; }"><?php echo htmlspecialchars($tienda['css_personalizado'] ?? ''); ?></textarea>
                            <small class="text-muted">Se inyecta en el &lt;head&gt; del catálogo público.</small>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="border-radius:12px;">
                            <iconify-icon icon="mdi:lock" width="18"></iconify-icon>
                            <span>Personalización visual disponible en Plan Pro y superiores. <a href="index.html#planes" class="fw-bold text-decoration-underline">Ver planes</a></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="tab-redes" role="tabpanel">
                        <?php if (plan_limite('personalizacion')): ?>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:instagram" width="16"></iconify-icon> Instagram</label>
                            <input type="text" name="instagram" value="<?php echo htmlspecialchars($tienda['instagram_url'] ?? ''); ?>" class="form-control" placeholder="https://instagram.com/tutienda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:facebook" width="16"></iconify-icon> Facebook</label>
                            <input type="text" name="facebook" value="<?php echo htmlspecialchars($tienda['facebook_url'] ?? ''); ?>" class="form-control" placeholder="https://facebook.com/tutienda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:tiktok" width="16"></iconify-icon> TikTok</label>
                            <input type="text" name="tiktok" value="<?php echo htmlspecialchars($tienda['tiktok_url'] ?? ''); ?>" class="form-control" placeholder="https://tiktok.com/@tutienda">
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom"><iconify-icon icon="mdi:twitter" width="16"></iconify-icon> X / Twitter</label>
                            <input type="text" name="twitter" value="<?php echo htmlspecialchars($tienda['twitter_url'] ?? ''); ?>" class="form-control" placeholder="https://x.com/tutienda">
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="border-radius:12px;">
                            <iconify-icon icon="mdi:lock" width="18"></iconify-icon>
                            <span>Redes sociales disponibles en Plan Pro y superiores. <a href="index.html#planes" class="fw-bold text-decoration-underline">Ver planes</a></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane" id="tab-avanzado" role="tabpanel">
                        <?php if (plan_limite('personalizacion')): ?>
                        <h6 class="fw-bold mb-3"><iconify-icon icon="mdi:search-web" width="18"></iconify-icon> SEO (Meta Tags)</h6>
                        <div class="mb-3">
                            <label class="form-label-custom">Meta descripción</label>
                            <textarea name="meta_descripcion" class="form-control" rows="2" placeholder="Breve descripción para buscadores..."><?php echo htmlspecialchars($tienda['meta_descripcion'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Palabras clave (separadas por coma)</label>
                            <input type="text" name="meta_palabras_clave" value="<?php echo htmlspecialchars($tienda['meta_palabras_clave'] ?? ''); ?>" class="form-control" placeholder="ropa, moda, tienda online, ...">
                        </div>

                        <h6 class="fw-bold mt-4 mb-3"><iconify-icon icon="mdi:code-tags" width="18"></iconify-icon> Tracking</h6>
                        <div class="mb-3">
                            <label class="form-label-custom">Código de tracking (Google Analytics, Facebook Pixel, etc.)</label>
                            <textarea name="codigo_tracking" class="form-control" rows="4" placeholder="&lt;script&gt;...&lt;/script&gt;"><?php echo htmlspecialchars($tienda['codigo_tracking'] ?? ''); ?></textarea>
                            <small class="text-muted">Se inyecta en el &lt;head&gt; del catálogo público.</small>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="border-radius:12px;">
                            <iconify-icon icon="mdi:lock" width="18"></iconify-icon>
                            <span>SEO y tracking disponibles en Plan Pro y superiores. <a href="index.html#planes" class="fw-bold text-decoration-underline">Ver planes</a></span>
                        </div>
                        <?php endif; ?>

                        <?php if (plan_limite('marca_blanca')): ?>
                        <h6 class="fw-bold mt-4 mb-3"><iconify-icon icon="mdi:palette" width="18"></iconify-icon> Marca blanca &amp; Dominio</h6>
                        <div class="mb-3 form-check form-switch">
                            <input type="hidden" name="marca_blanca" value="0">
                            <input type="checkbox" name="marca_blanca" id="marca_blanca" class="form-check-input" value="1" <?php echo $tienda['marca_blanca'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="marca_blanca">
                                <iconify-icon icon="mdi:palette" width="16"></iconify-icon> Marca blanca
                                <small class="text-muted d-block">Oculta el branding de micatalogo.app en emails y catálogo público</small>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label-custom">Dominio personalizado</label>
                            <input type="text" name="dominio" class="form-control" placeholder="tutienda.com" value="<?php echo htmlspecialchars($tienda['dominio'] ?? ''); ?>">
                            <small class="text-muted">Configurá un dominio propio. Debe apuntar con un CNAME a tu hosting.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-icon py-2 mt-4" style="font-weight: 600;">
                    <iconify-icon icon="mdi:content-save" width="18"></iconify-icon> Guardar Cambios
                </button>
            </form>

            <hr class="my-4">
            <div class="d-flex gap-2">
                <a href="cambiar-password.php" class="btn btn-outline-warning w-100 btn-icon" style="font-weight: 600;">
                    <iconify-icon icon="mdi:lock-reset" width="18"></iconify-icon> Cambiar Contraseña
                </a>
            </div>
        </div>

        <div class="card card-config glass-card p-4 mt-4" id="plan">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:crown" width="24"></iconify-icon>
                Plan: <?php echo htmlspecialchars(ucfirst($_SESSION['plan'])); ?>
            </h5>
            <ul class="list-unstyled small mb-0">
                <li class="mb-1"><iconify-icon icon="mdi:account-group" width="16"></iconify-icon> Staff: <?php echo plan_limite('staff'); ?> miembros</li>
                <li class="mb-1"><iconify-icon icon="mdi:store" width="16"></iconify-icon> Tiendas: <?php echo plan_limite('tiendas'); ?></li>
                <li class="mb-1"><iconify-icon icon="mdi:key-variant" width="16"></iconify-icon> API Keys: <?php echo plan_limite('api_keys'); ?></li>
                <li class="mb-1"><iconify-icon icon="mdi:palette" width="16"></iconify-icon> Marca blanca: <?php echo plan_limite('marca_blanca') ? 'Sí' : 'No'; ?></li>
                <li class="mb-1"><iconify-icon icon="mdi:tune" width="16"></iconify-icon> Personalización: <?php echo plan_limite('personalizacion') ? 'Sí' : 'No'; ?></li>
            </ul>
            <a href="index.html#planes" class="btn btn-sm btn-outline-primary mt-3 btn-icon">
                <iconify-icon icon="mdi:arrow-up-circle" width="16"></iconify-icon> Ver planes
            </a>
            <p class="text-muted small mt-2 mb-0">Los cambios de plan se gestionan manualmente. <a href="https://wa.me/34123456789" target="_blank" rel="noopener">Contactanos por WhatsApp</a> para mejorar tu plan.</p>
        </div>

        <div class="card card-config glass-card p-4 mt-4">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:key-variant" width="24"></iconify-icon>
                API Keys
            </h5>
            <p class="text-muted small mb-3">Usa estas claves para acceder a tus datos desde aplicaciones externas (productos, pedidos, categorías).</p>

            <?php if (count($api_keys) > 0): ?>
                <div class="mb-3">
                    <?php foreach ($api_keys as $k): ?>
                        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2" style="background:#f8fafc;">
                            <div>
                                <strong><?php echo htmlspecialchars($k['nombre']); ?></strong>
                                <code style="font-size:0.75rem;color:#64748b;display:block;"><?php echo htmlspecialchars(substr($k['api_key'], 0, 16)) . '…'; ?></code>
                                <small class="text-muted">Creada: <?php echo htmlspecialchars($k['created_at']); ?></small>
                            </div>
                            <form method="POST" action="configuracion.php" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="key_id" value="<?php echo $k['id']; ?>">
                                <button type="submit" name="revocar_api_key" class="btn btn-sm btn-outline-danger btn-icon" onclick="return confirm('¿Revocar esta API Key? Las integraciones que la usen dejarán de funcionar.');">
                                    <iconify-icon icon="mdi:delete" width="14"></iconify-icon> Revocar
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-3 empty-state">
                    <iconify-icon icon="mdi:key-variant" width="32"></iconify-icon>
                    <p class="text-muted small">No hay API Keys. Generá una para empezar.</p>
                </div>
            <?php endif; ?>

            <form method="POST" action="configuracion.php" class="d-flex gap-2 align-items-end">
                <?php echo csrf_field(); ?>
                <div class="flex-grow-1">
                    <label class="form-label-custom">Nombre para identificar esta key</label>
                    <input type="text" name="nombre_key" class="form-control" placeholder="Ej: App mobile, integración web" value="API v1">
                </div>
                <button type="submit" name="generar_api_key" class="btn btn-primary btn-icon py-2" style="font-weight:600;">
                    <iconify-icon icon="mdi:plus" width="16"></iconify-icon> Generar
                </button>
            </form>

            <p class="small text-muted mt-3 mb-0">
                <iconify-icon icon="mdi:information" width="14"></iconify-icon>
                Documentación: <code>GET /api.php?action=productos&api_key=...</code>
            </p>
        </div>

        <script nonce="<?= $csp_nonce ?>">
        document.querySelectorAll('button[name="revocar_api_key"]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('¿Revocar esta API Key? Las integraciones que la usen dejarán de funcionar.')) {
                    e.preventDefault();
                }
            });
        });
        </script>
        <script nonce="<?= $csp_nonce ?>">
        (function() {
            if (localStorage.getItem('dark_mode') === '1') { document.body.classList.add('dark-mode'); }
            var toggle = document.getElementById('darkModeToggle');
            var icon = toggle && toggle.querySelector('iconify-icon');
            var span = toggle && toggle.querySelector('span');
            if (localStorage.getItem('dark_mode') === '1') {
                if (icon) icon.setAttribute('icon', 'mdi:weather-sunny');
                if (span) span.textContent = 'Modo claro';
            }
            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.body.classList.toggle('dark-mode');
                    var isDark = document.body.classList.contains('dark-mode');
                    localStorage.setItem('dark_mode', isDark ? '1' : '0');
                    if (icon) icon.setAttribute('icon', isDark ? 'mdi:weather-sunny' : 'mdi:weather-night');
                    if (span) span.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
                });
            }
        })();
        </script>
    </div>
</body>
</html>
