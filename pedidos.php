<?php
session_start();
require_once 'conexion.php';

// Control de seguridad
if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

// Traemos los pedidos de esta tienda, ordenados para que los más nuevos salgan arriba
// Usamos JOIN para saber el nombre y precio del producto que el cliente seleccionó
$stmt = $pdo->prepare("
    SELECT p.*, prod.nombre AS producto_nombre, prod.precio 
    FROM pedidos p 
    JOIN productos prod ON p.producto_id = prod.id 
    WHERE p.tienda_id = ? 
    ORDER BY p.fecha_pedido DESC
");
$stmt->execute([$tienda_id]);
$pedidos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gestión de Pedidos - <?php echo $tienda_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="admin.php">🏪 <?php echo $tienda_nombre; ?></a>
            <div class="d-flex gap-3">
                <a href="admin.php" class="btn btn-sm btn-outline-light fw-bold">📦 Productos</a>
                <a href="pedidos.php" class="btn btn-sm btn-primary fw-bold">📋 Ver Pedidos</a>
                <a href="logout.php" class="btn btn-sm btn-danger">🚪 Salir</a>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 800px;">
        
        <div class="mb-4">
            <h2 class="fw-bold text-dark mb-0">Control de Pedidos de WhatsApp</h2>
            <p class="text-muted mb-0">Monitorea los clientes que iniciaron contacto para comprar</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID Pedido</th>
                            <th>Cliente</th>
                            <th>Producto Solicitado</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No hay registros de pedidos aún.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $ped): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $ped['id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($ped['nombre_cliente']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo $ped['producto_nombre']; ?></div>
                                        <small class="text-success fw-bold"><?php echo $ped['precio']; ?> €</small>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.85rem;">
                                        <?php echo date('d/m/Y H:i', strtotime($ped['fecha_pedido'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <span class="badge bg-warning text-dark">⏳ Pendiente</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">✅ Vendido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ped['estado'] === 'Pendiente'): ?>
                                            <a href="completar-pedido.php?id=<?php echo $ped['id']; ?>" class="btn btn-success btn-sm fw-bold">
                                                💰 Marcar como Vendido
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">Venta cerrada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>