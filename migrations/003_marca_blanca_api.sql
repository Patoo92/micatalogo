-- Marca blanca para tiendas (Business plan)
ALTER TABLE tiendas ADD COLUMN marca_blanca TINYINT(1) DEFAULT 0 AFTER activo;

-- API keys para acceso REST
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tienda_id INT NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    nombre VARCHAR(100) DEFAULT NULL,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
