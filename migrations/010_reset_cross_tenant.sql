-- =============================================================================
-- Migración 010 — C3: reset de contraseña ligado a tienda (anti cross-tenant)
-- =============================================================================
-- Problema: reset-password.php resolvía UPDATE tiendas SET password WHERE email
-- = ?, de modo que dos tiendas con el mismo email se pisaban la contraseña.
-- Fix: password_resets guarda el tienda_id al emitir el token y el reset se
-- resuelve por id.
-- =============================================================================

ALTER TABLE `password_resets`
  ADD COLUMN `tienda_id` INT(11) NULL AFTER `id`,
  ADD KEY `idx_resets_tienda` (`tienda_id`);

-- Backfill: ligar tokens existentes a una tienda con ese email (si hay varias,
-- se elige la de id más bajo; los tokens viejos expiran en 1h de todos modos).
UPDATE password_resets pr
JOIN (
    SELECT email, MIN(id) AS tienda_id FROM tiendas GROUP BY email
) t ON pr.email = t.email
SET pr.tienda_id = t.tienda_id
WHERE pr.tienda_id IS NULL;
