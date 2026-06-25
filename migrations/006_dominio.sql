-- Dominio personalizado por tienda (Business+)
ALTER TABLE tiendas ADD COLUMN dominio VARCHAR(255) DEFAULT NULL AFTER slug;
CREATE INDEX idx_tiendas_dominio ON tiendas(dominio);
