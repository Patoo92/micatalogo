ALTER TABLE tiendas ADD COLUMN trial_ends_at DATE DEFAULT NULL AFTER plan;
ALTER TABLE tiendas ADD INDEX idx_tiendas_trial (trial_ends_at);
