<?php
if (defined('HELPERS_LOADED')) return;
define('HELPERS_LOADED', true);

require_once __DIR__ . '/env.php';

function ruta_imagen($tienda_id) {
    $dir = "imagenes/{$tienda_id}";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function imagen_defecto() {
    return "https://images.unsplash.com/photo-1539109136881-3be0616acf4b?w=500";
}

function imagen_url($path) {
    if (empty($path)) return imagen_defecto();
    if (str_starts_with($path, 'http')) return $path;
    $cdn = _getenv('CDN_URL');
    if ($cdn !== '') {
        $cdn = rtrim($cdn, '/');
        return $cdn . '/' . ltrim($path, '/');
    }
    return $path;
}

function generar_thumbnail($origen, $destino, $ancho = 300, $alto = 300) {
    if (!file_exists($origen)) return false;
    $info = getimagesize($origen);
    if (!$info) return false;

    list($w, $h) = $info;
    $creators = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG  => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
        IMAGETYPE_GIF  => 'imagecreatefromgif',
    ];
    $fn = $creators[$info[2]] ?? null;
    $src = $fn && function_exists($fn) ? @$fn($origen) : null;
    if (!$src) return false;

    $thumb = imagecreatetruecolor($ancho, $alto);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $ancho, $alto, $w, $h);

    $ext = strtolower(pathinfo($destino, PATHINFO_EXTENSION));
    match ($ext) {
        'png'  => imagepng($thumb, $destino, 8),
        'webp' => imagewebp($thumb, $destino, 85),
        'gif'  => imagegif($thumb, $destino),
        default => imagejpeg($thumb, $destino, 85),
    };

    imagedestroy($src);
    imagedestroy($thumb);
    return true;
}

function ip_normalizada() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($ip === '::1' || $ip === '::ffff:127.0.0.1') return '127.0.0.1';
    return $ip;
}

function verificar_rate_limit($pdo, $tipo, $max_intentos = 5, $ventana_minutos = 15) {
    $ip = ip_normalizada();
    $desde = date('Y-m-d H:i:s', time() - $ventana_minutos * 60);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND tipo = ? AND created_at >= ?");
    $stmt->execute([$ip, $tipo, $desde]);
    return $stmt->fetchColumn() < $max_intentos;
}

function registrar_intento_login($pdo, $tipo) {
    $ip = ip_normalizada();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, tipo) VALUES (?, ?)");
    $stmt->execute([$ip, $tipo]);
}

function limpiar_intentos_login($pdo, $tipo) {
    $ip = ip_normalizada();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND tipo = ?");
    $stmt->execute([$ip, $tipo]);
}

function csrf_token() {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function verificar_csrf($token) {
    if (empty($_SESSION['_csrf']) || empty($token)) return false;
    $ok = hash_equals($_SESSION['_csrf'], $token);
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $ok;
}

function js_escape($str) {
    return json_encode($str ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
}

function js_string($str) {
    return str_replace(["'", "\\", "\n", "\r", "\t"], ["\\'", "\\\\", "\\n", "\\r", "\\t"], $str ?? '');
}

function _getenv($key, $default = '') {
    $val = getenv($key);
    return $val !== false && $val !== '' ? $val : $default;
}

function _env_path($key, $default = '') {
    $val = getenv($key);
    return $val !== false && $val !== '' ? rtrim($val, '\\/') . '/' : $default;
}

/**
 * Indica si una tienda puede cobrar online (pasarela) en el catálogo público.
 * Requiere plan Business/Enterprise Y clave de Stripe configurada.
 * @param array|null $tienda Fila de la tabla tiendas
 */
function pagos_online_habilitados($tienda) {
    if (empty($tienda) || !in_array($tienda['plan'] ?? '', ['business', 'enterprise'], true)) {
        return false;
    }
    return _getenv('STRIPE_SECRET_KEY') !== '';
}

function plan_limite($caracteristica) {
    $planes = [
        'starter'   => ['staff' => 1, 'tiendas' => 1, 'api_keys' => 0,  'marca_blanca' => false, 'personalizacion' => false],
        'pro'       => ['staff' => 3, 'tiendas' => 1, 'api_keys' => 0,  'marca_blanca' => false, 'personalizacion' => true],
        'business'  => ['staff' => 10,'tiendas' => 3, 'api_keys' => 5,  'marca_blanca' => true,  'personalizacion' => true],
        'enterprise'=> ['staff' => 999,'tiendas' => 999,'api_keys' => 999,'marca_blanca' => true,  'personalizacion' => true],
    ];
    $plan = $_SESSION['plan'] ?? 'starter';
    return $planes[$plan][$caracteristica] ?? 0;
}

function verificar_limite_plan($caracteristica, $actual, $titulo = 'Límite del plan') {
    $maximo = plan_limite($caracteristica);
    if ($actual >= $maximo) {
        $plan = ucfirst($_SESSION['plan'] ?? 'starter');
        $mensaje = "Tu plan $plan permite hasta $maximo " . htmlspecialchars($caracteristica) . ". Actualizá tu plan para ampliarlo.";
        mostrar_error($titulo, $mensaje, 'configuracion.php', 'Ver planes');
    }
}

/**
 * Confirma el pago de un pedido online (Stripe mode=payment) y descuenta el
 * stock de forma condicional (WHERE stock >= 1) para evitar sobreventa.
 *
 * Es idempotente: solo aplica cuando el pedido sigue en pago_estado='pendiente',
 * así un webhook reentregado o la página de éxito no descuentan dos veces.
 *
 * @param PDO    $pdo            Conexión activa
 * @param string $codigo_pedido  Código que agrupa las líneas del pedido
 * @param string $referencia     id del Payment Intent / sesión de Checkout
 * @return array ['aplicado' => bool, 'lineas' => int, 'fallos' => int]
 */
function confirmar_pago_producto($pdo, $codigo_pedido, $referencia) {
    $stmt = $pdo->prepare("SELECT id, producto_id, tienda_id FROM pedidos WHERE codigo_pedido = ? AND pago_estado = 'pendiente' AND estado = 'Pendiente'");
    $stmt->execute([$codigo_pedido]);
    $lineas = $stmt->fetchAll();

    if (!$lineas) {
        return ['aplicado' => false, 'lineas' => 0, 'fallos' => 0];
    }

    $pdo->beginTransaction();
    $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ? AND tienda_id = ? AND stock >= 1");
    $fallos = 0;
    foreach ($lineas as $linea) {
        if (!empty($linea['producto_id'])) {
            $stmtStock->execute([$linea['producto_id'], $linea['tienda_id']]);
            if ($stmtStock->rowCount() === 0) $fallos++;
        }
    }
    $stmtPagado = $pdo->prepare("UPDATE pedidos SET pago_estado = 'pagado', pago_referencia = COALESCE(?, pago_referencia) WHERE codigo_pedido = ?");
    $stmtPagado->execute([$referencia ?: null, $codigo_pedido]);
    $pdo->commit();

    return ['aplicado' => true, 'lineas' => count($lineas), 'fallos' => $fallos];
}

/**
 * Marca el pago de un pedido como fallido (sin tocar stock, que aún no se
 * descontó). Idempotente: solo afecta pedidos en 'pendiente'.
 */
function marcar_pago_fallido($pdo, $codigo_pedido) {
    $stmt = $pdo->prepare("UPDATE pedidos SET pago_estado = 'fallido' WHERE codigo_pedido = ? AND pago_estado = 'pendiente'");
    $stmt->execute([$codigo_pedido]);
    return $stmt->rowCount() > 0;
}

// Página de error amigable
function mostrar_error($titulo, $mensaje, $enlace = null, $texto_enlace = null) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" type="image/svg+xml" href="favicon.svg">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($titulo); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; }
        </style>
    </head>
    <body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;">
        <div class="card p-4 text-center" style="max-width:480px;width:100%;">
            <h2 class="fw-bold mt-2"><?php echo htmlspecialchars($titulo); ?></h2>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($mensaje); ?></p>
            <?php if ($enlace): ?>
                <a href="<?php echo htmlspecialchars($enlace); ?>" class="btn btn-primary"><?php echo htmlspecialchars($texto_enlace ?? 'Volver'); ?></a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
