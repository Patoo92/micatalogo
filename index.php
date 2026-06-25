<?php
require_once 'init_session.php';
require_once 'conexion.php';

// Dominio personalizado: si el host coincide con una tienda, se usa esa
$dominio_host = $_SERVER['HTTP_HOST'] ?? '';
$dominio_host = preg_replace('/^www\./', '', $dominio_host);

if (isset($_GET['tienda']) && !empty($_GET['tienda'])) {
    $slug_tienda = $_GET['tienda'];
} elseif (!empty($dominio_host)) {
    $stmtDom = $pdo->prepare("SELECT slug FROM tiendas WHERE dominio = ? AND activo = 1");
    $stmtDom->execute([$dominio_host]);
    $domRow = $stmtDom->fetch();
    if ($domRow) {
        $slug_tienda = $domRow['slug'];
    } else {
        header("Location: index.html");
        exit;
    }
} else {
    header("Location: index.html");
    exit;
}

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

    $stmtDest = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? AND destacado = 1 AND stock > 0");
    $stmtDest->execute([$tienda_id]);
    $destacados = $stmtDest->fetchAll();

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
