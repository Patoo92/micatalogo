<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('staff_eliminar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para eliminar staff.", "staff.php", "Volver a staff");
}

$staff_id = (int)$_GET['id'];
$tienda_id = $_SESSION['tienda_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        mostrar_error("Solicitud inválida", "Token de seguridad incorrecto.", "staff.php", "Volver a staff");
    }

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
        error_log("Error al eliminar staff: " . $e->getMessage());
        mostrar_error("Error al eliminar", "No se pudo eliminar el staff. Intenta de nuevo.", "staff.php", "Volver a staff");
    }
}

$stmt = $pdo->prepare("SELECT usuario FROM store_staff WHERE id = ? AND tienda_id = ?");
$stmt->execute([$staff_id, $tienda_id]);
$staff = $stmt->fetch();

if (!$staff) {
    mostrar_error("Staff no encontrado", "Este usuario no existe o no pertenece a tu tienda.", "staff.php", "Volver a staff");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;">
    <div class="card p-4 text-center" style="max-width:480px;width:100%;">
        <iconify-icon icon="mdi:account-remove" width="56" style="color: #dc2626;"></iconify-icon>
        <h3 class="fw-bold mt-2">¿Eliminar staff?</h3>
        <p class="text-muted mb-4">Estás a punto de eliminar a <strong><?php echo htmlspecialchars($staff['usuario']); ?></strong></p>
        <p class="text-danger small mb-4">Esta acción no se puede deshacer.</p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="d-flex gap-2">
                <a href="staff.php" class="btn btn-outline-secondary w-50 fw-bold">← Cancelar</a>
                <button type="submit" class="btn btn-danger w-50 fw-bold btn-icon">
                    <iconify-icon icon="mdi:delete" width="18"></iconify-icon> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</body>
</html>
