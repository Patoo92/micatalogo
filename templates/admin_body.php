<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel de Administración - <?php echo htmlspecialchars($tienda_nombre); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .navbar-admin { background: linear-gradient(135deg, #1e293b, #0f172a) !important; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-admin { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04); transition: box-shadow 0.2s; }
        .card-admin:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        .table-admin thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .badge-stock { font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 20px; }
        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; padding: 0; }
        iconify-icon { vertical-align: -2px; display: inline-flex; }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        @media (max-width: 991.98px) {
            .navbar-collapse .d-flex { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .navbar-collapse .d-flex .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light">

    <div class="toast-container-custom">
        <div id="flashToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
            <div class="d-flex">
                <div id="flashToastBody" class="toast-body d-flex align-items-center gap-2"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
    <?php if ($flash_message): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('flashToast');
        toastEl.classList.add('text-bg-<?php echo in_array($flash_type, ['success','danger','warning','info']) ? $flash_type : 'info'; ?>');
        document.getElementById('flashToastBody').innerHTML = '<iconify-icon icon="mdi:<?php echo $flash_type === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="20"></iconify-icon> <?php echo js_escape($flash_message); ?>';
        bootstrap.Toast.getOrCreateInstance(toastEl).show();
    });
    <?php endif; ?>
    </script>

    <?php if ($total_critico > 0): ?>
    <div class="container mt-3" style="max-width: 900px;">
        <div class="alert alert-danger d-flex align-items-center justify-content-between py-2 mb-0" style="border-radius: 12px;">
            <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:alert" width="20"></iconify-icon>
                <strong><?php echo $total_critico; ?> producto<?php echo $total_critico > 1 ? 's' : ''; ?> con stock crítico</strong>
            </div>
            <a href="#productos-lista" class="btn btn-sm btn-outline-danger fw-bold">Revisar</a>
        </div>
    </div>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="admin.php">
            <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
            <?php echo htmlspecialchars($tienda_nombre); ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="adminNav">
            <div class="d-flex gap-2 flex-wrap justify-content-end">
                <a href="admin.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                <a href="pedidos.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                <a href="staff.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:account-group" width="16"></iconify-icon> Staff</a>
                <a href="historial.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:history" width="16"></iconify-icon> Historial</a>
                <a href="configuracion.php" class="btn btn-sm btn-primary btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                <a href="backup.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:database-export" width="16"></iconify-icon> Respaldo</a>
                <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
            </div>
        </div>
    </div>
</nav>

    <div class="container my-4" style="max-width: 900px;" id="productos-lista">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0 d-flex align-items-center gap-2"><iconify-icon icon="mdi:package-variant-closed" width="28"></iconify-icon> Inventario</h2>
            </div>
            <a href="nuevo-producto.php" class="btn btn-success fw-bold btn-icon"><iconify-icon icon="mdi:plus" width="18"></iconify-icon> Nuevo Producto</a>
        </div>

        <div class="d-md-none">
    <?php foreach ($productos as $prod): ?>
    <div class="card mb-3 border-0 shadow-sm card-admin">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url'])); ?>" style="width: 60px; height: 60px; object-fit: cover;" class="rounded">
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($prod['precio']); ?> €</small>
                </div>
                <div class="text-end">
                    <div class="badge badge-stock <?php echo ($prod['stock'] <= $prod['stock_minimo']) ? 'bg-danger' : 'bg-success'; ?>">
                        <?php echo $prod['stock']; ?> uds
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <a href="editar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm btn-icon">
                    <iconify-icon icon="mdi:pencil" width="16"></iconify-icon> Editar
                </a>
                <a href="eliminar-producto.php?id=<?php echo $prod['id']; ?>" 
                   class="btn btn-outline-danger w-50 btn-sm btn-icon">
                   <iconify-icon icon="mdi:delete" width="16"></iconify-icon> Eliminar
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

        <div class="card card-admin d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-admin align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th class="text-center">Stock</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url'])); ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($prod['nombre']); ?></div>
                                <small class="text-muted">ID #<?php echo $prod['id']; ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($prod['nombre_categoria'] ?? 'Sin categoría'); ?></span></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($prod['precio']); ?> €</td>
                            <td class="text-center">
                                <span class="badge badge-stock <?php echo ($prod['stock'] <= $prod['stock_minimo']) ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $prod['stock']; ?> uds
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="editar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-secondary btn-action" title="Editar">
                                        <iconify-icon icon="mdi:pencil" width="16"></iconify-icon>
                                    </a>
                                    <a href="eliminar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-danger btn-action" title="Eliminar">
                                        <iconify-icon icon="mdi:delete" width="16"></iconify-icon>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($total_paginas > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination pagination-sm">
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                    <a class="page-link" href="?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</body>
</html>
