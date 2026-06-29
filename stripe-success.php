<?php
/**
 * Página de retorno después de Stripe Checkout.
 * Verifica la sesión y actualiza la tienda con los IDs de Stripe.
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/stripe_helper.php';
require_once __DIR__ . '/helpers.php';

$session_id = $_GET['session_id'] ?? '';
$tienda_id_input = (int)($_GET['tienda_id'] ?? 0);

if (!$session_id || !$tienda_id_input) {
    header("Location: login.php");
    exit;
}

try {
    $session = stripe_cliente()->checkout->sessions->retrieve($session_id);

    if ($session->payment_status !== 'paid' && $session->payment_status !== 'no_payment_required') {
        // En modo trial no hay pago inmediato
        header("Location: login.php");
        exit;
    }

    $tienda_id = (int)($session->metadata->tienda_id ?? $tienda_id_input);
    $plan = $session->metadata->plan ?? 'starter';
    $periodo = $session->metadata->periodo ?? 'mensual';
    $customer_id = $session->customer;
    $subscription_id = $session->subscription;

    require_once __DIR__ . '/conexion.php';

    if ($customer_id && $subscription_id) {
        $stmt = $pdo->prepare("UPDATE tiendas SET stripe_customer_id = ?, stripe_subscription_id = ? WHERE id = ?");
        $stmt->execute([$customer_id, $subscription_id, $tienda_id]);
    }

    // Registrar factura si hubo pago
    if ($session->payment_status === 'paid' && $session->amount_total > 0) {
        $monto = $session->amount_total / 100;
        $moneda = strtoupper($session->currency ?? 'eur');
        $numero = generar_numero_factura();
        $stmtF = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, moneda, estado, metodo_pago, fecha_emision, fecha_pago) VALUES (?, ?, ?, ?, ?, ?, 'pagada', 'Stripe', CURDATE(), CURDATE())");
        $stmtF->execute([$tienda_id, $numero, $plan, $periodo, $monto, $moneda]);
    }

    $_SESSION['flash_message'] = '¡Registro completado! Ya puedes iniciar sesión.';
    $_SESSION['flash_type'] = 'success';

} catch (\Exception $e) {
    error_log("Stripe success error: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Error al verificar el pago. Contacta a soporte.';
    $_SESSION['flash_type'] = 'danger';
}

header("Location: login.php");
exit;
