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

        $destacado = !empty($_POST['destacado']) ? 1 : 0;
        $etiqueta = trim($_POST['etiqueta'] ?? '');

        if (empty($error)) try {
            $sql = "INSERT INTO productos (tienda_id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, destacado, etiqueta, imagen_url, imagen_thumb) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tienda_id, $categoria_id, $nombre, $descripcion, $precio, $stock, $stock_minimo, $destacado, $etiqueta, $url_imagen_final, $url_thumb]);

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
<?php $page_title = 'A�adir Nuevo Producto'; ?>
<?php require __DIR__ . '/templates/head.php'; ?>
</head>
<body>

    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>
    <div class="page-wrapper">
    <?php require __DIR__ . '/templates/toast_partial.php'; ?>

    <?php if (!empty($exito)): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($exito); ?>, 'success'); });</script>
    <?php elseif (!empty($error)): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($error); ?>, 'danger'); });</script>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nuevoNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nuevoNav">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:arrow-left" width="16"></iconify-icon> Volver</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5" style="max-width: 640px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title d-flex align-items-center gap-2"><iconify-icon icon="mdi:package-variant-closed" width="24"></iconify-icon> Nuevo Producto</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Rellena los datos para subir un artículo al catálogo público</p>

                <form action="nuevo-producto.php" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto *</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Gorra Trucker Vintage" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles de tallas, colores..."></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Precio *</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Stock Inicial *</label>
                            <input type="number" name="stock" class="form-control" placeholder="10" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stock Mínimo *</label>
                            <input type="number" name="stock_minimo" class="form-control" value="3" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="hidden" name="destacado" value="0">
                            <input type="checkbox" name="destacado" id="destacado" class="form-check-input" value="1" checked>
                            <span class="form-check-label">Destacar en el catálogo</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Etiqueta especial</label>
                        <select name="etiqueta" class="form-select">
                            <option value="">Ninguna</option>
                            <option value="Nuevo">Nuevo</option>
                            <option value="Oferta">Oferta</option>
                            <option value="Sin stock">Sin stock</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto del Producto</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                        <small class="form-hint">Formatos: JPG, PNG, WEBP. Máx 2 MB.</small>
                    </div>

                    <div class="form-footer d-flex gap-2">
                        <a href="admin.php" class="btn btn-outline-secondary w-50">Cancelar</a>
                        <button type="submit" class="btn btn-primary w-50" data-loading="Guardando…">Guardar Producto</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/templates/darkmode_admin_partial.php'; ?>
    </div>
</body>
</html>