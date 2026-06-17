<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$pedido_id = (int)$_GET['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    // 1. Buscamos el pedido para verificar tienda y saber qué producto es
    $stmt = $pdo->prepare("SELECT producto_id, estado FROM pedidos WHERE id = ? AND tienda_id = ?");
    $stmt->execute([pedido_id, $tienda_id]);
    $pedido = $stmt->fetch();

    if ($pedido && $pedido['estado'] === 'Pendiente') {
        $producto_id = $pedido['producto_id'];

        $pdo->beginTransaction();

        // Acción A: Cambiar el estado del pedido a Vendido
        $stmtUpdatePedido = $pdo->prepare("UPDATE pedidos SET estado = 'Vendido' WHERE id = ?");
        $stmtUpdatePedido->execute([$pedido_id]);

        // Acción B: Restar 1 unidad al stock
        $stmtUpdateStock = $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ?");
        $stmtUpdateStock->execute([$producto_id]);

        $pdo->commit();

        // --- 🚀 NUEVA LÓGICA: ALERTA DE STOCK CRÍTICO POR EMAIL ---
        // Consultamos cómo quedó el stock del producto modificado y cuál es su mínimo
        $stmtCheck = $pdo->prepare("SELECT nombre, stock, stock_minimo FROM productos WHERE id = ?");
        $stmtCheck->execute([$producto_id]);
        $producto = $stmtCheck->fetch();

        // Si el stock actual es igual o menor al mínimo de seguridad... ¡Disparamos la alerta!
        if ($producto['stock'] <= $producto['stock_minimo']) {
            
            // Nota: En XAMPP local el correo no se enviará de verdad a internet a menos que configures
            // un servidor SMTP, pero el código dejará la orden lista para cuando lo subas a producción (cPanel).
            $para = "dueno_de_la_tienda@email.com"; // Aquí irá el correo del dueño en el futuro
            $asunto = "⚠️ ALERTA: Stock Crítico - " . $producto['nombre'];
            
            $mensaje = "Hola,\n\n";
            $mensaje .= "El producto '" . $producto['nombre'] . "' (ID: #" . $producto_id . ") ha alcanzado o superado su límite de seguridad.\n";
            $mensaje .= "Stock actual: " . $producto['stock'] . " unidades.\n";
            $mensaje .= "Stock mínimo configurado: " . $producto['stock_minimo'] . " unidades.\n\n";
            $mensaje .= "Por favor, repón el inventario lo antes posible desde tu panel de control.";
            
            $cabeceras = "From: noreply@tuscatalogos.com\r\n" .
                         "Reply-To: noreply@tuscatalogos.com\r\n" .
                         "X-Mailer: PHP/" . phpversion();

            // Ejecuta la función nativa de envío de correos de PHP
            @mail($para, $asunto, $mensaje, $cabeceras);
        }
        // --- FIN DE LA LÓGICA DE ALERTA ---
    }

    header("Location: pedidos.php");
    exit;

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error crítico al procesar la venta: " . $e->getMessage());
}