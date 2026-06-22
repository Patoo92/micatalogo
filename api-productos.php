<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$ids = $_GET['ids'] ?? '';
if (!$ids) { echo json_encode([]); exit; }

$ids_array = array_map('intval', explode(',', $ids));
$ids_array = array_filter($ids_array);
if (empty($ids_array)) { echo json_encode([]); exit; }

$placeholders = implode(',', array_fill(0, count($ids_array), '?'));
$stmt = $pdo->prepare("SELECT id, nombre, precio, imagen_thumb, imagen_url, stock FROM productos WHERE id IN ($placeholders)");
$stmt->execute(array_values($ids_array));
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($productos as &$p) {
    $p['imagen'] = $p['imagen_thumb'] ?: $p['imagen_url'];
    $p['precio'] = (float)$p['precio'];
    $p['stock'] = (int)$p['stock'];
    unset($p['imagen_thumb'], $p['imagen_url']);
}

echo json_encode($productos);
