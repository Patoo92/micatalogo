<?php
require_once 'init_session.php';
require_once 'conexion.php';
require_once __DIR__ . '/totp_helper.php';

$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if (isset($_SESSION['tienda_id'])) {
    header("Location: admin.php");
    exit;
}

// MFA pendiente (paso 2) para el OWNER. El staff nunca pasa por MFA.
$mfa_pending = $_SESSION['mfa_pending_tienda'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } elseif (!verificar_rate_limit($pdo, 'login', 5, 15)) {
        $error = "Demasiados intentos. Espera 15 minutos antes de volver a intentar.";
    } elseif (!empty($mfa_pending)) {
        // ── PASO 2: validar el código TOTP del owner ──
        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') {
            $error = "Introduce el código de 6 dígitos.";
        } else {
            $stmt = $pdo->prepare("SELECT totp_secret FROM tiendas WHERE id = ?");
            $stmt->execute([$mfa_pending['tienda_id']]);
            $totp_secret = $stmt->fetchColumn();

            if (!empty($totp_secret) && valida_codigo_totp($totp_secret, $codigo)) {
                unset($_SESSION['mfa_pending_tienda']);
                limpiar_intentos_login($pdo, 'login');
                session_regenerate_id(true);
                $_SESSION['_logout_token'] = bin2hex(random_bytes(32));
                $_SESSION['tienda_id']     = $mfa_pending['tienda_id'];
                $_SESSION['tienda_nombre'] = $mfa_pending['tienda_nombre'];
                $_SESSION['tienda_slug']   = $mfa_pending['tienda_slug'];
                $_SESSION['plan']          = $mfa_pending['plan'];
                $_SESSION['marca_blanca']  = $mfa_pending['marca_blanca'];
                $_SESSION['tema_admin']    = $mfa_pending['tema_admin'];
                registrar_actividad($pdo, $mfa_pending['tienda_id'], $mfa_pending['tienda_nombre'], 'owner', 'Inició sesión');
                header("Location: admin.php");
                exit;
            }
            registrar_intento_login($pdo, 'login');
            $error = "Código de verificación incorrecto.";
        }
    } else {
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        if (!empty($usuario) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT id, nombre_tienda, slug, password, activo, marca_blanca, plan, trial_ends_at, tema_admin, email, stripe_subscription_id, totp_secret FROM tiendas WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $tienda = $stmt->fetch();

            if ($tienda && password_verify($password, $tienda['password'])) {
                if ($tienda['activo'] == 0) {
                    $error = "Tu cuenta está suspendida. Contacta con soporte para reactivarla.";
                } elseif (!empty($tienda['totp_secret'])) {
                    // MFA habilitado en la tienda: segundo paso obligatorio.
                    $_SESSION['mfa_pending_tienda'] = [
                        'tienda_id'     => (int)$tienda['id'],
                        'tienda_nombre' => $tienda['nombre_tienda'],
                        'tienda_slug'   => $tienda['slug'],
                        'plan'          => $tienda['plan'] ?? 'starter',
                        'marca_blanca'  => (int)($tienda['marca_blanca'] ?? 0),
                        'tema_admin'    => $tienda['tema_admin'] ?? 'default',
                        'created_at'    => time(),
                    ];
                    header("Location: login.php");
                    exit;
                } else {
                    $plan_actual = $tienda['plan'] ?? 'starter';
                    // A4a: solo degradar si NO hay suscripción activa (un cliente que
                    // paga no puede ser degradado aunque el trial haya vencido).
                    if ($plan_actual !== 'starter' && empty($tienda['stripe_subscription_id'])
                        && !empty($tienda['trial_ends_at']) && $tienda['trial_ends_at'] <= date('Y-m-d')) {
                        $pdo->prepare("UPDATE tiendas SET plan = 'starter', marca_blanca = 0 WHERE id = ?")->execute([$tienda['id']]);
                        $plan_actual = 'starter';
                        $tienda['marca_blanca'] = 0;
                        $_SESSION['flash_message'] = 'Tu período de prueba ha finalizado. Has sido cambiado al plan Starter.';
                        $_SESSION['flash_type'] = 'warning';
                    } elseif ($plan_actual !== 'starter' && empty($tienda['stripe_subscription_id']) && !empty($tienda['trial_ends_at'])) {
                        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
                        $dias_restantes = (strtotime($tienda['trial_ends_at']) - time()) / 86400;
                        if ($dias_restantes <= 3 && $dias_restantes > 0 && empty($_SESSION['_trial_notified'])) {
                            $_SESSION['_trial_notified'] = true;
                            require_once 'email_helper.php';
                            $stmtTrialInfo = $pdo->prepare("SELECT nombre_tienda, marca_blanca FROM tiendas WHERE id = ?");
                            $stmtTrialInfo->execute([$tienda['id']]);
                            $trialInfo = $stmtTrialInfo->fetch();
                            $from_name = !empty($trialInfo['marca_blanca']) ? $trialInfo['nombre_tienda'] : null;
                            $dias_mostrar = max(1, (int)ceil($dias_restantes));
                            $asunto = 'Tu prueba gratuita de ' . $trialInfo['nombre_tienda'] . ' termina pronto';
                            $cuerpo = '<h2>Tu período de prueba está por terminar</h2>'
                                . '<p>Hola, tu prueba gratuita vence en <strong>' . $dias_mostrar . ' día(s)</strong>.</p>'
                                . '<p>Si quieres seguir disfrutando de las funciones ' . htmlspecialchars($plan_actual) . ', puedes actualizar tu plan desde el panel de administración.</p>'
                                . '<p style="text-align:center;margin:30px 0;"><a href="' . $basePath . '/configuracion.php#plan" style="background:#10b981;color:#fff;padding:12px 32px;border-radius:8px;text-decoration:none;font-weight:600;">Ver Planes</a></p>'
                                . '<p>Al finalizar la prueba, tu tienda pasará al plan Starter (gratuito) sin perder datos.</p>';
                            enviar_email($tienda['email'], $asunto, $cuerpo, $from_name);
                        }
                    }
                    limpiar_intentos_login($pdo, 'login');
                    session_regenerate_id(true);
                    $_SESSION['_logout_token'] = bin2hex(random_bytes(32));
                    $_SESSION['tienda_id']     = $tienda['id'];
                    $_SESSION['tienda_nombre'] = $tienda['nombre_tienda'];
                    $_SESSION['tienda_slug']   = $tienda['slug'];
                    $_SESSION['plan']          = $plan_actual;
                    $_SESSION['marca_blanca']  = (int)($tienda['marca_blanca'] ?? 0);
                    $_SESSION['tema_admin']    = $tienda['tema_admin'] ?? 'default';
                    registrar_actividad($pdo, $tienda['id'], $tienda['nombre_tienda'], 'owner', 'Inició sesión');
                    header("Location: admin.php");
                    exit;
                }
            } else {
                    $stmt = $pdo->prepare("
                        SELECT s.*, t.nombre_tienda, t.slug, t.activo AS tienda_activo, t.marca_blanca, t.plan, t.trial_ends_at, t.tema_admin
                        FROM store_staff s 
                        JOIN tiendas t ON s.tienda_id = t.id 
                        WHERE s.usuario = ?
                    ");
                $stmt->execute([$usuario]);
                $staff = $stmt->fetch();

                if ($staff && password_verify($password, $staff['password'])) {
                    if ($staff['activo'] == 0) {
                        $error = "Tu cuenta de staff está desactivada.";
                    } elseif ($staff['tienda_activo'] == 0) {
                        $error = "La tienda está suspendida.";
                    } else {
                        limpiar_intentos_login($pdo, 'login');
                        session_regenerate_id(true);
                        $_SESSION['_logout_token'] = bin2hex(random_bytes(32));
                        $_SESSION['tienda_id']      = $staff['tienda_id'];
                        $_SESSION['tienda_nombre']  = $staff['nombre_tienda'];
                        $_SESSION['tienda_slug']    = $staff['slug'];
                        $_SESSION['plan']           = $staff['plan'] ?? 'starter';
                        $_SESSION['marca_blanca']   = (int)($staff['marca_blanca'] ?? 0);
                        $_SESSION['tema_admin']     = $staff['tema_admin'] ?? 'default';
                        $_SESSION['staff_id']       = $staff['id'];
                        $_SESSION['staff_usuario']  = $staff['usuario'];
                        $_SESSION['staff_permisos'] = json_decode($staff['permisos'], true) ?? [];
                        registrar_actividad($pdo, $staff['tienda_id'], $staff['usuario'], 'staff', 'Inició sesión');
                        header("Location: admin.php");
                        exit;
                    }
                } else {
                    registrar_intento_login($pdo, 'login');
                    $error = "Usuario o contraseña incorrectos.";
                }
            }
        } else {
            $error = "Por favor, rellena todos los campos.";
        }
    }
}

// Los pendientes MFA expiran a los 5 minutos por seguridad.
if (!empty($mfa_pending) && (time() - $mfa_pending['created_at']) > 300) {
    unset($_SESSION['mfa_pending_tienda']);
    $mfa_pending = null;
    $error = "El código expiró. Vuelve a introducir tus credenciales.";
}?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso al Panel - Administrador</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .password-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px; }
        .password-toggle:hover { color: #64748b; }
        .input-wrapper { position: relative; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;">

    <div class="card card-md" style="max-width:400px;width:100%;">
        <div class="card-body text-center p-4">
            <iconify-icon icon="mdi:store" width="48" style="color: #0d6efd;"></iconify-icon>
            <h2 class="h3 mt-3 d-flex align-items-center justify-content-center gap-2"><iconify-icon icon="mdi:shield-account" width="24"></iconify-icon> Panel de Control</h2>
            <p class="text-muted mb-4">Inicia sesión para gestionar tu tienda</p>

        <?php if ($flash_message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash_type ?? 'info'); ?> d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                <iconify-icon icon="mdi:<?php echo $flash_type === 'success' ? 'check-circle' : 'alert-circle'; ?>" width="18"></iconify-icon>
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                <iconify-icon icon="mdi:alert-circle" width="18"></iconify-icon>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($mfa_pending)): ?>
            <div class="alert alert-info d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem; text-align:left;">
                <iconify-icon icon="mdi:shield-key" width="18"></iconify-icon>
                Contraseña verificada. Introduce el código de tu app de autenticación.
            </div>
            <form action="login.php" method="POST" style="text-align:left;">
                <?php echo csrf_field(); ?>
                <div class="mb-3">
                    <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:key-variant" width="16"></iconify-icon> Código de 6 dígitos</label>
                    <input type="text" name="codigo" class="form-control mt-1" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <iconify-icon icon="mdi:shield-check" width="18"></iconify-icon> Verificar código
                </button>
                <div class="text-center mt-3">
                    <a href="logout.php" class="text-decoration-none small text-muted">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
        <form action="login.php" method="POST">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:account" width="16"></iconify-icon> Usuario</label>
                <input type="text" name="usuario" class="form-control mt-1" placeholder="tu_usuario" required>
            </div>
            
            <div class="mb-4">
                <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:lock" width="16"></iconify-icon> Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="storePassword" class="form-control mt-1" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" id="btnTogglePass" title="Mostrar contraseña">
                        <iconify-icon icon="mdi:eye-outline" id="eyeIcon" width="18"></iconify-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <iconify-icon icon="mdi:login" width="18"></iconify-icon> Ingresar al Panel
            </button>
            <div class="text-center mt-3">
                <a href="recuperar.php" class="text-decoration-none small text-muted">¿Olvidaste tu contraseña?</a>
            </div>
        </form>
        <?php endif; ?>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
        const btnTogglePass = document.getElementById('btnTogglePass');
        if (btnTogglePass) {
            btnTogglePass.addEventListener('click', function() {
                const input = document.getElementById('storePassword');
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
