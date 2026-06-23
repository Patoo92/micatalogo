<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('configuracion_editar')) {
    header("Location: configuracion.php?error=permiso");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];

if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    header("Location: configuracion.php?error=csrf");
    exit;
}

$instagram = trim($_POST['instagram'] ?? '');
$color = trim($_POST['color'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');

if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#0d6efd';
}
if (!preg_match('/^\+?[0-9]{7,15}$/', $whatsapp)) {
    header("Location: configuracion.php?error=whatsapp_invalido");
    exit;
}
if (!empty($instagram) && !preg_match('/^[a-zA-Z0-9_.]+$/', $instagram)) {
    header("Location: configuracion.php?error=instagram_invalido");
    exit;
}

$logo_url = null;
if (!empty($_FILES['logo']['name'])) {
    if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
        header("Location: configuracion.php?error=logo_grande");
        exit;
    }
    $TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_real = $finfo->file($_FILES['logo']['tmp_name']);

    if (!in_array($mime_real, $TIPOS_PERMITIDOS)) {
        header("Location: configuracion.php?error=tipo_archivo_no_permitido");
        exit;
    }
    $EXT_POR_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $EXT_POR_MIME[$mime_real] ?? 'png';
    $target_dir = "uploads/";
    $logo_url = $target_dir . "logo_" . $tienda_id . "_" . time() . "." . $ext;
    move_uploaded_file($_FILES["logo"]["tmp_name"], $logo_url);
}

$sql = "UPDATE tiendas SET 
            instagram_url     = ?, 
            color_tema        = ?, 
            telefono_whatsapp = ?,
            logo_url          = COALESCE(?, logo_url) 
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$instagram, $color, $whatsapp, $logo_url, $tienda_id]);

$u = obtener_usuario_actual();
registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Actualizó la configuración de la tienda');

header("Location: configuracion.php?success=1");
exit;
?>
