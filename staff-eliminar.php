<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$staff_id = (int)$_GET['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    $stmt = $pdo->prepare("SELECT usuario FROM store_staff WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$staff_id, $tienda_id]);
    $staff = $stmt->fetch();

    if ($staff) {
        $stmt = $pdo->prepare("DELETE FROM store_staff WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$staff_id, $tienda_id]);

        $u = obtener_usuario_actual();
        registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Eliminó un miembro del staff', "Usuario: " . $staff['usuario']);

        $_SESSION['flash_message'] = 'Staff eliminado correctamente.';
        $_SESSION['flash_type'] = 'success';
    }

    header("Location: staff.php");
    exit;

} catch (PDOException $e) {
    mostrar_error("Error al eliminar", "No se pudo eliminar el staff. Intenta de nuevo.", "staff.php", "Volver a staff");
}
