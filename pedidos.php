<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('pedidos_ver')) {
    mostrar_error("Acceso denegado", "No tienes permiso para ver pedidos.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("
    SELECT p.*, prod.nombre AS producto_nombre, prod.precio 
    FROM pedidos p 
    JOIN productos prod ON p.producto_id = prod.id 
    WHERE p.tienda_id = ? 
    ORDER BY p.fecha_pedido DESC
");
$stmt->execute([$tienda_id]);
$pedidos = $stmt->fetchAll();
?>
<?php require __DIR__ . '/templates/pedidos_body.php';