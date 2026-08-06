<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: super-admin.php");
    exit;
}

$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

require_once __DIR__ . '/totp_helper.php';

// ── Flujo 2 pasos: paso 1 = usuario+contraseña, paso 2 = código TOTP ──
// Si se viene de un envío de código (mfa_pending), NO se puede volver a
// cambiar el usuario en la sesión pendiente: se valida contra lo ya verificado.
$mfa_pending = $_SESSION['mfa_pending_admin'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } elseif (!verificar_rate_limit($pdo, 'admin', 5, 15)) {
        $error = "Demasiados intentos. Espera 15 minutos.";
    } elseif (!empty($mfa_pending)) {
        // ── PASO 2: validar el código TOTP ──
        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            $error = "Introduce el código de 6 dígitos.";
        } else {
            $stmt = $pdo->prepare("SELECT totp_secret FROM admins WHERE id = ?");
            $stmt->execute([$mfa_pending['admin_id']]);
            $totp_secret = $stmt->fetchColumn();

            if (!empty($totp_secret) && valida_codigo_totp($totp_secret, $codigo)) {
                unset($_SESSION['mfa_pending_admin']);
                limpiar_intentos_login($pdo, 'admin');
                session_regenerate_id(true);
                $_SESSION['_logout_token'] = bin2hex(random_bytes(32));
                $_SESSION['admin_id']      = $mfa_pending['admin_id'];
                $_SESSION['admin_usuario'] = $mfa_pending['admin_usuario'];
                header("Location: super-admin.php");
                exit;
            }
            registrar_intento_login($pdo, 'admin');
            $error = "Código de verificación incorrecto.";
        }
    } else {
        // ── PASO 1: validar usuario y contraseña ──
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        if (!empty($usuario) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT id, usuario, password, totp_secret FROM admins WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                if (!empty($admin['totp_secret'])) {
                    // MFA habilitado: pedir el código en un segundo paso.
                    $_SESSION['mfa_pending_admin'] = [
                        'admin_id'      => (int)$admin['id'],
                        'admin_usuario' => $admin['usuario'],
                        'created_at'    => time(),
                    ];
                    header("Location: login-admin.php");
                    exit;
                }
                limpiar_intentos_login($pdo, 'admin');
                session_regenerate_id(true);
                $_SESSION['_logout_token'] = bin2hex(random_bytes(32));
                $_SESSION['admin_id']      = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                header("Location: super-admin.php");
                exit;
            } else {
                registrar_intento_login($pdo, 'admin');
                $error = "Credenciales incorrectas.";
            }
        } else {
            $error = "Rellena todos los campos.";
        }
    }
}

// Los pendientes MFA expiran a los 5 minutos por seguridad.
if (!empty($mfa_pending) && (time() - $mfa_pending['created_at']) > 300) {
    unset($_SESSION['mfa_pending_admin']);
    $mfa_pending = null;
    $error = "El código expiró. Vuelve a introducir tus credenciales.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Master — Admin</title>
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
            <div class="badge bg-success bg-opacity-10 text-success fw-bold mb-3" style="letter-spacing:0.08em;"><iconify-icon icon="mdi:shield-lock" width="14"></iconify-icon> Acceso restringido</div>
            <h2 class="d-flex align-items-center gap-2"><iconify-icon icon="mdi:security" width="24" style="color: #10b981;"></iconify-icon> Panel Master</h2>
            <p class="mb-4">Solo el administrador del SaaS puede acceder aquí.</p>

            <?php if ($flash_message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash_type ?? 'info'); ?> d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                    <iconify-icon icon="mdi:<?php echo $flash_type === 'success' ? 'check-circle' : 'info'; ?>" width="18"></iconify-icon>
                    <?php echo htmlspecialchars($flash_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                    <iconify-icon icon="mdi:alert-circle" width="18"></iconify-icon>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mfa_pending)): ?>
                <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                    <iconify-icon icon="mdi:shield-key" width="18"></iconify-icon>
                    Contraseña verificada. Introduce el código de tu app de autenticación.
                </div>
                <form method="POST" action="login-admin.php">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:key-variant" width="16"></iconify-icon> Código de 6 dígitos</label>
                        <input type="text" name="codigo" class="form-control mt-1" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                        <iconify-icon icon="mdi:shield-check" width="18"></iconify-icon> Verificar código
                    </button>
                    <div class="text-center mt-3">
                        <a href="logout-admin.php" class="text-decoration-none small text-secondary">Cancelar</a>
                    </div>
                </form>
            <?php else: ?>
            <form method="POST" action="login-admin.php">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:account" width="16"></iconify-icon> Usuario</label>
                    <input type="text" name="usuario" class="form-control mt-1" placeholder="superadmin" required>
                </div>
                <div class="mb-4">
                    <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:lock" width="16"></iconify-icon> Contraseña</label>
                    <div class="input-group input-group-flat">
                        <input type="password" name="password" id="adminPassword" class="form-control" placeholder="••••••••" required>
                        <span class="input-group-text">
                            <button type="button" class="btn btn-ghost-secondary" id="btnTogglePass" title="Mostrar contraseña" style="padding:0;">
                                <iconify-icon icon="mdi:eye-outline" id="eyeIcon" width="18"></iconify-icon>
                            </button>
                        </span>
                    </div>
                </div>
                <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                    <iconify-icon icon="mdi:login" width="18"></iconify-icon> Entrar al panel
                </button>
                <div class="text-center mt-3">
                    <a href="recuperar-admin.php" class="text-decoration-none small text-secondary">¿Olvidaste la contraseña?</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
        const btnTogglePass = document.getElementById('btnTogglePass');
        if (btnTogglePass) {
            btnTogglePass.addEventListener('click', function() {
                const input = document.getElementById('adminPassword');
                const icon = document.getElementById('eyeIcon');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.setAttribute('icon', 'mdi:eye-off-outline');
                } else {
                    input.type = 'password';
                    icon.setAttribute('icon', 'mdi:eye-outline');
                }
            });
        }
    </script>
</body>
</html>
