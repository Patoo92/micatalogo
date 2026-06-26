<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('productos_crear')) {
    mostrar_error("Acceso denegado", "No tienes permiso para importar productos.", "admin.php", "Volver");
}

$tienda_id = $_SESSION['tienda_id'];
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger">Solicitud inválida.</div>';
    } else {
        $file = $_FILES['csv'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
            $mensaje = '<div class="alert alert-danger">Selecciona un archivo CSV válido.</div>';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle);
            $importados = 0;
            $errores = 0;

            $stmt = $pdo->prepare("INSERT INTO productos (tienda_id, categoria_id, nombre, descripcion, precio, stock, stock_minimo, destacado, etiqueta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);
                if (empty($data['nombre']) || (float)($data['precio'] ?? 0) <= 0) {
                    $errores++;
                    continue;
                }
                try {
                    $stmt->execute([
                        $tienda_id,
                        !empty($data['categoria_id']) ? (int)$data['categoria_id'] : null,
                        $data['nombre'],
                        $data['descripcion'] ?? '',
                        (float)$data['precio'],
                        (int)($data['stock'] ?? 0),
                        (int)($data['stock_minimo'] ?? 3),
                        !empty($data['destacado']) ? 1 : 0,
                        $data['etiqueta'] ?? '',
                    ]);
                    $importados++;
                } catch (Exception $e) {
                    $errores++;
                }
            }
            fclose($handle);
            $mensaje = '<div class="alert alert-success">Importación completada: ' . $importados . ' productos importados' . ($errores ? ', ' . $errores . ' errores.' : '.') . '</div>';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<?php $page_title = 'Importar Productos CSV'; ?>
<?php require __DIR__ . '/templates/head.php'; ?>
</head>
<body>
    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>
    <div class="page-wrapper">
    <div class="container my-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><iconify-icon icon="mdi:file-upload" width="24"></iconify-icon> Importar Productos CSV</h3>
            </div>
            <div class="card-body">
                <?php echo $mensaje; ?>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Archivo CSV</label>
                        <input type="file" name="csv" class="form-control" accept=".csv" required>
                        <small class="text-muted">Columnas: nombre, descripcion, precio, stock, stock_minimo, categoria_id, destacado, etiqueta</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Importar</button>
                </form>
                <hr class="my-4">
                <a href="exportar-productos.php" class="btn btn-outline-success w-100"><iconify-icon icon="mdi:file-download" width="18"></iconify-icon> Descargar CSV de ejemplo</a>
            </div>
        </div>
    </div>
    <?php require __DIR__ . '/templates/darkmode_admin_partial.php'; ?>
    </div>
</body>
</html>
