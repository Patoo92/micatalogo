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

$nombre_tienda   = trim($_POST['nombre_tienda'] ?? '');
$email           = trim($_POST['email'] ?? '');
$instagram       = trim($_POST['instagram'] ?? '');
$facebook        = trim($_POST['facebook'] ?? '');
$tiktok          = trim($_POST['tiktok'] ?? '');
$twitter         = trim($_POST['twitter'] ?? '');
$color           = trim($_POST['color'] ?? '');
$moneda          = trim($_POST['moneda'] ?? '€');
$whatsapp        = trim($_POST['whatsapp'] ?? '');
$mensaje_wp      = trim($_POST['mensaje_whatsapp'] ?? '');
$descripcion     = trim($_POST['descripcion'] ?? '');
$direccion       = trim($_POST['direccion'] ?? '');
$horario         = trim($_POST['horario'] ?? '');
$marca_blanca    = !empty($_POST['marca_blanca']) ? 1 : 0;
$notif_pedido    = !empty($_POST['notif_nuevo_pedido']) ? 1 : 0;
$notif_stock     = !empty($_POST['notif_stock_bajo']) ? 1 : 0;
$meta_desc       = trim($_POST['meta_descripcion'] ?? '');
$meta_palabras   = trim($_POST['meta_palabras_clave'] ?? '');
$codigo_tracking = $_POST['codigo_tracking'] ?? '';
$css_personalizado = $_POST['css_personalizado'] ?? '';
$hero_title      = trim($_POST['hero_title'] ?? '');
$hero_subtitle   = trim($_POST['hero_subtitle'] ?? '');
$dominio         = trim($_POST['dominio'] ?? '');

if (empty($nombre_tienda)) {
    header("Location: configuracion.php?error=nombre_vacio");
    exit;
}
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: configuracion.php?error=email_invalido");
    exit;
}
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    $color = '#0d6efd';
}
if (!empty($whatsapp) && !preg_match('/^\+?[0-9]{7,15}$/', $whatsapp)) {
    header("Location: configuracion.php?error=whatsapp_invalido");
    exit;
}
if (!empty($instagram) && !preg_match('/^(https?:\/\/)?([a-zA-Z0-9_.]+\/?)+$/', $instagram)) {
    header("Location: configuracion.php?error=instagram_invalido");
    exit;
}
if ($marca_blanca && !plan_limite('marca_blanca')) {
    header("Location: configuracion.php?error=permiso");
    exit;
}
if (!empty($dominio) && !preg_match('/^(https?:\/\/)?([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i', $dominio)) {
    header("Location: configuracion.php?error=dominio_invalido");
    exit;
}
if (!empty($dominio) && !plan_limite('marca_blanca')) {
    $dominio = '';
}

if (!plan_limite('personalizacion')) {
    $instagram       = '';
    $facebook        = '';
    $tiktok          = '';
    $twitter         = '';
    $mensaje_wp      = '';
    $meta_desc       = '';
    $meta_palabras   = '';
    $codigo_tracking = '';
    $css_personalizado = '';
    $hero_title      = '';
    $hero_subtitle   = '';
    $notif_pedido    = 0;
    $notif_stock     = 0;
    $_FILES['banner']['name'] = '';
    $banner_url = null;
}

// Logo upload
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
    $target_dir = __DIR__ . "/uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $logo_url = "uploads/logo_" . $tienda_id . "_" . time() . "." . $ext;
    move_uploaded_file($_FILES["logo"]["tmp_name"], $target_dir . basename($logo_url));
}

// Banner upload
$banner_url = null;
if (!empty($_FILES['banner']['name'])) {
    if ($_FILES['banner']['size'] > 2 * 1024 * 1024) {
        header("Location: configuracion.php?error=banner_grande");
        exit;
    }
    $TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_real = $finfo->file($_FILES['banner']['tmp_name']);
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
    $target_dir = __DIR__ . "/uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
    $banner_url = "uploads/banner_" . $tienda_id . "_" . time() . "." . $ext;
    move_uploaded_file($_FILES["banner"]["tmp_name"], $target_dir . basename($banner_url));
}

$sql = "UPDATE tiendas SET 
            nombre_tienda     = ?,
            email             = ?,
            instagram_url     = ?, 
            facebook_url      = ?,
            tiktok_url        = ?,
            twitter_url       = ?,
            color_tema        = ?, 
            moneda            = ?,
            telefono_whatsapp = ?,
            mensaje_whatsapp  = ?,
            descripcion       = ?,
            direccion         = ?,
            horario           = ?,
            logo_url          = COALESCE(?, logo_url),
            banner_url        = COALESCE(?, banner_url),
            marca_blanca      = ?,
            notif_nuevo_pedido = ?,
            notif_stock_bajo  = ?,
            meta_descripcion   = ?,
            meta_palabras_clave = ?,
            codigo_tracking    = ?,
            css_personalizado  = ?,
            hero_title         = ?,
            hero_subtitle      = ?,
            dominio            = ?
        WHERE id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $nombre_tienda, $email,
    $instagram, $facebook, $tiktok, $twitter,
    $color, $moneda, $whatsapp, $mensaje_wp,
    $descripcion, $direccion, $horario,
    $logo_url, $banner_url,
    $marca_blanca, $notif_pedido, $notif_stock,
    $meta_desc, $meta_palabras, $codigo_tracking, $css_personalizado,
    $hero_title, $hero_subtitle,
    $dominio ?: null,
    $tienda_id
]);

$_SESSION['tienda_nombre'] = $nombre_tienda;
$_SESSION['marca_blanca'] = $marca_blanca;

$u = obtener_usuario_actual();
registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Actualizó la configuración de la tienda');

header("Location: configuracion.php?success=1");
exit;
