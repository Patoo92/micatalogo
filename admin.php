<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmtTienda = $pdo->prepare("SELECT moneda, tema_admin FROM tiendas WHERE id = ?");
$stmtTienda->execute([$tienda_id]);
$row = $stmtTienda->fetch();
$moneda_tienda = $row['moneda'] ?: '€';
$tema_admin = $row['tema_admin'] ?? 'default';

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Dashboard stats
$stmtTotalProd = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ?");
$stmtTotalProd->execute([$tienda_id]);
$stats_total_productos = (int)$stmtTotalProd->fetchColumn();

$stmtTotalPed = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ?");
$stmtTotalPed->execute([$tienda_id]);
$stats_total_pedidos = (int)$stmtTotalPed->fetchColumn();

$stmtPend = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ? AND estado = 'Pendiente'");
$stmtPend->execute([$tienda_id]);
$stats_pendientes = (int)$stmtPend->fetchColumn();

$stmtBajo = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ? AND stock <= stock_minimo AND stock > 0");
$stmtBajo->execute([$tienda_id]);
$stats_stock_bajo = (int)$stmtBajo->fetchColumn();

$stmtAgot = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ? AND stock = 0");
$stmtAgot->execute([$tienda_id]);
$stats_agotados = (int)$stmtAgot->fetchColumn();

$stmtHoy = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE tienda_id = ? AND DATE(fecha_pedido) = CURDATE()");
$stmtHoy->execute([$tienda_id]);
$stats_pedidos_hoy = (int)$stmtHoy->fetchColumn();

$pagina = max(1, (int)($_GET['p'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ?");
$stmtCount->execute([$tienda_id]);
$total_productos = (int)$stmtCount->fetchColumn();
$total_paginas = max(1, (int)ceil($total_productos / $por_pagina));

$stmt = $pdo->prepare("
    SELECT p.*, c.nombre_categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.tienda_id = ?
    ORDER BY p.id DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute([$tienda_id]);
$productos = $stmt->fetchAll();

$stock_critico = array_filter($productos, fn($p) => $p['stock'] <= $p['stock_minimo']);
$total_critico = count($stock_critico);

$stmtTrial = $pdo->prepare("SELECT trial_ends_at FROM tiendas WHERE id = ?");
$stmtTrial->execute([$tienda_id]);
$trial_ends_at = $stmtTrial->fetchColumn();
?>
<?php require __DIR__ . '/templates/admin_body.php';