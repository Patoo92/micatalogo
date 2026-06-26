<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login-admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf($_POST['_csrf'] ?? '')) {
        header("Location: super-admin.php");
        exit;
    }

    if (isset($_POST['toggle']) && isset($_POST['id'])) {
        $tienda_id  = (int)$_POST['id'];
        $nuevo_estado = (int)$_POST['toggle'];

        if ($nuevo_estado === 0 || $nuevo_estado === 1) {
            $stmt = $pdo->prepare("UPDATE tiendas SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $tienda_id]);
        }
        header("Location: super-admin.php");
        exit;
    }

    if (isset($_POST['delete']) && isset($_POST['confirm'])) {
        $tienda_id = (int)$_POST['delete'];
    try {
        $pdo->beginTransaction();
        $stmtDel = $pdo->prepare("DELETE FROM actividad WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM store_staff WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM pedidos WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM productos WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM api_keys WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM categorias WHERE tienda_id = ?");
        $stmtDel->execute([$tienda_id]);
        $stmtDel = $pdo->prepare("DELETE FROM tiendas WHERE id = ?");
        $stmtDel->execute([$tienda_id]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    header("Location: super-admin.php");
    exit;
    }

    if (isset($_POST['cambiar_plan']) && isset($_POST['id'])) {
        $tienda_id = (int)$_POST['id'];
        $nuevo_plan = $_POST['nuevo_plan'] ?? 'starter';
        $planes_validos = ['starter', 'pro', 'business', 'enterprise'];
        if (in_array($nuevo_plan, $planes_validos)) {
            $stmt = $pdo->prepare("UPDATE tiendas SET plan = ? WHERE id = ?");
            $stmt->execute([$nuevo_plan, $tienda_id]);
        }
        header("Location: super-admin.php");
        exit;
    }

    if (isset($_POST['extender_trial']) && isset($_POST['id'])) {
        $tienda_id = (int)$_POST['id'];
        $dias = max(1, min(365, (int)($_POST['dias_trial'] ?? 3)));
        $nueva_fecha = date('Y-m-d', strtotime("+$dias days"));
        $stmt = $pdo->prepare("UPDATE tiendas SET trial_ends_at = ? WHERE id = ?");
        $stmt->execute([$nueva_fecha, $tienda_id]);
        header("Location: super-admin.php");
        exit;
    }

    if (isset($_POST['crear_factura']) && isset($_POST['id'])) {
        $tienda_id = (int)$_POST['id'];
        $plan = $_POST['plan'] ?? 'starter';
        $periodo = $_POST['periodo'] ?? 'mensual';
        $monto = (float)($_POST['monto'] ?? 0);
        $estado = $_POST['estado'] ?? 'pendiente';
        $metodo_pago = trim($_POST['metodo_pago'] ?? '');
        $notas = trim($_POST['notas'] ?? '');
        $fecha_emision = $_POST['fecha_emision'] ?? date('Y-m-d');
        $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
        if ($monto > 0 && in_array($plan, ['starter','pro','business','enterprise']) && in_array($periodo, ['mensual','anual'])) {
            $numero = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare("INSERT INTO facturas (tienda_id, numero_factura, plan, periodo, monto, estado, metodo_pago, notas, fecha_emision, fecha_vencimiento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tienda_id, $numero, $plan, $periodo, $monto, $estado, $metodo_pago ?: null, $notas ?: null, $fecha_emision, $fecha_vencimiento]);
            if ($estado === 'pagada') {
                $stmt = $pdo->prepare("UPDATE facturas SET fecha_pago = ? WHERE id = ?");
                $stmt->execute([date('Y-m-d'), $pdo->lastInsertId()]);
            }
        }
        header("Location: super-admin.php?tab=facturas");
        exit;
    }

    if (isset($_POST['actualizar_estado_factura']) && isset($_POST['factura_id'])) {
        $factura_id = (int)$_POST['factura_id'];
        $nuevo_estado = $_POST['nuevo_estado'] ?? 'pendiente';
        if (in_array($nuevo_estado, ['pendiente','pagada','cancelada','vencida'])) {
            $stmt = $pdo->prepare("UPDATE facturas SET estado = ?, fecha_pago = IF(? = 'pagada' AND fecha_pago IS NULL, CURDATE(), fecha_pago) WHERE id = ?");
            $stmt->execute([$nuevo_estado, $nuevo_estado, $factura_id]);
        }
        header("Location: super-admin.php?tab=facturas");
        exit;
    }
}

$stmt = $pdo->query("
    SELECT 
        t.id,
        t.nombre_tienda,
        t.slug,
        t.usuario,
        t.email,
        t.telefono_whatsapp,
        t.activo,
        t.plan,
        t.trial_ends_at,
        t.marca_blanca,
        COUNT(DISTINCT p.id)  AS total_productos,
        COUNT(DISTINCT pe.id) AS total_pedidos
    FROM tiendas t
    LEFT JOIN productos p  ON p.tienda_id  = t.id
    LEFT JOIN pedidos   pe ON pe.tienda_id = t.id
    GROUP BY t.id
    ORDER BY t.id DESC
");
$tiendas = $stmt->fetchAll();

$total_tiendas  = count($tiendas);
$tiendas_activas = array_filter($tiendas, fn($t) => $t['activo'] == 1);

$tab = $_GET['tab'] ?? 'tiendas';

$stmtAct = $pdo->query("
    SELECT a.*, t.nombre_tienda
    FROM actividad a
    LEFT JOIN tiendas t ON a.tienda_id = t.id
    ORDER BY a.created_at DESC
    LIMIT 100
");
$actividades = $stmtAct->fetchAll();

$stmtFact = $pdo->query("
    SELECT f.*, t.nombre_tienda
    FROM facturas f
    LEFT JOIN tiendas t ON f.tienda_id = t.id
    ORDER BY f.created_at DESC
    LIMIT 100
");
$facturas = $stmtFact->fetchAll();
$total_facturas_pendientes = $pdo->query("SELECT COUNT(*) FROM facturas WHERE estado = 'pendiente'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Panel de Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/css/tabler.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0/dist/js/tabler.min.js" nonce="<?= $csp_nonce ?>"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        iconify-icon { display: inline-flex; vertical-align: -2px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark px-4">
        <span class="navbar-brand mb-0 h1">⚙️ Super<span style="color:#10b981;">Admin</span></span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-secondary" style="font-size: 0.85rem;">Sesión: <strong class="text-light"><?php echo htmlspecialchars($_SESSION['admin_usuario']); ?></strong></span>
            <a href="backup.php" class="text-secondary text-decoration-none small" style="display:inline-flex;align-items:center;gap:4px;"><iconify-icon icon="mdi:database-export" width="16"></iconify-icon> Respaldo BD</a>
            <a href="logout-admin.php" class="text-secondary text-decoration-none small">Cerrar sesión →</a>
        </div>
    </nav>

    <div class="container-fluid py-4 px-4" style="max-width: 1100px;">

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'tiendas' ? 'active' : ''; ?>" href="super-admin.php?tab=tiendas"><iconify-icon icon="mdi:store" width="18"></iconify-icon> Tiendas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'facturas' ? 'active' : ''; ?>" href="super-admin.php?tab=facturas"><iconify-icon icon="mdi:file-document" width="18"></iconify-icon> Facturas <?php if ($total_facturas_pendientes > 0): ?><span class="badge bg-danger ms-1"><?php echo $total_facturas_pendientes; ?></span><?php endif; ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'historial' ? 'active' : ''; ?>" href="super-admin.php?tab=historial"><iconify-icon icon="mdi:history" width="18"></iconify-icon> Historial Global</a>
            </li>
        </ul>

        <?php if ($tab === 'tiendas'): ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-semibold">Total tiendas</div>
                        <div class="h2 mb-0"><?php echo $total_tiendas; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-semibold">Tiendas activas</div>
                        <div class="h2 mb-0 text-success"><?php echo count($tiendas_activas); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-semibold">Bloqueadas</div>
                        <div class="h2 mb-0 text-danger"><?php echo $total_tiendas - count($tiendas_activas); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-secondary text-uppercase small fw-semibold">Total pedidos</div>
                        <div class="h2 mb-0"><?php echo array_sum(array_column($tiendas, 'total_pedidos')); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tiendas</h3>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tienda</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Trial</th>
                            <th class="text-center">Prod.</th>
                            <th class="text-center">Ped.</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tiendas)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4" style="color: #475569;">
                                    <iconify-icon icon="mdi:store-off" width="24" style="opacity:0.5;"></iconify-icon><br>
                                    No hay tiendas registradas aún.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tiendas as $tienda): ?>
                            <tr>
                                <td style="color: #475569;">#<?php echo $tienda['id']; ?></td>
                                <td>
                                    <div style="color: #f1f5f9; font-weight: 600;"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></div>
                                    <a href="index.php?tienda=<?php echo $tienda['slug']; ?>" target="_blank" class="text-info small font-monospace text-decoration-none">/<?php echo $tienda['slug']; ?></a>
                                </td>
                                <td><?php echo htmlspecialchars($tienda['usuario']); ?></td>
                                <td style="font-size:0.8rem;color:#94a3b8;"><?php echo htmlspecialchars($tienda['email'] ?? '—'); ?></td>
                                <td>
                                    <span class="badge bg-info-lt text-uppercase"><?php echo htmlspecialchars($tienda['plan'] ?? 'starter'); ?></span>
                                </td>
                                <td style="font-size:0.8rem;color:#94a3b8;">
                                    <?php if (!empty($tienda['trial_ends_at'])): ?>
                                        <?php echo $tienda['trial_ends_at']; ?>
                                        <?php if ($tienda['trial_ends_at'] < date('Y-m-d')): ?>
                                            <span style="color:#f87171;">(vencido)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $tienda['total_productos']; ?></td>
                                <td class="text-center"><?php echo $tienda['total_pedidos']; ?></td>
                                <td class="text-center">
                                    <?php if ($tienda['activo']): ?>
                                        <span class="badge bg-success">● Activa</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">● Bloqueada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end" style="min-width:200px;">
                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                    <?php if ($tienda['activo']): ?>
                                        <form method="POST" action="super-admin.php" class="d-inline form-confirm" data-confirm="¿Bloquear la tienda «<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>»?">
                                            <input type="hidden" name="toggle" value="0">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Bloquear">🔒</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="super-admin.php" class="d-inline">
                                            <input type="hidden" name="toggle" value="1">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-ghost-success" title="Desbloquear">✅</button>
                                        </form>
                                    <?php endif; ?>
                                        <form method="POST" action="super-admin.php" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <select name="nuevo_plan" class="form-select form-select-sm d-inline" style="width:auto;">
                                                <option value="starter" <?php echo ($tienda['plan'] ?? '') === 'starter' ? 'selected' : ''; ?>>Starter</option>
                                                <option value="pro" <?php echo ($tienda['plan'] ?? '') === 'pro' ? 'selected' : ''; ?>>Pro</option>
                                                <option value="business" <?php echo ($tienda['plan'] ?? '') === 'business' ? 'selected' : ''; ?>>Business</option>
                                                <option value="enterprise" <?php echo ($tienda['plan'] ?? '') === 'enterprise' ? 'selected' : ''; ?>>Enterprise</option>
                                            </select>
                                            <button type="submit" name="cambiar_plan" class="btn btn-sm btn-ghost-success" title="Cambiar plan">⇄</button>
                                        </form>
                                        <form method="POST" action="super-admin.php" class="d-inline">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <input type="number" name="dias_trial" value="7" min="1" max="365" class="form-control form-control-sm d-inline" style="width:50px;">
                                            <button type="submit" name="extender_trial" class="btn btn-sm btn-ghost-success" title="Extender trial">⏱️</button>
                                        </form>
                                        <form method="POST" action="super-admin.php?tab=facturas" class="d-inline" title="Crear factura">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="plan" value="<?php echo $tienda['plan'] ?? 'starter'; ?>">
                                            <input type="hidden" name="periodo" value="mensual">
                                            <input type="hidden" name="monto" value="0">
                                            <input type="hidden" name="estado" value="pendiente">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" name="crear_factura" class="btn btn-sm btn-ghost-info" title="Crear factura">🧾</button>
                                        </form>
                                        <form method="POST" action="super-admin.php" class="d-inline form-confirm" data-confirm="¿Eliminar permanentemente la tienda «<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>»? Se borrarán todos sus productos, pedidos y datos.">
                                            <input type="hidden" name="delete" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="confirm" value="1">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Eliminar">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <?php endif; ?>

        <?php if ($tab === 'facturas'): ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Facturas e Historial de Pagos</h3>
            </div>
            <div class="card-body">
            <?php if (empty($facturas)): ?>
                <div class="empty">
                    <div class="empty-icon"><iconify-icon icon="mdi:file-document-outline" width="48" style="color: #94a3b8;"></iconify-icon></div>
                    <p class="empty-title">No hay facturas registradas.</p>
                    <p class="empty-subtitle text-muted">Las facturas se generan al crear un pago manual desde el panel de tiendas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th># Factura</th>
                                <th>Tienda</th>
                                <th>Plan</th>
                                <th>Periodo</th>
                                <th>Monto</th>
                                <th>Emisión</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $f): ?>
                            <tr>
                                <td style="font-size:0.8rem;font-family:monospace;color:#64748b;"><?php echo htmlspecialchars($f['numero_factura']); ?></td>
                                <td><?php echo htmlspecialchars($f['nombre_tienda'] ?? '—'); ?></td>
                                <td><span class="badge bg-info-lt text-uppercase"><?php echo htmlspecialchars($f['plan']); ?></span></td>
                                <td><?php echo $f['periodo']; ?></td>
                                <td class="fw-semibold"><?php echo number_format($f['monto'], 2); ?> <?php echo htmlspecialchars($f['moneda']); ?></td>
                                <td style="font-size:0.8rem;color:#64748b;"><?php echo $f['fecha_emision']; ?></td>
                                <td style="font-size:0.8rem;color:#64748b;"><?php echo $f['fecha_vencimiento'] ?? '—'; ?></td>
                                <td>
                                    <?php if ($f['estado'] === 'pagada'): ?>
                                        <span class="badge bg-success">Pagada</span>
                                    <?php elseif ($f['estado'] === 'pendiente'): ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php elseif ($f['estado'] === 'vencida'): ?>
                                        <span class="badge bg-danger">Vencida</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cancelada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="super-admin.php?tab=facturas" class="d-inline">
                                        <input type="hidden" name="factura_id" value="<?php echo $f['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="pagada">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="actualizar_estado_factura" class="btn btn-sm btn-ghost-success" title="Marcar como pagada">✓</button>
                                    </form>
                                    <form method="POST" action="super-admin.php?tab=facturas" class="d-inline">
                                        <input type="hidden" name="factura_id" value="<?php echo $f['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="cancelada">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" name="actualizar_estado_factura" class="btn btn-sm btn-ghost-danger" title="Cancelar">✕</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($tab === 'historial'): ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Historial Global</h3>
            </div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tienda</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Acción</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($actividades)): ?>
                            <tr><td colspan="6" class="text-center py-4" style="color: #475569;"><iconify-icon icon="mdi:history" width="24" style="opacity:0.5;"></iconify-icon><br>Sin actividad registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($actividades as $a): ?>
                            <tr>
                                <td style="font-size:0.8rem; color:#64748b; white-space:nowrap;"><?php echo $a['created_at']; ?></td>
                                <td><?php echo htmlspecialchars($a['nombre_tienda'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars($a['usuario_nombre']); ?></td>
                                <td>
                                    <?php if ($a['usuario_tipo'] === 'owner'): ?>
                                        <span style="color:#38bdf8;">Dueño</span>
                                    <?php elseif ($a['usuario_tipo'] === 'staff'): ?>
                                        <span style="color:#a78bfa;">Staff</span>
                                    <?php else: ?>
                                        <span style="color:#34d399;">Superadmin</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($a['accion']); ?></td>
                                <td style="font-size:0.85rem; color:#94a3b8;"><?php echo htmlspecialchars($a['detalle'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>

        <?php endif; ?>

    </div>
    <script nonce="<?= $csp_nonce ?>">
    document.addEventListener('submit', function(e) {
        var form = e.target.closest('.form-confirm');
        if (form) {
            var msg = form.getAttribute('data-confirm');
            if (msg && !confirm(msg)) {
                e.preventDefault();
            }
        }
    });
    </script>
    <script nonce="<?= $csp_nonce ?>">
    (function() {
        var html = document.documentElement;
        if (localStorage.getItem('dark_mode') === '1') {
            html.setAttribute('data-bs-theme', 'dark');
        }
    })();
    </script>
</body>
</html>
