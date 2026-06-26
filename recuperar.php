<?php
require_once 'init_session.php';
require_once 'conexion.php';
require_once 'email_helper.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger">Solicitud inválida.</div>';
    } else {
        $email = trim($_POST['email']);

        $stmt = $pdo->prepare("SELECT id, nombre_tienda, usuario, marca_blanca FROM tiendas WHERE email = ?");
        $stmt->execute([$email]);
        $tienda = $stmt->fetch();

        if ($tienda) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE email = ? AND usado = 0");
            $stmt->execute([$email]);
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
            $stmt->execute([$email, $token]);

            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (!preg_match('/^[a-z0-9\.\-:]+$/i', $host)) { $host = 'localhost'; }
            $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $host . $basePath . '/reset-password.php?token=' . urlencode($token);
            $footer = '';
            if (empty($tienda['marca_blanca'])) {
                $footer = '<hr><small style="color:#94a3b8;">micatalogo.app — tu tienda online</small>';
            }
            $cuerpo = '<h2>Recuperación de contraseña</h2>
                <p>Hola <strong>' . htmlspecialchars($tienda['nombre_tienda']) . '</strong>,</p>
                <p>Recibimos una solicitud para restablecer tu contraseña. Hacé clic en el botón de abajo:</p>
                <p style="text-align:center;margin:30px 0;">
                    <a href="' . $link . '" style="background:#10b981;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Restablecer Contraseña</a>
                </p>
                <p>Si no solicitaste esto, ignorá este mensaje.</p>' . $footer;

            $from_name = !empty($tienda['marca_blanca']) ? $tienda['nombre_tienda'] : null;
            if (enviar_email($email, 'Recupera tu contraseña de ' . $tienda['nombre_tienda'], $cuerpo, $from_name)) {
                $mensaje = '<div class="alert alert-success"><strong>Correo enviado.</strong> Revisá tu bandeja de entrada (y la carpeta de spam).</div>';
            } else {
                $mensaje = '<div class="alert alert-warning">
                    <strong>No se pudo enviar el email.</strong> Configurá SMTP en <code>micatalogo-config/email.php</code>.<br>
                    Pedí un nuevo enlace cuando tengas SMTP funcionando.
                </div>';
            }
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
    </div>
</body>
</html>
