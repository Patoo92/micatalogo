<?php
session_start();
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

$stmt = $pdo->prepare("
    SELECT p.*, c.nombre_categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.tienda_id = ?
");
$stmt->execute([$tienda_id]);
$productos = $stmt->fetchAll();

$stock_critico = array_filter($productos, fn($p) => $p['stock'] <= $p['stock_minimo']);
$total_critico = count($stock_critico);
?>
<?php require __DIR__ . '/templates/admin_body.php';