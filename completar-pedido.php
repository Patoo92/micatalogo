<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('pedidos_gestionar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para gestionar pedidos.", "pedidos.php", "Volver");
}

$pedido_id = (int)$_GET['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    $stmt = $pdo->prepare("SELECT producto_id, estado FROM pedidos WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$pedido_id, $tienda_id]);
    $pedido = $stmt->fetch();

    if ($pedido && $pedido['estado'] === 'Pendiente') {
        $producto_id = $pedido['producto_id'];

        $pdo->beginTransaction();

        $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET estado = 'Vendido' WHERE id = ?");
        $stmtUpdatePedido->execute([$pedido_id]);

        $stmtUpdateStock = $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ?");
        $stmtUpdateStock->execute([$producto_id]);

        $pdo->commit();

        // --- ALERTA DE STOCK CRÍTICO POR EMAIL ---
        $stmtCheck = $pdo->prepare("SELECT nombre, stock, stock_minimo FROM productos WHERE id = ?");
        $stmtCheck->execute([$producto_id]);
        $producto = $stmtCheck->fetch();

        if ($producto['stock'] <= $producto['stock_minimo']) {

            $stmtEmail = $pdo->prepare("SELECT email FROM tiendas WHERE id = ?");
            $stmtEmail->execute([$tienda_id]);
            $tienda = $stmtEmail->fetch();

            if (!empty($tienda['email'])) {
                $para    = $tienda['email'];
                $asunto  = "⚠️ ALERTA: Stock Crítico - " . $producto['nombre'];

                $mensaje  = "Hola,\n\n";
                $mensaje .= "El producto '" . $producto['nombre'] . "' (ID: #" . $producto_id . ") ha alcanzado o superado su límite de seguridad.\n";
                $mensaje .= "Stock actual: " . $producto['stock'] . " unidades.\n";
                $mensaje .= "Stock mínimo configurado: " . $producto['stock_minimo'] . " unidades.\n\n";
                $mensaje .= "Por favor, repón el inventario lo antes posible desde tu panel de control.";

                $cabeceras = "From: noreply@tuscatalogos.com\r\n" .
                             "Reply-To: noreply@tuscatalogos.com\r\n" .
                             "X-Mailer: PHP/" . phpversion();

                @mail($para, $asunto, $mensaje, $cabeceras);
            }
        }
        // --- FIN ALERTA ---
    }

    $u = obtener_usuario_actual();
    $nombre_producto = $producto['nombre'] ?? "ID: $producto_id";
    registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Marcó pedido como vendido', "Pedido #$pedido_id - $nombre_producto");

    header("Location: pedidos.php");
    exit;

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    mostrar_error("Error al procesar", "No se pudo completar la venta. Intenta de nuevo.", "pedidos.php", "Volver a pedidos");
}