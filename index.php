<?php
require_once 'conexion.php';

if (!isset($_GET['tienda']) || empty($_GET['tienda'])) {
    die("Error: No se ha especificado ninguna tienda.");
}

$slug_tienda = $_GET['tienda'];

try {
    $stmtTienda = $pdo->prepare("SELECT * FROM tiendas WHERE slug = ?");
    $stmtTienda->execute([$slug_tienda]);
    $tienda = $stmtTienda->fetch();

    if (!$tienda) {
        header("HTTP/1.0 404 Not Found");
        die("La tienda '" . htmlspecialchars($slug_tienda) . "' no existe.");
    }

    $tienda_id = $tienda['id'];

    $stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE tienda_id = ?");
    $stmtCat->execute([$tienda_id]);
    $categorias = $stmtCat->fetchAll();

    $categoria_filtrada = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

    if ($categoria_filtrada) {
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ? AND categoria_id = ?");
        $stmtProd->execute([$tienda_id, $categoria_filtrada]);
    } else {
        $stmtProd = $pdo->prepare("SELECT * FROM productos WHERE tienda_id = ?");
        $stmtProd->execute([$tienda_id]);
    }
    $productos = $stmtProd->fetchAll();

} catch (PDOException $e) {
    die("Error de carga en el servidor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($tienda['nombre_tienda']); ?> - Tienda Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --color-principal: <?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .navbar-ecommerce { background-color: #ffffff !important; border-bottom: 1px solid #e2e8f0; }
        .nav-link-custom { color: #64748b !important; font-weight: 500; padding: 0.5rem 1rem !important; transition: color 0.2s; }
        .nav-link-custom:hover, .nav-link-custom.active { color: var(--color-principal) !important; }
        .hero-shop { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); border-radius: 0 0 24px 24px; }
        .btn-primary { background-color: var(--color-principal) !important; border-color: var(--color-principal) !important; border-radius: 10px !important; font-weight: 600; }
        .btn-outline-primary { color: var(--color-principal); border-color: #e2e8f0; background-color: white; font-weight: 600; }
        .btn-outline-primary:hover { background-color: var(--color-principal); color: white; border-color: var(--color-principal); }
        .product-card { border: none !important; border-radius: 16px !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important; transition: transform 0.2s ease, box-shadow 0.2s ease; background: #ffffff; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important; }
        .card-img-top { border-radius: 16px 16px 0 0 !important; }
        .scroll-clean::-webkit-scrollbar { display: none; }
        .scroll-clean { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light navbar-ecommerce sticky-top shadow-sm py-3">
    <div class="container" style="max-width: 1100px;">
        <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">
            <?php if (!empty($tienda['logo_url'])): ?>
                <img src="<?php echo htmlspecialchars($tienda['logo_url']); ?>" alt="Logo" style="max-height: 40px;">
            <?php else: ?>
                <span style="color: var(--color-principal);">🏪</span> <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavigation">
            <ul class="navbar-nav gap-1 mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom <?php echo !$categoria_filtrada ? 'active' : ''; ?>" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>#productos-catalogo">Productos</a>
                </li>
                <!-- Corregido: telefono_whatsapp en lugar de whatsapp_number -->
                <?php if (!empty($tienda['telefono_whatsapp'])): ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']); ?>" target="_blank">Contacto</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-shop text-center py-5 mb-5">
    <div class="container" style="max-width: 600px;">
        <h2 class="fw-bold mb-2">Explora nuestra colección</h2>
        <p class="text-muted mb-0">Selecciona los productos que te gusten y envíanos tu orden directamente por WhatsApp de forma inmediata.</p>
    </div>
</div>

<div class="container pb-5" id="productos-catalogo" style="max-width: 900px;">

    <div class="mb-4">
        <h5 class="fw-bold mb-3">Categorías</h5>
        <div class="d-flex gap-2 overflow-x-auto pb-2 scroll-clean">
            <a href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>#productos-catalogo"
               class="btn <?php echo !$categoria_filtrada ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-4 text-nowrap">Todos</a>
            <?php foreach ($categorias as $cat): ?>
                <a href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&categoria=<?php echo $cat['id']; ?>#productos-catalogo"
                   class="btn <?php echo ($categoria_filtrada == $cat['id']) ? 'btn-primary' : 'btn-outline-primary'; ?> rounded-pill px-4 text-nowrap">
                    <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Nuestro Catálogo</h5>
    <div class="row g-3">
        <?php if (count($productos) > 0): ?>
            <?php foreach ($productos as $prod): ?>
                <div class="col-6 col-md-4">
                    <div class="card h-100 product-card">
                        <img src="<?php echo htmlspecialchars($prod['imagen_url']); ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <h6 class="card-title fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                                <p class="text-success fw-bold mb-2"><?php echo number_format($prod['precio'], 2); ?> €</p>
                            </div>
                            <?php if ($prod['stock'] > 0): ?>
                                <form action="hacer-pedido.php" method="POST" class="mt-2">
                                    <input type="hidden" name="producto_id" value="<?php echo $prod['id']; ?>">
                                    <input type="hidden" name="tienda_id"   value="<?php echo $tienda_id; ?>">
                                    <input type="text" name="nombre_cliente" class="form-control form-control-sm mb-2"
                                           placeholder="Tu nombre" required>
                                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2">
                                        📲 Pedir por WhatsApp
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100 fw-bold py-2 mt-2" disabled>❌ Agotado</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No hay productos disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
