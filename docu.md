# micatalogo — Documentación Técnica

SaaS multi-tenant para catálogos de productos con pedidos por WhatsApp.

---

## 1. Requisitos

- **XAMPP** (PHP 8.2+, MySQL, Apache)
- **PHP Extensions**: PDO, MySQL, GD, fileinfo, openssl, zip
- **Navegador**: Chrome/Edge/Firefox actual

---

## 2. Instalación local

### 2.1 Estructura de directorios
```
C:\xampp\htdocs\micatalogo\          → webroot (app)
C:\xampp\micatalogo-config\          → credenciales (fuera del webroot)
```

### 2.2 Base de datos
- Crear BD: `catalogo_whatsapp` (charset `utf8mb4_general_ci`)
- Ejecutar migraciones:
```
mysql -u root catalogo_whatsapp < migrations/001_indices.sql
mysql -u root catalogo_whatsapp < migrations/002_email_cliente.sql
```

### 2.3 Configuración
`C:\xampp\micatalogo-config\db.php`:
```php
<?php
return [
    'host'    => 'localhost',
    'db'      => 'catalogo_whatsapp',
    'user'    => 'root',
    'pass'    => '',
    'charset' => 'utf8mb4',
];
```

`C:\xampp\micatalogo-config\email.php`:
```php
<?php
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'tu_usuario_smtp');
define('SMTP_PASS', 'tu_password_smtp');
define('SMTP_FROM', 'tutienda@tudominio.com');
define('SMTP_FROM_NAME', 'micatalogo');
```

### 2.4 Dependencias
```
cd C:\xampp\htdocs\micatalogo
composer install
```

---

## 3. GUÍA DE DESPLIEGUE A PRODUCCIÓN

### 3.1 Contratar hosting

Requisitos del hosting:
| Requisito | Mínimo | Recomendado |
|-----------|--------|-------------|
| PHP | 8.2 | 8.3 |
| MySQL | 5.7 | 8.0 |
| Apache | 2.4 | 2.4 con mod_rewrite |
| Almacenamiento | 1 GB | 5 GB+ |
| RAM | 256 MB | 1 GB+ |

**Opciones recomendadas**:
- **Hostinger** (Business: ~5€/mes) — buen rendimiento/precio
- **SiteGround** (StartUp: ~3€/mes primer año) — soporte excelente
- **IONOS** (Basic: ~1€/mes 6 meses) — económico
- **DigitalOcean** (VPS: ~6$/mes) — si necesitas escalar

### 3.2 Dominio

1. Comprar dominio en Namecheap, GoDaddy, o directamente en el hosting
2. Configurar DNS:
   ```
   A  @  →  IP del servidor
   CNAME  www  →  tudominio.com
   ```

**Recomendación**: comprar dominio + hosting en el mismo proveedor (más fácil).

### 3.3 Preparar archivos para subir

**Archivos a subir** (todo el contenido de `C:\xampp\htdocs\micatalogo`):
```
/var/www/html/micatalogo/
├── .htaccess
├── index.php
├── init_session.php
├── conexion.php
├── helpers.php
├── email_helper.php
├── *.php             (todos los .php)
├── templates/
├── migrations/
├── vendor/           (subir después de composer install)
├── imagenes/         (crear con permisos 755)
└── uploads/          (crear con permisos 755)
```

**Config fuera del webroot**:
```
/var/www/micatalogo-config/   (UN nivel arriba de html)
├── db.php
└── email.php
```

### 3.4 Subir archivos vía FTP

Usando FileZilla o similar:
```
Host: ftp.tudominio.com
Usuario: (el del hosting)
Contraseña: (la del hosting)
Puerto: 21
```

1. Subir todo `micatalogo/` a `public_html/` o `htdocs/`
2. Crear `micatalogo-config/` FUERA del webroot
3. Configurar `db.php` y `email.php`

### 3.5 Base de datos

1. Exportar BD local:
```bash
mysqldump -u root catalogo_whatsapp > backup.sql
```

2. Crear BD en el hosting (desde cPanel/phpMyAdmin): `catalogo_whatsapp`

3. Importar:
```bash
mysql -u usuario_hosting -p catalogo_whatsapp < backup.sql
```

4. Ejecutar migraciones:
```bash
mysql -u usuario_hosting -p catalogo_whatsapp < migrations/001_indices.sql
mysql -u usuario_hosting -p catalogo_whatsapp < migrations/002_email_cliente.sql
```

### 3.6 Instalar dependencias

Conectarse por SSH al hosting:
```bash
cd /var/www/html/micatalogo
php composer.phar install
```

Si el hosting no tiene SSH, subir `vendor/` desde local (se generó con `composer install` local).

### 3.7 Configurar SMTP (Brevo)

1. Crear cuenta en **brevo.com** (gratis, 300 emails/día)
2. Ir a **SMTP & API** → generar nueva clave SMTP
3. En **Senders** → verificar el remitente (tu dominio)
4. Si el hosting tiene IP fija, agregarla en **Authorized IPs**

Configurar `/var/www/micatalogo-config/email.php`:
```php
<?php
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'la_clave_que_te_dio_brevo');
define('SMTP_PASS', 'xsmtpsib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SMTP_FROM', 'tutienda@tudominio.com');
define('SMTP_FROM_NAME', 'micatalogo');
```

### 3.8 HTTPS (Let's Encrypt)

Si tienes cPanel: **SSL/TLS → Let's Encrypt → Instalar**.

Si tienes SSH:
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tudominio.com -d www.tudominio.com
```

Esto genera certificados y configura el redirect 80→443 automáticamente.

### 3.9 CDN para imágenes (Cloudflare, gratuito)

1. Crear cuenta en **cloudflare.com**
2. Agregar tu dominio
3. Cloudflare escanea los DNS existentes
4. Cambiar los nameservers del dominio a los de Cloudflare (te los dan al agregar el sitio)
5. En **Speed → Optimization → Polish**:
   - Activar **Polish** (comprime imágenes)
   - Activar **Brotli**
6. En **Caching → Configuration**:
   - Browser Cache TTL: 1 mes
7. Opcional: crear subdominio `cdn.tudominio.com` apuntando a las imágenes

**Sin Cloudflare**: solo configurar `CDN_URL` si tienes un subdominio dedicado para assets.

### 3.10 Variables de entorno (Apache)

En el `.htaccess` del hosting o `httpd.conf`:
```apache
SetEnv CDN_URL https://cdn.tudominio.com
```

O en el panel del hosting (si lo soporta).

### 3.11 Checklist final

Antes de abrir al público:

- [ ] **Web accesible** → `https://tudominio.com/micatalogo/`
- [ ] **Catálogo** → `https://tudominio.com/micatalogo/index.php?tienda=burger-co`
- [ ] **Login dueño** → `https://tudominio.com/micatalogo/login.php`
- [ ] **Login super-admin** → `https://tudominio.com/micatalogo/login-admin.php`
- [ ] **HTTPS** → redirect automático de HTTP a HTTPS
- [ ] **SMTP** → probar recuperar contraseña (te llega el email)
- [ ] **WhatsApp** → hacer un pedido de prueba y verificar el mensaje
- [ ] **Logs** → comprobar que `logs/error.log` tenga permisos de escritura
- [ ] **PHP info** → temporalmente crear `phpinfo.php` para verificar extensiones, luego borrarlo
- [ ] **display_errors=Off** → verificar que no se vean errores PHP
- [ ] **.htaccess** → comprobar que bloquea acceso a archivos sensibles
- [ ] **Migraciones ejecutadas** → verificar índices UNIQUE en `tiendas.slug` y `tiendas.usuario`

### 3.12 Backups automáticos

En el hosting (cPanel → Cron Jobs):
```bash
0 3 * * * /usr/bin/mysqldump -u USUARIO -pCLAVE catalogo_whatsapp > /backups/backup_$(date +\%Y\%m\%d).sql
```

O desde `backup.php`:
```
https://tudominio.com/micatalogo/backup.php?token=CLAVE_SECRETA
```
(proteger esta URL en `.htaccess` o con autenticación)

### 3.13 Solución de problemas comunes

| Problema | Causa | Solución |
|----------|-------|----------|
| Página en blanco | PHP error | Revisar `logs/error.log` |
| 500 Internal Server | Error PHP o .htaccess | Revisar logs de Apache (/var/log/apache2/) |
| No envía emails | SMTP mal configurado | Verificar credenciales en `email.php` |
| Imágenes no se ven | Ruta incorrecta | Verificar que `uploads/` y `imagenes/` tengan permisos 755 |
| Session no funciona | PHP session.save_path incorrecto | Configurar en php.ini del hosting |
| .htaccess no funciona | Apache AllowOverride None | Pedir al hosting que active mod_rewrite |

### 3.14 URLs finales

```
https://tudominio.com/micatalogo/                      → Página por defecto
https://tudominio.com/micatalogo/index.php?tienda=slug  → Catálogo público
https://tudominio.com/micatalogo/login.php               → Login dueño/staff
https://tudominio.com/micatalogo/login-admin.php         → Login super-admin
https://tudominio.com/micatalogo/admin.php               → Panel admin
https://tudominio.com/micatalogo/super-admin.php         → Panel super-admin
https://tudominio.com/micatalogo/registro.php            → Registro nueva tienda
https://tudominio.com/micatalogo/recuperar.php           → Recuperar contraseña
```

---

## 4. Arquitectura

### 4.1 Patrón
- Separación lógica/template: cada `.php` carga datos y hace `require` a `templates/`
- Seguridad: credenciales fuera del webroot, `.htaccess` restrictivo
- Estado: sesiones PHP, CSRF tokens, rate limiting por IP

### 4.2 Flujo de archivos
```
conexion.php  ← se incluye primero en todos los archivos
  └─ helpers.php (funciones reutilizables)
  └─ email_helper.php (PHPMailer wrapper)
init_session.php ← configura sesión ANTES de conexion.php
```

---

## 5. Base de Datos

### 5.1 Tablas principales
| Tabla | Descripción |
|-------|-------------|
| admins | Super-administradores del SaaS |
| tiendas | Cada tienda (tenant) |
| store_staff | Empleados con permisos JSON |
| productos | Productos con imagen + thumbnail |
| pedidos | Pedidos WhatsApp |
| categorias | Categorías por tienda |
| login_attempts | Rate limiting (5 intentos / 15 min) |
| password_resets | Tokens de recuperación |
| actividad | Historial de acciones |

### 5.2 store_staff.permisos (JSON)
```json
{
  "productos_crear": true,
  "productos_editar": true,
  "productos_eliminar": true,
  "pedidos_ver": true,
  "pedidos_gestionar": true,
  "configuracion_editar": true,
  "staff_ver": true,
  "staff_crear": true,
  "staff_editar": true,
  "staff_eliminar": true
}
```

---

## 6. Seguridad

- Credenciales fuera del webroot (`micatalogo-config/`)
- `.htaccess`: deniega `.sql`, `.md`, `.log`, `conexion.php`, `helpers.php`, `templates/`, `logs/`, `vendor/`
- CSRF tokens en todos los POST (rotación post-uso)
- Rate limiting (5 intentos / 15 min) en login
- Rate limiting en creación de pedidos (10 / 5 min)
- Passwords con bcrypt (`password_hash`)
- CSP con nonce dinámico por request
- Session: `use_strict_mode`, `httponly`, `SameSite=Strict`
- Permisos granulares por staff
- Headers de seguridad: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
- Imágenes en `uploads/` e `imagenes/` con `.htaccess` que bloquea ejecución PHP

---

## 7. Funcionalidades

### 7.1 Catálogo público
- URL: `index.php?tienda={slug}`
- Productos con imágenes, precios, stock, categorías
- Carrito multi-producto con localStorage
- Envío a WhatsApp con todos los productos seleccionados
- Thumbnails 300×300 generados con GD
- Email opcional del cliente para notificación

### 7.2 Panel admin
- CRUD productos con imágenes (subida + thumbnail automático)
- Gestión de pedidos (marcar como vendido)
- Staff con permisos granulares
- Configuración de tienda (nombre, logo, colores, WhatsApp)
- Stock crítico (alerta cuando stock ≤ stock_minimo)
- Cambio de contraseña
- Historial de actividad

### 7.3 Super Admin
- Listado de tiendas (crear, eliminar en cascada)
- Recuperación de contraseña de admin
- Historial global de actividad
- Backup de base de datos

---

## 8. Email (Brevo)

1. Crear cuenta en [brevo.com](https://www.brevo.com) (gratis, 300 emails/día)
2. **SMTP & API** → generar SMTP key
3. Agregar IP del servidor en **Authorized IPs** (si aplica)
4. Verificar remitente en **Senders**
5. Configurar en `micatalogo-config/email.php`

---

## 9. Stack técnico

| Componente | Versión / Librería |
|------------|-------------------|
| Backend | PHP 8.2+ |
| Frontend | Bootstrap 5.3, Iconify mdi |
| Base de datos | MySQL 8.0+ |
| Email | PHPMailer 6.9+ + Brevo SMTP |
| Thumbnails | GD (`imagecopyresampled`) |
| Servidor | Apache 2.4 con mod_rewrite |
| CDN | Cloudflare (gratuito) |

---

## 10. Tests

```bash
php phpunit.phar
```
PHPUnit 10.5 — 4 tests (helpers: ruta_imagen, imagen_defecto, rate_limit, thumbnail)
