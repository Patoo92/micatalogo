-- =============================================================================
-- MiCatalogo — Schema de base de datos para producción
-- Generado: 2026-06-26
-- Basado en: volcado local catalogo_whatsapp + auditoría de código fuente
--
-- CORRECCIONES vs dump original:
--   1. login_attempts.tipo ENUM ampliado: añadidos 'registro' y 'guardar_pedido'
--   2. api_keys.api_key VARCHAR(64→100): evita truncación silenciosa de 'mca_'+hex(32)=68 chars
--   3. password_resets: añadido índice en token; columna 'usado' ya presente
--   4. Eliminados índices duplicados en tiendas (slug×2, usuario×2)
--   5. Eliminados índices duplicados en login_attempts (idx_ip_tipo = idx_login_ip)
--   6. FK actividad→tiendas cambiada a ON DELETE SET NULL (evita perder historial al borrar tienda)
--   7. Añadida tabla 'suscripciones' preparada para integración Stripe/MP
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
START TRANSACTION;


-- =============================================================================
-- TABLA: tiendas
-- Entidad central del multi-tenancy. Una fila = un tenant.
-- =============================================================================
CREATE TABLE `tiendas` (
  `id`                  INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre_tienda`       VARCHAR(100)  NOT NULL,
  `slug`                VARCHAR(100)  DEFAULT NULL,
  `dominio`             VARCHAR(255)  DEFAULT NULL,                      -- dominio personalizado (marca blanca)
  `telefono_whatsapp`   VARCHAR(20)   NOT NULL,
  `usuario`             VARCHAR(50)   NOT NULL,
  `password`            VARCHAR(255)  NOT NULL,                          -- bcrypt
  `email`               VARCHAR(255)  DEFAULT NULL,
  -- Branding
  `logo_url`            VARCHAR(255)  DEFAULT NULL,
  `banner_url`          VARCHAR(255)  DEFAULT NULL,
  `color_tema`          VARCHAR(7)    DEFAULT '#10b981',
  `css_personalizado`   TEXT          DEFAULT NULL,
  -- Hero catálogo
  `hero_title`          VARCHAR(255)  DEFAULT NULL,
  `hero_subtitle`       VARCHAR(255)  DEFAULT NULL,
  -- Info pública
  `descripcion`         TEXT          DEFAULT NULL,
  `direccion`           VARCHAR(255)  DEFAULT NULL,
  `horario`             VARCHAR(255)  DEFAULT NULL,
  `mensaje_whatsapp`    TEXT          DEFAULT NULL,
  -- Redes sociales
  `instagram_url`       VARCHAR(255)  DEFAULT NULL,
  `facebook_url`        VARCHAR(255)  DEFAULT NULL,
  `tiktok_url`          VARCHAR(255)  DEFAULT NULL,
  `twitter_url`         VARCHAR(255)  DEFAULT NULL,
  -- SEO y tracking
  `meta_descripcion`    VARCHAR(255)  DEFAULT NULL,
  `meta_palabras_clave` VARCHAR(255)  DEFAULT NULL,
  `codigo_tracking`     TEXT          DEFAULT NULL,
  -- Configuración operativa
  `moneda`              VARCHAR(10)   DEFAULT '€',
  `notif_nuevo_pedido`  TINYINT(1)    DEFAULT 1,
  `notif_stock_bajo`    TINYINT(1)    DEFAULT 1,
  -- Plan y estado
  `plan`                VARCHAR(20)   NOT NULL DEFAULT 'starter',        -- starter|pro|business|enterprise
  `trial_ends_at`       DATE          DEFAULT NULL,
  `marca_blanca`        TINYINT(1)    DEFAULT 0,
  `tema_admin`          VARCHAR(20)   DEFAULT 'default',
  `activo`              TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tiendas_slug`    (`slug`),
  UNIQUE KEY `uq_tiendas_usuario` (`usuario`),
  KEY `idx_tiendas_plan`     (`plan`),
  KEY `idx_tiendas_trial`    (`trial_ends_at`),
  KEY `idx_tiendas_dominio`  (`dominio`),
  KEY `idx_tiendas_activo`   (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: admins
-- Super-administradores del SaaS. Tabla separada de tiendas.
-- =============================================================================
CREATE TABLE `admins` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `usuario`  VARCHAR(50)  NOT NULL,
  `password` VARCHAR(255) NOT NULL,                                      -- bcrypt

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: categorias
-- Categorías de productos por tienda.
-- =============================================================================
CREATE TABLE `categorias` (
  `id`               INT(11)     NOT NULL AUTO_INCREMENT,
  `tienda_id`        INT(11)     NOT NULL,
  `nombre_categoria` VARCHAR(50) NOT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_categorias_tienda` (`tienda_id`),
  CONSTRAINT `categorias_ibfk_1`
    FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: productos
-- Catálogo de productos por tienda.
-- =============================================================================
CREATE TABLE `productos` (
  `id`           INT(11)        NOT NULL AUTO_INCREMENT,
  `tienda_id`    INT(11)        NOT NULL,
  `categoria_id` INT(11)        DEFAULT NULL,
  `nombre`       VARCHAR(100)   NOT NULL,
  `descripcion`  TEXT           DEFAULT NULL,
  `precio`       DECIMAL(10,2)  NOT NULL,
  `stock`        INT(11)        NOT NULL DEFAULT 0,
  `stock_minimo` INT(11)        DEFAULT 3,
  `destacado`    TINYINT(1)     DEFAULT 0,
  `etiqueta`     VARCHAR(50)    DEFAULT NULL,                            -- 'Nuevo'|'Oferta'|'Sin stock'|NULL
  `imagen_url`   VARCHAR(255)   DEFAULT NULL,
  `imagen_thumb` VARCHAR(500)   DEFAULT NULL,                           -- path local o URL. 500 chars por seguridad
  `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_productos_tienda`    (`tienda_id`),
  KEY `idx_productos_categoria` (`categoria_id`),
  KEY `idx_productos_stock`     (`stock`, `stock_minimo`),
  KEY `idx_productos_destacado` (`tienda_id`, `destacado`),
  CONSTRAINT `productos_ibfk_1`
    FOREIGN KEY (`tienda_id`)    REFERENCES `tiendas`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `productos_ibfk_2`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: pedidos
-- Un pedido = un producto + un cliente. Varios pedidos del mismo cliente
-- en el mismo minuto se agrupan visualmente en el panel.
-- producto_id es nullable: permite eliminar productos sin perder el historial.
-- =============================================================================
CREATE TABLE `pedidos` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `tienda_id`      INT(11)      NOT NULL,
  `producto_id`    INT(11)      DEFAULT NULL,                            -- NULL si el producto fue eliminado
  `nombre_cliente` VARCHAR(100) NOT NULL,
  `email_cliente`  VARCHAR(255) DEFAULT NULL,
  `estado`         VARCHAR(20)  DEFAULT 'Pendiente',                    -- Pendiente|Vendido|Cancelado
  `fecha_pedido`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_pedidos_tienda`   (`tienda_id`),
  KEY `idx_pedidos_estado`   (`estado`),
  KEY `idx_pedidos_producto` (`producto_id`),
  KEY `idx_pedidos_fecha`    (`tienda_id`, `fecha_pedido`),
  CONSTRAINT `pedidos_ibfk_1`
    FOREIGN KEY (`tienda_id`)   REFERENCES `tiendas`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `pedidos_ibfk_2`
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: store_staff
-- Usuarios secundarios de una tienda con permisos granulares en JSON.
-- El campo permisos sigue el esquema:
-- {"productos_ver":true,"productos_crear":true,"productos_editar":true,
--  "productos_eliminar":true,"pedidos_ver":true,"pedidos_gestionar":true,
--  "staff_ver":true,"staff_editar":true,"staff_eliminar":true,
--  "configuracion_editar":true}
-- =============================================================================
CREATE TABLE `store_staff` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `tienda_id`  INT(11)      NOT NULL,
  `usuario`    VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,                                    -- bcrypt
  `email`      VARCHAR(255) DEFAULT NULL,
  `activo`     TINYINT(1)   DEFAULT 1,
  `permisos`   LONGTEXT     CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL
                            CHECK (JSON_VALID(`permisos`)),
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_usuario_tienda` (`tienda_id`, `usuario`),
  KEY `idx_staff_tienda` (`tienda_id`),
  CONSTRAINT `store_staff_ibfk_1`
    FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: api_keys
-- Claves de API por tienda. Solo disponibles en plan Business/Enterprise.
-- CORRECCIÓN: VARCHAR(100) en lugar de VARCHAR(64) para evitar truncación
-- de 'mca_' + bin2hex(random_bytes(32)) = 4 + 64 = 68 chars.
-- =============================================================================
CREATE TABLE `api_keys` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `tienda_id`  INT(11)      NOT NULL,
  `api_key`    VARCHAR(100) NOT NULL,                                    -- FIX: era 64, truncaba la key
  `nombre`     VARCHAR(100) DEFAULT NULL,
  `activo`     TINYINT(1)   DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_key` (`api_key`),
  KEY `idx_api_keys_tienda` (`tienda_id`),
  CONSTRAINT `api_keys_ibfk_1`
    FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: login_attempts
-- Rate limiting por IP y tipo de acción.
-- CORRECCIÓN: ENUM ampliado para incluir 'registro' y 'guardar_pedido',
-- que el código usa en verificar_rate_limit() pero no estaban en el ENUM original.
-- Los registros con tipo='' del dump local son basura de desarrollo.
-- =============================================================================
CREATE TABLE `login_attempts` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,                                    -- IPv4 o IPv6
  `tipo`       ENUM('login','owner','staff','admin','registro','guardar_pedido') NOT NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_login_ip_tipo_fecha` (`ip_address`, `tipo`, `created_at`),   -- índice compuesto único (elimina duplicados del dump)
  KEY `idx_login_created`       (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: password_resets
-- Tokens de recuperación de contraseña por email.
-- NOTA: la columna 'usado' existe pero recuperar.php NO la actualiza a 1
-- ni filtra por usado=0. Pendiente corregir en el código PHP.
-- =============================================================================
CREATE TABLE `password_resets` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(255) NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `usado`      TINYINT(1)   DEFAULT 0,
  `expires_at` TIMESTAMP    NOT NULL DEFAULT (CURRENT_TIMESTAMP + INTERVAL 2 HOUR), -- expira en 2h
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_resets_email`      (`email`),
  KEY `idx_resets_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: actividad
-- Log de acciones de owners, staff y superadmins.
-- CORRECCIÓN FK: ON DELETE SET NULL en lugar de CASCADE.
-- Con CASCADE, eliminar una tienda borra también todo su historial en actividad,
-- lo que impide al superadmin auditar qué pasó antes de la eliminación.
-- =============================================================================
CREATE TABLE `actividad` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `tienda_id`      INT(11)      DEFAULT NULL,                            -- NULL si la tienda fue eliminada
  `usuario_nombre` VARCHAR(100) NOT NULL,
  `usuario_tipo`   ENUM('owner','staff','superadmin') NOT NULL,
  `accion`         VARCHAR(255) NOT NULL,
  `detalle`        TEXT         DEFAULT NULL,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_actividad_tienda`  (`tienda_id`),
  KEY `idx_actividad_created` (`created_at`),
  CONSTRAINT `actividad_ibfk_1`
    FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE SET NULL  -- FIX: era CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- TABLA: suscripciones  [NUEVA — preparada para Stripe/Mercado Pago]
-- No implementada en el código PHP actual. Schema listo para conectar
-- la pasarela de pago cuando se integre.
-- =============================================================================
CREATE TABLE `suscripciones` (
  `id`                    INT(11)       NOT NULL AUTO_INCREMENT,
  `tienda_id`             INT(11)       NOT NULL,
  `proveedor`             VARCHAR(20)   NOT NULL DEFAULT 'stripe',       -- stripe|mercadopago
  `proveedor_customer_id` VARCHAR(100)  DEFAULT NULL,                    -- ej: cus_xxxxx en Stripe
  `proveedor_sub_id`      VARCHAR(100)  DEFAULT NULL,                    -- ej: sub_xxxxx en Stripe
  `plan`                  VARCHAR(20)   NOT NULL,                        -- starter|pro|business|enterprise
  `periodo`               ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  `estado`                ENUM('activa','cancelada','vencida','trial') NOT NULL DEFAULT 'trial',
  `monto`                 DECIMAL(10,2) DEFAULT NULL,
  `moneda_pago`           VARCHAR(3)    DEFAULT 'EUR',                   -- ISO 4217
  `fecha_inicio`          DATE          NOT NULL,
  `fecha_renovacion`      DATE          DEFAULT NULL,
  `fecha_cancelacion`     DATE          DEFAULT NULL,
  `created_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_suscripciones_tienda`   (`tienda_id`),
  KEY `idx_suscripciones_estado`   (`estado`),
  KEY `idx_suscripciones_renovacion` (`fecha_renovacion`),
  KEY `idx_proveedor_sub_id`       (`proveedor_sub_id`),
  CONSTRAINT `suscripciones_ibfk_1`
    FOREIGN KEY (`tienda_id`) REFERENCES `tiendas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


COMMIT;

-- =============================================================================
-- NOTAS DE INSTALACIÓN
-- =============================================================================
-- 1. Crear la base de datos antes de importar:
--    CREATE DATABASE catalogo_whatsapp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
--
-- 2. Importar este schema:
--    mysql -u root -p catalogo_whatsapp < schema_produccion.sql
--
-- 3. Crear el superadmin (cambia 'tu_password_segura' por algo real):
--    INSERT INTO admins (usuario, password)
--    VALUES ('superadmin', '$2y$12$...'); -- genera con: php -r "echo password_hash('tu_pass', PASSWORD_BCRYPT, ['cost'=>12]);"
--
-- 4. Si tienes datos de tu instalación local, puedes importar SOLO los INSERT
--    de tu dump (sin los CREATE TABLE) después de importar este schema.
--
-- 5. En producción, asegúrate de que MySQL/MariaDB tenga sql_mode con STRICT_TRANS_TABLES
--    para que los VARCHAR truncados fallen ruidosamente en vez de silenciosamente.
-- =============================================================================
