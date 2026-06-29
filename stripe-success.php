<?php
/**
 * Página de éxito después de completar el pago en Stripe.
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/conexion.php';

$tienda_id = (int)($_GET['tienda_id'] ?? 0);

if (!$tienda_id) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT nombre_tienda, plan FROM tiendas WHERE id = ?");
$stmt->execute([$tienda_id]);
$tienda = $stmt->fetch();

if (!$tienda) {
    header("Location: login.php");
    exit;
}

$_SESSION['flash_message'] = '¡Pago completado! Ya puedes iniciar sesión. Tu prueba de 3 días comienza ahora.';
$_SESSION['flash_type'] = 'success';
header("Location: login.php");
exit;
