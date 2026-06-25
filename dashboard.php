<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmtMoneda = $pdo->prepare("SELECT moneda, tema_admin FROM tiendas WHERE id = ?");
$stmtMoneda->execute([$tienda_id]);
$row = $stmtMoneda->fetch();
$moneda_tienda = $row['moneda'] ?: '€';
$tema_admin = $row['tema_admin'] ?? 'default';

$stats = [];
$s = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ?"); $s->execute([$tienda_id]); $stats['total_productos'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ?"); $s->execute([$tienda_id]); $stats['total_pedidos'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ? AND estado = 'Pendiente'"); $s->execute([$tienda_id]); $stats['pendientes'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ? AND stock <= stock_minimo AND stock > 0"); $s->execute([$tienda_id]); $stats['stock_bajo'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ? AND stock = 0"); $s->execute([$tienda_id]); $stats['agotados'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ? AND DATE(fecha_pedido) = CURDATE()"); $s->execute([$tienda_id]); $stats['hoy'] = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ? AND fecha_pedido >= DATE_SUB(NOW(), INTERVAL 7 DAY)"); $s->execute([$tienda_id]); $stats['semana'] = (int)$s->fetchColumn();

// Últimos 7 días: pedidos por día
$s = $pdo->prepare("SELECT DATE(fecha_pedido) AS dia, COUNT(*) AS total FROM pedidos WHERE tienda_id = ? AND fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(fecha_pedido) ORDER BY dia");
$s->execute([$tienda_id]);
$pedidos_por_dia = [];
$fechas = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $fechas[] = $d;
    $pedidos_por_dia[$d] = 0;
}
foreach ($s->fetchAll() as $row) {
    $pedidos_por_dia[$row['dia']] = (int)$row['total'];
}
$labels_dias = json_encode(array_map(fn($d) => date('d/m', strtotime($d)), $fechas));
$data_pedidos = json_encode(array_values(array_merge(array_intersect_key($pedidos_por_dia, array_flip($fechas)))));

// Productos por categoría
$s = $pdo->prepare("SELECT c.nombre_categoria, COUNT(p.id) AS total FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.tienda_id = ? GROUP BY p.categoria_id ORDER BY total DESC LIMIT 10");
$s->execute([$tienda_id]);
$cats_labels = []; $cats_data = [];
foreach ($s->fetchAll() as $row) {
    $cats_labels[] = $row['nombre_categoria'] ?? 'Sin categoría';
    $cats_data[] = (int)$row['total'];
}
$cats_labels_json = json_encode($cats_labels);
$cats_data_json = json_encode($cats_data);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - <?php echo htmlspecialchars($tienda_nombre); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .chart-card { border: none; border-radius: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 1.25rem; height: 100%; }
        .stat-card { border: none; border-radius: 16px; padding: 1.25rem; height: 100%; }
    </style>
</head>
<body class="bg-admin sidebar-open <?php echo htmlspecialchars($tema_admin !== 'default' ? 'theme-' . $tema_admin : ''); ?>">
    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>
    <?php require __DIR__ . '/templates/toast_partial.php'; ?>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:chart-bar" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#dashNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="dashNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-primary btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-4" style="max-width: 1000px;">
        <h2 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2"><iconify-icon icon="mdi:chart-bar" width="28"></iconify-icon> Dashboard</h2>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card bg-primary bg-opacity-10 text-center">
                    <div class="fw-bold fs-3 text-primary"><?php echo $stats['total_productos']; ?></div>
                    <small class="text-muted">Productos</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card bg-success bg-opacity-10 text-center">
                    <div class="fw-bold fs-3 text-success"><?php echo $stats['total_pedidos']; ?></div>
                    <small class="text-muted">Total pedidos</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card <?php echo $stats['pendientes'] > 0 ? 'bg-warning bg-opacity-10' : 'bg-success bg-opacity-10'; ?> text-center">
                    <div class="fw-bold fs-3 <?php echo $stats['pendientes'] > 0 ? 'text-warning' : 'text-success'; ?>"><?php echo $stats['pendientes']; ?></div>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card <?php echo $stats['stock_bajo'] + $stats['agotados'] > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10'; ?> text-center">
                    <div class="fw-bold fs-3 <?php echo $stats['stock_bajo'] + $stats['agotados'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $stats['stock_bajo']; ?> / <?php echo $stats['agotados']; ?></div>
                    <small class="text-muted">Stock bajo / Agotados</small>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="stat-card bg-info bg-opacity-10 d-flex align-items-center gap-3">
                    <iconify-icon icon="mdi:cart" width="36" style="color: #0dcaf0;"></iconify-icon>
                    <div>
                        <div class="fw-bold fs-4"><?php echo $stats['hoy']; ?> pedidos hoy</div>
                        <small class="text-muted"><?php echo date('d/m/Y'); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card bg-success bg-opacity-10 d-flex align-items-center gap-3">
                    <iconify-icon icon="mdi:calendar-clock" width="36" style="color: #198754;"></iconify-icon>
                    <div>
                        <div class="fw-bold fs-4"><?php echo $stats['semana']; ?> esta semana</div>
                        <small class="text-muted">últimos 7 días</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-7">
                <div class="chart-card">
                    <h6 class="fw-bold mb-3">Pedidos últimos 7 días</h6>
                    <canvas id="chartPedidos" height="220"></canvas>
                </div>
            </div>
            <div class="col-md-5">
                <div class="chart-card">
                    <h6 class="fw-bold mb-3">Productos por categoría</h6>
                    <canvas id="chartCategorias" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
    document.addEventListener('DOMContentLoaded', function() {
        new Chart(document.getElementById('chartPedidos'), {
            type: 'bar',
            data: {
                labels: <?php echo $labels_dias; ?>,
                datasets: [{ label: 'Pedidos', data: <?php echo $data_pedidos; ?>, backgroundColor: '#3b82f6', borderRadius: 6 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
        new Chart(document.getElementById('chartCategorias'), {
            type: 'doughnut',
            data: {
                labels: <?php echo $cats_labels_json; ?>,
                datasets: [{ data: <?php echo $cats_data_json; ?>, backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#06b6d4','#84cc16','#f97316','#6366f1'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
        });
    });
    </script>

    <script nonce="<?= $csp_nonce ?>">
    (function() {
        if (localStorage.getItem('dark_mode') === '1') document.body.classList.add('dark-mode');
        var toggle = document.getElementById('darkModeToggle');
        var icon = toggle && toggle.querySelector('iconify-icon');
        var span = toggle && toggle.querySelector('span');
        if (localStorage.getItem('dark_mode') === '1') { if (icon) icon.setAttribute('icon', 'mdi:weather-sunny'); if (span) span.textContent = 'Modo claro'; }
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('dark-mode');
                var isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('dark_mode', isDark ? '1' : '0');
                if (icon) icon.setAttribute('icon', isDark ? 'mdi:weather-sunny' : 'mdi:weather-night');
                if (span) span.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
            });
        }
    })();
    </script>
</body>
</html>
