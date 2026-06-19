<?php
require_once 'conexion.php';

if (isset($_POST['nombre_cliente']) && isset($_POST['producto_id']) && isset($_POST['tienda_id'])) {

    $nombre_cliente = trim($_POST['nombre_cliente']);
    $producto_id    = (int)$_POST['producto_id'];
    $tienda_id      = (int)$_POST['tienda_id'];

    if (empty($nombre_cliente)) {
        die("Por favor, introduce tu nombre para continuar.");
    }

    try {
        // 1. Insertamos el pre-pedido con estado 'Pendiente'
        $sql  = "INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, estado) VALUES (?, ?, ?, 'Pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tienda_id, $producto_id, $nombre_cliente]);

        $id_pedido = $pdo->lastInsertId();

        // 2. Traemos datos del producto y la tienda (query corregida, sin JOIN duplicado)
        $stmtDatos = $pdo->prepare("
            SELECT p.nombre AS producto_nombre, p.precio, t.telefono_whatsapp
            FROM productos p
            JOIN tiendas t ON p.tienda_id = t.id
            WHERE p.id = ?
        ");
        $stmtDatos->execute([$producto_id]);
        $datos = $stmtDatos->fetch();

        // 3. Construimos el mensaje de WhatsApp
        $textoMensaje = "¡Hola! Soy " . $nombre_cliente . ". Me interesa el producto: " . $datos['producto_nombre'] . " (Precio: " . $datos['precio'] . "€). Mi código de pedido es el #" . $id_pedido;
        $urlWhatsapp  = "https://wa.me/" . preg_replace('/[^0-9]/', '', $datos['telefono_whatsapp']) . "?text=" . urlencode($textoMensaje);

        // 4. Redirigimos al cliente a WhatsApp
        header("Location: " . $urlWhatsapp);
        exit;

    } catch (\PDOException $e) {
        die("Error al procesar el pedido: " . $e->getMessage());
    }

} else {
    header("Location: index.php");
    exit;
}
