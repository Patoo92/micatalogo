<?php
session_start();
require_once 'conexion.php';

// Si ya está logueado como admin, redirigir directo al panel
if (isset($_SESSION['admin_id'])) {
    header("Location: super-admin.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, usuario, password FROM admins WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']      = $admin['id'];
            $_SESSION['admin_usuario'] = $admin['usuario'];
            header("Location: super-admin.php");
            exit;
        } else {
            $error = "Credenciales incorrectas.";
        }
    } else {
        $error = "Rellena todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Master — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: #1e293b;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            border: 1px solid #334155;
        }
        .login-card h2 { color: #f1f5f9; font-size: 1.4rem; font-weight: 700; }
        .login-card p  { color: #94a3b8; font-size: 0.9rem; }
        label { color: #cbd5e1; font-size: 0.875rem; font-weight: 500; }
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f1f5f9;
            border-radius: 8px;
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
            border-radius: 8px;
            padding: 12px;
            width: 100%;
            transition: background 0.2s;
        }
        .btn-admin:hover { background: #059669; }
        .badge-master {
            display: inline-block;
            background: rgba(16,185,129,0.15);
            color: #10b981;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            border: 1px solid rgba(16,185,129,0.3);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="badge-master">⚙️ Acceso restringido</div>
        <h2>Panel Master</h2>
        <p class="mb-4">Solo el administrador del SaaS puede acceder aquí.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2" style="font-size: 0.875rem;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login-admin.php">
            <div class="mb-3">
                <label>Usuario</label>
                <input type="text" name="usuario" class="form-control mt-1" placeholder="superadmin" required>
            </div>
            <div class="mb-4">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control mt-1" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-admin">Entrar al panel →</button>
        </form>
    </div>
</body>
</html>
