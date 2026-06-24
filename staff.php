<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("SELECT * FROM store_staff WHERE tienda_id = ? ORDER BY id DESC");
$stmt->execute([$tienda_id]);
$staff = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .table-custom thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
        .badge-perm { font-size: 0.7rem; font-weight: 600; padding: 2px 8px; border-radius: 12px; }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .btn-action-sm { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; padding: 0; }
    </style>
</head>
<body class="bg-admin sidebar-open">

    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm d-lg-none">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-dark m-0 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:account-group" width="28"></iconify-icon> Staff
            </h2>
            <a href="staff-nuevo.php" class="btn btn-success fw-bold btn-icon">
                <iconify-icon icon="mdi:plus" width="18"></iconify-icon> Nuevo Staff
            </a>
        </div>

        <?php if (empty($staff)): ?>
            <div class="card card-custom glass-card p-5 text-center">
                <iconify-icon icon="mdi:account-off" width="48" style="color: #94a3b8;"></iconify-icon>
                <p class="text-muted mt-2 mb-0">No hay miembros de staff. Crea el primero.</p>
            </div>
        <?php else: ?>
            <div class="card card-custom glass-card">
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
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
                                    <div class="fw-bold"><?php echo htmlspecialchars($s['usuario']); ?></div>
                                </td>
                                <td class="text-muted"><?php echo htmlspecialchars($s['email'] ?? '—'); ?></td>
                                <td>
                                    <?php if ($permisos): ?>
                                        <?php foreach (['productos_crear', 'productos_editar', 'productos_eliminar', 'pedidos_gestionar', 'configuracion_editar'] as $p): ?>
                                            <span class="badge-perm <?php echo !empty($permisos[$p]) ? 'bg-success text-white' : 'bg-secondary text-white'; ?>">
                                                <?php echo !empty($permisos[$p]) ? '✓' : '✗'; ?> <?php echo str_replace('_', ' ', $p); ?>
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
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
