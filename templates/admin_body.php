<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel de Administración - <?php echo htmlspecialchars($tienda_nombre); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js" nonce="<?= $csp_nonce ?>"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>

    <?php require __DIR__ . '/sidebar_partial.php'; ?>
    <?php require __DIR__ . '/toast_partial.php'; ?>
    <div class="page-wrapper">

    <?php if ($flash_message): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($flash_message); ?>, '<?php echo in_array($flash_type, ['success','danger','warning','info']) ? $flash_type : 'info'; ?>'); });</script>
    <?php endif; ?>

    <?php if ($total_critico > 0): ?>
    <div class="container mt-3">
        <div class="alert alert-danger d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:alert" width="20"></iconify-icon>
                <strong><?php echo $total_critico; ?> producto<?php echo $total_critico > 1 ? 's' : ''; ?> con stock crítico</strong>
            </div>
            <a href="#productos-lista" class="btn btn-sm btn-outline-danger fw-bold">Revisar</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($trial_ends_at) && $trial_ends_at >= date('Y-m-d')): 
        $dias_restantes = (strtotime($trial_ends_at) - time()) / 86400;
        $dias_mostrar = max(0, (int)ceil($dias_restantes));
        $trial_tipo = $dias_mostrar <= 1 ? 'danger' : ($dias_mostrar <= 2 ? 'warning' : 'info');
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $trial_tipo; ?> d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:clock-outline" width="18"></iconify-icon>
                <strong>Período de prueba:</strong> te quedan <strong><?php echo $dias_mostrar; ?> día<?php echo $dias_mostrar !== 1 ? 's' : ''; ?></strong> de prueba.
                <?php if ($_SESSION['plan'] !== 'starter'): ?>
                Al finalizar pasarás al plan Starter.
                <?php endif; ?>
            </div>
            <a href="configuracion.php#plan" class="btn btn-sm btn-outline-<?php echo $trial_tipo; ?> fw-bold">Ver detalles del plan</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="container mt-3">
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center h-100">
                    <div class="fw-bold fs-4 text-primary"><?php echo $stats_total_productos; ?></div>
                    <small class="text-muted">Productos</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center h-100">
                    <div class="fw-bold fs-4 text-success"><?php echo $stats_total_pedidos; ?></div>
                    <small class="text-muted">Pedidos totales</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center h-100">
                    <div class="fw-bold fs-4 <?php echo $stats_pendientes > 0 ? 'text-warning' : 'text-success'; ?>"><?php echo $stats_pendientes; ?></div>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center h-100">
                    <div class="fw-bold fs-4 <?php echo $stats_stock_bajo + $stats_agotados > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $stats_stock_bajo; ?> / <?php echo $stats_agotados; ?></div>
                    <small class="text-muted">Stock bajo / Agotados</small>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4" style="max-width: 900px;" id="productos-lista">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0 d-flex align-items-center gap-2"><iconify-icon icon="mdi:package-variant-closed" width="28"></iconify-icon> Inventario</h2>
            </div>
            <div class="d-flex gap-2">
                <a href="exportar-productos.php" class="btn btn-outline-success fw-bold btn-icon btn-sm d-none d-md-inline-flex"><iconify-icon icon="mdi:file-download" width="16"></iconify-icon> Exportar</a>
                <a href="importar-productos.php" class="btn btn-outline-info fw-bold btn-icon btn-sm d-none d-md-inline-flex"><iconify-icon icon="mdi:file-upload" width="16"></iconify-icon> Importar</a>
                <a href="nuevo-producto.php" class="btn btn-success fw-bold btn-icon"><iconify-icon icon="mdi:plus" width="18"></iconify-icon> Nuevo Producto</a>
            </div>
        </div>

        <?php if (count($productos) === 0): ?>
        <div class="text-center py-5 empty-state">
            <iconify-icon icon="mdi:package-variant-closed" width="48"></iconify-icon>
            <p>No hay productos todavía. ¡Creá el primero!</p>
            <a href="nuevo-producto.php" class="btn btn-success fw-bold btn-icon mt-2"><iconify-icon icon="mdi:plus" width="18"></iconify-icon> Nuevo Producto</a>
        </div>
        <?php else: ?>

        <div class="d-md-none">
    <?php foreach ($productos as $prod): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url'])); ?>" style="width: 60px; height: 60px; object-fit: cover;" class="rounded">
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($prod['precio']); ?> <?php echo htmlspecialchars($moneda_tienda ?? '€'); ?></small>
                    <?php if (!empty($prod['nombre_categoria'])): ?>
                    <br><span class="badge bg-secondary mt-1"><?php echo htmlspecialchars($prod['nombre_categoria']); ?></span>
                    <?php endif; ?>
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

        <div class="card d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-vcenter align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Destacado</th>
                            <th>Etiqueta</th>
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
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($prod['precio']); ?> <?php echo htmlspecialchars($moneda_tienda ?? '€'); ?></td>
                            <td class="text-center">
                                <span class="badge badge-stock <?php echo ($prod['stock'] <= $prod['stock_minimo']) ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $prod['stock']; ?> uds
                                </span>
                            </td>
                            <td class="text-center"><?php echo !empty($prod['destacado']) ? '<iconify-icon icon="mdi:star" width="18" style="color:#f59e0b;"></iconify-icon>' : ''; ?></td>
                            <td><?php echo !empty($prod['etiqueta']) ? '<span class="badge" style="background:' . ($prod['etiqueta'] === 'Oferta' ? '#ef4444' : ($prod['etiqueta'] === 'Nuevo' ? '#10b981' : '#64748b')) . ';">' . htmlspecialchars($prod['etiqueta']) . '</span>' : ''; ?></td>
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
        <?php endif; ?>

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
</div>
    <script nonce="<?= $csp_nonce ?>">
    (function() {
        var html = document.documentElement;
        var toggle = document.getElementById('darkModeToggle');
        var icon = toggle && toggle.querySelector('iconify-icon');
        var span = toggle && toggle.querySelector('.nav-link-title');
        if (localStorage.getItem('dark_mode') === '1') {
            html.setAttribute('data-bs-theme', 'dark');
            if (icon) icon.setAttribute('icon', 'mdi:weather-sunny');
            if (span) span.textContent = 'Modo claro';
        }
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var isDark = html.getAttribute('data-bs-theme') === 'dark';
                if (isDark) {
                    html.removeAttribute('data-bs-theme');
                } else {
                    html.setAttribute('data-bs-theme', 'dark');
                }
                localStorage.setItem('dark_mode', html.getAttribute('data-bs-theme') === 'dark' ? '1' : '0');
                if (icon) icon.setAttribute('icon', html.getAttribute('data-bs-theme') === 'dark' ? 'mdi:weather-sunny' : 'mdi:weather-night');
                if (span) span.textContent = html.getAttribute('data-bs-theme') === 'dark' ? 'Modo claro' : 'Modo oscuro';
            });
        }
    })();
    </script>
</body>
</html>
