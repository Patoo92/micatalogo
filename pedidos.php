<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('pedidos_ver')) {
    mostrar_error("Acceso denegado", "No tienes permiso para ver pedidos.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$csrf_token = csrf_token();

$stmt = $pdo->prepare("
    SELECT p.*, COALESCE(prod.nombre, 'Producto eliminado') AS producto_nombre, prod.precio 
    FROM pedidos p 
    LEFT JOIN productos prod ON p.producto_id = prod.id 
    WHERE p.tienda_id = ? 
    ORDER BY p.fecha_pedido DESC
");
$stmt->execute([$tienda_id]);
$filas = $stmt->fetchAll();

$pedidos = [];
foreach ($filas as $f) {
    $clave = $f['nombre_cliente'] . '|' . date('Y-m-d H:i', strtotime($f['fecha_pedido']));
    if (!isset($pedidos[$clave])) {
        $pedidos[$clave] = [
            'nombre_cliente'  => $f['nombre_cliente'],
            'email_cliente'   => $f['email_cliente'],
            'fecha_pedido'    => $f['fecha_pedido'],
            'fecha_agrupada'  => $clave,
            'items'           => [],
            'total'           => 0,
            'pendientes'      => 0,
            'ids_estados'     => [],
        ];
    }
    $pedidos[$clave]['items'][] = $f;
    $pedidos[$clave]['total'] += (float)($f['precio'] ?? 0);
    if ($f['estado'] === 'Pendiente') $pedidos[$clave]['pendientes']++;
    $pedidos[$clave]['ids_estados'][$f['id']] = $f['estado'];
}
$pedidos = array_values($pedidos);
?>
<?php require __DIR__ . '/templates/pedidos_body.php';