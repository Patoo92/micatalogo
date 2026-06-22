# micatalogo — Documentación Técnica

SaaS multi-tenant para catálogos de productos con pedidos por WhatsApp.

---

## 1. Requisitos

- **XAMPP** (PHP 8.2+, MySQL, Apache)
- **PHP Extensions**: PDO, MySQL, GD, fileinfo, openssl
- **Navegador**: Chrome/Edge/Firefox actual

---

## 2. Instalación

### 2.1 Estructura de directorios
C:\xampp\htdocs\micatalogo\          → webroot (app)
C:\xampp\micatalogo-config\          → credenciales (fuera del webroot)

### 2.2 Base de datos
- Crear BD: `catalogo_whatsapp` (charset `utf8mb4_general_ci`)
- Ejecutar `migracion.php` (ya eliminado, las tablas existen)

### 2.3 Configuración
Editar `C:\xampp\micatalogo-config\db.php`:
```php
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'catalogo_whatsapp');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
Editar C:\xampp\micatalogo-config\email.php:
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'af9880001@smtp-brevo.com');
define('SMTP_PASS', 'xsmtpsib-xxxxxxxxxxxxxx');
define('SMTP_FROM', 'tu-email-verificado@ejemplo.com');
define('SMTP_FROM_NAME', 'micatalogo');
3. Arquitectura
3.1 Patrón
- Separación lógica/template: cada .php carga datos y hace require a templates/
- Seguridad: credenciales fuera del webroot, .htaccess restrictivo
- Estado: sesiones PHP, CSRF tokens, rate limiting por IP
3.2 Flujo de archivos
conexion.php  ← se incluye primero en todos los archivos
  └─ helpers.php (funciones reutilizables: rate limit, CSRF, imágenes, error)
  └─ email_helper.php (PHPMailer wrapper)
4. Base de Datos
4.1 Tablas principales
Tabla	Descripción
admins	Super-administradores del SaaS
tiendas	Cada tienda (tenant)
store_staff	Empleados con permisos JSON
productos	Productos con imagen + thumbnail
pedidos	Pedidos WhatsApp
categorias	Categorías por tienda
login_attempts	Rate limiting (5 intentos / 15 min)
password_resets	Tokens de recuperación
actividad	Historial de acciones
4.2 store_staff.permisos (JSON)
["productos_crear", "productos_editar", "productos_eliminar",
 "pedidos_ver", "pedidos_completar",
 "configuracion_editar",
 "staff_ver", "staff_crear", "staff_editar", "staff_eliminar"]
5. Funcionalidades
5.1 Catálogo público
- URL: index.php?tienda={slug}
- Productos con imágenes, precios, stock, categorías
- Carrito multi-producto: selección múltiple, cantidades, nombre único
- Envío a WhatsApp con todos los productos seleccionados
- Thumbnails 300×300 generados con GD
5.2 Login
- login.php → dueño y staff de tienda
- login-admin.php → super-admin
- Rate limiting: 5 intentos en 15 minutos por IP
- CSRF tokens en todos los formularios
- Recuperación de contraseña vía email (Brevo SMTP)
5.3 Panel admin
- CRUD productos, staff, pedidos, configuración
- Permisos granulares por staff
- Stock crítico (banner rojo cuando stock ≤ stock_minimo)
- Historial de actividad
- Respaldo de BD (mysqldump / INSERTs)
- Cambio de contraseña con registro en SQL
5.4 Super Admin
- super-admin.php: listado de tiendas, eliminar tienda (cascada)
- Historial global de actividad
- Respaldo completo
6. URLs de prueba
URL
http://localhost/micatalogo/
http://localhost/micatalogo/index.php?tienda=burger-co
http://localhost/micatalogo/login.php
http://localhost/micatalogo/login-admin.php
http://localhost/micatalogo/admin.php
http://localhost/micatalogo/super-admin.php
http://localhost/micatalogo/registro.php
http://localhost/micatalogo/recuperar.php
http://localhost/micatalogo/recuperar-admin.php
http://localhost/micatalogo/api-productos.php?ids=1,2,3
7. Seguridad
- Credenciales fuera del webroot (C:\xampp\micatalogo-config\)
- .htaccess: deniega .sql/.md/.log, conexion.php, helpers.php, templates/, logs/
- CSRF tokens en todos los POST
- Rate limiting (5 intentos / 15 min) en login
- Passwords con bcrypt (password_hash)
- Permisos por staff (JSON en store_staff.permisos)
- ServerSignature Off + ServerTokens Prod en Apache
- Error logging a logs/error.log
- mostrar_error() reemplaza die() en toda la app
8. Email (Brevo)
1. Crear cuenta en brevo.com (gratis, 300 emails/día)
2. SMTP & API → generar SMTP key
3. Agregar IP del servidor en Authorized IPs
4. Verificar remitente en Senders
5. Configurar en C:\xampp\micatalogo-config\email.php
9. Variables de Entorno
Las credenciales soportan variables de entorno para producción:
Variable
DB_HOST
DB_NAME
DB_USER
DB_PASS
SMTP_USER
SMTP_PASS
SMTP_FROM
SMTP_FROM_NAME
10. Tests
C:\xampp\php\php.exe phpunit.phar
PHPUnit 10.5.63 — 4 tests (helpers: ruta_imagen, imagen_defecto, rate_limit, thumbnail)
11. Producción (Checklist)
- HTTPS (certbot / Let's Encrypt)
- Variables de entorno configuradas
- SMTP con IP de servidor autorizada
- Remitente de email verificado en Brevo
- Modo producción PHP (display_errors = Off)
- Backups automáticos configurados
- CDN para imágenes (opcional)
12. Stack técnico
Componente	Versión / Librería
Backend	PHP 8.2
Frontend	Bootstrap 5.3, Iconify mdi (web component)
Base de datos	MySQL
Email	PHPMailer 6.9.3 + Brevo SMTP
Testing	PHPUnit 10.5.63
Thumbnails	GD (imagecopyresampled)
Servidor	Apache 2.4
```	 