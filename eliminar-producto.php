<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('productos_eliminar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para eliminar productos.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$producto_id = (int)$_GET['id'];
$eliminado = false;

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND tienda_id = ?");
$stmt->execute([$producto_id, $tienda_id]);
$producto = $stmt->fetch();

if (!$producto) {
    mostrar_error("Producto no encontrado", "Este producto no existe o no pertenece a tu tienda.", "admin.php", "Volver al panel");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirmar'] ?? '') === 'si') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        mostrar_error("Solicitud inválida", "Token de seguridad incorrecto.", "admin.php", "Volver al panel");
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM productos WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$producto_id, $tienda_id]);

        $u = obtener_usuario_actual();
        registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Eliminó un producto', "ID: $producto_id - " . $producto['nombre']);

        $_SESSION['flash_message'] = 'Producto eliminado correctamente.';
        $_SESSION['flash_type'] = 'success';
        header("Location: admin.php");
        exit;
    } catch (PDOException $e) {
        mostrar_error("Error al eliminar", "No se pudo eliminar el producto. Intenta de nuevo.", "admin.php", "Volver");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Eliminar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;">
    <div class="card p-4 text-center" style="max-width:480px;width:100%;">
        <iconify-icon icon="mdi:alert" width="56" style="color: #dc2626;"></iconify-icon>
        <h3 class="fw-bold mt-2">¿Eliminar producto?</h3>
        <p class="text-muted mb-1">Estás a punto de eliminar:</p>
        <p class="fw-bold fs-5 mb-1"><?php echo htmlspecialchars($producto['nombre']); ?></p>
        <p class="text-muted mb-4"><?php echo number_format($producto['precio'], 2); ?> € · Stock: <?php echo $producto['stock']; ?> uds</p>
        <p class="text-danger small mb-4">Esta acción no se puede deshacer.</p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="confirmar" value="si">
            <div class="d-flex gap-2">
                <a href="admin.php" class="btn btn-outline-secondary w-50 fw-bold">← Cancelar</a>
                <button type="submit" class="btn btn-danger w-50 fw-bold btn-icon">
                    <iconify-icon icon="mdi:delete" width="18"></iconify-icon> Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</body>
</html>
