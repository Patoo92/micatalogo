<?php
require_once 'init_session.php';
require_once 'conexion.php';
require_once __DIR__ . '/totp_helper.php';

// Aplica a superadmin (admins) y a owner de tienda (tiendas). El staff no.
$es_master   = isset($_SESSION['admin_id']);
$es_owner    = isset($_SESSION['tienda_id']) && !isset($_SESSION['staff_id']);

if (!$es_master && !$es_owner) {
    header("Location: login.php");
    exit;
}

$tabla   = $es_master ? 'admins'  : 'tiendas';
$campo_id = $es_master ? 'admin_id' : 'tienda_id';
$id      = $es_master ? (int)$_SESSION['admin_id'] : (int)$_SESSION['tienda_id'];
$etiqueta_cuenta = $es_master ? 'Panel Master' : 'Panel de Control';
$emisor  = 'MiCatalogo ' . ($es_master ? 'Master' : $_SESSION['tienda_nombre'] ?? 'Tienda');
$volver  = $es_master ? 'super-admin.php' : 'configuracion.php';
$limpiar_logout = $es_master ? 'logout-admin.php' : 'logout.php';

$stmt = $pdo->prepare("SELECT totp_secret FROM {$tabla} WHERE id = ?");
$stmt->execute([$id]);
$secret_guardado = $stmt->fetchColumn() ?: '';

$mensaje = '';
$error   = '';
$pendiente = $_SESSION['mfa_setup_pending'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        $error = 'Solicitud inválida. Recarga la página.';
    } elseif (isset($_POST['activar_mfa'])) {
        $nuevo_secret = genera_secreto_totp();
        $_SESSION['mfa_setup_pending'] = [
            'tabla'     => $tabla,
            'id'        => $id,
            'secret'    => $nuevo_secret,
            'created_at'=> time(),
        ];
        $pendiente = $_SESSION['mfa_setup_pending'];
    } elseif (isset($_POST['confirmar_mfa']) && !empty($pendiente)) {
        // Evitar que un pendiente de otra tabla/tienda se confirme aquí.
        if ($pendiente['tabla'] !== $tabla || (int)$pendiente['id'] !== $id) {
            unset($_SESSION['mfa_setup_pending']);
            $error = 'La sesión de configuración MFA expiró. Vuelve a empezar.';
            $pendiente = null;
        } else {
            $codigo = trim($_POST['codigo'] ?? '');
            if (valida_codigo_totp($pendiente['secret'], $codigo)) {
                $stmtU = $pdo->prepare("UPDATE {$tabla} SET totp_secret = ? WHERE id = ?");
                $stmtU->execute([$pendiente['secret'], $id]);
                unset($_SESSION['mfa_setup_pending']);
                $secret_guardado = $pendiente['secret'];
                $pendiente = null;
                $mensaje = 'Verificación en dos pasos activada. A partir de ahora el login pedirá el código.';
            } else {
                $error = 'El código no es válido. Comprueba que el reloj de tu teléfono esté sincronizado y vuelve a intentarlo.';
            }
        }
    } elseif (isset($_POST['desactivar_mfa'])) {
        if (!empty($secret_guardado)) {
            $codigo = trim($_POST['codigo'] ?? '');
            if (valida_codigo_totp($secret_guardado, $codigo)) {
                $stmtU = $pdo->prepare("UPDATE {$tabla} SET totp_secret = NULL WHERE id = ?");
                $stmtU->execute([$id]);
                $secret_guardado = '';
                $mensaje = 'Verificación en dos pasos desactivada.';
            } else {
                $error = 'Código incorrecto. Para desactivar el MFA necesitas tu código de autenticación.';
            }
        } else {
            $error = 'El MFA no está activado.';
        }
    }
}

if (!empty($pendiente) && (time() - $pendiente['created_at']) > 300) {
    unset($_SESSION['mfa_setup_pending']);
    $pendiente = null;
    $error = 'La configuración expiró. Vuelve a generar el código QR.';
}

$qr_uri = '';
if (!empty($secret_guardado) && !$pendiente) {
    $qr_uri = qr_totp($secret_guardado, $emisor, $es_master ? $_SESSION['admin_usuario'] : $_SESSION['tienda_nombre']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación en dos pasos — MiCatalogo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js" nonce="<?= $csp_nonce ?>"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .qr-box { background:#fff; border:1px solid #e9ecef; border-radius:12px; padding:16px; display:inline-block; }
        .secret-code { user-select:all; background:#1e293b; color:#10b981; padding:8px 12px; border-radius:6px; font-family:monospace; letter-spacing:0.05em; word-break:break-all; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh;padding:1rem;background:#f6f8fb;">
    <div class="card" style="max-width:520px;width:100%;border:none;border-radius:16px;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <iconify-icon icon="mdi:shield-key" width="40" style="color:#10b981;"></iconify-icon>
                <h2 class="h4 mt-2 mb-0">Verificación en dos pasos</h2>
                <p class="text-muted small mb-0"><?php echo htmlspecialchars($etiqueta_cuenta); ?></p>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 py-2" style="font-size:0.875rem;">
                    <iconify-icon icon="mdi:check-circle" width="18"></iconify-icon> <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 py-2" style="font-size:0.875rem;">
                    <iconify-icon icon="mdi:alert-circle" width="18"></iconify-icon> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pendiente)): ?>
                <!-- Paso 2: mostrar QR + secreto y pedir código para confirmar -->
                <div class="alert alert-info py-2" style="font-size:0.875rem;">
                    <iconify-icon icon="mdi:cellphone-lock" width="18"></iconify-icon> Escanea el QR con Google Authenticator (o app compatible) y confirma el código de 6 dígitos.
                </div>
                <div class="text-center mb-3">
                    <div class="qr-box" id="qrcode-pending"></div>
                </div>
                <p class="small text-muted mb-1">O introduce esta clave manualmente:</p>
                <p class="secret-code"><?php echo htmlspecialchars($pendiente['secret']); ?></p>
                <form method="POST" class="mt-3">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label small">Código de 6 dígitos</label>
                        <input type="text" name="codigo" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required autofocus>
                    </div>
                    <button type="submit" name="confirmar_mfa" class="btn btn-success w-100 py-2 fw-bold">
                        <iconify-icon icon="mdi:shield-check" width="18"></iconify-icon> Activar verificación en dos pasos
                    </button>
                    <div class="text-center mt-3">
                        <a href="mfa-config.php" class="text-decoration-none small text-secondary">Cancelar</a>
                    </div>
                </form>
            <?php elseif (!empty($secret_guardado)): ?>
                <!-- MFA ACTIVADO -->
                <div class="alert alert-success py-2" style="font-size:0.875rem;">
                    <iconify-icon icon="mdi:shield-check" width="18"></iconify-icon> La verificación en dos pasos está <strong>activa</strong> para tu cuenta.
                </div>
                <details class="mb-3">
                    <summary class="text-decoration-none small text-secondary cursor-pointer">Ver QR y clave de respaldo</summary>
                    <div class="text-center mt-3 mb-2">
                        <div class="qr-box" id="qrcode-active"></div>
                    </div>
                    <p class="small text-muted mb-1">Clave secreta (guárdala para reconfigurar la app):</p>
                    <p class="secret-code"><?php echo htmlspecialchars($secret_guardado); ?></p>
                </details>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label small">Para desactivar, introduce tu código actual</label>
                        <input type="text" name="codigo" class="form-control" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required>
                    </div>
                    <button type="submit" name="desactivar_mfa" class="btn btn-outline-danger w-100 py-2">
                        <iconify-icon icon="mdi:shield-off" width="18"></iconify-icon> Desactivar verificación en dos pasos
                    </button>
                </form>
            <?php else: ?>
                <!-- MFA DESACTIVADO -->
                <div class="alert alert-warning py-2" style="font-size:0.875rem;">
                    <iconify-icon icon="mdi:shield-alert" width="18"></iconify-icon> La verificación en dos pasos está <strong>desactivada</strong>. Actívala para proteger tu cuenta ante accesos no autorizados.
                </div>
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" name="activar_mfa" class="btn btn-success w-100 py-2 fw-bold">
                        <iconify-icon icon="mdi:shield-plus" width="18"></iconify-icon> Activar verificación en dos pasos
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="<?php echo htmlspecialchars($volver); ?>" class="text-decoration-none small text-secondary">
                    <iconify-icon icon="mdi:arrow-left" width="14"></iconify-icon> Volver al panel
                </a>
            </div>
        </div>
    </div>

    <script nonce="<?= $csp_nonce ?>">
        const renderQr = function(elementId, uri) {
            const el = document.getElementById(elementId);
            if (el && typeof QRCode !== 'undefined') {
                el.innerHTML = '';
                new QRCode(el, {
                    text: uri,
                    width: 200,
                    height: 200,
                    correctLevel: QRCode.CorrectLevel.M,
                });
            }
        };
        <?php if (!empty($pendiente)): ?>
            renderQr('qrcode-pending', <?php echo js_string(qr_totp($pendiente['secret'], $emisor, $es_master ? $_SESSION['admin_usuario'] : $_SESSION['tienda_nombre'])); ?>);
        <?php elseif (!empty($secret_guardado)): ?>
            renderQr('qrcode-active', <?php echo js_string($qr_uri); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
