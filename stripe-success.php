<?php
/**
 * Página de retorno después de Stripe Checkout.
 * Verifica la sesión, activa el plan desde metadata y registra la factura de
 * forma idempotente (stripe_referencia UNIQUE). El tienda_id se toma de la
 * metadata de la sesión de Stripe (nunca del GET) y, si hay sesión logueada,
 * se valida contra ella (evita IDOR).
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/stripe_helper.php';
require_once __DIR__ . '/helpers.php';

$session_id = $_GET['session_id'] ?? '';

if (!$session_id) {
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

    // A3: tienda_id y plan siempre desde la metadata de Stripe, nunca del GET.
    $tienda_id = (int)($session->metadata->tienda_id ?? 0);
    $plan      = in_array($session->metadata->plan ?? '', ['starter', 'pro', 'business', 'enterprise']) ? $session->metadata->plan : 'pro';
    $periodo   = $session->metadata->periodo === 'anual' ? 'anual' : 'mensual';
    $customer_id     = $session->customer;
    $subscription_id = $session->subscription;

    if (!$tienda_id) {
        header("Location: login.php");
        exit;
    }

    require_once __DIR__ . '/conexion.php';

    // A3: si hay sesión logueada, el tienda_id debe coincidir (anti-IDOR).
    if (isset($_SESSION['tienda_id']) && (int)$_SESSION['tienda_id'] !== $tienda_id) {
        http_response_code(403);
        error_log("stripe-success: tienda de sesión {$_SESSION['tienda_id']} != metadata $tienda_id");
        $_SESSION['flash_message'] = 'Error al verificar el pago. Contacta a soporte.';
        $_SESSION['flash_type'] = 'danger';
        header("Location: login.php");
        exit;
    }

    if ($customer_id && $subscription_id) {
        $stmt = $pdo->prepare("UPDATE tiendas SET stripe_customer_id = ?, stripe_subscription_id = ?, plan = ?, trial_ends_at = NULL WHERE id = ?");
        $stmt->execute([$customer_id, $subscription_id, $plan, $tienda_id]);
    }

    // Registrar factura si hubo pago (idempotente: stripe_referencia UNIQUE).
    if ($session->payment_status === 'paid' && $session->amount_total > 0) {
        $monto  = $session->amount_total / 100;
        $moneda = strtoupper($session->currency ?? 'eur');
        $numero = generar_numero_factura();
        $ref    = $session->id;
        $stmtF = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, moneda, estado, metodo_pago, stripe_referencia, fecha_emision, fecha_pago)
                                SELECT ?, ?, ?, ?, ?, ?, 'pagada', 'Stripe', ?, CURDATE(), CURDATE()
                                WHERE NOT EXISTS (SELECT 1 FROM facturas WHERE stripe_referencia = ?)");
        $stmtF->execute([$tienda_id, $numero, $plan, $periodo, $monto, $moneda, $ref, $ref]);
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
