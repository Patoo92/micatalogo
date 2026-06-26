<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$staff_id = (int)$_GET['id'];
$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM store_staff WHERE id = ? AND tienda_id = ?");
$stmt->execute([$staff_id, $tienda_id]);
$staff = $stmt->fetch();

if (!$staff) {
    mostrar_error("Staff no encontrado", "El miembro del staff que buscas no existe.", "staff.php", "Volver a staff");
}

$permisos = json_decode($staff['permisos'], true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $nuevos_permisos = [
        'productos_ver' => !empty($_POST['perm_productos_ver']),
        'productos_crear' => !empty($_POST['perm_productos_crear']),
        'productos_editar' => !empty($_POST['perm_productos_editar']),
        'productos_eliminar' => !empty($_POST['perm_productos_eliminar']),
        'pedidos_ver' => !empty($_POST['perm_pedidos_ver']),
        'pedidos_gestionar' => !empty($_POST['perm_pedidos_gestionar']),
        'configuracion_editar' => !empty($_POST['perm_configuracion_editar']),
    ];

    try {
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error = "La contraseña debe tener al menos 8 caracteres.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE store_staff SET email = ?, password = ?, permisos = ? WHERE id = ? AND tienda_id = ?");
                $stmt->execute([$email ?: null, $hash, json_encode($nuevos_permisos), $staff_id, $tienda_id]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE store_staff SET email = ?, permisos = ? WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$email ?: null, json_encode($nuevos_permisos), $staff_id, $tienda_id]);
        }

        if (empty($error)) {
            $u = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Editó un miembro del staff', "ID: $staff_id");

            $exito = "Staff actualizado correctamente.";
            $stmt = $pdo->prepare("SELECT * FROM store_staff WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$staff_id, $tienda_id]);
            $staff = $stmt->fetch();
            $permisos = json_decode($staff['permisos'], true) ?? [];
        }
    } catch (PDOException $e) {
        error_log("Error al editar staff: " . $e->getMessage());
        $error = "Error al actualizar. Intenta de nuevo.";
    }
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Editar Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js" nonce="<?= $csp_nonce ?>"></script>

</head>
<body>

    <?php require __DIR__ . '/templates/sidebar_partial.php'; ?>
    <div class="page-wrapper">
    <?php require __DIR__ . '/templates/toast_partial.php'; ?>

    <?php if ($exito): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($exito); ?>, 'success'); });</script>
    <?php elseif ($error): ?>
    <script nonce="<?= $csp_nonce ?>">window.addEventListener('DOMContentLoaded', function() { mostrarToast(<?php echo js_escape($error); ?>, 'danger'); });</script>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm d-lg-none">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2 text-white" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#staffEditarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="staffEditarNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 600px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title d-flex align-items-center gap-2"><iconify-icon icon="mdi:account-edit" width="24"></iconify-icon> Editar Staff: <?php echo htmlspecialchars($staff['usuario']); ?></h3>
            </div>
            <div class="card-body">

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($staff['usuario']); ?>" disabled>
                    <div class="form-text">No se puede cambiar el nombre de usuario.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label">Nueva contraseña <span class="text-muted">(dejar vacío para mantener)</span></label>
                    <input type="password" name="password" class="form-control" minlength="8">
                </div>

                <h6 class="fw-bold mb-2">Permisos</h6>
                <div class="card bg-light border-0 p-3 mb-4">
                    <?php $checks = [
                        'productos_ver' => 'Ver productos',
                        'productos_crear' => 'Crear productos',
                        'productos_editar' => 'Editar productos',
                        'productos_eliminar' => 'Eliminar productos',
                        'pedidos_ver' => 'Ver pedidos',
                        'pedidos_gestionar' => 'Gestionar pedidos (marcar como vendido)',
                        'configuracion_editar' => 'Editar configuración',
                    ]; ?>
                    <?php foreach ($checks as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="perm_<?php echo $key; ?>" id="perm_<?php echo $key; ?>" <?php echo !empty($permisos[$key]) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="perm_<?php echo $key; ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-footer d-flex gap-2">
                    <a href="staff.php" class="btn btn-outline-secondary w-50">← Volver</a>
                    <button type="submit" class="btn btn-primary w-50" data-loading="Guardando…">
                        <iconify-icon icon="mdi:content-save" width="18"></iconify-icon> Guardar Cambios
                    </button>
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
