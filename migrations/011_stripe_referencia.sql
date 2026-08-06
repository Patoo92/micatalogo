-- A3: idempotencia de facturas Stripe. Permite insertar facturas de forma
-- idempotente (INSERT ... SELECT ... WHERE NOT EXISTS (SELECT 1 FROM facturas
-- WHERE stripe_referencia = ?)) tanto en stripe-success.php como en
-- webhook-stripe.php, para que una recarga o un evento reentregado no dupliquen.
ALTER TABLE facturas
  ADD COLUMN stripe_referencia VARCHAR(100) NULL AFTER metodo_pago,
  ADD UNIQUE KEY uq_factura_stripe_ref (stripe_referencia);
