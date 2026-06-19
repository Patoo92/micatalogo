<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ?");
$stmt->execute([$_SESSION['tienda_id']]);
$tienda = $stmt->fetch();

// Mensaje de éxito o error tras guardar
$mensaje = '';
if (isset($_GET['success'])) {
    $mensaje = '<div class="alert alert-success">✅ Configuración guardada correctamente.</div>';
} elseif (isset($_GET['error'])) {
    $mensaje = '<div class="alert alert-danger">❌ Solo se permiten imágenes JPG, PNG, GIF o WEBP.</div>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Tienda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm border-0 p-4">
            <h4 class="mb-4">⚙️ Configuración de Tienda</h4>

            <?php echo $mensaje; ?>

            <form action="guardar-configuracion.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label>Logo de tu tienda</label>
                    <input type="file" name="logo" class="form-control">
                    <?php if (!empty($tienda['logo_url'])): ?>
                        <small class="text-muted">Logo actual: <?php echo htmlspecialchars($tienda['logo_url']); ?></small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label>Instagram URL</label>
                    <input type="text" name="instagram" value="<?php echo htmlspecialchars($tienda['instagram_url'] ?? ''); ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Color principal de la marca</label>
                    <input type="color" name="color" value="<?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>" class="form-control form-control-color">
                </div>

                <div class="mb-3">
                    <label>Número de WhatsApp (ej: +34600123456)</label>
                    <!-- Corregido: lee telefono_whatsapp, no whatsapp_number -->
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($tienda['telefono_whatsapp'] ?? ''); ?>" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary w-100">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
