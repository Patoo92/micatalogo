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
        @media (max-width: 991.98px) {
            .navbar-collapse .d-flex { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .navbar-collapse .d-flex .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light sidebar-open">

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
        <div class="card card-config p-4">
            <h4 class="mb-4 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:cog" width="28"></iconify-icon>
                Configuración de Tienda
            </h4>

            <?php echo $mensaje; ?>

            <form action="guardar-configuracion.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label-custom">Logo de tu tienda</label>
                    <input type="file" name="logo" class="form-control">
                    <?php if (!empty($tienda['logo_url'])): ?>
                        <small class="text-muted">Logo actual: <?php echo htmlspecialchars($tienda['logo_url']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">
                        <iconify-icon icon="mdi:instagram" width="16"></iconify-icon> Instagram URL
                    </label>
                    <input type="text" name="instagram" value="<?php echo htmlspecialchars($tienda['instagram_url'] ?? ''); ?>" class="form-control" placeholder="https://instagram.com/tutienda">
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">
                        <iconify-icon icon="mdi:palette" width="16"></iconify-icon> Color principal de la marca
                    </label>
                    <input type="color" name="color" value="<?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>" class="form-control form-control-color">
                </div>

                <div class="mb-3">
                    <label class="form-label-custom">
                        <iconify-icon icon="mdi:whatsapp" width="16"></iconify-icon> Número de WhatsApp
                    </label>
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($tienda['telefono_whatsapp'] ?? ''); ?>" class="form-control" placeholder="+34600123456">
                </div>

                <?php if (plan_limite('marca_blanca')): ?>
                <div class="mb-3 form-check form-switch">
                    <input type="hidden" name="marca_blanca" value="0">
                    <input type="checkbox" name="marca_blanca" id="marca_blanca" class="form-check-input" value="1" <?php echo $tienda['marca_blanca'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="marca_blanca">
                        <iconify-icon icon="mdi:palette" width="16"></iconify-icon> Marca blanca
                        <small class="text-muted d-block">Oculta el branding de micatalogo.app en emails y catálogo público</small>
                    </label>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-100 btn-icon py-2" style="font-weight: 600;">
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

        <div class="card card-config p-4 mt-4" id="plan">
            <h5 class="mb-3 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:crown" width="24"></iconify-icon>
                Plan: <?php echo htmlspecialchars(ucfirst($_SESSION['plan'])); ?>
            </h5>
            <ul class="list-unstyled small mb-0">
                <li class="mb-1"><iconify-icon icon="mdi:account-group" width="16"></iconify-icon> Staff: <?php echo plan_limite('staff'); ?> miembros</li>
                <li class="mb-1"><iconify-icon icon="mdi:store" width="16"></iconify-icon> Tiendas: <?php echo plan_limite('tiendas'); ?></li>
                <li class="mb-1"><iconify-icon icon="mdi:key-variant" width="16"></iconify-icon> API Keys: <?php echo plan_limite('api_keys'); ?></li>
                <li class="mb-1"><iconify-icon icon="mdi:palette" width="16"></iconify-icon> Marca blanca: <?php echo plan_limite('marca_blanca') ? 'Sí' : 'No'; ?></li>
            </ul>
            <a href="index.html#planes" class="btn btn-sm btn-outline-primary mt-3 btn-icon">
                <iconify-icon icon="mdi:arrow-up-circle" width="16"></iconify-icon> Ver planes
            </a>
            <p class="text-muted small mt-2 mb-0">Los cambios de plan se gestionan manualmente. <a href="https://wa.me/34123456789" target="_blank" rel="noopener">Contactanos por WhatsApp</a> para mejorar tu plan.</p>
        </div>

        <div class="card card-config p-4 mt-4">
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
    </div>
</body>
</html>
