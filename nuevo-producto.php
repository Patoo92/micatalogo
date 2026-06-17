<?php
session_start();
require_once 'conexion.php';

// Control de seguridad
if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$error = '';
$exito = '';

// Traemos las categorías de esta tienda para mostrarlas en el selector desplegable (Select)
$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tienda_id = ?");
$stmtCat->execute([$tienda_id]);
$categorias = $stmtCat->fetchAll();

// Procesamos el formulario cuando el dueño le da al botón "Guardar"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;

    // Validación básica
    if (empty($nombre) || $precio <= 0 || $stock < 0) {
        $error = "Por favor, rellena los campos obligatorios con valores válidos.";
    } else {
        // --- PROCESAMIENTO DE LA IMAGEN ---
        $url_imagen_final = "https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=500"; // Foto por defecto por si no sube ninguna

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $nombre_archivo = $_FILES['imagen']['name'];
            $ruta_temporal = $_FILES['imagen']['tmp_name'];
            
            // Limpiamos el nombre del archivo para evitar problemas con espacios o caracteres raros
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            $nuevo_nombre_archivo = time() . "_" . uniqid() . "." . $extension; // Ej: 171854321_666f.jpg
            
            $ruta_destino = "imagenes/" . $nuevo_nombre_archivo;

            // Movemos el archivo desde la carpeta temporal de XAMPP a nuestra carpeta 'imagenes'
            if (move_uploaded_file($ruta_temporal, $ruta_destino)) {
                // Si se movió con éxito, guardamos esa ruta relativa en la variable
                $url_imagen_final = $ruta_destino;
            }
        }

        // --- INSERTAR EN LA BASE DE DATOS ---
        try {
            $sql = "INSERT INTO productos (tienda_id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, imagen_url) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tienda_id, $categoria_id, $nombre, $descripcion, $precio, $stock, $stock_minimo, $url_imagen_final]);

            $exito = "¡Producto guardado exitosamente en tu catálogo!";
        } catch (\PDOException $e) {
            $error = "Error al guardar en la base de datos: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Añadir Nuevo Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

    <div class="container my-5" style="max-width: 600px;">
        
        <div class="card shadow-sm p-4 bg-white border-0">
            <h3 class="fw-bold text-dark mb-1">📦 Nuevo Producto</h3>
            <p class="text-muted mb-4">Rellena los datos para subir un artículo al catálogo público</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($exito)): ?>
                <div class="alert alert-success py-2"><?php echo $exito; ?></div>
            <?php endif; ?>

            <form action="nuevo-producto.php" method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del Producto *</label>
                    <input type="text" name="nombre" class="form-control" placeholder="Ej: Gorra Trucker Vintage" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descripción (Opcional)</label>
                    <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles de tallas, colores..."></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Precio (€) *</label>
                        <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Categoría</label>
                        <select name="categoria_id" class="form-select">
                            <option value="">Sin categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre_categoria']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Stock Inicial *</label>
                        <input type="number" name="stock" class="form-control" placeholder="10" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Stock Mínimo Alerta *</label>
                        <input type="number" name="stock_minimo" class="form-control" value="3" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Foto del Producto</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                    <div class="form-text">Formatos recomendados: JPG o PNG.</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="admin.php" class="btn btn-outline-secondary w-50 fw-bold">← Volver al Panel</a>
                    <button type="submit" class="btn btn-success w-50 fw-bold">Guardar Producto</button>
                </div>

            </form>
        </div>

    </div>

</body>
</html>