<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Pedidos - <?php echo htmlspecialchars($tienda_nombre); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .table-orders thead th { background: #f8fafc !important; color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        @media (max-width: 991.98px) {
            .navbar-collapse .d-flex { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .navbar-collapse .d-flex .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light">

    <?php require __DIR__ . '/toast_partial.php'; ?>

    <?php if ($flash_message): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($flash_message); ?>, '<?php echo in_array($flash_type, ['success','danger','warning','info']) ? $flash_type : 'info'; ?>'); });</script>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#pedidosNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="pedidosNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
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
                <p class="text-muted mb-0">Pedidos agrupados por cliente. Cada línea es un producto solicitado.</p>
            </div>
        </div>

        <?php if (empty($pedidos)): ?>
            <div class="text-center py-5 empty-state">
                <iconify-icon icon="mdi:package-variant-closed" width="48"></iconify-icon>
                <p>No hay registros de pedidos aún.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pedidos as $grupo): 
                $tiene_pendientes = $grupo['pendientes'] > 0;
                $todos_vendidos = $grupo['pendientes'] === 0 && count(array_filter($grupo['ids_estados'], fn($e) => $e === 'Vendido')) === count($grupo['ids_estados']);
                $todos_cancelados = count(array_filter($grupo['ids_estados'], fn($e) => $e === 'Cancelado')) === count($grupo['ids_estados']);
                $grupo_id = 'pedido-' . md5($grupo['fecha_agrupada']);
                $items_count = count($grupo['items']);
            ?>
            <div class="card card-orders mb-3">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 px-4 grupo-toggle" style="cursor:pointer;border-bottom:1px solid #e2e8f0;" data-grupo="<?php echo $grupo_id; ?>">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <strong class="text-dark"><?php echo htmlspecialchars($grupo['nombre_cliente']); ?></strong>
                            <?php if ($grupo['email_cliente']): ?>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($grupo['email_cliente']); ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-secondary badge-status"><?php echo $items_count; ?> producto<?php echo $items_count !== 1 ? 's' : ''; ?></span>
                        <?php if ($todos_vendidos): ?>
                            <span class="badge bg-success badge-status"><iconify-icon icon="mdi:check-circle" width="14"></iconify-icon> Vendido</span>
                        <?php elseif ($todos_cancelados): ?>
                            <span class="badge bg-danger badge-status"><iconify-icon icon="mdi:cancel" width="14"></iconify-icon> Cancelado</span>
                        <?php elseif ($tiene_pendientes): ?>
                            <span class="badge bg-warning text-dark badge-status"><iconify-icon icon="mdi:clock-outline" width="14"></iconify-icon> Pendiente (<?php echo $grupo['pendientes']; ?>)</span>
                        <?php endif; ?>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($grupo['fecha_pedido'])); ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <strong class="text-success"><?php echo number_format($grupo['total'], 2); ?> €</strong>
                        <iconify-icon icon="mdi:chevron-down" width="20" id="icon-<?php echo $grupo_id; ?>" style="transition:transform 0.2s;color:#94a3b8;"></iconify-icon>
                    </div>
                </div>
                <div id="<?php echo $grupo_id; ?>" class="collapse">
                    <div class="table-responsive">
                        <table class="table table-orders align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grupo['items'] as $ped): ?>
                                <tr>
                                    <td class="fw-bold" style="color:#94a3b8;font-size:0.85rem;">#<?php echo $ped['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($ped['producto_nombre']); ?></div>
                                    </td>
                                    <td class="text-success fw-bold"><?php echo htmlspecialchars($ped['precio']); ?> €</td>
                                    <td>
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <span class="badge bg-warning text-dark badge-status"><iconify-icon icon="mdi:clock-outline" width="14"></iconify-icon> Pendiente</span>
                                        <?php elseif ($ped['estado'] === 'Cancelado'): ?>
                                            <span class="badge bg-danger badge-status"><iconify-icon icon="mdi:cancel" width="14"></iconify-icon> Cancelado</span>
                                        <?php else: ?>
                                            <span class="badge bg-success badge-status"><iconify-icon icon="mdi:check-circle" width="14"></iconify-icon> Vendido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <div class="d-flex gap-1 justify-content-end">
                                                <form method="POST" action="completar-pedido.php" class="d-inline">
                                                    <input type="hidden" name="id" value="<?php echo $ped['id']; ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-success btn-sm btn-icon">
                                                        <iconify-icon icon="mdi:cash" width="14"></iconify-icon> Vender
                                                    </button>
                                                </form>
                                                <form method="POST" action="cancelar-pedido.php" class="d-inline form-confirm" data-confirm="¿Cancelar este producto? El stock se restituirá.">
                                                    <input type="hidden" name="id" value="<?php echo $ped['id']; ?>">
                                                    <?php echo csrf_field(); ?>
                                                    <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Cancelar">
                                                        <iconify-icon icon="mdi:cancel" width="14"></iconify-icon>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($ped['estado'] === 'Cancelado'): ?>
                                            <span class="badge bg-secondary badge-status">Cancelado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary badge-status">Vendido</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <script nonce="<?= $csp_nonce ?>">
    document.querySelectorAll('.grupo-toggle').forEach(function(header) {
        header.addEventListener('click', function() {
            var id = this.dataset.grupo;
            var el = document.getElementById(id);
            var icon = document.getElementById('icon-' + id);
            if (!el) return;
            var bs = new bootstrap.Collapse(el, { toggle: true });
            el.addEventListener('hidden.bs.collapse', function() {
                if (icon) icon.style.transform = 'rotate(0deg)';
                sessionStorage.removeItem('expanded_' + id);
            }, { once: true });
            el.addEventListener('shown.bs.collapse', function() {
                if (icon) icon.style.transform = 'rotate(180deg)';
                sessionStorage.setItem('expanded_' + id, '1');
            }, { once: true });
        });
    });
    document.querySelectorAll('.form-confirm').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) {
                e.preventDefault();
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.grupo-toggle').forEach(function(header) {
            var id = header.dataset.grupo;
            if (sessionStorage.getItem('expanded_' + id)) {
                var el = document.getElementById(id);
                var icon = document.getElementById('icon-' + id);
                if (el) {
                    new bootstrap.Collapse(el, { show: true });
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            }
        });
    });
    </script>
</body>
</html>
