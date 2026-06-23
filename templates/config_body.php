<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Configuración de Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        .navbar-admin { background: linear-gradient(135deg, #1e293b, #0f172a) !important; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-config { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04); }
        .form-label-custom { font-weight: 600; font-size: 0.875rem; color: #374151; margin-bottom: 4px; }
        iconify-icon { vertical-align: -2px; display: inline-flex; }
        @media (max-width: 991.98px) {
            .navbar-collapse .d-flex { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .navbar-collapse .d-flex .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#configNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="configNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
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
    </div>
</body>
</html>
