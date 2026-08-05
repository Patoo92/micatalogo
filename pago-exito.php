<?php
/**
 * Página de retorno de Stripe Checkout (mode=payment).
 * Verifica la sesión contra la API de Stripe y, si está pagada, confirma el
 * pedido (idempotente, por si el webhook aún no llegó).
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/stripe_helper.php';

$codigo     = trim($_GET['codigo'] ?? '');
$session_id = trim($_GET['session_id'] ?? '');

$pagado    = false;
$monto     = null;
$moneda    = '€';
$tienda_slug = '';
$tienda_nombre = '';

if (!empty($codigo)) {
    $stmt = $pdo->prepare("SELECT p.monto_total, p.moneda_pago, t.slug AS tienda_slug, t.moneda, t.nombre_tienda FROM pedidos p JOIN tiendas t ON p.tienda_id = t.id WHERE p.codigo_pedido = ? LIMIT 1");
    $stmt->execute([$codigo]);
    $pedido = $stmt->fetch();
    if ($pedido) {
        $moneda       = $pedido['moneda'] ?: '€';
        $tienda_slug  = $pedido['tienda_slug'];
        $tienda_nombre = $pedido['nombre_tienda'];

        // Estado actual en BD (el webhook puede haber confirmado antes que esta página)
        $stmtEstado = $pdo->prepare("SELECT pago_estado FROM pedidos WHERE codigo_pedido = ? LIMIT 1");
        $stmtEstado->execute([$codigo]);
        $pago_bd = $stmtEstado->fetchColumn();

        if ($pago_bd === 'pagado') {
            $pagado = true;
            $monto  = $pedido['monto_total'];
        } elseif (!empty($session_id)) {
            $session = stripe_obtener_sesion_checkout($session_id);
            if ($session && ($session->payment_status ?? '') === 'paid') {
                $res = confirmar_pago_producto($pdo, $codigo, $session->id);
                $pagado = true;
                $monto  = $pedido['monto_total'];
            }
        }
    }
}

if ($pagado) {
    $monto_texto = $monto !== null ? number_format((float)$monto, 2, ',', '.') . ' ' . $moneda : '';
    $titulo   = '¡Pago confirmado!';
    $mensaje  = 'Tu pedido fue pagado correctamente.';
    $detalle  = !empty($monto_texto) ? 'Total abonado: <strong>' . htmlspecialchars($monto_texto) . '</strong>' : '';
    $icono    = 'mdi:check-circle';
    $color    = '#10b981';
    $texto_boton = 'Volver al catálogo';
} else {
    $titulo   = 'Pago pendiente';
    $mensaje  = 'No pudimos confirmar tu pago. Si el dinero fue descontado, avisanos y lo revisamos.';
    $detalle  = !empty($codigo) ? 'Código de pedido: <strong>' . htmlspecialchars($codigo) . '</strong>' : '';
    $icono    = 'mdi:clock-alert-outline';
    $color    = '#f59e0b';
    $texto_boton = 'Reintentar';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;background:#f6f8fb;">
    <div class="card p-4 text-center shadow-sm" style="max-width:460px;width:100%;border:none;border-radius:16px;">
        <iconify-icon icon="<?php echo htmlspecialchars($icono); ?>" width="56" style="color:<?php echo $color; ?>;"></iconify-icon>
        <h2 class="fw-bold mt-3 mb-1"><?php echo htmlspecialchars($titulo); ?></h2>
        <p class="text-muted mb-2"><?php echo htmlspecialchars($mensaje); ?></p>
        <?php if ($detalle): ?>
            <p class="mb-3"><?php echo $detalle; ?></p>
        <?php endif; ?>
        <?php if (!empty($tienda_nombre)): ?>
            <p class="small text-muted mb-3"><?php echo htmlspecialchars($tienda_nombre); ?></p>
        <?php endif; ?>
        <a href="<?php echo $tienda_slug ? 'index.php?tienda=' . htmlspecialchars(urlencode($tienda_slug)) : 'index.php'; ?>" class="btn btn-primary w-100 py-2 fw-bold">
            <?php echo htmlspecialchars($texto_boton); ?>
        </a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js"></script>
</body>
</html>
