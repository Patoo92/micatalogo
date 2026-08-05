-- =============================================================================
-- Migración 009 — Pagos online en pedidos (Stripe Checkout, mode=payment)
-- =============================================================================
-- Añade al modelo de pedidos el soporte para cobrar el carrito por pasarela,
-- manteniendo el flujo actual de WhatsApp.
--
--   codigo_pedido   → agrupa las líneas de un mismo carrito/compra (también
--                     viaja en metadata de Stripe y en la URL de retorno).
--   metodo_pago     → whatsapp | stripe
--   pago_estado     → pendiente | pagado | fallido | cancelado
--                     - Pedidos por WhatsApp: quedan 'pendiente' (se cobra al
--                       entregar) o se marcan 'pagado' manualmente.
--                     - Pedidos por Stripe: 'pendiente' al crear la sesión,
--                       'pagado' cuando el webhook confirma, 'fallido' si no.
--   pago_referencia → id de la sesión de Checkout o del Payment Intent.
--   monto_total     → total del carrito (para el grupo), en moneda_pago.
--
-- NOTA sobre stock: en el flujo online el stock se descuenta SOLO cuando el
-- webhook confirma el pago (ver webhook-stripe.php, evento
-- checkout.session.completed en mode=payment). El descuento es condicional
-- (WHERE stock >= cantidad) para evitar sobreventa.
-- =============================================================================

ALTER TABLE `pedidos`
  ADD COLUMN `codigo_pedido`   VARCHAR(20)   DEFAULT NULL AFTER `estado`,
  ADD COLUMN `metodo_pago`     VARCHAR(20)   NOT NULL DEFAULT 'whatsapp' AFTER `codigo_pedido`,
  ADD COLUMN `pago_estado`     VARCHAR(20)   NOT NULL DEFAULT 'pendiente' AFTER `metodo_pago`,
  ADD COLUMN `pago_referencia` VARCHAR(100)  DEFAULT NULL AFTER `pago_estado`,
  ADD COLUMN `monto_total`     DECIMAL(10,2) DEFAULT NULL AFTER `pago_referencia`,
  ADD COLUMN `moneda_pago`     VARCHAR(3)    DEFAULT NULL AFTER `monto_total`;

ALTER TABLE `pedidos` ADD INDEX `idx_pedidos_codigo` (`codigo_pedido`);
ALTER TABLE `pedidos` ADD INDEX `idx_pedidos_pago` (`pago_estado`);

-- Stripe necesita el código ISO de moneda (EUR, USD...), no el símbolo (€, $).
-- Se usa solo para la pasarela; 'moneda' (símbolo) sigue siendo la de visualización.
ALTER TABLE `tiendas`
  ADD COLUMN `moneda_iso` VARCHAR(3) NOT NULL DEFAULT 'EUR' AFTER `moneda`;
