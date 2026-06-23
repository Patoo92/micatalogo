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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_api_key'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } else {
        $nueva_key = 'mca_' . bin2hex(random_bytes(32));
        $nombre_key = trim($_POST['nombre_key'] ?? 'API v1');
        $stmt = $pdo->prepare("INSERT INTO api_keys (tienda_id, api_key, nombre) VALUES (?, ?, ?)");
        $stmt->execute([$tienda_id, $nueva_key, $nombre_key]);
        $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> API Key generada. <strong>Cópiala ahora</strong>, no se mostrará de nuevo:<br><code style="user-select:all;background:#1e293b;color:#10b981;padding:6px 10px;border-radius:4px;display:inline-block;margin-top:6px;font-size:0.85rem;">' . htmlspecialchars($nueva_key) . '</code></div>';
    }
}

if (isset($_POST['revocar_api_key']) && isset($_POST['key_id'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } else {
        $key_id = (int)$_POST['key_id'];
        $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$key_id, $tienda_id]);
        $mensaje = '<div class="alert alert-warning d-flex align-items-center gap-2"><iconify-icon icon="mdi:key-off" width="20"></iconify-icon> API Key revocada.</div>';
    }
}

if (isset($_GET['success'])) {
    $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> Configuración guardada correctamente.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'tipo_archivo_no_permitido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solo se permiten imágenes JPG, PNG, GIF o WEBP.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'logo_grande') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El logo no puede superar los 2 MB.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'permiso') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:lock" width="20"></iconify-icon> No tienes permiso para editar la configuración.</div>';
}

$stmtKeys = $pdo->prepare("SELECT id, api_key, nombre, activo, created_at FROM api_keys WHERE tienda_id = ? ORDER BY created_at DESC");
$stmtKeys->execute([$tienda_id]);
$api_keys = $stmtKeys->fetchAll();
?>
<?php require __DIR__ . '/templates/config_body.php';
