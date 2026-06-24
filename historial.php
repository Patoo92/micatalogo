<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("SELECT * FROM actividad WHERE tienda_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$tienda_id]);
$actividades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Historial de Actividad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .table-custom thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .timeline-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    </style>
</head>
<body class="bg-light sidebar-open">

    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm d-lg-none">
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
        <div class="d-flex align-items-center gap-2 mb-4">
            <iconify-icon icon="mdi:history" width="32"></iconify-icon>
            <div>
                <h2 class="fw-bold text-dark m-0">Historial de Actividad</h2>
                <p class="text-muted mb-0">Últimas 100 acciones registradas en tu tienda.</p>
            </div>
        </div>

        <?php if (empty($actividades)): ?>
            <div class="card card-custom glass-card p-5 text-center">
                <iconify-icon icon="mdi:history" width="48" style="color: #94a3b8;"></iconify-icon>
                <p class="text-muted mt-2 mb-0">Aún no hay actividad registrada.</p>
            </div>
        <?php else: ?>
            <div class="card card-custom glass-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
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
                                <td class="fw-bold"><?php echo htmlspecialchars($a['usuario_nombre']); ?></td>
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
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
