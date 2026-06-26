ALTER TABLE tiendas ADD COLUMN precio_mensual DECIMAL(10,2) DEFAULT NULL AFTER moneda;
ALTER TABLE tiendas ADD COLUMN precio_anual DECIMAL(10,2) DEFAULT NULL AFTER precio_mensual;
ALTER TABLE tiendas ADD COLUMN stripe_customer_id VARCHAR(100) DEFAULT NULL AFTER precio_anual;
ALTER TABLE tiendas ADD COLUMN stripe_subscription_id VARCHAR(100) DEFAULT NULL AFTER stripe_customer_id;

CREATE TABLE IF NOT EXISTS facturas (
  id              INT(11)       NOT NULL AUTO_INCREMENT,
  tienda_id       INT(11)       NOT NULL,
  numero_factura  VARCHAR(50)   NOT NULL,
  plan            VARCHAR(20)   NOT NULL,
  periodo         ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  monto           DECIMAL(10,2) NOT NULL,
  moneda          VARCHAR(3)    DEFAULT 'EUR',
  estado          ENUM('pendiente','pagada','cancelada','vencida') NOT NULL DEFAULT 'pendiente',
  metodo_pago     VARCHAR(50)   DEFAULT NULL,
  fecha_emision   DATE          NOT NULL,
  fecha_pago      DATE          DEFAULT NULL,
  fecha_vencimiento DATE        DEFAULT NULL,
  notas           TEXT          DEFAULT NULL,
  created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_factura_numero (numero_factura),
  KEY idx_facturas_tienda (tienda_id),
  KEY idx_facturas_estado (estado),
  KEY idx_facturas_fecha_vencimiento (fecha_vencimiento),
  CONSTRAINT facturas_ibfk_1 FOREIGN KEY (tienda_id) REFERENCES tiendas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
