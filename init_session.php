<?php
date_default_timezone_set('Europe/Madrid');
ini_set('display_errors', '0');

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

// Timeout de sesión por inactividad (30 min)
$SESSION_TIMEOUT = 1800;
$is_admin = isset($_SESSION['admin_id']);
if (isset($_SESSION['tienda_id']) || $is_admin) {
    $last = $_SESSION['_last_activity'] ?? 0;
    if ($last > 0 && (time() - $last) > $SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        header("Location: " . ($is_admin ? 'login-admin.php' : 'login.php'));
        exit;
    }
    $_SESSION['_last_activity'] = time();
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
}

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$csp_nonce = bin2hex(random_bytes(16));
