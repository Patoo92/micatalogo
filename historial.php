<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("SELECT * FROM actividad WHERE tienda_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$tienda_id]);
$actividades = $stmt->fetchAll();
?>

<?php require __DIR__ . '/templates/historial_body.php'; ?>