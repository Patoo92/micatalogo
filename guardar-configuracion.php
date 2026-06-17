<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];

// 1. Recibir datos del formulario
$instagram = $_POST['instagram'];
$color = $_POST['color'];
$whatsapp = $_POST['whatsapp'];

// 2. Lógica para subir el Logo
$logo_url = null;
if (!empty($_FILES['logo']['name'])) {

    // --- VALIDACIÓN MIME REAL (no confiar en la extensión del nombre) ---
    $TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_real = $finfo->file($_FILES['logo']['tmp_name']);

    if (!in_array($mime_real, $TIPOS_PERMITIDOS)) {
        // Rechazamos el archivo y redirigimos con error
        header("Location: configuracion.php?error=tipo_archivo_no_permitido");
        exit;
    }
    // --- FIN VALIDACIÓN MIME ---

    $target_dir = "uploads/";
    // Forzamos extensión .png independientemente del nombre original
    $logo_url = $target_dir . "logo_" . $tienda_id . "_" . time() . ".png";
    move_uploaded_file($_FILES["logo"]["tmp_name"], $logo_url);
}

// 3. Actualizar la base de datos
$sql = "UPDATE tiendas SET 
            instagram_url = ?, 
            color_tema = ?, 
            whatsapp_number = ?,
            logo_url = COALESCE(?, logo_url) 
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$instagram, $color, $whatsapp, $logo_url, $tienda_id]);

header("Location: configuracion.php?success=1");
exit;
?>