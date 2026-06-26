<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('productos_editar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para editar productos.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$producto_id = (int)$_GET['id'];
$error = '';
$exito = '';

$stmtProd = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND tienda_id = ?");
$stmtProd->execute([$producto_id, $tienda_id]);
$producto = $stmtProd->fetch();

if (!$producto) {
    mostrar_error("Producto no encontrado", "No tienes permisos para editar este producto.", "admin.php", "Volver al panel");
}

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
    
    $url_imagen_final = $_POST['imagen_actual'];
    $url_thumb = $producto['imagen_thumb'] ?? '';

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

    if (isset($thumb_ruta) && file_exists($thumb_ruta)) {
        $url_thumb = $thumb_ruta;
    }

    $destacado = !empty($_POST['destacado']) ? 1 : 0;
    $etiqueta = trim($_POST['etiqueta'] ?? '');

    if (empty($error)) try {
        $sql = "UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, stock = ?, stock_minimo = ?, destacado = ?, etiqueta = ?, imagen_url = ?, imagen_thumb = ? WHERE id = ? AND tienda_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoria_id, $nombre, $descripcion, $precio, $stock, $stock_minimo, $destacado, $etiqueta, $url_imagen_final, $url_thumb, $producto_id, $tienda_id]);
        $exito = "¡Producto actualizado correctamente!";
        $u = obtener_usuario_actual();
        registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Editó un producto', "ID: $producto_id - $nombre");
        // Recargar producto para reflejar datos actualizados
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND tienda_id = ?");
        $stmtProd->execute([$producto_id, $tienda_id]);
        $producto = $stmtProd->fetch();
    } catch (\PDOException $e) {
        $error = "Error al actualizar datos. Intenta de nuevo.";
    }
}
}

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js" nonce="<?= $csp_nonce ?>"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
    </style>
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
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#editarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="editarNav">
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
                <h3 class="card-title d-flex align-items-center gap-2"><iconify-icon icon="mdi:pencil" width="24"></iconify-icon> Editar Producto</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Modifica los datos del producto seleccionado.</p>

                <form action="editar-producto.php?id=<?php echo $producto_id; ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    
                    <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($producto['imagen_url']); ?>">

                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto *</label>
                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="2"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Precio *</label>
                            <div class="input-group">
                                <span class="input-group-text">€</span>
                                <input type="number" step="0.01" name="precio" class="form-control" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Categoría</label>
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

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Stock Actual *</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo htmlspecialchars($producto['stock']); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Mínimo Alerta *</label>
                            <input type="number" name="stock_minimo" class="form-control" value="<?php echo htmlspecialchars($producto['stock_minimo']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="hidden" name="destacado" value="0">
                            <input type="checkbox" name="destacado" id="destacado" class="form-check-input" value="1" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                            <span class="form-check-label">Producto destacado</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Etiqueta especial</label>
                        <select name="etiqueta" class="form-select">
                            <option value="">Ninguna</option>
                            <option value="Nuevo" <?php echo ($producto['etiqueta'] ?? '') === 'Nuevo' ? 'selected' : ''; ?>>Nuevo</option>
                            <option value="Oferta" <?php echo ($producto['etiqueta'] ?? '') === 'Oferta' ? 'selected' : ''; ?>>Oferta</option>
                            <option value="Sin stock" <?php echo ($producto['etiqueta'] ?? '') === 'Sin stock' ? 'selected' : ''; ?>>Sin stock</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cambiar Foto</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="<?php echo htmlspecialchars(imagen_url($producto['imagen_url'])); ?>" style="width: 50px; height: 50px; object-fit: cover;" class="rounded border">
                            <input type="file" name="imagen" class="form-control" accept="image/*">
                        </div>
                        <small class="form-hint">Si no eliges archivo, se mantendrá la foto actual.</small>
                    </div>

                    <div class="form-footer d-flex gap-2">
                        <a href="admin.php" class="btn btn-outline-secondary w-50">Cancelar</a>
                        <button type="submit" class="btn btn-primary w-50" data-loading="Guardando…">Actualizar Datos</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
    (function() {
        var html = document.documentElement;
        var toggle = document.getElementById('darkModeToggle');
        var icon = toggle && toggle.querySelector('iconify-icon');
        var span = toggle && toggle.querySelector('span');
        if (localStorage.getItem('dark_mode') === '1') {
            html.setAttribute('data-bs-theme', 'dark');
            if (icon) icon.setAttribute('icon', 'mdi:weather-sunny');
            if (span) span.textContent = 'Modo claro';
        }
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var isDark = html.getAttribute('data-bs-theme') === 'dark';
                if (isDark) {
                    html.removeAttribute('data-bs-theme');
                } else {
                    html.setAttribute('data-bs-theme', 'dark');
                }
                localStorage.setItem('dark_mode', html.getAttribute('data-bs-theme') === 'dark' ? '1' : '0');
                if (icon) icon.setAttribute('icon', html.getAttribute('data-bs-theme') === 'dark' ? 'mdi:weather-sunny' : 'mdi:weather-night');
                if (span) span.textContent = html.getAttribute('data-bs-theme') === 'dark' ? 'Modo claro' : 'Modo oscuro';
            });
        }
    })();
    </script>
    </div>
</body>
</html>