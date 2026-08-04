<?php
if (defined('ENV_LOADED')) return;
define('ENV_LOADED', true);

/**
 * Cargador de variables de entorno desde un archivo .env (sin dependencias).
 *
 * - Las variables reales del entorno (getenv()) tienen prioridad sobre el .env.
 * - No sobrescribe variables ya definidas.
 * - Población en getenv()/putenv(), $_ENV y $_SERVER.
 *
 * Uso en producción: definir las variables en el entorno real
 * (systemd, Docker, hosting panel) y el .env queda como respaldo local.
 */
function cargar_env($dir = null) {
    $dir = $dir ?: __DIR__;
    $archivo = $dir . '/.env';
    if (!is_file($archivo) || !is_readable($archivo)) {
        return;
    }

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lineas === false) return;

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
            continue;
        }

        [$key, $valor] = explode('=', $linea, 2);
        $key = trim($key);
        $valor = trim($valor);
        if ($key === '') continue;

        $len = strlen($valor);
        if ($len >= 2
            && (($valor[0] === '"' && $valor[$len - 1] === '"')
             || ($valor[0] === "'" && $valor[$len - 1] === "'"))) {
            $valor = substr($valor, 1, -1);
        }

        if (getenv($key) !== false) continue;

        putenv("$key=$valor");
        $_ENV[$key] = $valor;
        $_SERVER[$key] = $valor;
    }
}

cargar_env();
