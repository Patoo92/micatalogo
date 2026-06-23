-- Índices para rendimiento
-- Ejecutar: mysql -u root catalogo_whatsapp < migrations/001_indices.sql

ALTER TABLE productos ADD INDEX idx_productos_tienda (tienda_id);
ALTER TABLE productos ADD INDEX idx_productos_categoria (categoria_id);
ALTER TABLE productos ADD INDEX idx_productos_stock (stock, stock_minimo);

ALTER TABLE pedidos ADD INDEX idx_pedidos_tienda (tienda_id);
ALTER TABLE pedidos ADD INDEX idx_pedidos_estado (estado);
ALTER TABLE pedidos ADD INDEX idx_pedidos_producto (producto_id);

ALTER TABLE categorias ADD INDEX idx_categorias_tienda (tienda_id);

ALTER TABLE store_staff ADD INDEX idx_staff_tienda (tienda_id);

ALTER TABLE login_attempts ADD INDEX idx_login_ip (ip_address, tipo, created_at);
ALTER TABLE login_attempts ADD INDEX idx_login_created (created_at);

ALTER TABLE actividad ADD INDEX idx_actividad_tienda (tienda_id);
ALTER TABLE actividad ADD INDEX idx_actividad_created (created_at);

ALTER TABLE password_resets ADD INDEX idx_resets_email (email);

-- Prevención de race conditions en registro
ALTER TABLE tiendas ADD UNIQUE INDEX uq_tiendas_slug (slug);
ALTER TABLE tiendas ADD UNIQUE INDEX uq_tiendas_usuario (usuario);
