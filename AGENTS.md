# micatalogo — SaaS multi-tenant de catálogos con pedidos WhatsApp

## Stack
- PHP 8.2+ vanilla (sin framework), MySQL, Apache/XAMPP
- Bootstrap 5.3, iconify, PHPMailer (SMTP Brevo)
- CSP nonce obliga a `addEventListener` en vez de `onclick=""`

## Estado actual del proyecto

### Planes y monetización (sin pasarela de pago)
- **Starter** (gratis, siempre): 1 staff, 1 tienda, 0 API keys, sin marca blanca
- **Pro**: 3 staff, 1 tienda, 1 API key, sin marca blanca
- **Business**: 10 staff, 3 tiendas, 5 API keys, marca blanca sí
- **Enterprise**: staff/tiendas/API keys ilimitados, marca blanca sí
- Trial: 3 días solo para Pro/Business; al vencer → downgrade automático a Starter (login.php)
- Upgrades: gestión manual desde super-admin (sin pasarela de pago aún)

### Features implementadas
- Registro con selección de plan, trial, validación completa (NIST password: min 10 chars, sin reglas de mayúscula/número)
- Login con downgrade automático de trial vencido
- Staff con permisos granulares
- API keys + API REST (api.php, api-productos.php)
- Marca blanca (oculta branding en emails y catálogo público)
- Productos: CRUD, stock, stock mínimo, alerta email, thumbnails, WebP con fallback
- Catálogo público (catalogo.php) con carrito, lightbox, compartir WhatsApp, meta tags OG/Twitter
- Página individual de producto (producto.php)
- Pedidos: agrupados por cliente+fecha, expandibles, completar, cancelar con restauración de stock
- Super-admin: gestión de tiendas, actividad, cambiar plan, extender trial
- Seguridad: CSP nonce, CSRF tokens, rate limiting login, FOR UPDATE en pedidos
- Email: SMTP Brevo con PHPMailer, alertas stock mínimo, confirmación al cliente

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

#### Otras correcciones
- Botones admin navbar: cambiados de `btn-outline-light` a `btn-light` + `text-white`
- Bootstrap JS agregado en pedidos_body.php (faltaba)
- Enlace "Mejorar plan" ahora apunta a `index.html#planes` con nota de gestión manual
- `mostrar_error()` no tiene CSP nonce (página standalone sin scripts)

### Archivos relevantes
- `/micatalogo/`: PHP raíz (cada archivo = una ruta)
- `/micatalogo/templates/`: vistas partials
- `/micatalogo/Css/style.css`: estilos
- `/micatalogo/migrations/`: SQL de migraciones
- `/micatalogo/logs/error.log`: log de errores PHP
- `C:\xampp\micatalogo-config\db.php`: config DB
- `C:\xampp\micatalogo-config\email.php`: config SMTP Brevo

### Próximos pasos
1. Integrar Stripe/Mercado Pago como pasarela de pago
2. Encontrar/subir capturas de pantalla para la landing
3. Desplegar en hosting real (PHP 8.2+, MySQL, Apache)
4. Configurar dominio + DNS + HTTPS + SMTP (Brevo) + Cloudflare CDN

### Notas técnicas
- El CSP nonce se genera en `init_session.php` y se pasa como `$csp_nonce` a los templates
- `conexion.php` incluye `helpers.php` con `require_once`; `init_session.php` no incluye helpers
- El toast usa `mostrarToast()` definido en `templates/toast_partial.php`
- Las rutas de configuración tienen fallback a `C:\xampp\micatalogo-config\` si la ruta relativa no existe
