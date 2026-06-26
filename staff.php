<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("SELECT * FROM store_staff WHERE tienda_id = ? ORDER BY id DESC");
$stmt->execute([$tienda_id]);
$staff = $stmt->fetchAll();
?>

<?php require __DIR__ . '/templates/staff_body.php'; ?>