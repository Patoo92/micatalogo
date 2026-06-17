<?php
session_start();
require_once 'conexion.php';

// Control de seguridad y verificación de que llega un ID
if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$producto_id = (int)$_GET['id'];
$error = '';
$exito = '';

// 1. PROCESAR EL FORMULARIO CUANDO SE LE DA A "ACTUALIZAR"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    
    // Recuperamos la URL de la imagen actual por si el dueño no sube una nueva
    $url_imagen_final = $_POST['imagen_actual'];

    // Si el usuario subió una imagen nueva, la procesamos
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $nombre_archivo = $_FILES['imagen']['name'];
        $ruta_temporal = $_FILES['imagen']['tmp_name'];
        $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
        $nuevo_nombre_archivo = time() . "_" . uniqid() . "." . $extension;
        $ruta_destino = "imagenes/" . $nuevo_nombre_archivo;

        if (move_uploaded_file($ruta_temporal, $ruta_destino)) {
            $url_imagen_final = $ruta_destino; // Sobrescribimos la vieja con la nueva
        }
    }

    // Actualizamos la base de datos
    try {
        $sql = "UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, stock = ?, stock_minimo = ?, imagen_url = ? WHERE id = ? AND tienda_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoria_id, $nombre, $descripcion, $precio, $stock, $stock_minimo, $url_imagen_final, $producto_id, $tienda_id]);
        $exito = "¡Producto actualizado correctamente!";
    } catch (\PDOException $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// 2. OBTENER LOS DATOS ACTUALES DEL PRODUCTO PARA RELLENAR EL FORMULARIO
$stmtProd = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND tienda_id = ?");
$stmtProd->execute([$producto_id, $tienda_id]);
$producto = $stmtProd->fetch();

// Si un intruso pone un ID de un producto que no es suyo, lo bloqueamos
if (!$producto) {
    die("Producto no encontrado o no tienes permisos para editarlo.");
}

// 3. OBTENER LAS CATEGORÍAS PARA EL SELECTOR
$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tienda_id = ?");
$stmtCat->execute([$tienda_id]);
$categorias = $stmtCat->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <div class="container my-5" style="max-width: 600px;">
        
        <div class="card shadow-sm p-4 bg-white border-0">
            <h3 class="fw-bold text-dark mb-1">✏️ Editar Producto</h3>
            <p class="text-muted mb-4">Modifica los datos del producto seleccionado.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="alert alert-success py-2"><?php echo $exito; ?></div>
            <?php endif; ?>

            <form action="editar-producto.php?id=<?php echo $producto_id; ?>" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($producto['imagen_url']); ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Precio (€) *</label>
                        <input type="number" step="0.01" name="precio" class="form-control" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($producto['categoria_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Stock Actual *</label>
                        <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Mínimo Alerta *</label>
                        <input type="number" name="stock_minimo" class="form-control" value="<?php echo htmlspecialchars($producto['stock_minimo']); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Cambiar Foto (Opcional)</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="<?php echo htmlspecialchars($producto['imagen_url']); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded border">
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                    </div>
                    <div class="form-text">Si no eliges archivo, se mantendrá la foto actual.</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="admin.php" class="btn btn-outline-secondary w-50 fw-bold">← Volver</a>
                    <button type="submit" class="btn btn-primary w-50 fw-bold">Actualizar Datos</button>
                </div>

            </form>
        </div>

    </div>

</body>
</html>