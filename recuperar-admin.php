<?php
require_once 'init_session.php';
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger">Solicitud inválida.</div>';
    } else {
        $stmt = $pdo->query("SELECT id, usuario, password FROM admins LIMIT 1");
        $admin = $stmt->fetch();

        if ($admin) {
            $nueva_password = bin2hex(random_bytes(12));
            $hash = password_hash($nueva_password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $upd->execute([$hash, $admin['id']]);

            $mensaje = '
            <div class="alert alert-success">
                <strong>Contraseña restablecida.</strong> Copiala ahora, solo se muestra una vez:
                <div style="background:#0f172a;color:#10b981;padding:12px;border-radius:8px;text-align:center;font-size:1.2rem;font-weight:700;letter-spacing:2px;margin:12px 0;font-family:monospace;user-select:all;">' . htmlspecialchars($nueva_password) . '</div>
                <div style="font-size:0.8rem;color:#64748b;display:flex;align-items:center;gap:6px;"><iconify-icon icon="mdi:alert" width="14"></iconify-icon> Si cerrás esta página sin copiarla, tendrás que generar otra.</div>
            </div>
            <div class="text-center mt-2">
                <a href="login-admin.php" class="btn btn-success" style="display:inline-flex;padding:10px 24px;width:auto;">Ir al inicio de sesión</a>
            </div>';

        } else {
            $mensaje = '<div class="alert alert-danger">No hay ningún administrador registrado.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Acceso Master</title>
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
                <h2 class="d-flex align-items-center justify-content-center gap-2 mt-2"><iconify-icon icon="mdi:lock-reset" width="24" style="color:#10b981;"></iconify-icon> Recuperar Acceso</h2>
                <p class="text-muted">Si pierdes la contraseña, puedes generar una nueva aquí.</p>
            </div>

            <?php if ($mensaje): ?>
                <?php echo $mensaje; ?>
            <?php else: ?>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <p class="text-muted small mb-3">Al hacer clic en "Restablecer", se generará una nueva contraseña de administrador. La actual quedará invalidada.</p>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                        <iconify-icon icon="mdi:refresh" width="18"></iconify-icon> Restablecer Contraseña
                    </button>
                    <div class="text-center mt-3">
                        <a href="login-admin.php" class="text-decoration-none small text-secondary">← Volver al login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
