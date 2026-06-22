<?php
session_start();
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

$instagram = $_POST['instagram'];
$color = $_POST['color'];
$whatsapp = $_POST['whatsapp'];

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
    $target_dir = "uploads/";
    $logo_url = $target_dir . "logo_" . $tienda_id . "_" . time() . ".png";
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
