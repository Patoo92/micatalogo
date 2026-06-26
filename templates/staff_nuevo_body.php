<!DOCTYPE html>
<html lang="es">
<?php $page_title = 'Nuevo Staff'; ?>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>

    <?php require __DIR__ . '/sidebar_partial.php'; ?>
    <div class="page-wrapper">
    <?php require __DIR__ . '/toast_partial.php'; ?>

    <?php if ($exito): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($exito); ?>, 'success'); });</script>
    <?php elseif ($error): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($error); ?>, 'danger'); });</script>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#staffNuevoNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="staffNuevoNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 600px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title d-flex align-items-center gap-2"><iconify-icon icon="mdi:account-plus" width="24"></iconify-icon> Nuevo Staff</h3>
            </div>
            <div class="card-body">

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Usuario *</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" class="form-control" minlength="10" required>
                    <div class="form-text">Mínimo 10 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <h6 class="fw-bold mb-2">Permisos</h6>
                <div class="card bg-light border-0 p-3 mb-4">
                    <?php $checks = [
                        'productos_ver' => 'Ver productos',
                        'productos_crear' => 'Crear productos',
                        'productos_editar' => 'Editar productos',
                        'productos_eliminar' => 'Eliminar productos',
                        'pedidos_ver' => 'Ver pedidos',
                        'pedidos_gestionar' => 'Gestionar pedidos (marcar como vendido)',
                        'configuracion_editar' => 'Editar configuración',
                    ]; ?>
                    <?php foreach ($checks as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="perm_<?php echo $key; ?>" id="perm_<?php echo $key; ?>">
                            <label class="form-check-label" for="perm_<?php echo $key; ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-footer d-flex gap-2">
                    <a href="staff.php" class="btn btn-outline-secondary w-50">← Volver</a>
                    <button type="submit" class="btn btn-primary w-50" data-loading="Guardando…">
                        <iconify-icon icon="mdi:account-plus" width="18"></iconify-icon> Crear Staff
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
    <?php require __DIR__ . '/darkmode_admin_partial.php'; ?>
    </div>
</body>
</html>
