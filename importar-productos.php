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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Productos CSV</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .container { max-width: 600px; }
    </style>
</head>
<body>
    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>
    <div class="page-wrapper">
    <div class="container my-5">
        <div class="card p-4">
            <h4 class="fw-bold mb-3"><iconify-icon icon="mdi:file-upload" width="24"></iconify-icon> Importar Productos CSV</h4>
            <?php echo $mensaje; ?>
            <form method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Archivo CSV</label>
                    <input type="file" name="csv" class="form-control" accept=".csv" required>
                    <small class="text-muted">Columnas: nombre, descripcion, precio, stock, stock_minimo, categoria_id, destacado, etiqueta</small>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Importar</button>
            </form>
            <hr class="my-4">
            <a href="exportar-productos.php" class="btn btn-outline-success w-100 fw-bold"><iconify-icon icon="mdi:file-download" width="18"></iconify-icon> Descargar CSV de ejemplo</a>
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
