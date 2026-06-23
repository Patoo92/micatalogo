<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Pedidos - <?php echo htmlspecialchars($tienda_nombre); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .navbar-admin { background: linear-gradient(135deg, #1e293b, #0f172a) !important; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-orders { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04); }
        .table-orders thead th { background: #f8fafc !important; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .badge-status { font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
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
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#pedidosNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="pedidosNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-primary btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Ver Pedidos</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 800px;">
        
        <div class="mb-4 d-flex align-items-center gap-2">
            <iconify-icon icon="mdi:format-list-bulleted" width="32"></iconify-icon>
            <div>
                <h2 class="fw-bold text-dark mb-0">Control de Pedidos</h2>
                <p class="text-muted mb-0">Monitorea los clientes que iniciaron contacto para comprar</p>
            </div>
        </div>

        <div class="card card-orders">
            <div class="table-responsive">
                <table class="table table-orders align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID Pedido</th>
                            <th>Cliente</th>
                            <th>Producto Solicitado</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay registros de pedidos aún.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $ped): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $ped['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($ped['nombre_cliente']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($ped['producto_nombre']); ?></div>
                                        <small class="text-success fw-bold"><?php echo htmlspecialchars($ped['precio']); ?> €</small>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($ped['fecha_pedido'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <span class="badge bg-warning text-dark badge-status">
                                                <iconify-icon icon="mdi:clock-outline" width="14"></iconify-icon> Pendiente
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success badge-status">
                                                <iconify-icon icon="mdi:check-circle" width="14"></iconify-icon> Vendido
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <form method="POST" action="completar-pedido.php" class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $ped['id']; ?>">
                                                <?php echo csrf_field(); ?>
                                                <button type="submit" class="btn btn-success btn-sm fw-bold btn-icon">
                                                    <iconify-icon icon="mdi:cash" width="16"></iconify-icon> Marcar como Vendido
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="badge bg-secondary badge-status"><iconify-icon icon="mdi:check" width="14"></iconify-icon> Venta cerrada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
