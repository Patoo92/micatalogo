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
$whatsapp = $_POST['whatsapp']; // Nueva variable

// 2. Lógica para subir el Logo
$logo_url = null;
if (!empty($_FILES['logo']['name'])) {
    $target_dir = "uploads/";
    // Creamos un nombre único para evitar sobrescribir logos de otras tiendas
    $logo_url = $target_dir . "logo_" . $tienda_id . "_" . time() . ".png";
    move_uploaded_file($_FILES["logo"]["tmp_name"], $logo_url);
}

// 3. Actualizar la base de datos
// Usamos COALESCE: si no se sube un nuevo logo, mantiene el que ya estaba
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