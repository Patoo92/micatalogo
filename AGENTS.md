# micatalogo — SaaS multi-tenant de catálogos con pedidos WhatsApp

## Stack
- PHP 8.2+ vanilla (sin framework), MySQL, Apache/XAMPP
- Bootstrap 5.3, iconify, PHPMailer (SMTP Brevo)
- CSP nonce obliga a `addEventListener` en vez de `onclick=""`

## Estado actual del proyecto

### Planes y monetización (sin pasarela de pago)
- **Starter** (gratis, siempre): 1 staff, 1 tienda, 0 API keys, sin marca blanca, sin personalización
- **Pro** (12€/mes): 3 staff, 1 tienda, 1 API key, personalización completa (SEO, hero, RRSS, CSS, tracking, notificaciones), sin marca blanca
- **Business** (19€/mes): 10 staff, 3 tiendas, 5 API keys, marca blanca, dominio personalizado, todo lo de Pro
- **Enterprise** (39€/mes): staff/tiendas/API keys ilimitados, personalización total, soporte dedicado
- Trial: 3 días solo para Pro/Business; al vencer → downgrade automático a Starter (login.php)
- Upgrades: gestión manual desde super-admin (sin pasarela de pago aún)

### Nuevas implementaciones (25 junio 2026)
- **Dashboard stats**: admin.php ahora muestra cards con total productos, pedidos totales, pendientes, stock bajo/agotados, pedidos hoy y esta semana
- **Dominio personalizado**: columna `dominio` en tiendas, campo en config (Business+), routing automático en index.php y producto.php — si el HTTP_HOST coincide con un dominio registrado, carga esa tienda sin necesidad de `?tienda=`
- **Dashboard.php con gráficos**: nueva página dedicada con Chart.js (pedidos/7 días, productos por categoría). Stats movidas de admin.php a dashboard.php como tab independiente
- **Modo oscuro público**: toggle en navbar de catálogo y producto.php, gated por plan Pro+. Persiste en localStorage por tienda (`public_dark_mode_{id}`)
- **Efecto parallax**: clase `hero-parallax` en hero-shop del catálogo, solo Pro+. `background-attachment: fixed` con fallback scroll en móvil
- **Layout alternativo**: toggle grid/lista en catálogo (Pro+). Clase `list-view` en #productGrid, persiste en localStorage
- **Tema visual admin**: columna `tema_admin` en tiendas. Selector en configuración (Pro+). 5 temas: default, ocean, forest, sunset, midnight. Almacenado en `$_SESSION['tema_admin']`, aplicado como clase `theme-{nombre}` al body de todas las páginas admin. Estilos en css/style.css

### Fixes (post-implementación)
- **Dark mode público**: reemplazado iconify-icon por emoji unicode (&#9790;/&#9728;) para evitar dependencia CSP connect-src. CSS usa `background-image` en vez de `background` shorthand para no resetear `background-attachment`
- **Parallax hero**: `.hero-shop` cambió a `background-image` (separado de attachment). Banner con `::before` pseudo-element para overlay, `background-image` inline para la imagen, `hero-parallax` class para attachment
- **List view**: CSS más robusto con `!important` en todas las propiedades clave para resistir CSS personalizado. Imagen 130x130 fija, tarjeta horizontal con flex
- **Dashboard**: agregado try/catch en Chart.js, fallback si no hay datos, eliminada query rota duplicada
- **Theme variants**: agregados estilos para `.navbar-admin`, `.glass-card`, `.glass-table`, `.btn-primary` en cada tema
- **reset-password.php**: corregida validación de contraseña (solo min 10 chars, sin mayúscula/número)

### Features implementadas
- Registro con selección de plan, trial, validación completa (NIST password: min 10 chars, sin reglas de mayúscula/número)
- Login con downgrade automático de trial vencido
- Staff con permisos granulares
- API keys + API REST (api.php, api-productos.php)
- Marca blanca (oculta branding en emails y catálogo público)
- Productos: CRUD, stock, stock mínimo, alerta email, thumbnails, WebP con fallback, destacado, etiqueta (Nuevo/Oferta/Sin stock)
- Catálogo público (catalogo.php) con carrito, lightbox, compartir WhatsApp, meta tags OG/Twitter, hero personalizable, CSS custom, tracking
- Página individual de producto (producto.php) con meta tags, tracking, CSS custom, etiqueta badge
- Pedidos: agrupados por cliente+fecha, expandibles, completar, cancelar con restauración de stock, email de confirmación al cliente
- Super-admin: gestión de tiendas, actividad, cambiar plan, extender trial
- Seguridad: CSP nonce, CSRF tokens, rate limiting login, FOR UPDATE en pedidos
- Email: SMTP Brevo con PHPMailer, alertas stock mínimo, confirmación al cliente
- Configuración de tienda: nombre, email, moneda, redes sociales (Facebook, TikTok, Twitter/X), descripción, dirección, horario, mensaje WhatsApp, banner, hero title/subtitle, meta tags, tracking, CSS personalizado, notificaciones
- Footer genérico con dirección/horario, cookies consent banner, página de privacidad
- Exportar/Importar productos CSV

### Sidebar lateral (navegación moderna)
- Creado `templates/sidebar_partial.php`: sidebar fijo (240px) en escritorio con gradiente oscuro
- Navbar superior visible solo en móvil (`d-lg-none`), sidebar oculto en móvil (`d-none d-lg-flex`)
- Links: Productos, Pedidos, Staff, Historial, Configuración, Respaldo + Modo oscuro, Ver tienda, Cambiar contraseña, Salir
- Link activo resaltado con CSS dinámico `basename($_SERVER['PHP_SELF'])`
- Aplicado en todos los templates admin para consistencia visual
- Estilos en `css/style.css` con hover/active states

### Dark mode
- Toggle en sidebar (ídem `darkModeToggle`) con persistencia en `localStorage('dark_mode')`
- Script replicado en cada admin page (`admin_body.php`, `config_body.php`, `pedidos_body.php`, y páginas standalone)
- CSS: overrides para glass-card, glass-table, sidebar, forms, botones, badges, paginación, inputs, tables, etc. bajo `body.dark-mode`
- Clase `dark-mode` se aplica/remueve del `<body>`

### Landing con capturas reales
- `index.html`: reemplazados placeholders por `imagenes/captura-admin.jpg` y `captura-movil.jpg`

### Bugs corregidos (24 junio 2026)

**Críticos:**
- **Css/ → css/**: carpeta renombrada a minúscula para compatibilidad Linux
- **editar-producto.php**: SELECT del producto movido antes del bloque POST; ya no se pierde thumbnail
- **login.php**: alias `t.activo AS tienda_activo`; staff de tienda suspendida ya no puede entrar

**Medios:**
- **staff-nuevo.php**: validación de contraseña homogeneizada (solo min 10 chars, sin mayúscula/número)
- **helpers.php**: `verificar_limite_plan()` ya no pasa HTML con `<strong>` a `mostrar_error()` (evita doble escape)
- **guardar-pedido.php**: validación `stock > 0` antes de insertar pedido desde el carrito
- **hacer-pedido.php**: usa `$tienda['moneda']` en vez de `€` hardcodeado

**Menores:**
- **api.php**: `Access-Control-Allow-Origin` ahora refleja el origin específico en vez de `*`
- **backup.php**: warning en SQL output sobre datos de staff incluidos
- **cancelar-pedido.php**: no ejecuta UPDATE stock si `producto_id` es NULL; log refleja el caso real
- **Css/ → css/**: carpeta renombrada a minúscula para compatibilidad Linux (case-sensitive). Todos los PHP referenciaban `css/style.css` pero la carpeta era `Css/`.
- **editar-producto.php**: movida la consulta `SELECT` del producto antes del bloque `POST`. Ya no se usa `$producto` antes de ser definida; al editar sin cambiar imagen ya no se pierde el thumbnail.
- **login.php**: añadido alias `t.activo AS tienda_activo` al SELECT de staff. El segundo `elseif` ahora compara `$staff['tienda_activo']` en vez de duplicar la condición de `activo`. Staff de tienda suspendida ya no puede iniciar sesión.

### Cambios recientes (junio 2026)

#### helpers.php
- `generar_thumbnail()`: verifica `function_exists('imagecreatefromwebp')` antes de usarlo
- `plan_limite()`: define límites por plan
- `verificar_limite_plan()`: muestra error si se excede el límite
- Agregado guard `HELPERS_LOADED` al inicio para evitar doble inclusión

#### Pedidos agrupados (pedidos.php + templates/pedidos_body.php)
- `pedidos.php`: agrupa pedidos por `nombre_cliente + fecha_pedido (minuto exacto)` en un array `$pedidos[]` con items, total, pendientes, ids_estados
- `pedidos_body.php`: tarjetas colapsables tipo acordeón con header que muestra cliente, email, cantidad productos, badge de estado del grupo, fecha, total
- Estado del grupo: "Vendido" si todos vendidos, "Cancelado" si todos cancelados, "Pendiente (N)" si hay pendientes
- Tabla detalle dentro del collapse con acciones Vender/Cancelar por item
- Estado expandido persiste entre recargas vía `sessionStorage`
- CSP nonce compliance: cero `onclick=""` nativos

#### Base de datos
- `pedidos.producto_id` ahora es `INT NULL` con FK `ON DELETE SET NULL` (permite eliminar productos con pedidos)
- `tiendas.plan VARCHAR(20) DEFAULT 'starter'`
- `tiendas.trial_ends_at DATE NULL`
- Migrations: `migrations/004_planes.sql`, `migrations/005_trial.sql`
- Nuevas columnas en `tiendas`: nombre_tienda, email, moneda, facebook_url, tiktok_url, twitter_url, descripcion, direccion, horario, mensaje_whatsapp, banner, notif_nuevo_pedido, notif_stock_bajo, meta_descripcion, meta_palabras_clave, codigo_tracking, css_personalizado, hero_title, hero_subtitle
- Nuevas columnas en `productos`: destacado (TINYINT(1)), etiqueta (VARCHAR(20))

#### Otras correcciones
- Botones admin navbar: cambiados de `btn-outline-light` a `btn-light` + `text-white`
- Bootstrap JS agregado en pedidos_body.php (faltaba)
- Enlace "Mejorar plan" ahora apunta a `index.html#planes` con nota de gestión manual
- `mostrar_error()` no tiene CSP nonce (página standalone sin scripts)
- Tabla de productos en admin: columnas Destacado (estrella), Etiqueta (badge color), moneda desde config
- Exportar/Importar CSV: botones en header inventario, archivos exportar-productos.php / importar-productos.php
- `backup.php`: fix `continue` fuera de loop cambiado a `exit`
- `card-config`: removed `background: transparent !important`
- Dark mode: agregado a todos los admin pages con toggle persistente

### Personalización por plan (junio 2026)
- Se agregó `personalizacion` a `plan_limite()` en `helpers.php`
- Starter: `personalizacion = false` → oculta en config y deshabilita en backend: banner, SEO/meta, hero, RRSS, tracking, CSS personalizado, notificaciones
- Pro/Business/Enterprise: `personalizacion = true` → muestra todos los campos de personalización
- `guardar-configuracion.php`: si no tiene permiso, se limpian los campos de personalización antes de guardar
- `catalogo.php` y `producto.php`: verifican `$tienda['plan']` directamente (no `$_SESSION`) para gatear features en el catálogo público
- Pricing cards en `index.html` actualizadas con 4 planes (Starter, Pro, Business, Enterprise) y features correctas
- CSS personalizado: se inyecta en `<head>` vía `<style>` tag, pero los selectores deben coincidir con las clases del template (`.product-card`, `.card-title`, `.btn-primary`, etc.)
- Redes sociales: además del dropdown en navbar, se muestran como iconos en el footer (`footer_partial.php`) gated por plan
- burger-co actualizado a plan `pro` en DB para pruebas

### Archivos relevantes
- `/micatalogo/`: PHP raíz (cada archivo = una ruta)
- `/micatalogo/templates/`: vistas partials
- `/micatalogo/Css/style.css`: estilos
- `/micatalogo/migrations/`: SQL de migraciones
- `/micatalogo/logs/error.log`: log de errores PHP
- `C:\xampp\micatalogo-config\db.php`: config DB
- `C:\xampp\micatalogo-config\email.php`: config SMTP Brevo

### Próximos pasos
1. Subir capturas de pantalla reales a la landing (imagenes/captura-admin.jpg, captura-movil.jpg)
2. Integrar Stripe/Mercado Pago como pasarela de pago
3. Desplegar en hosting real (PHP 8.2+, MySQL, Apache)
4. Configurar dominio + DNS + HTTPS + SMTP (Brevo) + Cloudflare CDN

### Notas técnicas
- El CSP nonce se genera en `init_session.php` y se pasa como `$csp_nonce` a los templates
- `conexion.php` incluye `helpers.php` con `require_once`; `init_session.php` no incluye helpers
- El toast usa `mostrarToast()` definido en `templates/toast_partial.php`
- Las rutas de configuración tienen fallback a `C:\xampp\micatalogo-config\` si la ruta relativa no existe
- El dark mode se persiste en `localStorage` con clave `dark_mode` ('1' = activo)
- Para agregar dark mode a una página nueva: (1) sidebar ya tiene el toggle, (2) copiar el script bloque justo antes de `</body>`
