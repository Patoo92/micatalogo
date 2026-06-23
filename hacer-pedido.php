<?php
require_once 'init_session.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    mostrar_error("Solicitud inválida", "Token de seguridad incorrecto.");
}

if (!verificar_rate_limit($pdo, 'pedido', 10, 5)) {
    mostrar_error("Demasiados pedidos", "Has realizado muchos pedidos en poco tiempo. Espera unos minutos.");
}

if (isset($_POST['nombre_cliente']) && isset($_POST['producto_id']) && isset($_POST['slug'])) {

    $nombre_cliente = trim($_POST['nombre_cliente']);
    $producto_id    = (int)$_POST['producto_id'];
    $slug           = trim($_POST['slug']);

    if (empty($nombre_cliente)) {
        die("Por favor, introduce tu nombre para continuar.");
    }

    try {
        $stmtTienda = $pdo->prepare("SELECT id, telefono_whatsapp FROM tiendas WHERE slug = ? AND activo = 1");
        $stmtTienda->execute([$slug]);
        $tienda = $stmtTienda->fetch();

        if (!$tienda) {
            mostrar_error("Tienda no encontrada", "La tienda no existe o está inactiva.");
        }

        $stmtProd = $pdo->prepare("SELECT id, nombre, precio, tienda_id FROM productos WHERE id = ? AND tienda_id = ?");
        $stmtProd->execute([$producto_id, $tienda['id']]);
        $producto = $stmtProd->fetch();

        if (!$producto) {
            mostrar_error("Producto no encontrado", "El producto no existe o no pertenece a esta tienda.");
        }

        $sql  = "INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, estado) VALUES (?, ?, ?, 'Pendiente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tienda['id'], $producto_id, $nombre_cliente]);

        $id_pedido = $pdo->lastInsertId();

        $textoMensaje = "¡Hola! Soy " . $nombre_cliente . ". Me interesa el producto: " . $producto['nombre'] . " (Precio: " . $producto['precio'] . "€). Mi código de pedido es el #" . $id_pedido;
        $urlWhatsapp  = "https://wa.me/" . preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']) . "?text=" . urlencode($textoMensaje);

        header("Location: " . $urlWhatsapp);
        exit;

    } catch (\PDOException $e) {
        error_log("Error al crear pedido: " . $e->getMessage());
        mostrar_error("Error al procesar", "No se pudo realizar el pedido. Intenta de nuevo.");
    }

} else {
    header("Location: index.php");
    exit;
}
