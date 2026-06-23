<?php
if (!isset($csp_nonce)) {
    $csp_nonce = bin2hex(random_bytes(16));
}

header_remove('X-Powered-By');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$csp_nonce}' https://code.iconify.design https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' https: data:; connect-src 'self'; frame-src 'none'; object-src 'none'");

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
ini_set('log_errors', 1);
ini_set('error_log', $logDir . '/error.log');

$configPath = __DIR__ . '/../../micatalogo-config/db.php';
if (!file_exists($configPath)) {
    $configPath = 'C:\xampp\micatalogo-config\db.php';
}
$config = require $configPath;

$host    = $config['host'];
$db      = $config['db'];
$user    = $config['user'];
$pass    = $config['pass'];
$charset = $config['charset'];

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Error de conexión BD: " . $e->getMessage());
    die("Error crítico de conexión. Intenta de nuevo más tarde.");
}

require_once __DIR__ . '/helpers.php';

function registrar_actividad($pdo, $tienda_id, $usuario_nombre, $usuario_tipo, $accion, $detalle = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO actividad (tienda_id, usuario_nombre, usuario_tipo, accion, detalle) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$tienda_id, $usuario_nombre, $usuario_tipo, $accion, $detalle]);
    } catch (PDOException $e) {}
}

function obtener_usuario_actual() {
    if (isset($_SESSION['admin_id'])) {
        return [
            'nombre' => $_SESSION['admin_usuario'],
            'tipo'   => 'superadmin',
            'tienda_id' => null,
        ];
    } elseif (isset($_SESSION['staff_id'])) {
        return [
            'nombre' => $_SESSION['staff_usuario'],
            'tipo'   => 'staff',
            'tienda_id' => $_SESSION['tienda_id'],
        ];
    } elseif (isset($_SESSION['tienda_id'])) {
        return [
            'nombre' => $_SESSION['tienda_nombre'],
            'tipo'   => 'owner',
            'tienda_id' => $_SESSION['tienda_id'],
        ];
    }
    return null;
}

function verificar_permiso($permiso) {
    if (!isset($_SESSION['tienda_id'])) return false;
    if (!isset($_SESSION['staff_id'])) return true;
    $permisos = $_SESSION['staff_permisos'] ?? [];
    return !empty($permisos[$permiso]);
}
