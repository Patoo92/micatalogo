-- Plan de suscripcion para cada tienda
ALTER TABLE tiendas ADD COLUMN plan VARCHAR(20) NOT NULL DEFAULT 'starter' AFTER activo;
ALTER TABLE tiendas ADD INDEX idx_tiendas_plan (plan);
