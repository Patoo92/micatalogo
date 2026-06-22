<?php
session_start();
require_once 'conexion.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: super-admin.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } elseif (!verificar_rate_limit($pdo, 'admin', 5, 15)) {
        $error = "Demasiados intentos. Espera 15 minutos.";
    } else {
        $usuario  = trim($_POST['usuario']);
        $password = trim($_POST['password']);

        if (!empty($usuario) && !empty($password)) {
            $stmt = $pdo->prepare("SELECT id, usuario, password FROM admins WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                limpiar_intentos_login($pdo, 'admin');
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Master — Admin</title>
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
        .login-card {
            background: #1e293b;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: fadeUp 0.4s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-card h2 { color: #f1f5f9; font-size: 1.4rem; font-weight: 700; }
        .login-card p  { color: #94a3b8; font-size: 0.9rem; }
        label { color: #cbd5e1; font-size: 0.875rem; font-weight: 500; }
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f1f5f9;
            border-radius: 10px;
            padding: 10px 14px;
        }
        .form-control::placeholder { color: #475569; }
        .form-control:focus {
            background: #0f172a;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
            color: #f1f5f9;
        }
        .btn-admin {
            background: #10b981;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-admin:hover { background: #059669; transform: translateY(-1px); }
        .badge-master {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16,185,129,0.15);
            color: #10b981;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(16,185,129,0.3);
            margin-bottom: 1rem;
        }
        iconify-icon { display: inline-flex; vertical-align: -2px; }
        .password-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #64748b; cursor: pointer; padding: 4px;
        }
        .password-toggle:hover { color: #94a3b8; }
        .input-wrapper { position: relative; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="badge-master"><iconify-icon icon="mdi:shield-lock" width="14"></iconify-icon> Acceso restringido</div>
        <h2 class="d-flex align-items-center gap-2"><iconify-icon icon="mdi:security" width="24" style="color: #10b981;"></iconify-icon> Panel Master</h2>
        <p class="mb-4">Solo el administrador del SaaS puede acceder aquí.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" style="font-size: 0.875rem;">
                <iconify-icon icon="mdi:alert-circle" width="18"></iconify-icon>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login-admin.php">
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:account" width="16"></iconify-icon> Usuario</label>
                <input type="text" name="usuario" class="form-control mt-1" placeholder="superadmin" required>
            </div>
            <div class="mb-4">
                <label class="d-flex align-items-center gap-1"><iconify-icon icon="mdi:lock" width="16"></iconify-icon> Contraseña</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="adminPassword" class="form-control mt-1" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()" title="Mostrar contraseña">
                        <iconify-icon icon="mdi:eye-outline" id="eyeIcon" width="18"></iconify-icon>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-admin">
                <iconify-icon icon="mdi:login" width="18"></iconify-icon> Entrar al panel
            </button>
            <div class="text-center mt-3">
                <a href="recuperar-admin.php" class="text-decoration-none small text-secondary">¿Olvidaste la contraseña?</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('adminPassword');
            const icon = document.getElementById('eyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('icon', 'mdi:eye-off-outline');
            } else {
                input.type = 'password';
                icon.setAttribute('icon', 'mdi:eye-outline');
            }
        }
    </script>
</body>
</html>
