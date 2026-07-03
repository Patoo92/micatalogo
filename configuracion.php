<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['tienda_id'])) {
    header("Location: login.php");
    exit;
}
if (!verificar_permiso('configuracion_editar')) {
    mostrar_error("Acceso denegado", "No tienes permiso para editar la configuración.", "admin.php", "Volver al panel");
}

$tienda_id = $_SESSION['tienda_id'];
$tienda_nombre = $_SESSION['tienda_nombre'];

$stripe_disponible = false;
try {
    require_once __DIR__ . '/stripe_helper.php';
    $stripe_config = stripe_config();
    $stripe_disponible = $stripe_config && !empty($stripe_config['secret_key']);
} catch (\Exception $e) {
    $stripe_disponible = false;
}

$stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ?");
$stmt->execute([$_SESSION['tienda_id']]);
$tienda = $stmt->fetch();

if (!$tienda) {
    mostrar_error("Tienda no encontrada", "La tienda no existe.", "admin.php", "Volver al panel");
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_api_key'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } else {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE tienda_id = ?");
        $stmtCount->execute([$tienda_id]);
        verificar_limite_plan('api_keys', (int)$stmtCount->fetchColumn(), 'Límite de API Keys');
        $nueva_key = 'mca_' . bin2hex(random_bytes(32));
        $nombre_key = trim($_POST['nombre_key'] ?? 'API v1');
        $stmt = $pdo->prepare("INSERT INTO api_keys (tienda_id, api_key, nombre) VALUES (?, ?, ?)");
        $stmt->execute([$tienda_id, $nueva_key, $nombre_key]);
        $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> API Key generada. <strong>Cópiala ahora</strong>, no se mostrará de nuevo:<br><code style="user-select:all;background:#1e293b;color:#10b981;padding:6px 10px;border-radius:4px;display:inline-block;margin-top:6px;font-size:0.85rem;">' . htmlspecialchars($nueva_key) . '</code></div>';
    }
}

if (isset($_POST['revocar_api_key']) && isset($_POST['key_id'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } else {
        $key_id = (int)$_POST['key_id'];
        $stmt = $pdo->prepare("DELETE FROM api_keys WHERE id = ? AND tienda_id = ?");
        $stmt->execute([$key_id, $tienda_id]);
        $mensaje = '<div class="alert alert-warning d-flex align-items-center gap-2"><iconify-icon icon="mdi:key-off" width="20"></iconify-icon> API Key revocada.</div>';
    }
}

if (isset($_GET['success'])) {
    $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> Configuración guardada correctamente.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'tipo_archivo_no_permitido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solo se permiten imágenes JPG, PNG, GIF o WEBP.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'logo_grande') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El logo no puede superar los 2 MB.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'permiso') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:lock" width="20"></iconify-icon> No tienes permiso para editar la configuración.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'csrf') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida. Recarga la página e inténtalo de nuevo.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'whatsapp_invalido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El número de WhatsApp no es válido (opcional, déjalo vacío si no lo usas).</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'instagram_invalido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> La URL de Instagram no es válida (opcional, déjalo vacío si no lo usas).</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'nombre_vacio') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El nombre de la tienda no puede estar vacío.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'email_invalido') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El email no es válido.</div>';
} elseif (isset($_GET['error']) && $_GET['error'] === 'banner_grande') {
    $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> El banner no puede superar los 2 MB.</div>';
}

// ─── Gestión de suscripción Stripe ───
$stripe_customer_id = $tienda['stripe_customer_id'] ?? null;
$stripe_subscription_id = $tienda['stripe_subscription_id'] ?? null;
$stripe_sub_status = null;
$stripe_plan_name = null;
$stripe_current_period_end = null;

if ($stripe_subscription_id && $stripe_disponible) {
    try {
        $sub = stripe_cliente()->subscriptions->retrieve($stripe_subscription_id);
        $stripe_sub_status = $sub->status;
        $stripe_current_period_end = $sub->current_period_end ?? null;
        // Extraer el plan del price_id
        foreach ($sub->items->data as $item) {
            $price_id = $item->price->id;
            foreach (stripe_config()['prices'] as $pname => $periodos) {
                foreach ($periodos as $pid) {
                    if ($pid === $price_id) {
                        $stripe_plan_name = $pname;
                        break 2;
                    }
                }
            }
        }
    } catch (\Exception $e) {
        error_log("Error al recuperar suscripción Stripe: " . $e->getMessage());
    }
}

// Redirigir al Customer Portal de Stripe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_subscription'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } elseif (!$stripe_customer_id) {
        $mensaje = '<div class="alert alert-warning d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> No hay suscripción activa en Stripe.</div>';
    } elseif (!$stripe_disponible) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Stripe no está configurado.</div>';
    } else {
        try {
            $return_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/configuracion.php?success=suscripcion';
            $portal_url = stripe_crear_portal($stripe_customer_id, $return_url);
            header("Location: " . $portal_url);
            exit;
        } catch (\Exception $e) {
            error_log("Error al crear portal Stripe: " . $e->getMessage());
            $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Error al conectar con Stripe. Intenta más tarde.</div>';
        }
    }
}

// Cancelar suscripción desde acá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_subscription'])) {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Solicitud inválida.</div>';
    } elseif (!$stripe_subscription_id) {
        $mensaje = '<div class="alert alert-warning d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> No hay suscripción activa.</div>';
    } elseif (!$stripe_disponible) {
        $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Stripe no está configurado.</div>';
    } else {
        try {
            stripe_cliente()->subscriptions->cancel($stripe_subscription_id);
            $pdo->prepare("UPDATE tiendas SET plan = 'starter', stripe_subscription_id = NULL WHERE id = ?")->execute([$tienda_id]);
            $mensaje = '<div class="alert alert-warning d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> Suscripción cancelada. Has vuelto al plan Starter.</div>';
            // Refrescar data
            $stripe_subscription_id = null;
            $stripe_customer_id = null;
            $stripe_sub_status = null;
            $stripe_plan_name = null;
            $stripe_current_period_end = null;
            $_SESSION['plan'] = 'starter';
        } catch (\Exception $e) {
            error_log("Error al cancelar suscripción Stripe: " . $e->getMessage());
            $mensaje = '<div class="alert alert-danger d-flex align-items-center gap-2"><iconify-icon icon="mdi:alert-circle" width="20"></iconify-icon> Error al cancelar la suscripción.</div>';
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] === 'suscripcion') {
    // Refrescar datos de tienda después de volver del portal
    $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ?");
    $stmt->execute([$tienda_id]);
    $tienda = $stmt->fetch();
    $_SESSION['plan'] = $tienda['plan'];
    $mensaje = '<div class="alert alert-success d-flex align-items-center gap-2"><iconify-icon icon="mdi:check-circle" width="20"></iconify-icon> Suscripción actualizada correctamente.</div>';
}

$stmtKeys = $pdo->prepare("SELECT id, api_key, nombre, activo, created_at FROM api_keys WHERE tienda_id = ? ORDER BY created_at DESC");
$stmtKeys->execute([$tienda_id]);
$api_keys = $stmtKeys->fetchAll();
?>
<?php require __DIR__ . '/templates/config_body.php';
