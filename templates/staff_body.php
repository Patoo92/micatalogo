<!DOCTYPE html>
<html lang="es">
<?php $page_title = 'Gestión de Staff'; ?>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>

    <?php require __DIR__ . '/sidebar_partial.php'; ?>
    <div class="page-wrapper">

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#staffNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="staffNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4" style="max-width: 900px;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Staff</h3>
                <a href="staff-nuevo.php" class="btn btn-success btn-icon">
                    <iconify-icon icon="mdi:plus" width="18"></iconify-icon> Nuevo Staff
                </a>
            </div>
            <div class="card-body">

        <?php if (empty($staff)): ?>
                <div class="empty">
                    <div class="empty-icon"><iconify-icon icon="mdi:account-off" width="48" style="color: #94a3b8;"></iconify-icon></div>
                    <p class="empty-title">No hay miembros de staff. Crea el primero.</p>
                </div>
        <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter align-middle">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Permisos</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $s): 
                                $permisos = json_decode($s['permisos'], true) ?? [];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($s['usuario']); ?></div>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($s['email'] ?? '—'); ?></td>
                                <td>
                                    <?php if ($permisos): ?>
                                        <?php foreach (['productos_crear', 'productos_editar', 'productos_eliminar', 'pedidos_gestionar', 'configuracion_editar'] as $p): ?>
                                            <span class="badge-perm <?php echo !empty($permisos[$p]) ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo !empty($permisos[$p]) ? '?' : '?'; ?> <?php echo str_replace('_', ' ', $p); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin permisos</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="staff-editar.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-secondary btn-action-sm" title="Editar">
                                        <iconify-icon icon="mdi:pencil" width="16"></iconify-icon>
                                    </a>
                                    <a href="staff-eliminar.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger btn-action-sm" title="Eliminar">
                                        <iconify-icon icon="mdi:delete" width="16"></iconify-icon>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/darkmode_admin_partial.php'; ?>
    </div>
</body>
</html>