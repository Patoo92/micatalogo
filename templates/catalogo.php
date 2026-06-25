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
        .card-img-top { border-radius: 16px 16px 0 0 !important; height: 200px; object-fit: cover; cursor: pointer; }
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
        .qty-btn { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-weight: 600; transition: all 0.15s; }
        .qty-btn:hover { background: #f1f5f9; border-color: var(--color-principal); }
        .toast-container-custom { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast.show { display: flex !important; }
        .offcanvas-backdrop { position: fixed; inset: 0; z-index: 1040; background: rgba(0,0,0,0.5); }
        .offcanvas.show { transform: none !important; visibility: visible !important; }

        /* ===== Public Dark Mode ===== */
        .btn-dark-mode-public { display: inline-flex; align-items: center; gap: 4px; border: none; background: transparent; color: #64748b; cursor: pointer; padding: 0.5rem; border-radius: 10px; transition: all 0.2s; }
        .btn-dark-mode-public:hover { background: rgba(0,0,0,0.05); }
        body.public-dark-mode { background-color: #0f172a !important; color: #e2e8f0 !important; }
        body.public-dark-mode .navbar-ecommerce { background: rgba(15,23,42,0.95) !important; border-bottom-color: rgba(255,255,255,0.08) !important; }
        body.public-dark-mode .navbar-ecommerce .navbar-brand { color: #e2e8f0 !important; }
        body.public-dark-mode .nav-link-custom { color: #94a3b8 !important; }
        body.public-dark-mode .nav-link-custom:hover, body.public-dark-mode .nav-link-custom.active { color: var(--color-principal) !important; }
        body.public-dark-mode .hero-shop { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important; }
        body.public-dark-mode .product-card { background: rgba(30,41,59,0.6) !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2) !important; }
        body.public-dark-mode .product-card:hover { box-shadow: 0 14px 20px -4px rgba(0,0,0,0.3) !important; }
        body.public-dark-mode .product-card .card-title, body.public-dark-mode .product-card h6 { color: #e2e8f0 !important; }
        body.public-dark-mode .product-card .text-muted { color: #94a3b8 !important; }
        body.public-dark-mode .search-wrapper .form-control { background: rgba(30,41,59,0.8) !important; border-color: rgba(255,255,255,0.1) !important; color: #e2e8f0 !important; }
        body.public-dark-mode .search-wrapper .form-control:focus { background: rgba(30,41,59,0.95) !important; }
        body.public-dark-mode .btn-outline-primary { background: rgba(30,41,59,0.6) !important; border-color: rgba(255,255,255,0.15) !important; color: #e2e8f0 !important; }
        body.public-dark-mode .btn-outline-primary:hover { background: var(--color-principal) !important; }
        body.public-dark-mode .btn-outline-primary.active { background: var(--color-principal) !important; border-color: var(--color-principal) !important; color: #fff !important; }
        body.public-dark-mode .hero-shop h2, body.public-dark-mode .hero-shop p { color: #e2e8f0 !important; }
        body.public-dark-mode .offcanvas { background: #1e293b !important; color: #e2e8f0 !important; }
        body.public-dark-mode .offcanvas .btn-close { filter: invert(1); }
        body.public-dark-mode .cart-item { border-bottom-color: rgba(255,255,255,0.08) !important; }
        body.public-dark-mode .qty-btn { background: rgba(30,41,59,0.8) !important; border-color: rgba(255,255,255,0.15) !important; color: #e2e8f0 !important; }
        body.public-dark-mode .qty-btn:hover { background: rgba(30,41,59,0.95) !important; }
        body.public-dark-mode .footer-custom { background: #0f172a !important; border-top-color: rgba(255,255,255,0.08) !important; }
        body.public-dark-mode .footer-custom, body.public-dark-mode .footer-custom a { color: #94a3b8 !important; }
        body.public-dark-mode .cards-consent { background: #1e293b !important; border-color: rgba(255,255,255,0.1) !important; color: #e2e8f0 !important; }
        body.public-dark-mode .btn-dark-mode-public { background: rgba(255,255,255,0.1) !important; color: #e2e8f0 !important; }
        body.public-dark-mode .btn-dark-mode-public:hover { background: rgba(255,255,255,0.2) !important; }
        body.public-dark-mode h5.fw-bold:not(.product-card *) { color: #e2e8f0 !important; }
        body.public-dark-mode .product-card .text-success { color: #6ee7b7 !important; }

        /* ===== Parallax hero ===== */
        .hero-parallax { background-attachment: fixed !important; }
        @media (max-width: 768px) { .hero-parallax { background-attachment: scroll !important; } }

        /* ===== Layout toggle: List view ===== */
        #productGrid.list-view .product-item { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
        #productGrid.list-view .product-item .product-card { flex-direction: row !important; }
        #productGrid.list-view .product-item .product-card-img { width: 140px !important; min-height: 120px; flex-shrink: 0; border-radius: 16px 0 0 16px !important; }
        #productGrid.list-view .product-item .card-img-top { height: 120px !important; border-radius: 16px 0 0 16px !important; }
        #productGrid.list-view .product-item .card-body { flex: 1 !important; }
        #productGrid.list-view .product-item .btn-add-cart { max-width: 160px; }
        .btn-layout { display: inline-flex; align-items: center; gap: 4px; border: 1px solid #e2e8f0; background: #fff; color: #64748b; cursor: pointer; padding: 0.4rem 0.7rem; border-radius: 8px; transition: all 0.2s; font-size: 0.85rem; }
        .btn-layout:hover { background: #f1f5f9; border-color: var(--color-principal); }
        .btn-layout.active { background: var(--color-principal); border-color: var(--color-principal); color: #fff; }
        body.public-dark-mode .btn-layout { background: rgba(30,41,59,0.6); border-color: rgba(255,255,255,0.15); color: #94a3b8; }
        body.public-dark-mode .btn-layout:hover { background: rgba(30,41,59,0.9); }
        body.public-dark-mode .btn-layout.active { background: var(--color-principal); color: #fff; }

        /* buscador */
        .search-wrapper { position: relative; flex: 1; }
        .search-wrapper .form-control { padding-left: 2.5rem; border-radius: 40px; border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.95rem; }
        .search-wrapper .form-control:focus { background: #fff; border-color: var(--color-principal); box-shadow: 0 0 0 3px rgba(var(--color-principal), 0.1); }
        .search-wrapper .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }

        /* lightbox */
        .lightbox-overlay { position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); display: none; align-items: center; justify-content: center; cursor: zoom-out; animation: fadeIn 0.2s ease; }
        .lightbox-overlay.show { display: flex; }
        .lightbox-overlay img { max-width: 90vw; max-height: 90vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); animation: scaleIn 0.25s ease; }
        .lightbox-overlay .lightbox-close { position: absolute; top: 16px; right: 24px; color: #fff; font-size: 2rem; cursor: pointer; background: none; border: none; opacity: 0.7; transition: opacity 0.2s; }
        .lightbox-overlay .lightbox-close:hover { opacity: 1; }

        /* animaciones */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        .fade-in { animation: slideUp 0.4s ease both; }
        .product-card { border: none !important; border-radius: 16px !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03) !important; transition: transform 0.25s ease, box-shadow 0.25s ease; background: #ffffff; }
        .product-card:hover { transform: translateY(-6px); box-shadow: 0 14px 20px -4px rgba(0,0,0,0.1), 0 6px 10px -4px rgba(0,0,0,0.05) !important; }
        .product-card .product-card-img { overflow: hidden; border-radius: 16px 16px 0 0; }
        .product-card .product-card-img img { transition: transform 0.35s ease; width: 100%; height: 200px; object-fit: cover; }
        .product-card:hover .product-card-img img { transform: scale(1.05); }

        /* skeleton */
        .skeleton { background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: pulse 1.5s ease infinite; border-radius: 8px; }
        .skeleton-img { height: 200px; border-radius: 16px 16px 0 0; }
        .skeleton-text { height: 14px; margin-bottom: 8px; width: 70%; }
        .skeleton-price { height: 18px; width: 40%; margin-bottom: 12px; }
        .skeleton-btn { height: 38px; border-radius: 10px; }

        /* share button overlay */
        .product-card .share-overlay { position: absolute; top: 10px; right: 10px; z-index: 2; opacity: 0; transition: opacity 0.2s; }
        .product-card:hover .share-overlay { opacity: 1; }
        .product-card .share-overlay .btn { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.9); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #64748b; transition: all 0.15s; backdrop-filter: blur(4px); }
        .product-card .share-overlay .btn:hover { background: #fff; color: var(--color-principal); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }

        /* estado vacío búsqueda */
        #searchEmpty { display: none; }

        /* skeleton wrapper */
        #skeletonGrid { display: none; }
        #skeletonGrid.show { display: block; }
    </style>
<?php $cat_plan = $tienda['plan'] ?? 'starter'; $cat_pers = in_array($cat_plan, ['pro', 'business', 'enterprise']); ?>
<?php if ($cat_pers && !empty($tienda['meta_descripcion'])): ?>
    <meta name="description" content="<?php echo htmlspecialchars($tienda['meta_descripcion']); ?>">
<?php endif; ?>
<?php if ($cat_pers && !empty($tienda['meta_palabras_clave'])): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($tienda['meta_palabras_clave']); ?>">
<?php endif; ?>
<?php if ($cat_pers && !empty($tienda['codigo_tracking'])): ?>
    <?php echo $tienda['codigo_tracking']; ?>
<?php endif; ?>
<?php if ($cat_pers && !empty($tienda['css_personalizado'])): ?>
<style><?php echo $tienda['css_personalizado']; ?></style>
<?php endif; ?>
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
                <?php if ($cat_pers): ?>
                <li class="nav-item d-flex align-items-center">
                    <button id="darkModeTogglePublic" class="btn-dark-mode-public" title="Modo oscuro">
                        <iconify-icon id="darkModeIconPublic" icon="mdi:weather-night" width="16"></iconify-icon>
                    </button>
                </li>
                <?php endif; ?>
                <li class="nav-item position-relative">
                    <button id="btnToggleCart" class="nav-link nav-link-custom border-0 bg-transparent" style="cursor:pointer;">
                        <iconify-icon icon="mdi:cart-outline" width="22"></iconify-icon>
                        <span id="cartBadgeNav" class="cart-badge">0</span>
                    </button>
                </li>
                <?php if ($cat_pers && (!empty($tienda['instagram_url']) || !empty($tienda['facebook_url']) || !empty($tienda['tiktok_url']) || !empty($tienda['twitter_url']))): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link nav-link-custom dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Redes</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!empty($tienda['instagram_url'])): ?><li><a class="dropdown-item" href="<?php echo htmlspecialchars($tienda['instagram_url']); ?>" target="_blank"><iconify-icon icon="mdi:instagram" width="16"></iconify-icon> Instagram</a></li><?php endif; ?>
                        <?php if (!empty($tienda['facebook_url'])): ?><li><a class="dropdown-item" href="<?php echo htmlspecialchars($tienda['facebook_url']); ?>" target="_blank"><iconify-icon icon="mdi:facebook" width="16"></iconify-icon> Facebook</a></li><?php endif; ?>
                        <?php if (!empty($tienda['tiktok_url'])): ?><li><a class="dropdown-item" href="<?php echo htmlspecialchars($tienda['tiktok_url']); ?>" target="_blank"><iconify-icon icon="mdi:tiktok" width="16"></iconify-icon> TikTok</a></li><?php endif; ?>
                        <?php if (!empty($tienda['twitter_url'])): ?><li><a class="dropdown-item" href="<?php echo htmlspecialchars($tienda['twitter_url']); ?>" target="_blank"><iconify-icon icon="mdi:twitter" width="16"></iconify-icon> X / Twitter</a></li><?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-shop text-center py-5 mb-5 <?php echo $cat_pers ? 'hero-parallax' : ''; ?>" style="<?php if ($cat_pers && !empty($tienda['banner_url'])): ?>background: linear-gradient(135deg, rgba(0,0,0,0.5), rgba(0,0,0,0.3)), url(<?php echo htmlspecialchars(imagen_url($tienda['banner_url'])); ?>); background-size: cover; background-position: center; color: #fff;<?php endif; ?>">
    <div class="container" style="max-width: 600px;">
        <?php if ($cat_pers && !empty($tienda['banner_url'])): ?>
            <h2 class="fw-bold mb-2" style="color:#fff;"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></h2>
        <?php else: ?>
            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($cat_pers && !empty($tienda['hero_title']) ? $tienda['hero_title'] : 'Explora nuestra colección'); ?></h2>
        <?php endif; ?>
        <p class="<?php echo ($cat_pers && !empty($tienda['banner_url'])) ? 'text-light' : 'text-muted'; ?> mb-0">
            <?php echo htmlspecialchars($cat_pers && !empty($tienda['hero_subtitle']) ? $tienda['hero_subtitle'] : ($tienda['descripcion'] ?? 'Agrega productos a tu carrito y envíanos tu orden completa por WhatsApp.')); ?>
        </p>
    </div>
</div>

<div class="container pb-5" id="productos-catalogo" style="max-width: 900px;">

<?php if (!empty($destacados)): ?>
<div class="mb-5">
    <h5 class="fw-bold mb-3"><iconify-icon icon="mdi:star" width="20" style="color: #f59e0b;"></iconify-icon> Destacados</h5>
    <div class="row g-3">
        <?php foreach ($destacados as $prod): ?>
        <div class="col-6 col-md-3 fade-in">
            <a href="producto.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&id=<?php echo $prod['id']; ?>" class="text-decoration-none">
                <div class="card h-100 product-card">
                    <div class="product-card-img position-relative">
                        <img src="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" loading="lazy">
                        <?php if (!empty($prod['etiqueta'])): ?>
                        <span class="position-absolute top-0 start-0 badge m-2" style="background:<?php echo $prod['etiqueta'] === 'Oferta' ? '#ef4444' : ($prod['etiqueta'] === 'Nuevo' ? '#10b981' : '#64748b'); ?>;font-size:0.75rem;"><?php echo htmlspecialchars($prod['etiqueta']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                        <p class="text-success fw-bold mb-0"><?php echo number_format($prod['precio'], 2); ?> <?php echo htmlspecialchars($tienda['moneda'] ?? '€'); ?></p>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

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

    <div class="d-flex gap-2 align-items-center mb-4">
        <div class="search-wrapper">
            <iconify-icon icon="mdi:magnify" class="search-icon" width="18"></iconify-icon>
            <input type="text" id="searchInput" class="form-control" placeholder="Buscar productos…" autocomplete="off">
        </div>
        <?php if ($cat_pers): ?>
        <div class="d-flex gap-1 flex-shrink-0">
            <button id="layoutGrid" class="btn-layout active" title="Cuadrícula"><iconify-icon icon="mdi:grid" width="16"></iconify-icon></button>
            <button id="layoutList" class="btn-layout" title="Lista"><iconify-icon icon="mdi:view-list" width="16"></iconify-icon></button>
        </div>
        <?php endif; ?>
    </div>

    <h5 class="fw-bold mb-3">Nuestro Catálogo</h5>

    <!-- skeleton -->
    <div class="row g-3" id="skeletonGrid">
        <?php for ($i = 0; $i < min(6, count($productos) ?: 6); $i++): ?>
        <div class="col-6 col-md-4">
            <div class="card h-100 product-card">
                <div class="skeleton skeleton-img"></div>
                <div class="card-body p-3">
                    <div class="skeleton skeleton-text"></div>
                    <div class="skeleton skeleton-price"></div>
                    <div class="skeleton skeleton-btn"></div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <div class="row g-3" id="productGrid">
        <?php if (count($productos) > 0): ?>
            <?php foreach ($productos as $i => $prod): ?>
                <div class="col-6 col-md-4 product-item" data-nombre="<?php echo htmlspecialchars(strtolower($prod['nombre']), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="card h-100 product-card" style="animation-delay: <?php echo $i * 0.05; ?>s;">
                        <div class="position-relative product-card-img">
                            <img src="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url'])); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($prod['nombre']); ?>" loading="lazy" data-img="<?php echo htmlspecialchars(imagen_url($prod['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if (!empty($prod['etiqueta'])): ?>
                            <span class="position-absolute top-0 start-0 badge m-2" style="background:<?php echo $prod['etiqueta'] === 'Oferta' ? '#ef4444' : ($prod['etiqueta'] === 'Nuevo' ? '#10b981' : '#64748b'); ?>;font-size:0.75rem;"><?php echo htmlspecialchars($prod['etiqueta']); ?></span>
                            <?php endif; ?>
                            <div class="share-overlay">
                                <button class="btn btn-share" data-url="producto.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&id=<?php echo $prod['id']; ?>" data-nombre="<?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?>" title="Compartir">
                                    <iconify-icon icon="mdi:share-variant" width="14"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <a href="producto.php?tienda=<?php echo htmlspecialchars($tienda['slug']); ?>&id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                                    <h6 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($prod['nombre']); ?></h6>
                                </a>
                                <?php if (!empty($prod['descripcion'])): ?>
                                    <small class="text-muted d-block mb-1" style="line-height:1.3;"><?php echo htmlspecialchars(mb_substr($prod['descripcion'], 0, 60)) . (mb_strlen($prod['descripcion'] ?? '') > 60 ? '…' : ''); ?></small>
                                <?php endif; ?>
                                <p class="text-success fw-bold mb-2"><?php echo number_format($prod['precio'], 2); ?> <?php echo htmlspecialchars($tienda['moneda'] ?? '€'); ?></p>
                            </div>
                            <?php if ($prod['stock'] > 0): ?>
                                <button class="btn btn-primary btn-sm w-100 py-2 btn-icon btn-add-cart" data-id="<?php echo $prod['id']; ?>" data-nombre="<?php echo htmlspecialchars($prod['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-precio="<?php echo $prod['precio']; ?>" data-img="<?php echo htmlspecialchars(imagen_url($prod['imagen_thumb'] ?: $prod['imagen_url']), ENT_QUOTES, 'UTF-8'); ?>">
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

    <div id="searchEmpty" class="text-center py-5">
        <iconify-icon icon="mdi:package-variant-closed" width="48" style="color:#94a3b8;"></iconify-icon>
        <p class="text-muted mt-3">No encontramos productos con ese nombre.</p>
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
            <div class="mb-3">
                <label class="form-label fw-semibold">Tu email <span class="text-muted">(opcional, para notificarte)</span></label>
                <input type="email" id="cartEmail" class="form-control" placeholder="tu@email.com">
            </div>
            <button id="btnWhatsAppCart" class="btn btn-success w-100 py-2 fw-bold btn-icon">
                <iconify-icon icon="mdi:whatsapp" width="18"></iconify-icon> Enviar Pedido por WhatsApp
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script nonce="<?= $csp_nonce ?>">
let carrito = JSON.parse(localStorage.getItem('carrito_' + <?php echo $tienda_id; ?>)) || [];
let carritoData = [];
const whatsappNum = '<?php echo preg_replace('/[^0-9]/', '', $tienda['telefono_whatsapp'] ?? ''); ?>';
const moneda = '<?php echo htmlspecialchars($tienda['moneda'] ?? '€'); ?>';
const tiendaSlug = '<?php echo htmlspecialchars($tienda['slug']); ?>';
const csrfToken = '<?php echo csrf_token(); ?>';

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
        const img = document.createElement('img');
        img.src = prod.imagen;
        img.alt = prod.nombre;
        div.appendChild(img);
        const infoDiv = document.createElement('div');
        infoDiv.className = 'flex-grow-1';
        const nameDiv = document.createElement('div');
        nameDiv.className = 'fw-bold small';
        nameDiv.textContent = prod.nombre;
        infoDiv.appendChild(nameDiv);
        const priceSmall = document.createElement('small');
        priceSmall.className = 'text-success fw-bold';
        priceSmall.textContent = prod.precio.toFixed(2) + ' ' + moneda;
        infoDiv.appendChild(priceSmall);
        div.appendChild(infoDiv);
        const qtyDiv = document.createElement('div');
        qtyDiv.className = 'd-flex align-items-center gap-1';
        const minusBtn = document.createElement('button');
        minusBtn.className = 'qty-btn';
        minusBtn.textContent = '−';
        minusBtn.addEventListener('click', function() { cambiarCant(item.id, -1); });
        qtyDiv.appendChild(minusBtn);
        const qtySpan = document.createElement('span');
        qtySpan.className = 'fw-bold mx-1';
        qtySpan.style.cssText = 'min-width:20px;text-align:center;';
        qtySpan.textContent = item.c;
        qtyDiv.appendChild(qtySpan);
        const plusBtn = document.createElement('button');
        plusBtn.className = 'qty-btn';
        plusBtn.textContent = '+';
        plusBtn.addEventListener('click', function() { cambiarCant(item.id, 1); });
        qtyDiv.appendChild(plusBtn);
        div.appendChild(qtyDiv);
        const delBtn = document.createElement('button');
        delBtn.className = 'btn btn-sm btn-outline-danger border-0';
        delBtn.innerHTML = '<iconify-icon icon="mdi:close" width="16"></iconify-icon>';
        delBtn.addEventListener('click', function() { eliminarDelCarrito(item.id); });
        div.appendChild(delBtn);
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
    guardarCarrito();
    actualizarBadge();
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
            let msg = '<?php echo htmlspecialchars($tienda['mensaje_whatsapp'] ?? 'Hola, quiero pedir:'); ?>'.replace(/\\n/g, '\n') + '\n';
            let total = 0;
            carrito.forEach(item => {
                const prod = carritoData.find(p => p.id === item.id);
                if (!prod) return;
                msg += '\n• ' + prod.nombre + ' x' + item.c + ' = ' + (prod.precio * item.c).toFixed(2) + moneda;
                total += prod.precio * item.c;
            });
            msg += '\n\n*Total: ' + total.toFixed(2) + moneda + '*';
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
            alert('Error de conexión. Intenta de nuevo.');
        });
}

/* --- buscador en vivo --- */
var searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        var term = this.value.toLowerCase().trim();
        var items = document.querySelectorAll('.product-item');
        var anyVisible = false;
        items.forEach(function(el) {
            var match = el.dataset.nombre.indexOf(term) !== -1;
            el.style.display = match ? '' : 'none';
            if (match) anyVisible = true;
        });
        document.getElementById('searchEmpty').style.display = (term && !anyVisible) ? 'block' : 'none';
    });
}

/* --- lightbox --- */
document.querySelectorAll('.card-img-top[data-img]').forEach(function(img) {
    img.addEventListener('click', function() {
        var lb = document.getElementById('lightbox');
        document.getElementById('lightboxImg').src = this.dataset.img;
        lb.classList.add('show');
    });
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
document.querySelectorAll('.btn-share').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var url = window.location.origin + '/' + this.dataset.url;
        var nombre = this.dataset.nombre;
        var text = 'Mira este producto: ' + nombre + ' - ' + url;
        if (navigator.share) {
            navigator.share({ title: nombre, text: text, url: url }).catch(function(){});
        } else {
            var waUrl = 'https://wa.me/' + whatsappNum + '?text=' + encodeURIComponent(text);
            window.open(waUrl, '_blank');
        }
    });
});

/* --- fade-in on scroll --- */
var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.product-item').forEach(function(el) {
    observer.observe(el);
});

/* --- skeleton loader --- */
(function() {
    var skeleton = document.getElementById('skeletonGrid');
    var grid = document.getElementById('productGrid');
    if (skeleton && grid) {
        skeleton.classList.add('show');
        grid.style.display = 'none';
        window.addEventListener('load', function() {
            setTimeout(function() {
                skeleton.classList.remove('show');
                skeleton.style.display = 'none';
                grid.style.display = '';
            }, 300);
        });
    }
})();

/* --- event listeners (CSP-safe) --- */
document.querySelectorAll('.btn-add-cart').forEach(function(btn) {
    btn.addEventListener('click', function() {
        agregarAlCarrito(parseInt(this.dataset.id), this.dataset.nombre, parseFloat(this.dataset.precio), this.dataset.img);
    });
});
document.getElementById('btnToggleCart').addEventListener('click', toggleCart);
document.getElementById('btnWhatsAppCart').addEventListener('click', enviarWhatsApp);

actualizarBadge();

/* --- public dark mode --- */
(function() {
    var key = 'public_dark_mode_<?php echo $tienda_id; ?>';
    var body = document.body;
    var toggle = document.getElementById('darkModeTogglePublic');
    var icon = document.getElementById('darkModeIconPublic');
    if (localStorage.getItem(key) === '1') {
        body.classList.add('public-dark-mode');
        if (icon) icon.setAttribute('icon', 'mdi:weather-sunny');
    }
    if (toggle) {
        toggle.addEventListener('click', function() {
            body.classList.toggle('public-dark-mode');
            var isDark = body.classList.contains('public-dark-mode');
            localStorage.setItem(key, isDark ? '1' : '0');
            if (icon) icon.setAttribute('icon', isDark ? 'mdi:weather-sunny' : 'mdi:weather-night');
        });
    }
})();

/* --- layout toggle --- */
(function() {
    var grid = document.getElementById('productGrid');
    var btnGrid = document.getElementById('layoutGrid');
    var btnList = document.getElementById('layoutList');
    var layoutKey = 'catalog_layout_<?php echo $tienda_id; ?>';
    if (!grid || !btnGrid || !btnList) return;
    function setLayout(layout) {
        if (layout === 'list') {
            grid.classList.add('list-view');
            btnGrid.classList.remove('active');
            btnList.classList.add('active');
        } else {
            grid.classList.remove('list-view');
            btnGrid.classList.add('active');
            btnList.classList.remove('active');
        }
        localStorage.setItem(layoutKey, layout);
    }
    var saved = localStorage.getItem(layoutKey);
    if (saved === 'list') setLayout('list');
    btnGrid.addEventListener('click', function() { setLayout('grid'); });
    btnList.addEventListener('click', function() { setLayout('list'); });
})();
</script>
<?php require __DIR__ . '/footer_partial.php'; ?>
</body>
</html>
