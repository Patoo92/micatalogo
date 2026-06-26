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
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $por_pagina = 20;
    $offset = ($pagina - 1) * $por_pagina;

    if ($categoria_filtrada) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ? AND categoria_id = ?");
        $stmtCount->execute([$tienda_id, $categoria_filtrada]);
        $total_productos = (int)$stmtCount->fetchColumn();
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? AND categoria_id = ? ORDER BY id DESC LIMIT $por_pagina OFFSET $offset");
        $stmtProd->execute([$tienda_id, $categoria_filtrada]);
    } else {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE tienda_id = ?");
        $stmtCount->execute([$tienda_id]);
        $total_productos = (int)$stmtCount->fetchColumn();
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? ORDER BY id DESC LIMIT $por_pagina OFFSET $offset");
        $stmtProd->execute([$tienda_id]);
    }
    $productos = $stmtProd->fetchAll();
    $total_paginas = max(1, (int)ceil($total_productos / $por_pagina));

} catch (PDOException $e) {
    mostrar_error("Error del servidor", "No se pudo cargar la tienda. Intenta de nuevo más tarde.");
}

require __DIR__ . '/templates/catalogo.php';
