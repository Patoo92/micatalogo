<?php
require_once 'init_session.php';
require_once 'conexion.php';

header('Content-Type: text/plain; charset=utf-8');

if (isset($_SESSION['admin_id'])) {
    $es_admin = true;
} elseif (isset($_SESSION['tienda_id'])) {
    $es_admin = false;
} else {
    header("Location: login.php");
    exit;
}

$configPath = __DIR__ . '/../../micatalogo-config/db.php';
if (!file_exists($configPath)) {
    $configPath = 'C:\xampp\micatalogo-config\db.php';
}
$config = require $configPath;

if ($es_admin) {
    $filename = 'backup_' . $config['db'] . '_' . date('Ymd_His') . '.sql';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $mysqldump = 'mysqldump';
    $paths = ['C:\\xampp\\mysql\\bin\\mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe', 'mysqldump'];
    foreach ($paths as $p) {
        if (file_exists($p) || strpos($p, '\\') === false) { $mysqldump = $p; break; }
    }

    putenv("MYSQL_PWD={$config['pass']}");
    $cmd = sprintf(
        '"%s" --host=%s --user=%s --single-transaction --routines --triggers %s 2>&1',
        $mysqldump,
        escapeshellarg($config['host']),
        escapeshellarg($config['user']),
        escapeshellarg($config['db'])
    );

    passthru($cmd, $exit_code);
    putenv("MYSQL_PWD");

    if ($exit_code !== 0) {
        header('Content-Type: text/html; charset=utf-8');
        mostrar_error("Error de respaldo", "No se pudo generar el respaldo. Verifica que mysqldump esté disponible.", "super-admin.php", "Volver");
    }
    exit;

} else {
    $tienda_id = $_SESSION['tienda_id'];
    $tienda_nombre = $_SESSION['tienda_nombre'];
    $filename = 'backup_' . str_replace(' ', '_', $tienda_nombre) . '_' . date('Ymd_His') . '.sql';
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "-- Respaldo de tienda: $tienda_nombre (ID: $tienda_id)\n";
    echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";

    echo "SET NAMES utf8mb4;\n\n";

    $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ?");
    $stmt->execute([$tienda_id]);
    $tienda = $stmt->fetch();
    if (!$tienda) { echo "# Tienda no encontrada\n\n"; exit; }
    echo "# Datos de tienda\n";
    echo "UPDATE tiendas SET nombre_tienda = " . $pdo->quote($tienda['nombre_tienda']) . " WHERE id = $tienda_id;\n\n";

    foreach (['categorias', 'productos', 'pedidos', 'store_staff', 'actividad'] as $tabla) {
        $stmt = $pdo->prepare("SELECT * FROM $tabla WHERE tienda_id = ?");
        $stmt->execute([$tienda_id]);
        $rows = $stmt->fetchAll();
        if (count($rows) === 0) continue;

        echo "# Tabla: $tabla (" . count($rows) . " registros)\n";
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $vals = array_map(function($v) use ($pdo) {
                return $v === null ? 'NULL' : $pdo->quote($v);
            }, array_values($row));
            echo "INSERT INTO $tabla (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        echo "\n";
    }
    exit;
}