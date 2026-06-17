<?php
// Configuración de las credenciales de tu servidor local
$host = 'localhost';
$db   = 'catalogo_whatsapp';
$user = 'root'; // Por defecto en XAMPP el usuario es root
$pass = '';     // Por defecto en XAMPP la contraseña está vacía
$charset = 'utf8mb4';

// El "DSR" es la cadena de conexión que le dice a PHP dónde está la base de datos
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones de seguridad para que si hay un error en SQL, PHP nos avise claramente
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Intentamos conectar
    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (\PDOException $e) {
    // Si algo sale mal (ej: pusiste mal la contraseña), el sistema se detiene y te dice por qué
    die("Error crítico de conexión: " . $e->getMessage());
}
?>