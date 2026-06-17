<?php
session_start();
session_destroy(); // Borra los datos de la sesión del servidor
header("Location: login.php"); // Lo manda de vuelta al inicio
exit;
?>