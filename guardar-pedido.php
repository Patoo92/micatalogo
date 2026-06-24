<?php
require_once 'init_session.php';
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

if (!verificar_rate_limit($pdo, 'guardar_pedido', 10, 5)) {
    echo json_encode(['success' => false, 'message' => 'Demasiados pedidos. Espera unos minutos.']);
    exit;
}

$nombre_cliente = trim($_POST['nombre_cliente'] ?? '');
$email_cliente  = trim($_POST['email_cliente'] ?? '');
$slug           = trim($_POST['slug'] ?? '');
$items_json     = trim($_POST['items'] ?? '');

if (empty($nombre_cliente) || empty($slug) || empty($items_json)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
    exit;
}

if (!empty($email_cliente) && !filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido.']);
    exit;
}

$items = json_decode($items_json, true);
if (!is_array($items) || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Carrito vacío.']);
    exit;
}

try {
    $stmtTienda = $pdo->prepare("SELECT id FROM tiendas WHERE slug = ? AND activo = 1");
    $stmtTienda->execute([$slug]);
    $tienda = $stmtTienda->fetch();

    if (!$tienda) {
        echo json_encode(['success' => false, 'message' => 'Tienda no encontrada.']);
        exit;
    }

    $tienda_id = $tienda['id'];
    $creados = 0;

    $stmtPedido = $pdo->prepare("INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, email_cliente, estado) VALUES (?, ?, ?, ?, 'Pendiente')");

    foreach ($items as $item) {
        $producto_id = (int)($item['id'] ?? 0);
        $cantidad    = (int)($item['c'] ?? 1);

        if ($producto_id <= 0 || $cantidad <= 0) continue;

        $stmtCheck = $pdo->prepare("SELECT id FROM productos WHERE id = ? AND tienda_id = ?");
        $stmtCheck->execute([$producto_id, $tienda_id]);
        if (!$stmtCheck->fetch()) continue;

        $email_db = !empty($email_cliente) ? $email_cliente : null;
        for ($i = 0; $i < $cantidad; $i++) {
            $stmtPedido->execute([$tienda_id, $producto_id, $nombre_cliente, $email_db]);
            $creados++;
        }
    }

    if ($creados === 0) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar ningún producto.']);
        exit;
    }

    // Email de confirmación al cliente
    if (!empty($email_cliente)) {
        require_once 'email_helper.php';
        $stmtTiendaInfo = $pdo->prepare("SELECT nombre_tienda, marca_blanca FROM tiendas WHERE id = ?");
        $stmtTiendaInfo->execute([$tienda_id]);
        $tiendaInfo = $stmtTiendaInfo->fetch();
        $from_name = !empty($tiendaInfo['marca_blanca']) ? $tiendaInfo['nombre_tienda'] : null;
        $asunto = 'Confirmación de pedido - ' . $tiendaInfo['nombre_tienda'];
        $cuerpo = '<p>Hola <strong>' . htmlspecialchars($nombre_cliente) . '</strong>,</p>'
                . '<p>Hemos recibido tu pedido en <strong>' . htmlspecialchars($tiendaInfo['nombre_tienda']) . '</strong>.</p>'
                . '<p>Te contactaremos pronto por WhatsApp para confirmar los detalles.</p>'
                . '<p>Gracias por tu compra.</p>';
        enviar_email($email_cliente, $asunto, $cuerpo, $from_name);
    }

    echo json_encode(['success' => true, 'message' => "Pedido guardado ($creados producto(s))."]);

} catch (\PDOException $e) {
    error_log("Error al guardar pedido: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al procesar el pedido.']);
}
