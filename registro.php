<?php
require_once 'conexion.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_tienda     = trim($_POST['nombre_tienda']);
    $slug              = trim($_POST['slug']);
    $usuario           = trim($_POST['usuario']);
    $password          = trim($_POST['password']);
    $password_confirm  = trim($_POST['password_confirm']);
    $telefono_whatsapp = trim($_POST['telefono_whatsapp']);

    // Validaciones básicas
    if (empty($nombre_tienda) || empty($slug) || empty($usuario) || empty($password) || empty($telefono_whatsapp)) {
        $error = "Todos los campos son obligatorios.";

    } elseif (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
        $error = "El slug solo puede contener letras minúsculas, números y guiones. Ej: mi-tienda";

    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";

    } elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";

    } else {
        // Verificar que el slug y el usuario no estén ya en uso
        $stmtCheck = $pdo->prepare("SELECT id FROM tiendas WHERE slug = ? OR usuario = ?");
        $stmtCheck->execute([$slug, $usuario]);

        if ($stmtCheck->fetch()) {
            $error = "El nombre de URL o el usuario ya están en uso. Elige otros.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO tiendas (nombre_tienda, slug, usuario, password, telefono_whatsapp, activo)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$nombre_tienda, $slug, $usuario, $hash, $telefono_whatsapp]);

            $exito = "¡Tienda creada correctamente! Ya puedes <a href='login.php' class='alert-link'>iniciar sesión</a>.";
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
        <div class="brand-badge">Nuevo en el plan</div>
        <h2 class="fw-bold mb-1" style="font-size: 1.6rem;">Crea tu tienda</h2>
        <p class="text-muted mb-4" style="font-size: 0.95rem;">Rellena los datos y empieza a vender por WhatsApp hoy mismo.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($exito): ?>
            <div class="alert alert-success py-2"><?php echo $exito; ?></div>
        <?php else: ?>

        <form method="POST" action="registro.php">

            <div class="mb-3">
                <label>Nombre de tu tienda *</label>
                <input type="text" name="nombre_tienda" class="form-control mt-1"
                       placeholder="Ej: Ropa Sofía" value="<?php echo htmlspecialchars($_POST['nombre_tienda'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label>URL de tu catálogo *</label>
                <input type="text" name="slug" id="slug" class="form-control mt-1"
                       placeholder="Ej: ropa-sofia" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>" required>
                <div class="slug-preview">Tu catálogo estará en: tudominio.com/<span id="slug-preview">ropa-sofia</span></div>
            </div>

            <div class="mb-3">
                <label>Usuario para el panel *</label>
                <input type="text" name="usuario" class="form-control mt-1"
                       placeholder="Ej: sofia123" value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label>Contraseña *</label>
                <input type="password" name="password" class="form-control mt-1" placeholder="Mínimo 8 caracteres" required>
            </div>

            <div class="mb-3">
                <label>Repetir contraseña *</label>
                <input type="password" name="password_confirm" class="form-control mt-1" placeholder="Repite la contraseña" required>
            </div>

            <div class="mb-4">
                <label>Número de WhatsApp *</label>
                <input type="text" name="telefono_whatsapp" class="form-control mt-1"
                       placeholder="Ej: +34600123456" value="<?php echo htmlspecialchars($_POST['telefono_whatsapp'] ?? ''); ?>" required>
                <div class="slug-preview">Incluye el código de país. Ej: +54911...</div>
            </div>

            <button type="submit" class="btn-register">Crear mi tienda →</button>
        </form>

        <p class="text-center text-muted mt-3" style="font-size: 0.85rem;">
            ¿Ya tienes cuenta? <a href="login.php" style="color: #10b981;">Inicia sesión aquí</a>
        </p>

        <?php endif; ?>
    </div>

    <script>
        const slugInput = document.getElementById('slug');
        const slugPreview = document.getElementById('slug-preview');
        if (slugInput) {
            slugInput.addEventListener('input', function () {
                const val = this.value.trim() || 'tu-tienda';
                slugPreview.textContent = val;
            });
        }
    </script>
</body>
</html>
