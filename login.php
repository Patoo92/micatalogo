<?php
// Inicializamos el sistema de sesiones de PHP
session_start();
require_once 'conexion.php';

$error = '';

// Si el usuario ya está logueado, lo mandamos directo al panel de control
if (isset($_SESSION['tienda_id'])) {
    header("Location: admin.php");
    exit;
}

// Verificamos si el dueño envió el formulario de acceso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        // Buscamos si existe una tienda con ese usuario
        $stmt = $pdo->prepare("SELECT id, nombre_tienda, password FROM tiendas WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $tienda = $stmt->fetch();


        if ($tienda && password_verify($password, $tienda['password'])) {
            // ¡Contraseña correcta! Guardamos los datos en la Sesión del servidor
            $_SESSION['tienda_id'] = $tienda['id'];
            $_SESSION['tienda_nombre'] = $tienda['nombre_tienda'];

            // Lo redirigimos al panel de administración
            header("Location: admin.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor, rellena todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Acceso al Panel - Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">

    <div class="card shadow-lg p-4 bg-white rounded" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark">🔑 Panel de Control</h2>
            <p class="text-muted">Inicia sesión para gestionar tu tienda</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 text-center" style="font-size: 0.9rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Usuario</label>
                <input type="text" name="usuario" class="form-control" placeholder="Ej: admin" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary">Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                Ingresar al Panel →
            </button>
        </form>
    </div>

</body>
</html>