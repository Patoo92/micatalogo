<?php
require_once 'init_session.php';
require_once 'conexion.php';

$error = '';
$exito = '';
$planes_disponibles = ['starter', 'pro', 'business'];
$plan_seleccionado = in_array($_GET['plan'] ?? '', $planes_disponibles) ? $_GET['plan'] : 'starter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } elseif (!verificar_rate_limit($pdo, 'registro', 3)) {
        $error = "Demasiados intentos. Espera 15 minutos.";
    } else {
    registrar_intento_login($pdo, 'registro');
    $nombre_tienda     = trim($_POST['nombre_tienda']);
    $slug              = trim($_POST['slug']);
    $usuario           = trim($_POST['usuario']);
    $password          = trim($_POST['password']);
    $password_confirm  = trim($_POST['password_confirm']);
    $telefono_whatsapp = trim($_POST['telefono_whatsapp']);
    $email             = trim($_POST['email']);
    $plan              = in_array($_POST['plan'] ?? '', $planes_disponibles) ? $_POST['plan'] : 'starter';
    $dias_trial        = $plan === 'starter' ? 0 : 3;
    $trial_ends_at     = $dias_trial > 0 ? date('Y-m-d', strtotime("+$dias_trial days")) : null;

    if (empty($nombre_tienda) || empty($slug) || empty($usuario) || empty($password) || empty($telefono_whatsapp)) {
        $error = "Todos los campos son obligatorios.";

    } elseif (mb_strlen($nombre_tienda) > 100) {
        $error = "El nombre de la tienda no puede superar los 100 caracteres.";

    } elseif (mb_strlen($nombre_tienda) < 3) {
        $error = "El nombre de la tienda debe tener al menos 3 caracteres.";

    } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
        $error = "El slug solo puede contener letras minúsculas, números y guiones. Ej: mi-tienda";

    } elseif (strlen($slug) < 4 || strlen($slug) > 60) {
        $error = "La URL debe tener entre 4 y 60 caracteres.";

    } elseif (strlen($usuario) < 4 || strlen($usuario) > 30) {
        $error = "El usuario debe tener entre 4 y 30 caracteres.";

    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
        $error = "El usuario solo puede contener letras, números y guión bajo.";

    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El email ingresado no es válido.";

    } elseif (!empty($email) && mb_strlen($email) > 254) {
        $error = "El email es demasiado largo.";

    } elseif (!preg_match('/^\+[1-9][0-9]{6,14}$/', $telefono_whatsapp)) {
        $error = "El teléfono debe empezar con + y contener entre 7 y 15 dígitos. Ej: +34600123456";

    } elseif (strlen($password) < 10) {
        $error = "La contraseña debe tener al menos 10 caracteres.";

    } elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";

    } else {
        $stmtCheck = $pdo->prepare("SELECT id FROM tiendas WHERE slug = ? OR usuario = ?");
        $stmtCheck->execute([$slug, $usuario]);

        if ($stmtCheck->fetch()) {
            $error = "El nombre de URL o el usuario ya están en uso. Elige otros.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO tiendas (nombre_tienda, slug, usuario, password, telefono_whatsapp, email, activo, plan, trial_ends_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
            ");
            $stmt->execute([$nombre_tienda, $slug, $usuario, $hash, $telefono_whatsapp, $email ?: null, $plan, $trial_ends_at]);

            $_SESSION['flash_message'] = '¡Tienda creada correctamente! Ya puedes iniciar sesión.';
            $_SESSION['flash_type'] = 'success';
            header("Location: login.php");
            exit;
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
    <title>Crear tu Tienda — Registro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7fafc;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
        }
        .brand-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 1rem;
        }
        .slug-preview {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
        }
        .slug-preview span {
            color: #10b981;
            font-weight: 600;
        }
        .btn-register {
            background-color: #10b981;
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .btn-register:hover { background-color: #059669; color: white; }
        label { font-weight: 500; font-size: 0.9rem; color: #374151; }
        .form-control:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="brand-badge"><?php echo $plan_seleccionado === 'starter' ? 'Gratis' : '3 días gratis'; ?></div>
        <h2 class="fw-bold mb-1" style="font-size: 1.6rem;"><?php echo $plan_seleccionado === 'starter' ? 'Crea tu tienda gratis' : 'Prueba ' . htmlspecialchars(ucfirst($plan_seleccionado)) . ' 3 días'; ?></h2>
        <p class="text-muted mb-4" style="font-size: 0.95rem;">Rellena los datos y empieza a vender por WhatsApp hoy mismo.</p>

        <div class="d-flex gap-2 mb-4">
            <?php foreach (['starter' => 'Starter', 'pro' => 'Pro', 'business' => 'Business'] as $key => $label): ?>
                <a href="registro.php?plan=<?php echo $key; ?>" class="btn <?php echo $plan_seleccionado === $key ? 'btn-dark' : 'btn-outline-secondary'; ?> flex-fill fw-semibold py-2" style="border-radius:10px;font-size:0.9rem;">
                    <?php echo $label; ?>
                    <small class="d-block" style="font-size:0.65rem;opacity:0.7;">
                        <?php echo $key === 'starter' ? 'Gratis' : '3 días gratis'; ?>
                    </small>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($exito): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($exito); ?></div>
        <?php else: ?>

        <form method="POST" action="registro.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan_seleccionado); ?>">

            <div class="mb-3">
                <label>Nombre de tu tienda *</label>
                <input type="text" name="nombre_tienda" class="form-control mt-1"
                       placeholder="Ej: Ropa Sofía" value="<?php echo htmlspecialchars($_POST['nombre_tienda'] ?? ''); ?>"
                       minlength="3" maxlength="100" required>
            </div>

            <div class="mb-3">
                <label>URL de tu catálogo *</label>
                <input type="text" name="slug" id="slug" class="form-control mt-1"
                       placeholder="Ej: ropa-sofia" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                       pattern="[a-z0-9\-]+" minlength="4" maxlength="60" required>
                <div class="slug-preview">Tu catálogo estará en: tudominio.com/<span id="slug-preview">ropa-sofia</span></div>
                <small class="text-muted">Minúsculas, números y guiones. 4-60 caracteres.</small>
            </div>

            <div class="mb-3">
                <label>Usuario para el panel *</label>
                <input type="text" name="usuario" class="form-control mt-1"
                       placeholder="Ej: sofia123" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>"
                       pattern="[a-zA-Z0-9_]+" minlength="4" maxlength="30" required>
                <small class="text-muted">Letras, números y guión bajo. 4-30 caracteres.</small>
            </div>

            <div class="mb-3">
                <label>Contraseña *</label>
                <input type="password" name="password" class="form-control mt-1" id="regPassword"
                       placeholder="Mínimo 10 caracteres" minlength="10" required>
                <small class="text-muted">Mínimo 10 caracteres. Puede ser una frase larga.</small>
            </div>

            <div class="mb-3">
                <label>Repetir contraseña *</label>
                <input type="password" name="password_confirm" class="form-control mt-1" id="regPasswordConfirm"
                       placeholder="Repite la contraseña" minlength="10" required>
                <small class="text-muted" id="passwordMatchMsg" style="display:none;"></small>
            </div>

            <div class="mb-3">
                <label>Email <span class="text-muted">(opcional)</span></label>
                <input type="email" name="email" class="form-control mt-1"
                       placeholder="Ej: sofia@ejemplo.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       maxlength="254">
            </div>

            <div class="mb-4">
                <label>Número de WhatsApp *</label>
                <input type="tel" name="telefono_whatsapp" class="form-control mt-1"
                       placeholder="Ej: +34600123456" value="<?php echo htmlspecialchars($_POST['telefono_whatsapp'] ?? ''); ?>"
                       pattern="\+[1-9][0-9]{6,14}" required>
                <small class="text-muted">Incluye código de país. Ej: +54911..., +34600...</small>
            </div>

            <button type="submit" class="btn-register">Crear mi tienda →</button>
        </form>

        <p class="text-center text-muted mt-3" style="font-size: 0.85rem;">
            ¿Ya tienes cuenta? <a href="login.php" style="color: #10b981;">Inicia sesión aquí</a>
        </p>

        <?php endif; ?>
    </div>

    <script nonce="<?= $csp_nonce ?>">
        const slugInput = document.getElementById('slug');
        const slugPreview = document.getElementById('slug-preview');
        if (slugInput) {
            slugInput.addEventListener('input', function () {
                const val = this.value.trim() || 'tu-tienda';
                slugPreview.textContent = val;
            });
        }

        const pw = document.getElementById('regPassword');
        const pw2 = document.getElementById('regPasswordConfirm');
        const msg = document.getElementById('passwordMatchMsg');
        if (pw && pw2) {
            function checkMatch() {
                if (pw2.value.length === 0) { msg.style.display = 'none'; return; }
                if (pw.value !== pw2.value) {
                    msg.style.display = 'block';
                    msg.style.color = '#dc2626';
                    msg.textContent = 'Las contraseñas no coinciden.';
                    pw2.setCustomValidity(' ');
                } else {
                    msg.style.display = 'block';
                    msg.style.color = '#16a34a';
                    msg.textContent = '✓ Coinciden.';
                    pw2.setCustomValidity('');
                }
            }
            pw.addEventListener('input', checkMatch);
            pw2.addEventListener('input', checkMatch);
        }
    </script>
</body>
</html>
