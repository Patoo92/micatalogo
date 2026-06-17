<?php
require_once 'conexion.php';

// Validamos que nos lleguen los datos obligatorios por el formulario
if (isset($_POST['nombre_cliente']) && isset($_POST['producto_id']) && isset($_POST['tienda_id'])) {
    
    $nombre_cliente = trim($_POST['nombre_cliente']);
    $producto_id = (int)$_POST['producto_id'];
    $tienda_id = (int)$_POST['tienda_id'];

    if (empty($nombre_cliente)) {
        die("Por favor, introduce tu nombre para continuar.");
    }

    try {
        // 1. Insertamos el pre-pedido en la base de datos con estado 'Pendiente'
        $sql = "INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, estado) VALUES (?, ?, ?, 'Pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tienda_id, $producto_id, $nombre_cliente]);
        
        // Recuperamos el ID del pedido que se acaba de crear
        $id_pedido = $pdo->lastInsertId();

        // 2. Traemos los datos del producto y la tienda para armar el mensaje de WhatsApp
        $stmtDatos = $pdo->prepare("
            SELECT p.nombre AS producto_nombre, p.precio, t.whatsapp_number AS telefono_whatsapp
            FROM productos p JOIN tiendas t ON p.tienda_id = t.id WHERE p.id = ?
            JOIN tiendas t ON p.tienda_id = t.id 
            WHERE p.id = ?
        ");
        $stmtDatos->execute([$producto_id]);
        $datos = $stmtDatos->fetch();

        // 3. Construimos el mensaje incluyendo el ID del pedido para que el dueño sepa cuál es
        $textoMensaje = "¡Hola! Soy " . $nombre_cliente . ". Me interesa el producto: " . $datos['producto_nombre'] . " (Precio: " . $datos['precio'] . "€). Mi código de pedido es el #" . $id_pedido;
        $urlWhatsapp = "https://wa.me/" . $datos['telefono_whatsapp'] . "?text=" . urlencode($textoMensaje);

        // 4. Redirigimos automáticamente al cliente a WhatsApp
        header("Location: " . $urlWhatsapp);
        exit;

    } catch (\PDOException $e) {
        die("Error al procesar el pedido: " . $e->getMessage());
    }
} else {
    // Si alguien intenta entrar a este archivo a la fuerza, lo mandamos al catálogo
    header("Location: index.php");
    exit;
}