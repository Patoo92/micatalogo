<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('configuracion_editar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para editar la configuración.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ?");
$stmt->execute([$_SESSION['tienda_id']]);
$tienda = $stmt->fetch();

if (!$tienda) {
    mostrar_error("Tienda no encontrada", "La tienda no existe.", "admin.php", "Volver al panel");
}

$mensaje = '';
if (isset($_GET['success'])) {
    $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> Configuración guardada correctamente.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'tipo_archivo_no_permitido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solo se permiten imágenes JPG, PNG, GIF o WEBP.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'logo_grande') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El logo no puede superar los 2 MB.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'permiso') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:lock" width="20"></iconify-icon> No tienes permiso para editar la configuración.</div>';
}
?>
<?php require __DIR__ . '/templates/config_body.php';
