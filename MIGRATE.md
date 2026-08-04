# Migrar a otro ordenador

## Lo que necesitás llevar

### 1. Código del proyecto (git)
```bash
git clone https://github.com/Patoo92/micatalogo.git
cd micatalogo
composer install
```

### 2. Archivos subidos (imágenes)
Del **origen** (`C:\Users\Usuario\Desktop\PROPIAS\mi_catalogo\`):
```
imagenes/   → 10 archivos
uploads/    →  4 archivos
```
Copiarlos al mismo lugar en el destino.

### 3. Configuración (variables de entorno)
En el **origen**, copiar el `.env` local (`C:\Users\Usuario\Desktop\PROPIAS\mi_catalogo\.env`) que contiene:
```
DB_*        → credenciales BD
SMTP_*      → SMTP Brevo
STRIPE_*    → API keys Stripe + price IDs
MYSQLDUMP_PATH → ruta al mysqldump local
```
En el **destino**: copiar el `.env` (o reconstruirlo desde `.env.example`) a la raíz del proyecto. Si se usa un servidor real, definir estas variables en el entorno del hosting en vez del `.env`. **No** se necesitan archivos de configuración fuera del webroot.

### 4. Base de datos
En el **origen**, exportar:
```bash
# Si XAMPP MySQL está corriendo:
C:\xampp\mysql\bin\mysqldump -u root catalogo_whatsapp > catalogo_whatsapp.sql
```
En el **destino**, importar:
```bash
C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE catalogo_whatsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql -u root catalogo_whatsapp < catalogo_whatsapp.sql
```

### 5. PHP extension GD
En `C:\xampp\php\php.ini`, descomentar:
```ini
extension=gd
```

### 6. vendor/ (dependencias)
Ya está en `.gitignore`. En el destino, instalar [Composer](https://getcomposer.org/download/) y luego:
```bash
composer install
```

## Resumen: checklist para el destino

| Item | Origen | Destino |
|------|--------|---------|
| Código | `mi_catalogo/` (git) | `git clone` |
| Imágenes | `imagenes/`, `uploads/` | Copiar carpetas |
| Config | `.env` (gitignored) | Copiar `.env` o definir variables de entorno |
| BD | Exportar con mysqldump | Importar + crear DB |
| GD | `extension=gd` en php.ini | Descomentar igual |
| Dependencias | `vendor/` | `composer install` |

## Post-instalación
- El proyecto se sirve desde `http://localhost/micatalogo/`
- Registro: `http://localhost/micatalogo/registro.php`
- Login admin: `http://localhost/micatalogo/admin.php`
- Stripe opera en **modo test** (tarjeta: `4242 4242 4242 4242`)
- Webhook Stripe: configurar en Stripe Dashboard si se necesita
