<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger">Solicitud inválida.</div>';
    } else {
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("SELECT id, nombre_tienda FROM tiendas WHERE email = ?");
        $stmt->execute([$email]);
        $tienda = $stmt->fetch();

        if ($tienda) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $token]);

            $link = "reset-password.php?token=" . urlencode($token);
            $mensaje = '<div class="alert alert-success">
                <strong>Enlace de recuperación generado:</strong><br>
                <a href="' . $link . '">' . $link . '</a>
                <br><small class="text-muted">En producción este enlace se envía por email.</small>
            </div>';
        } else {
            $mensaje = '<div class="alert alert-danger">No hay ninguna cuenta registrada con ese email.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: system-ui, sans-serif; }
        .card { border: none; border-radius: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); padding: 2.5rem; max-width: 440px; width: 100%; animation: fadeUp 0.3s ease; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        iconify-icon { vertical-align: -2px; display: inline-flex; }
    </style>
</head>
<body>
    <div class="card">
        <div class="text-center mb-4">
            <iconify-icon icon="mdi:lock-reset" width="48" style="color: #10b981;"></iconify-icon>
            <h4 class="fw-bold mt-2">Recuperar Contraseña</h4>
            <p class="text-muted small">Ingresa el email que usaste al registrar tu tienda.</p>
        </div>

        <?php echo $mensaje; ?>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email de la cuenta</label>
                <input type="email" name="email" class="form-control" placeholder="tutienda@ejemplo.com" required>
            </div>
            <button type="submit" class="btn btn-success w-100 fw-bold py-2">Generar enlace</button>
        </form>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none small">← Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>
