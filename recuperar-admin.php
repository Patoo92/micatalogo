<?php
require_once 'init_session.php';
require_once 'conexion.php';

// C1: la regeneración de la contraseña master ya NO es anónima. Requiere
// sesión de administrador autenticada. Nunca se muestra una contraseña.
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    mostrar_error("Acceso denegado", "La regeneración de contraseña requiere iniciar sesión como administrador.", "login-admin.php", "Ir al login");
    exit;
}

$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = 'Solicitud inválida.';
    } elseif (!verificar_rate_limit($pdo, 'admin', 5, 15)) {
        $error = 'Demasiados intentos. Espera 15 minutos.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 10) {
            $error = 'La contraseña debe tener al menos 10 caracteres.';
        } elseif ($password !== $confirm) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $upd  = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $upd->execute([$hash, $_SESSION['admin_id']]);
            $mensaje = 'Contraseña del administrador actualizada correctamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña Master</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;">
    <div class="card card-md">
        <div class="card-body">
            <div class="text-center mb-3">
                <iconify-icon icon="mdi:shield-lock" width="40" style="color: #10b981;"></iconify-icon>
                <h2 class="d-flex align-items-center justify-content-center gap-2 mt-2"><iconify-icon icon="mdi:lock-reset" width="24" style="color:#10b981;"></iconify-icon> Cambiar Contraseña Master</h2>
                <p class="text-muted">Define una nueva contraseña para el panel de administración.</p>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success text-center">
                    <strong><?php echo htmlspecialchars($mensaje); ?></strong>
                    <div class="mt-2">
                        <a href="super-admin.php" class="btn btn-success btn-sm">Ir al panel</a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php include __DIR__ . '/templates/_form_cambiar_pass_admin.php'; ?>
            <?php else: ?>
                <?php include __DIR__ . '/templates/_form_cambiar_pass_admin.php'; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
