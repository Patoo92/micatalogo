<?php
/**
 * Redirige al usuario a Stripe Checkout para completar el pago.
 * Llamado desde registro.php después de crear una tienda de plan pago.
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/stripe_helper.php';

$tienda_id = (int)($_GET['tienda_id'] ?? 0);
$plan = $_GET['plan'] ?? '';
$periodo = $_GET['periodo'] ?? 'mensual';

if (!$tienda_id || !in_array($plan, ['pro', 'business', 'enterprise'])) {
    header("Location: registro.php");
    exit;
}

require_once __DIR__ . '/conexion.php';

$stmt = $pdo->prepare("SELECT email, nombre_tienda FROM tiendas WHERE id = ?");
$stmt->execute([$tienda_id]);
$tienda = $stmt->fetch();

if (!$tienda) {
    header("Location: registro.php");
    exit;
}

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$success_url = rtrim($base_url, '/') . '/stripe-success.php?tienda_id=' . $tienda_id;
$cancel_url  = rtrim($base_url, '/') . '/registro.php?plan=' . $plan;

try {
    $result = stripe_crear_sesion_checkout(
        $tienda_id,
        $plan,
        $periodo,
        $success_url,
        $cancel_url,
        $tienda['email'] ?: ''
    );
    header("Location: " . $result['url']);
    exit;
} catch (\Exception $e) {
    error_log("Stripe checkout error: " . $e->getMessage());
    echo "Error al conectar con Stripe. Intenta de nuevo más tarde.";
    exit;
}
