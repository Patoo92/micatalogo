<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stmt = $pdo->prepare("
    SELECT p.*, c.nombre_categoria 
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE p.tienda_id = ?
");
$stmt->execute([$tienda_id]);
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel de Administración - <?php echo $tienda_nombre; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="admin.php">🏪 <?php echo $tienda_nombre; ?></a>
        <div class="d-flex gap-2">
            <a href="admin.php" class="btn btn-sm btn-outline-light">Productos</a>
            <a href="pedidos.php" class="btn btn-sm btn-outline-light">Pedidos</a>
            <a href="configuracion.php" class="btn btn-sm btn-primary">⚙️ Configuración</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Salir</a>
        </div>
    </div>
</nav>

    <div class="container my-4" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark m-0">Inventario</h2>
            </div>
            <a href="nuevo-producto.php" class="btn btn-success fw-bold">+ Nuevo</a>
        </div>

        <div class="d-md-none">
    <?php foreach ($productos as $prod): ?>
    <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?php echo $prod['imagen_url']; ?>" style="width: 60px; height: 60px; object-fit: cover;" class="rounded">
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold"><?php echo $prod['nombre']; ?></h6>
                    <small class="text-muted"><?php echo $prod['precio']; ?> €</small>
                </div>
                <div class="text-end">
                    <div class="badge <?php echo ($prod['stock'] <= $prod['stock_minimo']) ? 'bg-danger' : 'bg-success'; ?>">
                        <?php echo $prod['stock']; ?> uds
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <a href="editar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-outline-secondary w-50 btn-sm">✏️ Editar</a>
                <a href="eliminar-producto.php?id=<?php echo $prod['id']; ?>" 
                   class="btn btn-outline-danger w-50 btn-sm" 
                   onclick="return confirm('¿Eliminar <?php echo $prod['nombre']; ?>?');">
                   🗑️ Eliminar
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

        <div class="card shadow-sm border-0 d-none d-md-block">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th class="text-center">Stock</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td><img src="<?php echo $prod['imagen_url']; ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;"></td>
                            <td>
                                <div class="fw-bold"><?php echo $prod['nombre']; ?></div>
                                <small class="text-muted">ID: #<?php echo $prod['id']; ?></small>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo $prod['nombre_categoria'] ?? 'N/A'; ?></span></td>
                            <td class="fw-bold text-primary"><?php echo $prod['precio']; ?> €</td>
                            <td class="text-center">
                                <span class="badge <?php echo ($prod['stock'] <= $prod['stock_minimo']) ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo $prod['stock']; ?> uds
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="editar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-secondary">✏️</a>
                                    <a href="eliminar-producto.php?id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar?');">🗑️</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>