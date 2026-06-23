<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

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