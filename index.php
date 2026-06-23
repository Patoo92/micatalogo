<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_GET['tienda']) || empty($_GET['tienda'])) {
    header("Location: index.html");
    exit;
}

$slug_tienda = $_GET['tienda'];

try {
    $stmtTienda = $pdo->prepare("SELECT * FROM tiendas WHERE slug = ?");
    $stmtTienda->execute([$slug_tienda]);
    $tienda = $stmtTienda->fetch();

    if (!$tienda) {
        header("HTTP/1.0 404 Not Found");
        mostrar_error("Tienda no encontrada", "La tienda '" . htmlspecialchars($slug_tienda) . "' no existe.");
    }

    if ($tienda['activo'] == 0) {
        header("HTTP/1.0 403 Forbidden");
        mostrar_error("Tienda suspendida", "Esta tienda está temporalmente suspendida.");
    }

    $tienda_id = $tienda['id'];

    $stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tienda_id = ?");
    $stmtCat->execute([$tienda_id]);
    $categorias = $stmtCat->fetchAll();

    $categoria_filtrada = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

    if ($categoria_filtrada) {
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? AND categoria_id = ?");
        $stmtProd->execute([$tienda_id, $categoria_filtrada]);
    } else {
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ?");
        $stmtProd->execute([$tienda_id]);
    }
    $productos = $stmtProd->fetchAll();

} catch (PDOException $e) {
    mostrar_error("Error del servidor", "No se pudo cargar la tienda. Intenta de nuevo más tarde.");
}

require __DIR__ . '/templates/catalogo.php';
