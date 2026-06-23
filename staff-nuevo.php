<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('staff_crear')) {
    mostrar_error("Acceso denegado", "No tienes permiso para crear miembros del staff.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);

    $permisos = [
        'productos_ver' => !empty($_POST['perm_productos_ver']),
        'productos_crear' => !empty($_POST['perm_productos_crear']),
        'productos_editar' => !empty($_POST['perm_productos_editar']),
        'productos_eliminar' => !empty($_POST['perm_productos_eliminar']),
        'pedidos_ver' => !empty($_POST['perm_pedidos_ver']),
        'pedidos_gestionar' => !empty($_POST['perm_pedidos_gestionar']),
        'configuracion_editar' => !empty($_POST['perm_configuracion_editar']),
    ];

    if (empty($usuario) || empty($password)) {
        $error = "Usuario y contraseña son obligatorios.";
    } elseif (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "La contraseña debe tener al menos 10 caracteres, una mayúscula y un número.";
    } else {
        $check = $pdo->prepare("SELECT id FROM store_staff WHERE tienda_id = ? AND usuario = ?");
        $check->execute([$tienda_id, $usuario]);
        if ($check->fetch()) {
            $error = "Ese usuario ya existe en tu tienda.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO store_staff (tienda_id, usuario, password, email, permisos) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tienda_id, $usuario, $hash, $email ?: null, json_encode($permisos)]);

            $u = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Creó un miembro del staff', "Usuario: $usuario");

            $exito = "Staff creado correctamente.";
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
    <title>Nuevo Staff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar-admin { background: linear-gradient(135deg, #1e293b, #0f172a) !important; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        .card-custom { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
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

    <script nonce="<?= $csp_nonce ?>">
    <?php if ($exito): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var t = document.getElementById('crudToast');
        t.classList.add('text-bg-success');
        document.getElementById('toastBody').innerHTML = '<iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> <?php echo js_escape($exito); ?>';
        bootstrap.Toast.getOrCreateInstance(t).show();
    });
    <?php elseif ($error): ?>
    window.addEventListener('DOMContentLoaded', function() {
        var t = document.getElementById('crudToast');
        t.classList.add('text-bg-danger');
        document.getElementById('toastBody').innerHTML = '<iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> <?php echo js_escape($error); ?>';
        bootstrap.Toast.getOrCreateInstance(t).show();
    });
    <?php endif; ?>
    </script>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-admin shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="admin.php">
                <iconify-icon icon="mdi:store" width="28" height="28"></iconify-icon>
                <?php echo htmlspecialchars($tienda_nombre); ?>
            </a>
            <div class="d-flex gap-2">
                <a href="admin.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:package-variant-closed" width="16"></iconify-icon> Productos</a>
                <a href="pedidos.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:format-list-bulleted" width="16"></iconify-icon> Pedidos</a>
                <a href="configuracion.php" class="btn btn-sm btn-outline-light btn-icon"><iconify-icon icon="mdi:cog" width="16"></iconify-icon> Configuración</a>
                <a href="logout.php" class="btn btn-sm btn-danger btn-icon"><iconify-icon icon="mdi:logout" width="16"></iconify-icon> Salir</a>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 600px;">
        <div class="card card-custom p-4">
            <h4 class="mb-4 d-flex align-items-center gap-2">
                <iconify-icon icon="mdi:account-plus" width="28"></iconify-icon>
                Nuevo Staff
            </h4>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Usuario *</label>
                    <input type="text" name="usuario" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña *</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control">
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
                            <input class="form-check-input" type="checkbox" name="perm_<?php echo $key; ?>" id="perm_<?php echo $key; ?>">
                            <label class="form-check-label" for="perm_<?php echo $key; ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex gap-2">
                    <a href="staff.php" class="btn btn-outline-secondary w-50 fw-bold">← Volver</a>
                    <button type="submit" class="btn btn-success w-50 fw-bold btn-icon">
                        <iconify-icon icon="mdi:account-plus" width="18"></iconify-icon> Crear Staff
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
