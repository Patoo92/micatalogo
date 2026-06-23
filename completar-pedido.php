<?php
require_once 'init_session.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['tienda_id']) || !isset($_POST['id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('pedidos_gestionar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para gestionar pedidos.", "pedidos.php", "Volver");
}
if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    mostrar_error("Solicitud inválida", "Token de seguridad incorrecto.", "pedidos.php", "Volver a pedidos");
}

$pedido_id = (int)$_POST['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT producto_id, estado, nombre_cliente, email_cliente FROM pedidos WHERE id = ? AND tienda_id = ? FOR UPDATE");
    $stmt->execute([$pedido_id, $tienda_id]);
    $pedido = $stmt->fetch();

    if ($pedido && $pedido['estado'] === 'Pendiente') {
        $producto_id = $pedido['producto_id'];

        $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET estado = 'Vendido' WHERE id = ?");
        $stmtUpdatePedido->execute([$pedido_id]);

        $stmtUpdateStock = $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ? AND stock > 0");
        $stmtUpdateStock->execute([$producto_id]);
    }

    $pdo->commit();

    $nombre_producto = 'Producto desconocido';
    if (isset($producto_id)) {
        $stmtCheck = $pdo->prepare("SELECT nombre, stock, stock_minimo FROM productos WHERE id = ?");
        $stmtCheck->execute([$producto_id]);
        $producto = $stmtCheck->fetch();
        $nombre_producto = $producto['nombre'] ?? "ID: $producto_id";

        require_once __DIR__ . '/email_helper.php';

        if ($producto && $producto['stock'] <= $producto['stock_minimo']) {

            $stmtEmail = $pdo->prepare("SELECT email, nombre_tienda FROM tiendas WHERE id = ?");
            $stmtEmail->execute([$tienda_id]);
            $tienda = $stmtEmail->fetch();

            if ($tienda && !empty($tienda['email'])) {
                $asunto  = "⚠️ ALERTA: Stock Crítico - " . $producto['nombre'];

                $mensaje  = "Hola,\n\n";
                $mensaje .= "El producto '" . $producto['nombre'] . "' (ID: #" . $producto_id . ") ha alcanzado o superado su límite de seguridad.\n";
                $mensaje .= "Stock actual: " . $producto['stock'] . " unidades.\n";
                $mensaje .= "Stock mínimo configurado: " . $producto['stock_minimo'] . " unidades.\n\n";
                $mensaje .= "Por favor, repón el inventario lo antes posible desde tu panel de control.";

                $cuerpo_html = nl2br(htmlspecialchars($mensaje));
                enviar_email($tienda['email'], $asunto, $cuerpo_html);
            }
        }

        if (!empty($pedido['email_cliente']) && $producto) {
            $stmtTiendaMail = $pdo->prepare("SELECT nombre_tienda FROM tiendas WHERE id = ?");
            $stmtTiendaMail->execute([$tienda_id]);
            $tiendaInfo = $stmtTiendaMail->fetch();

            if ($tiendaInfo) {
                $asunto = "✅ Pedido Completado - " . $producto['nombre'];
                $mensaje = "Hola " . htmlspecialchars($pedido['nombre_cliente']) . ",<br><br>";
                $mensaje .= "Tu pedido de <strong>" . htmlspecialchars($producto['nombre']) . "</strong> ha sido completado.<br><br>";
                $mensaje .= "Gracias por tu compra.<br><br>";
                $mensaje .= "Saludos,<br>" . htmlspecialchars($tiendaInfo['nombre_tienda']);

                $from = !empty($_SESSION['marca_blanca']) ? $tiendaInfo['nombre_tienda'] : null;
                enviar_email($pedido['email_cliente'], $asunto, $mensaje, $from);
            }
        }
    }

    $u = obtener_usuario_actual();
    registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Marcó pedido como vendido', "Pedido #$pedido_id - $nombre_producto");

    header("Location: pedidos.php");
    exit;

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al completar pedido #$pedido_id: " . $e->getMessage());
    mostrar_error("Error al procesar", "No se pudo completar la venta. Intenta de nuevo.", "pedidos.php", "Volver a pedidos");
}