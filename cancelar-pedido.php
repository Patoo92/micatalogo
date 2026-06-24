<?php
require_once 'init_session.php';
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['tienda_id']) || !isset($_POST['id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('pedidos_gestionar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para gestionar pedidos.", "pedidos.php", "Volver");
}
if (!verificar_csrf($_POST['_csrf'] ?? '')) {
    mostrar_error("Solicitud inválida", "Token de seguridad incorrecto.", "pedidos.php", "Volver a pedidos");
}

$pedido_id = (int)$_POST['id'];
$tienda_id = $_SESSION['tienda_id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT producto_id, estado FROM pedidos WHERE id = ? AND tienda_id = ? FOR UPDATE");
    $stmt->execute([$pedido_id, $tienda_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        $pdo->rollBack();
        mostrar_error("Pedido no encontrado", "", "pedidos.php", "Volver");
    }

    if ($pedido['estado'] !== 'Pendiente') {
        $pdo->rollBack();
        mostrar_error("El pedido ya fue procesado", "Solo se pueden cancelar pedidos Pendientes.", "pedidos.php", "Volver a pedidos");
    }

    $stmtUpd = $pdo->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ?");
    $stmtUpd->execute([$pedido_id]);

    if ($pedido['producto_id'] !== null) {
        $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock + 1 WHERE id = ?");
        $stmtStock->execute([$pedido['producto_id']]);
    }

    $pdo->commit();

    $u = obtener_usuario_actual();
    $detalle = $pedido['producto_id'] !== null ? "Pedido #$pedido_id - stock restituido" : "Pedido #$pedido_id - producto eliminado, stock no restituido";
    registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Canceló pedido', $detalle);

    $_SESSION['flash_message'] = 'Pedido cancelado y stock restituido.';
    $_SESSION['flash_type'] = 'warning';
    header("Location: pedidos.php");
    exit;

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al cancelar pedido #$pedido_id: " . $e->getMessage());
    mostrar_error("Error al procesar", "No se pudo cancelar el pedido.", "pedidos.php", "Volver a pedidos");
}
