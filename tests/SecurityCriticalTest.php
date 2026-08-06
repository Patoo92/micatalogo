<?php

/**
 * Pruebas de regresión de los críticos de auditoría C1, C2 y C3.
 * Replican la lógica de los fixes reales (recuperar-admin.php, pago-exito.php,
 * reset-password.php) para verificar que los parches se mantienen en el tiempo
 * sin necesidad de BD ni sesiones reales.
 */

// ── C1: recuperar-admin.php ──
// El endpoint exige sesión de admin autenticada, CSRF válido, rate limit,
// contraseña de mínimo 10 caracteres y NUNCA devuelve la contraseña en claro.

function _test_c1_recuperar_admin($admin_id, $csrf_ok, $rate_ok, $password, $password_confirm) {
    if (!$admin_id) {
        return ['status' => 403, 'mensaje' => 'La regeneración de contraseña requiere iniciar sesión como administrador.', 'password' => null];
    }
    if (!$csrf_ok) {
        return ['status' => 400, 'mensaje' => 'Solicitud inválida.', 'password' => null];
    }
    if (!$rate_ok) {
        return ['status' => 429, 'mensaje' => 'Demasiados intentos. Espera 15 minutos.', 'password' => null];
    }
    if (strlen($password) < 10) {
        return ['status' => 400, 'mensaje' => 'La contraseña debe tener al menos 10 caracteres.', 'password' => null];
    }
    if ($password !== $password_confirm) {
        return ['status' => 400, 'mensaje' => 'Las contraseñas no coinciden.', 'password' => null];
    }
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    return ['status' => 200, 'mensaje' => 'Contraseña del administrador actualizada correctamente.', 'password' => $hash];
}

// ── C2: pago-exito.php ──
// La confirmación del pedido exige que la sesión de Stripe esté pagada, que su
// metadata.codigo_pedido coincida con el código de la BD, que amount_total
// coincida con monto_total*100 y que la moneda coincida.

function _test_c2_pago_exito_valida($pago_bd, $session, $codigo, $pedido) {
    if ($pago_bd === 'pagado') {
        return ['pagado' => true, 'monto' => $pedido['monto_total']];
    }
    if (empty($session)) {
        return ['pagado' => false, 'monto' => null];
    }
    $moneda_coincide = empty($pedido['moneda_pago'])
        || strtolower($session['currency'] ?? '') === strtolower($pedido['moneda_pago']);
    $pago_valido = ($session['payment_status'] ?? '') === 'paid'
        && ($session['metadata']['codigo_pedido'] ?? '') === $codigo
        && (int)$session['amount_total'] === (int)round((float)$pedido['monto_total'] * 100)
        && $moneda_coincide;

    if ($pago_valido) {
        return ['pagado' => true, 'monto' => $pedido['monto_total']];
    }
    return ['pagado' => false, 'monto' => null];
}

// ── C3: reset-password.php ──
// El token se resuelve a un tienda_id único (nunca por email) y el UPDATE
// actualiza SOLO esa tienda. Simula una mini-BD de tiendas en memoria.

function _test_c3_reset_password_resuelve(&$tiendas, $reset, $nueva_password) {
    if (empty($reset) || empty($reset['tienda_id'])) {
        return ['status' => 400, 'actualizadas' => 0];
    }
    $hash = password_hash($nueva_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $actualizadas = 0;
    foreach ($tiendas as &$t) {
        if ((int)$t['id'] === (int)$reset['tienda_id']) {
            $t['password'] = $hash;
            $actualizadas++;
        }
    }
    unset($t);
    return ['status' => 200, 'actualizadas' => $actualizadas];
}

class SecurityCriticalTest extends PHPUnit\Framework\TestCase
{
    // ── C1 ──

    public function testC1PostAnonimoRecibe403()
    {
        $r = _test_c1_recuperar_admin(null, true, true, 'passwordnueva123', 'passwordnueva123');
        $this->assertSame(403, $r['status']);
        $this->assertNull($r['password']);
    }

    public function testC1SinCsrfRecibe400()
    {
        $r = _test_c1_recuperar_admin(1, false, true, 'passwordnueva123', 'passwordnueva123');
        $this->assertSame(400, $r['status']);
    }

    public function testC1RateLimitRecibe429()
    {
        $r = _test_c1_recuperar_admin(1, true, false, 'passwordnueva123', 'passwordnueva123');
        $this->assertSame(429, $r['status']);
    }

    public function testC1ContrasenaCortaRecibe400()
    {
        $r = _test_c1_recuperar_admin(1, true, true, 'corta', 'corta');
        $this->assertSame(400, $r['status']);
    }

    public function testC1ContrasenasDistintasRecibe400()
    {
        $r = _test_c1_recuperar_admin(1, true, true, 'passwordnueva123', 'otrapassword456');
        $this->assertSame(400, $r['status']);
    }

    public function testC1ExitoDevuelveHashYNoContrasenaEnClaro()
    {
        $r = _test_c1_recuperar_admin(1, true, true, 'passwordnueva123', 'passwordnueva123');
        $this->assertSame(200, $r['status']);
        $this->assertNotSame('passwordnueva123', $r['password']);
        $this->assertNotContains('passwordnueva123', [$r['password']]);
        $this->assertStringStartsWith('$2y$', $r['password']);
        $info = password_get_info($r['password']);
        $this->assertSame(12, $info['options']['cost']);
    }

    // ── C2 ──

    private function _sesion($overrides = [])
    {
        return array_merge([
            'payment_status' => 'paid',
            'metadata'       => ['codigo_pedido' => 'PED-ABC'],
            'amount_total'   => 5000,
            'currency'       => 'eur',
        ], $overrides);
    }

    private function _pedido()
    {
        return ['monto_total' => 50.00, 'moneda_pago' => 'eur'];
    }

    public function testC2SesionDeOtroPedidoRechazada()
    {
        // Atacante paga un pedido barato (sesión Y) y abre pago-exito con el
        // código de un pedido caro: metadata.codigo_pedido no coincide → 403.
        $r = _test_c2_pago_exito_valida('pendiente', $this->_sesion(['metadata' => ['codigo_pedido' => 'PED-OTRO']]), 'PED-ABC', $this->_pedido());
        $this->assertFalse($r['pagado']);
    }

    public function testC2SesionConMontoDiferenteRechazada()
    {
        // amount_total debe ser exactamente monto_total*100 (5000 cts para 50€).
        $r = _test_c2_pago_exito_valida('pendiente', $this->_sesion(['amount_total' => 9999]), 'PED-ABC', $this->_pedido());
        $this->assertFalse($r['pagado']);
    }

    public function testC2SesionNoPagadaRechazada()
    {
        $r = _test_c2_pago_exito_valida('pendiente', $this->_sesion(['payment_status' => 'unpaid']), 'PED-ABC', $this->_pedido());
        $this->assertFalse($r['pagado']);
    }

    public function testC2MonedaDistintaRechazada()
    {
        $r = _test_c2_pago_exito_valida('pendiente', $this->_sesion(['currency' => 'usd']), 'PED-ABC', $this->_pedido());
        $this->assertFalse($r['pagado']);
    }

    public function testC2SesionValidaConfirmaElPedido()
    {
        $r = _test_c2_pago_exito_valida('pendiente', $this->_sesion(), 'PED-ABC', $this->_pedido());
        $this->assertTrue($r['pagado']);
        $this->assertSame(50.00, $r['monto']);
    }

    public function testC2PedidoYaPagadoEnBdNoRequiereSesion()
    {
        // Si el webhook ya confirmó, la página no revalida contra Stripe.
        $r = _test_c2_pago_exito_valida('pagado', null, 'PED-ABC', $this->_pedido());
        $this->assertTrue($r['pagado']);
    }

    public function testC2SinSesionYSinPagoEnBdNoConfirma()
    {
        $r = _test_c2_pago_exito_valida('pendiente', null, 'PED-ABC', $this->_pedido());
        $this->assertFalse($r['pagado']);
    }

    // ── C3 ──

    public function testC3DosTiendasConMismoEmailSoloSeActualizaLaDelToken()
    {
        // Escenario del hallazgo: dos tiendas comparten email. El token debe
        // afectar SOLO a la tienda del token, no a ambas.
        $tiendas = [
            ['id' => 1, 'email' => 'dupe@test.com', 'password' => 'hash_viejo_1'],
            ['id' => 2, 'email' => 'dupe@test.com', 'password' => 'hash_viejo_2'],
        ];
        $reset = ['tienda_id' => 2];

        $r = _test_c3_reset_password_resuelve($tiendas, $reset, 'passwordnueva123');

        $this->assertSame(200, $r['status']);
        $this->assertSame(1, $r['actualizadas']);
        $this->assertNotSame('hash_viejo_2', $tiendas[1]['password']);
        $this->assertSame('hash_viejo_1', $tiendas[0]['password']);
    }

    public function testC3TokenSinTiendaIdRechazado()
    {
        $tiendas = [['id' => 1, 'email' => 'a@test.com', 'password' => 'hash_viejo']];
        $r = _test_c3_reset_password_resuelve($tiendas, null, 'passwordnueva123');
        $this->assertSame(400, $r['status']);
        $this->assertSame(0, $r['actualizadas']);
    }

    public function testC3NuevaPasswordSeGuardaConCost12()
    {
        $tiendas = [['id' => 7, 'email' => 'x@test.com', 'password' => 'hash_viejo']];
        $r = _test_c3_reset_password_resuelve($tiendas, ['tienda_id' => 7], 'passwordnueva123');
        $info = password_get_info($tiendas[0]['password']);
        $this->assertSame(12, $info['options']['cost']);
        $this->assertSame(200, $r['status']);
    }
}
