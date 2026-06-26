<?php
require_once 'init_session.php';
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
    } elseif (!verificar_rate_limit($pdo, 'cambio_password', 5)) {
        $error = "Demasiados intentos. Espera 15 minutos.";
    } else {
    $actual = $_POST['password_actual'];
    $nueva = $_POST['password_nueva'];
    $confirmar = $_POST['password_confirmar'];

    if (empty($actual) || empty($nueva) || empty($confirmar)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($nueva) < 10 || !preg_match('/[A-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
        $error = "La nueva contraseña debe tener al menos 10 caracteres, una mayúscula y un número.";
    } elseif ($nueva !== $confirmar) {
        $error = "Las contraseñas nuevas no coinciden.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM tiendas WHERE id = ?");
        $stmt->execute([$tienda_id]);
        $tienda = $stmt->fetch();

        if (!$tienda || !password_verify($actual, $tienda['password'])) {
            $error = "La contraseña actual no es correcta.";
            registrar_intento_login($pdo, 'cambio_password');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js" nonce="<?= $csp_nonce ?>"></script>
    <style>
        .input-wrapper { position: relative; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;
        }
        .password-toggle:hover { color: #64748b; }
    </style>
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
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#passwordNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="passwordNav">
                <div class="d-flex gap-2 flex-wrap justify-content-end">
                    <a href="admin.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                    <a href="pedidos.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                    <a href="configuracion.php" class="btn btn-sm btn-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                    <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 500px;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title d-flex align-items-center gap-2"><iconify-icon icon="mdi:lock-reset" width="24"></iconify-icon> Cambiar Contraseña</h3>
            </div>
            <div class="card-body">

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label">Contraseña actual</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_actual" class="form-control" required>
                        <button type="button" class="password-toggle" data-toggle-pass="actual">
                            <iconify-icon icon="mdi:eye-outline" width="18"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nueva contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_nueva" class="form-control" minlength="8" required>
                        <button type="button" class="password-toggle" data-toggle-pass="nueva">
                            <iconify-icon icon="mdi:eye-outline" width="18"></iconify-icon>
                        </button>
                    </div>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" name="password_confirmar" class="form-control" required>
                        <button type="button" class="password-toggle" data-toggle-pass="confirmar">
                            <iconify-icon icon="mdi:eye-outline" width="18"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100" data-loading="Guardando…">
                        <iconify-icon icon="mdi:content-save" width="18"></iconify-icon> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
    document.querySelectorAll('[data-toggle-pass]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var field = this.getAttribute('data-toggle-pass');
            var input = document.querySelector('input[name="password_' + field + '"]');
            if (!input) return;
            var icon = this.querySelector('iconify-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('icon', 'mdi:eye-off-outline');
            } else {
                input.type = 'password';
                icon.setAttribute('icon', 'mdi:eye-outline');
            }
        });
    });
    </script>
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
