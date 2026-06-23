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
}

$stmt = $pdo->query("
    SELECT 
        t.id,
        t.nombre_tienda,
        t.slug,
        t.usuario,
        t.telefono_whatsapp,
        t.activo,
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin — Panel de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { background-color: #0f172a; font-family: 'Inter', sans-serif; color: #e2e8f0; }

        .top-bar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-bar .brand { font-weight: 700; font-size: 1.1rem; color: #f1f5f9; }
        .top-bar .brand span { color: #10b981; }

        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
        }
        .stat-card .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; font-weight: 600; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #f1f5f9; line-height: 1.1; }
        .stat-card .value.green { color: #10b981; }

        .table-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
        }
        .table-card thead th {
            background: #0f172a;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 600;
            border-color: #334155;
            padding: 0.75rem 1rem;
        }
        .table-card tbody td {
            border-color: #334155;
            color: #cbd5e1;
            padding: 0.85rem 1rem;
            vertical-align: middle;
        }
        .table-card tbody tr:hover td { background: rgba(255,255,255,0.02); }

        .badge-activa    { background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        .badge-bloqueada { background: rgba(239,68,68,0.12);  color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .badge-custom { font-size: 0.75rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; display: inline-block; }

        .btn-bloquear    { background: rgba(239,68,68,0.1);  color: #f87171; border: 1px solid rgba(239,68,68,0.3);  font-size: 0.8rem; padding: 4px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.15s; }
        .btn-bloquear:hover    { background: rgba(239,68,68,0.25); color: #f87171; }
        .btn-desbloquear { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); font-size: 0.8rem; padding: 4px 12px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.15s; }
        .btn-desbloquear:hover { background: rgba(16,185,129,0.25); color: #10b981; }

        .slug-link { color: #38bdf8; font-size: 0.8rem; font-family: monospace; }

        .logout-btn { color: #64748b; font-size: 0.85rem; text-decoration: none; }
        .logout-btn:hover { color: #f87171; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="brand">⚙️ Super<span>Admin</span></div>
        <div class="d-flex align-items-center gap-3">
            <span style="color: #64748b; font-size: 0.85rem;">Sesión: <strong style="color:#94a3b8;"><?php echo htmlspecialchars($_SESSION['admin_usuario']); ?></strong></span>
            <a href="backup.php" class="logout-btn" style="display:inline-flex;align-items:center;gap:4px;"><iconify-icon icon="mdi:database-export" width="16"></iconify-icon> Respaldo BD</a>
            <a href="logout-admin.php" class="logout-btn">Cerrar sesión →</a>
        </div>
    </div>

    <div class="container-fluid py-4 px-4" style="max-width: 1100px;">

        <ul class="nav nav-tabs border-0 mb-4" style="border-bottom: 1px solid #334155 !important;">
            <li class="nav-item">
                <a class="nav-link fw-semibold <?php echo $tab === 'tiendas' ? 'active' : ''; ?>" <?php echo $tab === 'tiendas' ? 'style="background:#1e293b;border-color:#334155;color:#f1f5f9;"' : 'style="color:#64748b;border-color:transparent;"'; ?> href="super-admin.php?tab=tiendas"><iconify-icon icon="mdi:store" width="18"></iconify-icon> Tiendas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold <?php echo $tab === 'historial' ? 'active' : ''; ?>" <?php echo $tab === 'historial' ? 'style="background:#1e293b;border-color:#334155;color:#f1f5f9;"' : 'style="color:#64748b;border-color:transparent;"'; ?> href="super-admin.php?tab=historial"><iconify-icon icon="mdi:history" width="18"></iconify-icon> Historial Global</a>
            </li>
        </ul>

        <?php if ($tab === 'tiendas'): ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">Total tiendas</div>
                    <div class="value"><?php echo $total_tiendas; ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">Tiendas activas</div>
                    <div class="value green"><?php echo count($tiendas_activas); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">Bloqueadas</div>
                    <div class="value" style="color:#f87171;"><?php echo $total_tiendas - count($tiendas_activas); ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">Total pedidos</div>
                    <div class="value"><?php echo array_sum(array_column($tiendas, 'total_pedidos')); ?></div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tienda</th>
                            <th>Usuario</th>
                            <th>WhatsApp</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Pedidos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tiendas)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4" style="color: #475569;">
                                    No hay tiendas registradas aún.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tiendas as $tienda): ?>
                            <tr>
                                <td style="color: #475569;">#<?php echo $tienda['id']; ?></td>
                                <td>
                                    <div style="color: #f1f5f9; font-weight: 600;"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></div>
                                    <a href="index.php?tienda=<?php echo $tienda['slug']; ?>" target="_blank" class="slug-link">/<?php echo $tienda['slug']; ?></a>
                                </td>
                                <td><?php echo htmlspecialchars($tienda['usuario']); ?></td>
                                <td style="font-size: 0.85rem;"><?php echo htmlspecialchars($tienda['telefono_whatsapp'] ?? '—'); ?></td>
                                <td class="text-center"><?php echo $tienda['total_productos']; ?></td>
                                <td class="text-center"><?php echo $tienda['total_pedidos']; ?></td>
                                <td class="text-center">
                                    <?php if ($tienda['activo']): ?>
                                        <span class="badge-custom badge-activa">● Activa</span>
                                    <?php else: ?>
                                        <span class="badge-custom badge-bloqueada">● Bloqueada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                    <?php if ($tienda['activo']): ?>
                                        <form method="POST" action="super-admin.php" class="d-inline form-confirm" data-confirm="¿Bloquear la tienda «<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>»?">
                                            <input type="hidden" name="toggle" value="0">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn-bloquear" style="border:none;cursor:pointer;">🔒</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="super-admin.php" class="d-inline">
                                            <input type="hidden" name="toggle" value="1">
                                            <input type="hidden" name="id" value="<?php echo $tienda['id']; ?>">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn-desbloquear" style="border:none;cursor:pointer;">✅</button>
                                        </form>
                                    <?php endif; ?>
                                        <form method="POST" action="super-admin.php" class="d-inline form-confirm" data-confirm="¿Eliminar permanentemente la tienda «<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>»? Se borrarán todos sus productos, pedidos y datos.">
                                            <input type="hidden" name="delete" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="confirm" value="1">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn-bloquear" style="border-color:rgba(239,68,68,0.3);border:none;cursor:pointer;">🗑️</button>
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

        <?php endif; ?>

        <?php if ($tab === 'historial'): ?>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table mb-0">
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
                            <tr><td colspan="6" class="text-center py-4" style="color: #475569;">Sin actividad registrada.</td></tr>
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
</body>
</html>
