<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('staff_crear')) {
    mostrar_error("Acceso denegado", "No tienes permiso para crear miembros del staff.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);

    $permisos = [
        'productos_ver' => !empty($_POST['perm_productos_ver']),
        'productos_crear' => !empty($_POST['perm_productos_crear']),
        'productos_editar' => !empty($_POST['perm_productos_editar']),
        'productos_eliminar' => !empty($_POST['perm_productos_eliminar']),
        'pedidos_ver' => !empty($_POST['perm_pedidos_ver']),
        'pedidos_gestionar' => !empty($_POST['perm_pedidos_gestionar']),
        'configuracion_editar' => !empty($_POST['perm_configuracion_editar']),
    ];

    if (empty($usuario) || empty($password)) {
        $error = "Usuario y contraseña son obligatorios.";
    } elseif (strlen($password) < 10) {
        $error = "La contraseña debe tener al menos 10 caracteres.";
    } else {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM store_staff WHERE tienda_id = ?");
        $stmtCount->execute([$tienda_id]);
        $total_staff = (int)$stmtCount->fetchColumn();
        verificar_limite_plan('staff', $total_staff, 'Límite de staff alcanzado');

        $check = $pdo->prepare("SELECT id FROM store_staff WHERE tienda_id = ? AND usuario = ?");
        $check->execute([$tienda_id, $usuario]);
        if ($check->fetch()) {
            $error = "Ese usuario ya existe en tu tienda.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO store_staff (tienda_id, usuario, password, email, permisos) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tienda_id, $usuario, $hash, $email ?: null, json_encode($permisos)]);

            $u = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Creó un miembro del staff', "Usuario: $usuario");

            $exito = "Staff creado correctamente.";
        }
    }
}
}
?>

<?php require __DIR__ . '/templates/staff_nuevo_body.php'; ?>