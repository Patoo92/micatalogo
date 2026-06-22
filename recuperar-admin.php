<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger">Solicitud inválida.</div>';
    } else {
        $stmt = $pdo->query("SELECT id, usuario, password FROM admins LIMIT 1");
        $admin = $stmt->fetch();

        if ($admin) {
            $nueva_password = bin2hex(random_bytes(6));
            $hash = password_hash($nueva_password, PASSWORD_BCRYPT);
            $upd = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $upd->execute([$hash, $admin['id']]);

            $mensaje = '<div class="alert alert-success">
                <strong>Contraseña restablecida.</strong><br>
                Usuario: <code>' . htmlspecialchars($admin['usuario']) . '</code><br>
                Nueva contraseña: <code>' . $nueva_password . '</code><br>
                <span class="text-muted">Guárdala en un lugar seguro.</span>
            </div>';
            $mensaje .= '<a href="login-admin.php" class="btn btn-admin w-100" style="background:#10b981;color:#fff;font-weight:600;border-radius:10px;padding:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
                <iconify-icon icon="mdi:login" width="18"></iconify-icon> Ir al login</a>';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 1rem;
        }
        .card-rec { background: #1e293b; border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 420px; border: 1px solid #334155; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); animation: fadeUp 0.4s ease; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .card-rec h2 { color: #f1f5f9; font-size: 1.4rem; font-weight: 700; }
        .card-rec p { color: #94a3b8; font-size: 0.9rem; }
        label { color: #cbd5e1; font-size: 0.875rem; font-weight: 500; }
        .btn-admin {
            background: #10b981; color: white; font-weight: 600; border: none; border-radius: 10px;
            padding: 12px; width: 100%; transition: all 0.2s;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
        }
        .btn-admin:hover { background: #059669; transform: translateY(-1px); }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .alert { border-radius: 12px; border: none; }
    </style>
</head>
<body>
    <div class="card-rec">
        <div class="text-center mb-3">
            <iconify-icon icon="mdi:shield-lock" width="40" style="color: #10b981;"></iconify-icon>
            <h2 class="d-flex align-items-center justify-content-center gap-2 mt-2"><iconify-icon icon="mdi:lock-reset" width="24" style="color:#10b981;"></iconify-icon> Recuperar Acceso</h2>
            <p>Si pierdes la contraseña, puedes generar una nueva aquí.</p>
        </div>

        <?php if ($mensaje): ?>
            <?php echo $mensaje; ?>
        <?php else: ?>
            <form method="POST">
                <?php echo csrf_field(); ?>
                <p class="text-muted small mb-3">Al hacer clic en "Restablecer", se generará una nueva contraseña de administrador. La actual quedará invalidada.</p>
                <button type="submit" class="btn-admin">
                    <iconify-icon icon="mdi:refresh" width="18"></iconify-icon> Restablecer Contraseña
                </button>
                <div class="text-center mt-3">
                    <a href="login-admin.php" class="text-decoration-none small text-secondary">← Volver al login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
