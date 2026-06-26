<?php
require_once 'init_session.php';
require_once 'conexion.php';

$error = '';
$exito = '';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND usado = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "El enlace es inválido o ha expirado.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
        $password = $_POST['password'];
        $confirm = $_POST['password_confirm'];

        if (strlen($password) < 10) {
            $error = "La contraseña debe tener al menos 10 caracteres.";
        } elseif ($password !== $confirm) {
            $error = "Las contraseñas no coinciden.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE tiendas SET password = ? WHERE email = ?");
                $stmt->execute([$hash, $reset['email']]);
                $stmt = $pdo->prepare("UPDATE password_resets SET usado = 1 WHERE id = ?");
                $stmt->execute([$reset['id']]);
                $pdo->commit();
                $exito = "Contraseña actualizada. <a href='login.php' class='alert-link'>Inicia sesión</a>";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error al actualizar. Intenta de nuevo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña</title>
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
                <iconify-icon icon="mdi:form-textbox-password" width="48" style="color: #10b981;"></iconify-icon>
                <h4 class="fw-bold mt-2">Nueva Contraseña</h4>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($exito): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($exito); ?></div>
            <?php else: ?>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva contraseña</label>
                        <input type="password" name="password" class="form-control" minlength="10" placeholder="Mínimo 10 caracteres" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmar contraseña</label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Repite la contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">Cambiar contraseña</button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none small">← Volver al inicio de sesión</a>
            </div>
        </div>
    </div>
</body>
</html>
