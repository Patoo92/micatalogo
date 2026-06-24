<?php
require_once 'init_session.php';
require_once 'conexion.php';

if (!isset($_GET['tienda']) || !isset($_GET['id'])) {
    mostrar_error("Faltan datos", "Enlace inválido.", "index.html", "Volver al inicio");
}

$slug_tienda = $_GET['tienda'];
$producto_id = (int)$_GET['id'];

try {
    $stmtTienda = $pdo->prepare("SELECT * FROM tiendas WHERE slug = ?");
    $stmtTienda->execute([$slug_tienda]);
    $tienda = $stmtTienda->fetch();

    if (!$tienda) {
        mostrar_error("Tienda no encontrada", "La tienda solicitada no existe.", "index.html", "Volver al inicio");
    }

    if ($tienda['activo'] == 0) {
        mostrar_error("Tienda suspendida", "Esta tienda está temporalmente suspendida.");
    }

    $tienda_id = $tienda['id'];

    $stmtProd = $pdo->prepare("SELECT p.*, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id WHERE p.id = ? AND p.tienda_id = ?");
    $stmtProd->execute([$producto_id, $tienda_id]);
    $producto = $stmtProd->fetch();

    if (!$producto) {
        mostrar_error("Producto no encontrado", "El producto que buscas no existe o fue eliminado.", "index.php?tienda=" . urlencode($slug_tienda), "Ver catálogo");
    }

    $stmtRel = $pdo->prepare("SELECT id, nombre, precio, imagen_thumb, imagen_url FROM productos WHERE tienda_id = ? AND categoria_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
    $stmtRel->execute([$tienda_id, $producto['categoria_id'], $producto_id]);
    $relacionados = $stmtRel->fetchAll();

} catch (PDOException $e) {
    mostrar_error("Error del servidor", "No se pudo cargar el producto. Intenta de nuevo más tarde.");
}

$prod_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/producto.php?tienda=' . urlencode($slug_tienda) . '&id=' . $producto_id;
$prod_imagen = imagen_url($producto['imagen_url']);
$prod_nombre = htmlspecialchars($producto['nombre']);
$prod_descripcion = htmlspecialchars($producto['descripcion'] ?? '');
$prod_precio = number_format($producto['precio'], 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo $prod_nombre; ?> - <?php echo htmlspecialchars($tienda['nombre_tienda']); ?></title>
    <meta name="description" content="<?php echo $prod_descripcion ?: $prod_nombre . ' — ' . $prod_precio . ' €'; ?>">

    <meta property="og:title" content="<?php echo $prod_nombre; ?>">
    <meta property="og:description" content="<?php echo $prod_descripcion ?: 'Disponible en ' . htmlspecialchars($tienda['nombre_tienda']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($prod_imagen); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($prod_url); ?>">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $prod_nombre; ?>">
    <meta name="twitter:description" content="<?php echo $prod_descripcion ?: 'Disponible en ' . htmlspecialchars($tienda['nombre_tienda']); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($prod_imagen); ?>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        :root { --color-principal: <?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .navbar-ecommerce { background-color: #ffffff !important; border-bottom: 1px solid #e2e8f0; backdrop-filter: blur(12px); background-color: rgba(255,255,255,0.9) !important; }
        .nav-link-custom { color: #64748b !important; font-weight: 500; padding: 0.5rem 1rem !important; transition: color 0.2s; }
        .nav-link-custom:hover, .nav-link-custom.active { color: var(--color-principal) !important; }
        .btn-primary { background-color: var(--color-principal) !important; border-color: var(--color-principal) !important; border-radius: 10px !important; font-weight: 600; }
        .btn-outline-primary { color: var(--color-principal); border-color: #e2e8f0; background-color: white; font-weight: 600; }
        .btn-outline-primary:hover { background-color: var(--color-principal); color: white; border-color: var(--color-principal); }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        iconify-icon { vertical-align: -2px; display: inline-flex; }

        .product-main-img { border-radius: 20px; width: 100%; max-height: 500px; object-fit: cover; cursor: zoom-in; transition: opacity 0.3s; }
        .product-detail-card { border-radius: 20px; border: 1px solid #e2e8f0; background: #fff; padding: 2rem; }
        .product-price { font-size: 2rem; font-weight: 700; color: var(--color-principal); }
        .badge-stock-detail { padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .descripcion-text { line-height: 1.7; color: #475569; white-space: pre-wrap; }

        .lightbox-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); display: none; align-items: center; justify-content: center; cursor: zoom-out; animation: fadeIn 0.2s ease; }
        .lightbox-overlay.show { display: flex; }
        .lightbox-overlay img { max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); animation: scaleIn 0.25s ease; }
        .lightbox-overlay .lightbox-close { position: absolute; top: 16px; right: 24px; color: #fff; font-size: 2rem; cursor: pointer; background: none; border: none; opacity: 0.7; transition: opacity 0.2s; }
        .lightbox-overlay .lightbox-close:hover { opacity: 1; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: slideUp 0.4s ease both; }

        .related-card { border: none !important; border-radius: 16px !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important; transition: transform 0.25s ease, box-shadow 0.25s ease; background: #ffffff; }
        .related-card:hover { transform: translateY(-6px); box-shadow: 0 14px 20px -4px rgba(0,0,0,0.1), 0 6px 10px -4px rgba(0,0,0,0.05) !important; }
        .related-card img { height: 180px; object-fit: cover; border-radius: 16px 16px 0 0; }
        .cart-badge { position: absolute; top: -4px; right: -6px; font-size: 0.6rem; padding: 2px 5px; border-radius: 50%; background: #ef4444; color: #fff; font-weight: 700; min-width: 18px; text-align: center; display: none; }
        .cart-float { position: fixed; bottom: 24px; right: 24px; z-index: 999; }
        .cart-float .btn { width: 56px; height: 56px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.2); position: relative; }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast.show { display: flex !important; }
        .offcanvas-backdrop { position: fixed; inset: 0; z-index: 1040; background: rgba(0,0,0,0.5); }
        .offcanvas.show { transform: none !important; visibility: visible !important; }
        .cart-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .cart-item img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-weight: 600; transition: all 0.15s; }
        .qty-btn:hover { background: #f1f5f9; border-color: var(--color-principal); }
    </style>
</head>
<body>

<div class="toast-container-custom">
    <div id="cartToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2000">
        <div class="d-flex">
            <div id="cartToastBody" class="toast-body d-flex align-items-center gap-2"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="lightbox-overlay" id="lightbox">
    <button class="lightbox-close" id="lightboxClose">&times;</button>
    <img id="lightboxImg" src="" alt="">
</div>

<nav class="navbar navbar-expand-lg navbar-light navbar-ecommerce sticky-top shadow-sm py-3">
    <div class="container" style="max-width: 1100px;">
        <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">
            <?php if (!empty($tienda['logo_url'])): ?>
                <img src="<?php echo htmlspecialchars(imagen_url($tienda['logo_url'])); ?>" alt="Logo" style="max-height: 40px;">
            <?php else: ?>
                <iconify-icon icon="mdi:store" width="24" style="color: var(--color-principal);"></iconify-icon> <?php echo htmlspecialchars($tienda['nombre_tienda']); ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNavigation">
            <ul class="navbar-nav gap-1 mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>#productos-catalogo">Productos</a>
                </li>
                <?php if (!empty($tienda['telefono_whatsapp'])): ?>
                <li class="nav-item">
                    <a class="nav-link nav-link-custom" href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']); ?>" target="_blank">Contacto</a>
                </li>
                <?php endif; ?>
                <li class="nav-item position-relative">
                    <button id="btnToggleCart" class="nav-link nav-link-custom border-0 bg-transparent" style="cursor:pointer;">
                        <iconify-icon icon="mdi:cart-outline" width="22"></iconify-icon>
                        <span id="cartBadgeNav" class="cart-badge">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5" style="max-width: 1000px;">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>" class="text-decoration-none" style="color: var(--color-principal);">Inicio</a></li>
            <?php if (!empty($producto['nombre_categoria'])): ?>
            <li class="breadcrumb-item"><a href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&categoria=<?php echo $producto['categoria_id']; ?>" class="text-decoration-none" style="color: var(--color-principal);"><?php echo htmlspecialchars($producto['nombre_categoria']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $prod_nombre; ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="position-relative">
                <img src="<?php echo htmlspecialchars($prod_imagen); ?>" class="product-main-img" alt="<?php echo $prod_nombre; ?>" id="mainImage" loading="eager">
                <button class="btn position-absolute" style="top:12px;right:12px;background:rgba(255,255,255,0.9);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;backdrop-filter:blur(4px);" id="btnShareProduct" title="Compartir">
                    <iconify-icon icon="mdi:share-variant" width="18" style="color:#64748b;"></iconify-icon>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="product-detail-card">
                <?php if (!empty($producto['nombre_categoria'])): ?>
                    <span class="badge bg-secondary mb-2" style="border-radius:20px;font-weight:500;"><?php echo htmlspecialchars($producto['nombre_categoria']); ?></span>
                <?php endif; ?>
                <h1 class="fw-bold mb-3" style="font-size:1.75rem;"><?php echo $prod_nombre; ?></h1>
                <p class="product-price mb-3"><?php echo $prod_precio; ?> €</p>

                <?php if ($producto['stock'] > 0): ?>
                    <span class="badge bg-success badge-stock-detail mb-3 d-inline-flex align-items-center gap-1"><iconify-icon icon="mdi:check-circle" width="14"></iconify-icon> En stock (<?php echo $producto['stock']; ?> uds)</span>
                <?php else: ?>
                    <span class="badge bg-danger badge-stock-detail mb-3 d-inline-flex align-items-center gap-1"><iconify-icon icon="mdi:close-circle" width="14"></iconify-icon> Agotado</span>
                <?php endif; ?>

                <?php if (!empty($producto['descripcion'])): ?>
                    <hr>
                    <h6 class="fw-bold mb-2">Descripción</h6>
                    <p class="descripcion-text"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                <?php endif; ?>

                <hr>

                <div class="d-flex gap-2 flex-wrap">
                    <?php if ($producto['stock'] > 0): ?>
                        <button class="btn btn-primary btn-lg flex-fill btn-icon py-3" id="btnAddCart" data-id="<?php echo $producto['id']; ?>" data-nombre="<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-precio="<?php echo $producto['precio']; ?>" data-img="<?php echo htmlspecialchars(imagen_url($producto['imagen_thumb'] ?: $producto['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>">
                            <iconify-icon icon="mdi:cart-plus" width="20"></iconify-icon> Agregar al Carrito
                        </button>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-lg flex-fill btn-icon py-3" disabled><iconify-icon icon="mdi:close-circle" width="20"></iconify-icon> Agotado</button>
                    <?php endif; ?>
                    <?php if (!empty($tienda['telefono_whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp']); ?>?text=<?php echo urlencode('Hola, me interesa: ' . $prod_nombre . ' - ' . $prod_precio . ' € - ' . $prod_url); ?>" target="_blank" class="btn btn-success btn-lg btn-icon py-3" style="border-radius:10px;">
                            <iconify-icon icon="mdi:whatsapp" width="20"></iconify-icon> Consultar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($relacionados) > 0): ?>
    <hr class="my-5">
    <h4 class="fw-bold mb-4"><iconify-icon icon="mdi:tag-multiple" width="24"></iconify-icon> Productos Relacionados</h4>
    <div class="row g-3">
        <?php foreach ($relacionados as $rel): ?>
        <div class="col-6 col-md-3 fade-in">
            <a href="producto.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&id=<?php echo $rel['id']; ?>" class="text-decoration-none">
                <div class="card h-100 related-card">
                    <img src="<?php echo htmlspecialchars(imagen_url($rel['imagen_thumb'] ?: $rel['imagen_url'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($rel['nombre']); ?>" loading="lazy">
                    <div class="card-body p-3">
                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($rel['nombre']); ?></h6>
                        <p class="text-success fw-bold mb-0"><?php echo number_format($rel['precio'], 2); ?> €</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
    <div class="offcanvas-header border-bottom">
        <h5 class="fw-bold mb-0"><iconify-icon icon="mdi:cart" width="22"></iconify-icon> Tu Carrito</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="cartItems" class="flex-grow-1">
            <p class="text-muted text-center py-4" id="cartEmpty">Tu carrito está vacío. Agrega productos desde el catálogo.</p>
        </div>
        <div id="cartFooter" class="border-top pt-3 d-none">
            <div class="mb-3">
                <label class="form-label fw-semibold">Tu nombre</label>
                <input type="text" id="cartNombre" class="form-control" placeholder="Escribe tu nombre" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Tu email <span class="text-muted">(opcional)</span></label>
                <input type="email" id="cartEmail" class="form-control" placeholder="tu@email.com">
            </div>
            <button id="btnWhatsAppCart" class="btn btn-success w-100 py-2 fw-bold btn-icon">
                <iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp
            </button>
        </div>
    </div>
</div>

<a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp'] ?? ''); ?>" target="_blank" class="text-decoration-none cart-float">
    <button class="btn btn-success shadow-lg d-flex align-items-center justify-content-center">
        <iconify-icon icon="mdi:whatsapp" width="24"></iconify-icon>
    </button>
</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script nonce="<?= $csp_nonce ?>">
let carrito = JSON.parse(localStorage.getItem('carrito_' + <?php echo $tienda_id; ?>)) || [];
let carritoData = [];
const whatsappNum = '<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp'] ?? ''); ?>';
const tiendaSlug = '<?php echo htmlspecialchars($tienda['slug']); ?>';
const csrfToken = '<?php echo csrf_token(); ?>';

function guardarCarrito() { localStorage.setItem('carrito_' + <?php echo $tienda_id; ?>, JSON.stringify(carrito)); }
function actualizarBadge() {
    const total = carrito.reduce((s, i) => s + i.c, 0);
    const badge = document.getElementById('cartBadgeNav');
    if (total > 0) { badge.style.display = 'inline'; badge.textContent = total; }
    else { badge.style.display = 'none'; }
}
function cargarCarrito() {
    const ids = carrito.map(i => i.id).join(',');
    if (!ids) { carritoData = []; renderCarrito(); return; }
    fetch('api-productos.php?ids=' + ids + '&tienda=' + <?php echo $tienda_id; ?>)
        .then(r => r.json())
        .then(data => { carritoData = data; renderCarrito(); });
}
function renderCarrito() {
    const container = document.getElementById('cartItems');
    const footer = document.getElementById('cartFooter');
    const empty = document.getElementById('cartEmpty');
    if (carrito.length === 0) {
        empty.style.display = 'block'; footer.classList.add('d-none');
        container.querySelectorAll('.cart-item').forEach(el => el.remove());
        return;
    }
    empty.style.display = 'none'; footer.classList.remove('d-none');
    container.querySelectorAll('.cart-item').forEach(el => el.remove());
    carrito.forEach(item => {
        const prod = carritoData.find(p => p.id === item.id);
        if (!prod) return;
        const div = document.createElement('div');
        div.className = 'cart-item';
        const img = document.createElement('img'); img.src = prod.imagen; img.alt = prod.nombre; div.appendChild(img);
        const infoDiv = document.createElement('div'); infoDiv.className = 'flex-grow-1';
        const nameDiv = document.createElement('div'); nameDiv.className = 'fw-bold small'; nameDiv.textContent = prod.nombre; infoDiv.appendChild(nameDiv);
        const priceSmall = document.createElement('small'); priceSmall.className = 'text-success fw-bold'; priceSmall.textContent = prod.precio.toFixed(2) + ' €'; infoDiv.appendChild(priceSmall);
        div.appendChild(infoDiv);
        const qtyDiv = document.createElement('div'); qtyDiv.className = 'd-flex align-items-center gap-1';
        const minusBtn = document.createElement('button'); minusBtn.className = 'qty-btn'; minusBtn.textContent = '\u2212';
        minusBtn.addEventListener('click', function() { cambiarCant(item.id, -1); }); qtyDiv.appendChild(minusBtn);
        const qtySpan = document.createElement('span'); qtySpan.className = 'fw-bold mx-1'; qtySpan.style.cssText = 'min-width:20px;text-align:center;'; qtySpan.textContent = item.c; qtyDiv.appendChild(qtySpan);
        const plusBtn = document.createElement('button'); plusBtn.className = 'qty-btn'; plusBtn.textContent = '+';
        plusBtn.addEventListener('click', function() { cambiarCant(item.id, 1); }); qtyDiv.appendChild(plusBtn);
        div.appendChild(qtyDiv);
        const delBtn = document.createElement('button'); delBtn.className = 'btn btn-sm btn-outline-danger border-0';
        delBtn.innerHTML = '<iconify-icon icon="mdi:close" width="16"></iconify-icon>';
        delBtn.addEventListener('click', function() { eliminarDelCarrito(item.id); }); div.appendChild(delBtn);
        container.appendChild(div);
    });
}
function mostrarToast(mensaje, tipo) {
    const el = document.getElementById('cartToast');
    el.classList.remove('text-bg-success', 'text-bg-danger', 'show');
    el.classList.add('text-bg-' + tipo);
    el.querySelector('#cartToastBody').textContent = mensaje;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 3000);
}
function agregarAlCarrito(id, nombre, precio, img) {
    const idx = carrito.findIndex(i => i.id === id);
    if (idx >= 0) { carrito[idx].c += 1; }
    else { carrito.push({ id, c: 1 }); }
    guardarCarrito(); actualizarBadge();
    mostrarToast(nombre + ' agregado al carrito', 'success');
}
function cambiarCant(id, delta) {
    const item = carrito.find(i => i.id === id);
    if (!item) return;
    item.c += delta;
    if (item.c <= 0) { carrito = carrito.filter(i => i.id !== id); }
    guardarCarrito(); actualizarBadge(); cargarCarrito();
}
function eliminarDelCarrito(id) {
    carrito = carrito.filter(i => i.id !== id);
    guardarCarrito(); actualizarBadge(); cargarCarrito();
}
function toggleCart() {
    const el = document.getElementById('cartOffcanvas');
    el.classList.toggle('show');
    let backdrop = document.querySelector('.offcanvas-backdrop');
    if (el.classList.contains('show')) {
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'offcanvas-backdrop';
            backdrop.addEventListener('click', toggleCart);
            document.body.appendChild(backdrop);
        }
        cargarCarrito();
    } else {
        if (backdrop) backdrop.remove();
    }
}
function enviarWhatsApp() {
    const nombre = document.getElementById('cartNombre').value.trim();
    const email = document.getElementById('cartEmail').value.trim();
    if (!nombre) { document.getElementById('cartNombre').classList.add('is-invalid'); return; }
    document.getElementById('cartNombre').classList.remove('is-invalid');
    if (!whatsappNum) { alert('La tienda no tiene WhatsApp configurado.'); return; }
    const btn = document.getElementById('btnWhatsAppCart');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
    const formData = new FormData();
    formData.append('_csrf', csrfToken);
    formData.append('nombre_cliente', nombre);
    formData.append('email_cliente', email);
    formData.append('slug', tiendaSlug);
    formData.append('items', JSON.stringify(carrito));
    fetch('guardar-pedido.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                mostrarToast(data.message, 'danger');
                btn.disabled = false;
                btn.innerHTML = '<iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp';
                return;
            }
            let msg = 'Hola, soy *' + nombre + '* y quiero pedir:\n';
            let total = 0;
            carrito.forEach(item => {
                const prod = carritoData.find(p => p.id === item.id);
                if (!prod) return;
                msg += '\n\u2022 ' + prod.nombre + ' x' + item.c + ' = ' + (prod.precio * item.c).toFixed(2) + '\u20AC';
                total += prod.precio * item.c;
            });
            msg += '\n\n*Total: ' + total.toFixed(2) + '\u20AC*';
            localStorage.removeItem('carrito_' + <?php echo $tienda_id; ?>);
            carrito = []; carritoData = [];
            actualizarBadge(); renderCarrito();
            toggleCart();
            window.open('https://wa.me/' + whatsappNum + '?text=' + encodeURIComponent(msg), '_blank');
            btn.disabled = false;
            btn.innerHTML = '<iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp';
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp';
            alert('Error de conexi\u00f3n. Intenta de nuevo.');
        });
}

/* --- lightbox --- */
document.getElementById('mainImage').addEventListener('click', function() {
    const lb = document.getElementById('lightbox');
    document.getElementById('lightboxImg').src = this.src;
    lb.classList.add('show');
});
document.getElementById('lightboxClose').addEventListener('click', function() {
    document.getElementById('lightbox').classList.remove('show');
});
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('lightbox').classList.remove('show');
});

/* --- compartir --- */
document.getElementById('btnShareProduct').addEventListener('click', function() {
    const url = '<?php echo htmlspecialchars($prod_url, ENT_QUOTES, 'UTF-8'); ?>';
    const text = '<?php echo $prod_nombre; ?> - <?php echo $prod_precio; ?> €';
    if (navigator.share) {
        navigator.share({ title: '<?php echo $prod_nombre; ?>', text: text, url: url }).catch(function(){});
    } else {
        const waUrl = 'https://wa.me/' + whatsappNum + '?text=' + encodeURIComponent(text + ' - ' + url);
        window.open(waUrl, '_blank');
    }
});

/* --- event listeners (CSP-safe) --- */
document.getElementById('btnAddCart').addEventListener('click', function() {
    agregarAlCarrito(parseInt(this.dataset.id), this.dataset.nombre, parseFloat(this.dataset.precio), this.dataset.img);
});
document.getElementById('btnToggleCart').addEventListener('click', toggleCart);
document.getElementById('btnWhatsAppCart').addEventListener('click', enviarWhatsApp);

actualizarBadge();
</script>
<?php require __DIR__ . '/templates/footer_partial.php'; ?>
</body>
</html>
