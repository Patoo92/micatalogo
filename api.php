<?php
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/conexion.php';

$api_key = $_GET['api_key'] ?? $_POST['api_key'] ?? '';
if (!$api_key) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        $api_key = $m[1];
    }
}

if (!$api_key) {
    responder(false, 'API key requerida.', 401);
}

$stmt = $pdo->prepare("SELECT k.tienda_id, t.plan FROM api_keys k JOIN tiendas t ON k.tienda_id = t.id WHERE k.api_key = ? AND k.activo = 1");
$stmt->execute([$api_key]);
$key = $stmt->fetch();

if (!$key) {
    responder(false, 'API key inválida o desactivada.', 401);
}

$planes_api = ['business', 'enterprise'];
if (!in_array($key['plan'], $planes_api)) {
    responder(false, 'API Keys disponibles solo en plan Business+.', 403);
}

$tienda_id = (int)$key['tienda_id'];

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'productos':
        $stmt = $pdo->prepare("SELECT id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, imagen_url, imagen_thumb, created_at FROM productos WHERE tienda_id = ? ORDER BY nombre");
        $stmt->execute([$tienda_id]);
        responder(true, 'OK', 200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'producto':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) responder(false, 'ID de producto requerido.', 400);
        $stmt = $pdo->prepare("SELECT id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, imagen_url, imagen_thumb, created_at FROM productos WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$id, $tienda_id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$prod) responder(false, 'Producto no encontrado.', 404);
        responder(true, 'OK', 200, $prod);
        break;

    case 'categorias':
        $stmt = $pdo->prepare("SELECT id, nombre_categoria, created_at FROM categorias WHERE tienda_id = ? ORDER BY nombre_categoria");
        $stmt->execute([$tienda_id]);
        responder(true, 'OK', 200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'pedidos':
        $stmt = $pdo->prepare("SELECT p.id, p.producto_id, pr.nombre AS producto_nombre, p.nombre_cliente, p.email_cliente, p.estado, p.created_at FROM pedidos p LEFT JOIN productos pr ON p.producto_id = pr.id WHERE p.tienda_id = ? ORDER BY p.created_at DESC");
        $stmt->execute([$tienda_id]);
        responder(true, 'OK', 200, $stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'crear-pedido':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 'Método no permitido.', 405);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;
        $producto_id = (int)($input['producto_id'] ?? 0);
        $nombre = trim($input['nombre_cliente'] ?? '');
        $email = trim($input['email_cliente'] ?? '');
        if (!$producto_id) responder(false, 'producto_id requerido.', 400);
        if (!$nombre) responder(false, 'nombre_cliente requerido.', 400);

        $stmt = $pdo->prepare("SELECT id, nombre, precio FROM productos WHERE id = ? AND tienda_id = ? AND stock > 0");
        $stmt->execute([$producto_id, $tienda_id]);
        $producto = $stmt->fetch();
        if (!$producto) responder(false, 'Producto no disponible.', 404);

        $stmt = $pdo->prepare("SELECT slug, telefono_whatsapp FROM tiendas WHERE id = ?");
        $stmt->execute([$tienda_id]);
        $tienda = $stmt->fetch();

        $stmt = $pdo->prepare("INSERT INTO pedidos (tienda_id, producto_id, nombre_cliente, email_cliente, estado) VALUES (?, ?, ?, ?, 'Pendiente')");
        $stmt->execute([$tienda_id, $producto_id, $nombre, $email ?: null]);
        $pedido_id = $pdo->lastInsertId();

        $texto = "¡Hola! Soy $nombre. Me interesa: {$producto['nombre']} ({$producto['precio']}€). Pedido #$pedido_id";
        $url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']) . "?text=" . urlencode($texto);

        responder(true, 'Pedido creado.', 201, [
            'id' => (int)$pedido_id,
            'producto' => $producto['nombre'],
            'url_whatsapp' => $url,
        ]);
        break;

    default:
        responder(false, 'Acción no válida. Usa: productos, producto, categorias, pedidos, crear-pedido', 400);
}

function responder($success, $message, $status = 200, $data = null) {
    http_response_code($status);
    $res = ['success' => $success, 'message' => $message];
    if ($data !== null) $res['data'] = $data;
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}
