<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$stmt = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? ORDER BY nombre");
$stmt->execute([$tienda_id]);
$productos = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="productos_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['nombre', 'descripcion', 'precio', 'stock', 'stock_minimo', 'categoria_id', 'destacado', 'etiqueta', 'imagen_url']);

foreach ($productos as $p) {
    fputcsv($output, [
        $p['nombre'],
        $p['descripcion'],
        $p['precio'],
        $p['stock'],
        $p['stock_minimo'],
        $p['categoria_id'],
        $p['destacado'] ?? 0,
        $p['etiqueta'] ?? '',
        $p['imagen_url'],
    ]);
}

fclose($output);
