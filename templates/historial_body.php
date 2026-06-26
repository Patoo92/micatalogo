<!DOCTYPE html>
<html lang="es">
<?php $page_title = 'Historial de Actividad'; ?>
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
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#historialNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="historialNav">
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
            <div class="card-header">
                <h3 class="card-title">Historial de Actividad</h3>
            </div>
            <div class="card-body">

        <?php if (empty($actividades)): ?>
                <div class="empty">
                    <div class="empty-icon"><iconify-icon icon="mdi:history" width="48" style="color: #94a3b8;"></iconify-icon></div>
                    <p class="empty-title">Aún no hay actividad registrada.</p>
                </div>
        <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Acción</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($actividades as $a): ?>
                            <tr>
                                <td class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                                    <?php echo date('d/m/Y H:i', strtotime($a['created_at'])); ?>
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($a['usuario_nombre']); ?></td>
                                <td>
                                    <?php if ($a['usuario_tipo'] === 'owner'): ?>
                                        <span class="badge bg-primary">Dueño</span>
                                    <?php elseif ($a['usuario_tipo'] === 'staff'): ?>
                                        <span class="badge bg-info text-dark">Staff</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark">Superadmin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($a['accion']); ?></td>
                                <td class="text-muted" style="font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($a['detalle'] ?? '—'); ?>
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
