-- =============================================================================
-- Migración 008 — Eliminar tabla 'suscripciones'
-- =============================================================================
-- La tabla 'suscripciones' se añadió en el schema inicial como preparación para
-- Stripe/Mercado Pago, pero NUNCA fue usada por el código PHP. La facturación
-- real vive en tiendas.* (precio_mensual, precio_anual, stripe_customer_id,
-- stripe_subscription_id) y en la tabla 'facturas' (migración 007).
--
-- Para instalaciones existentes que hayan creado esta tabla, ejecutar:
--     mysql -u root -p catalogo_whatsapp < migrations/008_eliminar_suscripciones.sql
--
-- Las instalaciones nuevas ya no la crean (eliminada de schema.sql).
-- =============================================================================

DROP TABLE IF EXISTS `suscripciones`;
