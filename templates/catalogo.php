<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($tienda['nombre_tienda']); ?> - Tienda Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        :root { --color-principal: <?php echo htmlspecialchars($tienda['color_tema'] ?? '#0d6efd'); ?>; }
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .navbar-ecommerce { background-color: #ffffff !important; border-bottom: 1px solid #e2e8f0; backdrop-filter: blur(12px); background-color: rgba(255,255,255,0.9) !important; }
        .nav-link-custom { color: #64748b !important; font-weight: 500; padding: 0.5rem 1rem !important; transition: color 0.2s; }
        .nav-link-custom:hover, .nav-link-custom.active { color: var(--color-principal) !important; }
        .hero-shop { background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); border-radius: 0 0 24px 24px; }
        .btn-primary { background-color: var(--color-principal) !important; border-color: var(--color-principal) !important; border-radius: 10px !important; font-weight: 600; }
        .btn-outline-primary { color: var(--color-principal); border-color: #e2e8f0; background-color: white; font-weight: 600; }
        .btn-outline-primary:hover { background-color: var(--color-principal); color: white; border-color: var(--color-principal); }
        .product-card { border: none !important; border-radius: 16px !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important; transition: transform 0.2s ease, box-shadow 0.2s ease; background: #ffffff; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important; }
        .card-img-top { border-radius: 16px 16px 0 0 !important; height: 180px; object-fit: cover; }
        .scroll-clean::-webkit-scrollbar { display: none; }
        .scroll-clean { -ms-overflow-style: none; scrollbar-width: none; }
        .btn-icon { display: inline-flex; align-items: center; gap: 6px; }
        iconify-icon { vertical-align: -2px; display: inline-flex; }
        .cart-badge { position: absolute; top: -4px; right: -6px; font-size: 0.6rem; padding: 2px 5px; border-radius: 50%; background: #ef4444; color: #fff; font-weight: 700; min-width: 18px; text-align: center; display: none; }
        .cart-float { position: fixed; bottom: 24px; right: 24px; z-index: 999; }
        .cart-float .btn { width: 56px; height: 56px; border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.2); position: relative; }
        .cart-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .cart-item:last-child { border-bottom: none; }
        .cart-item img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; }
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-weight: 600; }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
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

<nav class="navbar navbar-expand-lg navbar-light navbar-ecommerce sticky-top shadow-sm py-3">
    <div class="container" style="max-width: 1100px;">
        <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">
            <?php if (!empty($tienda['logo_url'])): ?>
                <img src="<?php echo htmlspecialchars($tienda['logo_url']); ?>" alt="Logo" style="max-height: 40px;">
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
                    <a class="nav-link nav-link-custom <?php echo !$categoria_filtrada ? 'active' : ''; ?>" href="index.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>">Inicio</a>
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
                    <button class="nav-link nav-link-custom border-0 bg-transparent" onclick="toggleCart()" style="cursor:pointer;">
                        <iconify-icon icon="mdi:cart-outline" width="22"></iconify-icon>
                        <span id="cartBadgeNav" class="cart-badge">0</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-shop text-center py-5 mb-5">
    <div class="container" style="max-width: 600px;">
        <h2 class="fw-bold mb-2">Explora nuestra colección</h2>
        <p class="text-muted mb-0">Agrega productos a tu carrito y envíanos tu orden completa por WhatsApp.</p>
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
                        <img src="<?php echo htmlspecialchars($prod['imagen_thumb'] ?: $prod['imagen_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <h6 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                                <p class="text-success fw-bold mb-2"><?php echo number_format($prod['precio'], 2); ?> €</p>
                            </div>
                            <?php if ($prod['stock'] > 0): ?>
                                <button onclick="agregarAlCarrito(<?php echo $prod['id']; ?>, '<?php echo addslashes($prod['nombre']); ?>', <?php echo $prod['precio']; ?>, '<?php echo addslashes($prod['imagen_thumb'] ?: $prod['imagen_url']); ?>')" class="btn btn-primary btn-sm w-100 py-2 btn-icon">
                                    <iconify-icon icon="mdi:cart-plus" width="16"></iconify-icon> Agregar
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm w-100 fw-bold py-2 btn-icon" disabled><iconify-icon icon="mdi:close-circle" width="16"></iconify-icon> Agotado</button>
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
            <button id="btnWhatsAppCart" class="btn btn-success w-100 py-2 fw-bold btn-icon" onclick="enviarWhatsApp()">
                <iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let carrito = JSON.parse(localStorage.getItem('carrito_' + <?php echo $tienda_id; ?>)) || [];
let carritoData = [];
const whatsappNum = '<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp'] ?? ''); ?>';

function guardarCarrito() {
    localStorage.setItem('carrito_' + <?php echo $tienda_id; ?>, JSON.stringify(carrito));
}

function actualizarBadge() {
    const total = carrito.reduce((s, i) => s + i.c, 0);
    const badge = document.getElementById('cartBadgeNav');
    if (total > 0) { badge.style.display = 'inline'; badge.textContent = total; }
    else { badge.style.display = 'none'; }
}

function cargarCarrito() {
    const ids = carrito.map(i => i.id).join(',');
    if (!ids) { carritoData = []; renderCarrito(); return; }
    fetch('api-productos.php?ids=' + ids)
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
    let html = '';
    carrito.forEach(item => {
        const prod = carritoData.find(p => p.id === item.id);
        if (!prod) return;
        const subtotal = (prod.precio * item.c).toFixed(2);
        html += '<div class="cart-item">';
        html += '<img src="' + prod.imagen + '" alt="' + prod.nombre + '">';
        html += '<div class="flex-grow-1"><div class="fw-bold small">' + prod.nombre + '</div><small class="text-success fw-bold">' + prod.precio.toFixed(2) + ' €</small></div>';
        html += '<div class="d-flex align-items-center gap-1">';
        html += '<button class="qty-btn" onclick="cambiarCant(' + item.id + ', -1)">−</button>';
        html += '<span class="fw-bold mx-1" style="min-width:20px;text-align:center;">' + item.c + '</span>';
        html += '<button class="qty-btn" onclick="cambiarCant(' + item.id + ', 1)">+</button>';
        html += '</div>';
        html += '<button class="btn btn-sm btn-outline-danger border-0" onclick="eliminarDelCarrito(' + item.id + ')"><iconify-icon icon="mdi:close" width="16"></iconify-icon></button>';
        html += '</div>';
    });
    container.insertAdjacentHTML('beforeend', html);
}

function agregarAlCarrito(id, nombre, precio, img) {
    const idx = carrito.findIndex(i => i.id === id);
    if (idx >= 0) { carrito[idx].c += 1; }
    else { carrito.push({ id, c: 1 }); }
    guardarCarrito();
    actualizarBadge();
    const toastEl = document.getElementById('cartToast');
    toastEl.classList.remove('text-bg-danger');
    toastEl.classList.add('text-bg-success');
    document.getElementById('cartToastBody').innerHTML = '<iconify-icon icon="mdi:check-circle" width="18"></iconify-icon> ' + nombre + ' agregado al carrito';
    bootstrap.Toast.getOrCreateInstance(toastEl).show();
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
    const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas'));
    if (offcanvas) offcanvas.show(); else new bootstrap.Offcanvas(document.getElementById('cartOffcanvas')).show();
    cargarCarrito();
}

function enviarWhatsApp() {
    const nombre = document.getElementById('cartNombre').value.trim();
    if (!nombre) { document.getElementById('cartNombre').classList.add('is-invalid'); return; }
    document.getElementById('cartNombre').classList.remove('is-invalid');
    if (!whatsappNum) { alert('La tienda no tiene WhatsApp configurado.'); return; }
    let msg = 'Hola, soy *' + nombre + '* y quiero pedir:\n';
    let total = 0;
    carrito.forEach(item => {
        const prod = carritoData.find(p => p.id === item.id);
        if (!prod) return;
        msg += '\n• ' + prod.nombre + ' x' + item.c + ' = ' + (prod.precio * item.c).toFixed(2) + '€';
        total += prod.precio * item.c;
    });
    msg += '\n\n*Total: ' + total.toFixed(2) + '€*';
    localStorage.removeItem('carrito_' + <?php echo $tienda_id; ?>);
    carrito = []; carritoData = [];
    actualizarBadge(); renderCarrito();
    bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas')).hide();
    window.open('https://wa.me/' + whatsappNum + '?text=' + encodeURIComponent(msg), '_blank');
}

actualizarBadge();
</script>
</body>
</html>
