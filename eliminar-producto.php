<?php
session_start();
require_once 'conexion.php';

// Control de seguridad: si no está logueado o no viene un ID, fuera
if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$producto_id = (int)$_GET['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    // Borramos el producto, pero asegurándonos de que pertenezca a la tienda del usuario logueado
    $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$producto_id, $tienda_id]);

    // Lo mandamos de vuelta al panel con los cambios aplicados
    header("Location: admin.php");
    exit;

} catch (\PDOException $e) {
    die("Error al eliminar el producto: " . $e->getMessage());
}