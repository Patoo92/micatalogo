<?php
header('Content-Type: application/json');
require_once 'conexion.php';
require_once 'helpers.php';

if (!verificar_rate_limit($pdo, 'api_productos', 60, 1)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiadas solicitudes.']);
    exit;
}
registrar_intento_login($pdo, 'api_productos');

$ids = $_GET['ids'] ?? '';
$tienda_id_param = isset($_GET['tienda']) ? (int)$_GET['tienda'] : 0;
if (!$ids || !$tienda_id_param) { echo json_encode([]); exit; }

$ids_array = array_map('intval', explode(',', $ids));
$ids_array = array_filter($ids_array);
if (empty($ids_array)) { echo json_encode([]); exit; }

$placeholders = implode(',', array_fill(0, count($ids_array), '?'));
$stmt = $pdo->prepare("SELECT id, nombre, precio, imagen_thumb, imagen_url, stock FROM productos WHERE id IN ($placeholders) AND tienda_id = ?");
$params = array_values($ids_array);
$params[] = $tienda_id_param;
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($productos as &$p) {
    $p['imagen'] = $p['imagen_thumb'] ?: $p['imagen_url'];
    $p['precio'] = (float)$p['precio'];
    $p['stock'] = (int)$p['stock'];
    unset($p['imagen_thumb'], $p['imagen_url']);
}

echo json_encode($productos);
