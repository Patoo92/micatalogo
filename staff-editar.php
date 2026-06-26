<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];
$staff_id = (int)$_GET['id'];
$error = '';
$exito = '';

$stmt = $pdo->prepare("SELECT * FROM store_staff WHERE id = ? AND tienda_id = ?");
$stmt->execute([$staff_id, $tienda_id]);
$staff = $stmt->fetch();

if (!$staff) {
    mostrar_error("Staff no encontrado", "El miembro del staff que buscas no existe.", "staff.php", "Volver a staff");
}

$permisos = json_decode($staff['permisos'], true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $nuevos_permisos = [
        'productos_ver' => !empty($_POST['perm_productos_ver']),
        'productos_crear' => !empty($_POST['perm_productos_crear']),
        'productos_editar' => !empty($_POST['perm_productos_editar']),
        'productos_eliminar' => !empty($_POST['perm_productos_eliminar']),
        'pedidos_ver' => !empty($_POST['perm_pedidos_ver']),
        'pedidos_gestionar' => !empty($_POST['perm_pedidos_gestionar']),
        'configuracion_editar' => !empty($_POST['perm_configuracion_editar']),
    ];

    try {
        if (!empty($password)) {
            if (strlen($password) < 8) {
                $error = "La contraseña debe tener al menos 8 caracteres.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE store_staff SET email = ?, password = ?, permisos = ? WHERE id = ? AND tienda_id = ?");
                $stmt->execute([$email ?: null, $hash, json_encode($nuevos_permisos), $staff_id, $tienda_id]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE store_staff SET email = ?, permisos = ? WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$email ?: null, json_encode($nuevos_permisos), $staff_id, $tienda_id]);
        }

        if (empty($error)) {
            $u = obtener_usuario_actual();
            registrar_actividad($pdo, $tienda_id, $u['nombre'], $u['tipo'], 'Editó un miembro del staff', "ID: $staff_id");

            $exito = "Staff actualizado correctamente.";
            $stmt = $pdo->prepare("SELECT * FROM store_staff WHERE id = ? AND tienda_id = ?");
            $stmt->execute([$staff_id, $tienda_id]);
            $staff = $stmt->fetch();
            $permisos = json_decode($staff['permisos'], true) ?? [];
        }
    } catch (PDOException $e) {
        error_log("Error al editar staff: " . $e->getMessage());
        $error = "Error al actualizar. Intenta de nuevo.";
    }
}
}
?>

<?php require __DIR__ . '/templates/staff_editar_body.php'; ?>