<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $actual = $_POST['password_actual'];
    $nueva = $_POST['password_nueva'];
    $confirmar = $_POST['password_confirmar'];

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($nueva) < 8) {
        $error = "La nueva contraseña debe tener al menos 8 caracteres.";
    } elseif ($nueva !== $confirmar) {
        $error = "Las contraseñas nuevas no coinciden.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM tiendas WHERE id = ?");
        $stmt->execute([$tienda_id]);
        $tienda = $stmt->fetch();

        if (!$tienda || !password_verify($actual, $tienda['password'])) {
            $error = "La contraseña actual no es correcta.";
        } else {
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE tiendas SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $tienda_id]);

            $usuario = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $usuario['nombre'], $usuario['tipo'], 'Cambió su contraseña');

            $exito = "Contraseña actualizada correctamente.";
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
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar-admin { background: linear-gradient(135deg, #1e293b, #0f172a) !important; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .input-wrapper { position: relative; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;
        }
        .password-toggle:hover { color: #64748b; }
    </style>
</head>
<body class="bg-light">

    <div class="toast-container-custom">
        <div id="crudToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
            <div class="d-flex">
                <div id="toastBody" class="toast-body d-flex align-items-center gap-2"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script>
    <?php if ($exito): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var t = document.getElementById('crudToast');
        t.classList.add('text-bg-success');
        document.getElementById('toastBody').innerHTML = '<iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> <?php echo addslashes($exito); ?>';
        bootstrap.Toast.getOrCreateInstance(t).show();
    });
    <?php elseif ($error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var t = document.getElementById('crudToast');
        t.classList.add('text-bg-danger');
        document.getElementById('toastBody').innerHTML = '<iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> <?php echo addslashes($error); ?>';
        bootstrap.Toast.getOrCreateInstance(t).show();
    });
    <?php endif; ?>
    </script>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo $tienda_nombre; ?>
            </a>
            <div class="d-flex gap-2">
                <a href="admin.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                <a href="pedidos.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                <a href="configuracion.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 500px;">
        <div class="card card-custom p-4">
            <h4 class="mb-4 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:lock-reset" width="28"></iconify-icon>
                Cambiar Contraseña
            </h4>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña actual</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_actual" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="toggle('actual', 'eyeActual')">
                            <iconify-icon icon="mdi:eye-outline" id="eyeActual" width="18"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nueva contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_nueva" class="form-control" minlength="8" required>
                        <button type="button" class="password-toggle" onclick="toggle('nueva', 'eyeNueva')">
                            <iconify-icon icon="mdi:eye-outline" id="eyeNueva" width="18"></iconify-icon>
                        </button>
                    </div>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_confirmar" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="toggle('confirmar', 'eyeConfirm')">
                            <iconify-icon icon="mdi:eye-outline" id="eyeConfirm" width="18"></iconify-icon>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-icon py-2">
                    <iconify-icon icon="mdi:content-save" width="18"></iconify-icon> Guardar Cambios
                </button>
            </form>
        </div>
    </div>

    <script>
    function toggle(field, eyeId) {
        var input = document.querySelector('input[name="password_' + field + '"]');
        var icon = document.getElementById(eyeId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('icon', 'mdi:eye-off-outline');
        } else {
            input.type = 'password';
            icon.setAttribute('icon', 'mdi:eye-outline');
        }
    }
    </script>
</body>
</html>
