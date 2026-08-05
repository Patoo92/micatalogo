<?php
/**
 * Crea una sesión de Stripe Checkout (mode=payment) para cobrar el carrito.
 * NO descuenta stock: el stock se descuenta recién cuando el webhook de Stripe
 * confirma el pago (ver webhook-stripe.php, checkout.session.completed).
 *
 * El flujo WhatsApp (guardar-pedido.php) queda intacto; este es el flujo
 * alternativo "Pagar ahora" del catálogo, disponible solo para planes
 * Business/Enterprise con Stripe configurado.
 */
require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    mostrar_error("Solicitud inválida", "Token de seguridad incorrecto. Volvé al catálogo y reintentá.", "index.php", "Volver al catálogo");
}

if (!verificar_rate_limit($pdo, 'guardar_pedido', 10, 5)) {
    mostrar_error("Demasiados pedidos", "Has realizado muchos pedidos en poco tiempo. Esperá unos minutos.", "index.php", "Volver al catálogo");
}

$nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
$email_cliente  = trim($_POST['email_cliente'] ?? '');
$slug           = trim($_POST['slug'] ?? '');
$items_json     = trim($_POST['items'] ?? '');

if (empty($nombre_cliente) || empty($slug) || empty($items_json)) {
    mostrar_error("Faltan datos", "Revisá el carrito y completá tu nombre.", "index.php", "Volver al catálogo");
}

if (!empty($email_cliente) && !filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    mostrar_error("Email no válido", "El email ingresado no es correcto.", "index.php", "Volver al catálogo");
}

$items = json_decode($items_json, true);
if (!is_array($items) || empty($items)) {
    mostrar_error("Carrito vacío", "No hay productos en el carrito.", "index.php", "Ver catálogo");
}

try {
    $stmtTienda = $pdo->prepare("SELECT id, plan, moneda, moneda_iso, nombre_tienda, email, slug AS tienda_slug FROM tiendas WHERE slug = ? AND activo = 1");
    $stmtTienda->execute([$slug]);
    $tienda = $stmtTienda->fetch();

    if (!$tienda) {
        mostrar_error("Tienda no encontrada", "La tienda no existe o está inactiva.", "index.php", "Volver");
    }

    // Pagos online solo para planes Business/Enterprise
    if (!in_array($tienda['plan'], ['business', 'enterprise'], true)) {
        mostrar_error("Pagos online no disponibles", "Esta tienda no acepta pagos por tarjeta. Usá el pedido por WhatsApp.", "index.php?tienda=" . urlencode($tienda['tienda_slug']), "Volver al catálogo");
    }

    // Stripe debe estar configurado (secret_key)
    require_once __DIR__ . '/stripe_helper.php';
    $stripe_cfg = stripe_config();
    if (empty($stripe_cfg['secret_key'])) {
        mostrar_error("Pasarela no configurada", "Los pagos online no están disponibles en este momento. Usá el pedido por WhatsApp.", "index.php?tienda=" . urlencode($tienda['tienda_slug']), "Volver al catálogo");
    }

    $moneda_simbolo = $tienda['moneda'] ?: '€';
    $moneda_iso     = strtoupper($tienda['moneda_iso'] ?: 'EUR');

    // Validar cada item contra la BD (precio y stock de la BD, nunca del cliente)
    $lineas = [];
    $total  = 0;
    foreach ($items as $item) {
        $producto_id = (int)($item['id'] ?? 0);
        $cantidad    = (int)($item['c'] ?? 1);
        if ($producto_id <= 0 || $cantidad <= 0) continue;

        $stmtProd = $pdo->prepare("SELECT id, nombre, precio, stock, imagen_thumb, imagen_url FROM productos WHERE id = ? AND tienda_id = ?");
        $stmtProd->execute([$producto_id, $tienda['id']]);
        $prod = $stmtProd->fetch();
        if (!$prod) continue;

        if ((int)$prod['stock'] < $cantidad) {
            mostrar_error("Stock insuficiente", "«" . htmlspecialchars($prod['nombre']) . "» no tiene suficiente stock. Reducí la cantidad y volvé a intentar.", "index.php?tienda=" . urlencode($tienda['tienda_slug']), "Volver al catálogo");
        }

        $imagen = imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url']);
        $lineas[] = [
            'producto_id' => $producto_id,
            'nombre'      => $prod['nombre'],
            'precio'      => (float)$prod['precio'],
            'cantidad'    => $cantidad,
            'imagen'      => $imagen,
        ];
        $total += (float)$prod['precio'] * $cantidad;
    }

    if (empty($lineas)) {
        mostrar_error("Carrito inválido", "Los productos del carrito no son válidos.", "index.php?tienda=" . urlencode($tienda['tienda_slug']), "Volver al catálogo");
    }

    $codigo_pedido = 'PED-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    // Insertar las líneas del pedido como PENDIENTES de pago (sin descontar stock)
    $pdo->beginTransaction();
    $stmtPedido = $pdo->prepare("INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, email_cliente, estado, codigo_pedido, metodo_pago, pago_estado, monto_total, moneda_pago) VALUES (?, ?, ?, ?, 'Pendiente', ?, 'stripe', 'pendiente', ?, ?)");
    foreach ($lineas as $linea) {
        for ($i = 0; $i < $linea['cantidad']; $i++) {
            $stmtPedido->execute([$tienda['id'], $linea['producto_id'], $nombre_cliente, $email_cliente ?: null, $codigo_pedido, $total, $moneda_iso]);
        }
    }
    $pdo->commit();

    // Construir line items para Stripe (precios desde la BD)
    $stripe_line_items = [];
    foreach ($lineas as $linea) {
        $stripe_line_items[] = [
            'name'        => $linea['nombre'],
            'unit_amount' => (int)round($linea['precio'] * 100),
            'currency'    => $moneda_iso,
            'quantity'    => $linea['cantidad'],
            'images'      => $linea['imagen'] ? [$linea['imagen']] : [],
        ];
    }

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $success_url = $base_url . '/pago-exito.php?codigo=' . urlencode($codigo_pedido) . '&session_id={CHECKOUT_SESSION_ID}';
    $cancel_url  = $base_url . '/index.php?tienda=' . urlencode($tienda['tienda_slug']);

    try {
        $resultado = stripe_crear_sesion_pago(
            $tienda['id'],
            $codigo_pedido,
            $stripe_line_items,
            $success_url,
            $cancel_url,
            $email_cliente ?: ''
        );
    } catch (\Exception $e) {
        error_log("Stripe crear sesión pago: " . $e->getMessage());
        // FIX: se usa marcar_pago_fallido() (definida en helpers.php) en vez de un
        // UPDATE inline. La función ya trae el guard de idempotencia
        // (WHERE pago_estado='pendiente') y es la misma que usa webhook-stripe.php
        // para el caso checkout.session.async_payment_failed — evita que este catch
        // y el webhook diverjan si la lógica de "marcar fallido" cambia más adelante.
        marcar_pago_fallido($pdo, $codigo_pedido);
        mostrar_error("Error al iniciar el pago", "No se pudo conectar con la pasarela. Reintentá en unos minutos o usá el pedido por WhatsApp.", "index.php?tienda=" . urlencode($tienda['tienda_slug']), "Volver al catálogo");
    }

    header("Location: " . $resultado['url']);
    exit;

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error crear-checkout: " . $e->getMessage());
    mostrar_error("Error al procesar", "Ocurrió un error inesperado. Reintentá en unos minutos.", "index.php", "Volver al catálogo");
}
