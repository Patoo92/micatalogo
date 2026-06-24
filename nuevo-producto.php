<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('productos_crear')) {
    mostrar_error("Acceso denegado", "No tienes permiso para crear productos.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$error = '';
$exito = '';

$stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tienda_id = ?");
$stmtCat->execute([$tienda_id]);
$categorias = $stmtCat->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $stock = (int)$_POST['stock'];
    $stock_minimo = (int)$_POST['stock_minimo'];
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;

    if (empty($nombre) || $precio <= 0 || $stock < 0) {
        $error = "Por favor, rellena los campos obligatorios con valores válidos.";
    } else {
        $url_imagen_final = imagen_defecto();

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ruta_temporal = $_FILES['imagen']['tmp_name'];

            $TIPOS_PERMITIDOS = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_real = $finfo->file($ruta_temporal);

            if (!array_key_exists($mime_real, $TIPOS_PERMITIDOS)) {
                $error = "Solo se permiten imágenes JPG, PNG, GIF o WEBP.";
            } else {
                $extension = $TIPOS_PERMITIDOS[$mime_real];
                $nuevo_nombre_archivo = time() . "_" . uniqid() . "." . $extension;
                $ruta_destino = ruta_imagen($tienda_id) . "/" . $nuevo_nombre_archivo;

                if (move_uploaded_file($ruta_temporal, $ruta_destino)) {
                    $url_imagen_final = $ruta_destino;
                    $thumb_nombre = 'thumb_' . $nuevo_nombre_archivo;
                    $thumb_ruta = ruta_imagen($tienda_id) . "/" . $thumb_nombre;
                    generar_thumbnail($ruta_destino, $thumb_ruta, 300, 300);
                }
            }
        }

        $url_thumb = '';
        if (isset($thumb_ruta) && file_exists($thumb_ruta)) {
            $url_thumb = $thumb_ruta;
        }

        if (empty($error)) try {
            $sql = "INSERT INTO productos (tienda_id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, imagen_url, imagen_thumb) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tienda_id, $categoria_id, $nombre, $descripcion, $precio, $stock, $stock_minimo, $url_imagen_final, $url_thumb]);

            $exito = "¡Producto guardado exitosamente en tu catálogo!";
            $u = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Creó un producto', "Nombre: $nombre");
        } catch (\PDOException $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            $error = "Error al guardar el producto. Intenta de nuevo.";
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
    </style>
</head>
<body class="bg-light">

    <?php require __DIR__ . '/templates/toast_partial.php'; ?>

    <?php if (!empty($exito)): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($exito); ?>, 'success'); });</script>
    <?php elseif (!empty($error)): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($error); ?>, 'danger'); });</script>
    <?php endif; ?>

    <div class="container my-5" style="max-width: 600px;">
        
        <div class="card shadow-sm glass-card p-4 border-0">
            <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2"><iconify-icon icon="mdi:package-variant-closed" width="24"></iconify-icon> Nuevo Producto</h3>
            <p class="text-muted mb-4">Rellena los datos para subir un artículo al catálogo público</p>

            <form action="nuevo-producto.php" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                
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
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
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
                    <button type="submit" class="btn btn-success w-50 fw-bold" data-loading="Guardando…">Guardar Producto</button>
                </div>

            </form>
        </div>

    </div>

</body>
</html>