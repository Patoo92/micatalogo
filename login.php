<?php
require_once 'init_session.php';
require_once 'conexion.php';

$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
$flash_type = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if (isset($_SESSION['tienda_id'])) {
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } elseif (!verificar_rate_limit($pdo, 'login', 5, 15)) {
        $error = "Demasiados intentos. Espera 15 minutos antes de volver a intentar.";
    } else {
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        if (!empty($usuario) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT id, nombre_tienda, password, activo, marca_blanca FROM tiendas WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $tienda = $stmt->fetch();

            if ($tienda && password_verify($password, $tienda['password'])) {
                if ($tienda['activo'] == 0) {
                    $error = "Tu cuenta está suspendida. Contacta con soporte para reactivarla.";
                } else {
                    limpiar_intentos_login($pdo, 'login');
                    session_regenerate_id(true);
                    $_SESSION['tienda_id']     = $tienda['id'];
                    $_SESSION['tienda_nombre'] = $tienda['nombre_tienda'];
                    $_SESSION['marca_blanca']  = (int)($tienda['marca_blanca'] ?? 0);
                    registrar_actividad($pdo, $tienda['id'], $tienda['nombre_tienda'], 'owner', 'Inició sesión');
                    header("Location: admin.php");
                    exit;
                }
            } else {
                $stmt = $pdo->prepare("
                    SELECT s.*, t.nombre_tienda, t.activo, t.marca_blanca 
                    FROM store_staff s 
                    JOIN tiendas t ON s.tienda_id = t.id 
                    WHERE s.usuario = ?
                ");
                $stmt->execute([$usuario]);
                $staff = $stmt->fetch();

                if ($staff && password_verify($password, $staff['password'])) {
                    if ($staff['activo'] == 0) {
                        $error = "Tu cuenta de staff está desactivada.";
                    } elseif ($staff['activo'] == 0) {
                        $error = "La tienda está suspendida.";
                    } else {
                        limpiar_intentos_login($pdo, 'login');
                        session_regenerate_id(true);
                        $_SESSION['tienda_id']      = $staff['tienda_id'];
                        $_SESSION['tienda_nombre']  = $staff['nombre_tienda'];
                        $_SESSION['marca_blanca']   = (int)($staff['marca_blanca'] ?? 0);
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
}?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso al Panel - Administrador</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: fadeUp 0.4s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card h2 { font-size: 1.4rem; font-weight: 700; color: #0f172a; }
        .login-card p { color: #64748b; font-size: 0.9rem; }
        label { font-weight: 600; font-size: 0.875rem; color: #374151; }
        .form-control {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.9rem;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
        }
        .btn-primary {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer; padding: 4px;
        }
        .password-toggle:hover { color: #64748b; }
        .input-wrapper { position: relative; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <iconify-icon icon="mdi:store" width="48" style="color: #0d6efd;"></iconify-icon>
            <h2 class="d-flex align-items-center justify-content-center gap-2 mt-2"><iconify-icon icon="mdi:shield-account" width="24"></iconify-icon> Panel de Control</h2>
            <p class="mb-0">Inicia sesión para gestionar tu tienda</p>
        </div>

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
    </div>

    <script nonce="<?= $csp_nonce ?>">
        document.getElementById('btnTogglePass').addEventListener('click', function() {
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
    </script>

</body>
</html>
