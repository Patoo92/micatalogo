-- =============================================================================
-- Migración 012 — C4: MFA/TOTP para el panel
-- =============================================================================
-- Añade la columna totp_secret para almacenar el secreto TOTP (Base32) en:
--   - admins  → superadmin (master)
--   - tiendas → owner de cada tienda (opcional)
-- El login, cuando totp_secret no es NULL, exige un segundo factor (código de
-- 6 dígitos RFC 6238) tras verificar la contraseña.
-- NULL = MFA desactivado. Dejar el secreto a NULL no requiere migración de datos.
-- =============================================================================

ALTER TABLE `admins`
  ADD COLUMN `totp_secret` VARCHAR(100) NULL DEFAULT NULL AFTER `password`;

ALTER TABLE `tiendas`
  ADD COLUMN `totp_secret` VARCHAR(100) NULL DEFAULT NULL AFTER `password`;
