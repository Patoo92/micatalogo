<?php

/**
 * Pruebas de flujos de negocio.
 * Las funciones probadas aquí replican la lógica de conexion.php
 * para poder testearlas sin conexión a BD real.
 */

// ── Funciones auxiliares (misma lógica que en conexion.php) ──

function _test_obtener_usuario_actual() {
    if (isset($_SESSION['admin_id'])) {
        return ['nombre' => $_SESSION['admin_usuario'], 'tipo' => 'superadmin', 'tienda_id' => null];
    } elseif (isset($_SESSION['staff_id'])) {
        return ['nombre' => $_SESSION['staff_usuario'], 'tipo' => 'staff', 'tienda_id' => $_SESSION['tienda_id']];
    } elseif (isset($_SESSION['tienda_id'])) {
        return ['nombre' => $_SESSION['tienda_nombre'], 'tipo' => 'owner', 'tienda_id' => $_SESSION['tienda_id']];
    }
    return null;
}

function _test_verificar_permiso($permiso) {
    if (!isset($_SESSION['tienda_id'])) return false;
    if (!isset($_SESSION['staff_id'])) return true; // owner
    $permisos = $_SESSION['staff_permisos'] ?? [];
    return !empty($permisos[$permiso]);
}

class BusinessFlowsTest extends PHPUnit\Framework\TestCase
{
    private $originalSession;

    protected function setUp(): void
    {
        $this->originalSession = $_SESSION ?? [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
    }

    // ── obtener_usuario_actual ──

    public function testObtenerUsuarioActualSinSesion()
    {
        $_SESSION = [];
        $this->assertNull(_test_obtener_usuario_actual());
    }

    public function testObtenerUsuarioActualOwner()
    {
        $_SESSION = ['tienda_id' => 1, 'tienda_nombre' => 'Mi Tienda'];
        $user = _test_obtener_usuario_actual();
        $this->assertSame('owner', $user['tipo']);
        $this->assertSame('Mi Tienda', $user['nombre']);
        $this->assertSame(1, $user['tienda_id']);
    }

    public function testObtenerUsuarioActualStaff()
    {
        $_SESSION = ['tienda_id' => 2, 'staff_id' => 5, 'staff_usuario' => 'juan'];
        $user = _test_obtener_usuario_actual();
        $this->assertSame('staff', $user['tipo']);
        $this->assertSame('juan', $user['nombre']);
        $this->assertSame(2, $user['tienda_id']);
    }

    public function testObtenerUsuarioActualSuperadmin()
    {
        $_SESSION = ['admin_id' => 1, 'admin_usuario' => 'admin'];
        $user = _test_obtener_usuario_actual();
        $this->assertSame('superadmin', $user['tipo']);
        $this->assertSame('admin', $user['nombre']);
        $this->assertNull($user['tienda_id']);
    }

    // ── verificar_permiso ──

    public function testVerificarPermisoSinSesion()
    {
        $_SESSION = [];
        $this->assertFalse(_test_verificar_permiso('productos_crear'));
    }

    public function testVerificarPermisoOwnerSiempreTrue()
    {
        $_SESSION = ['tienda_id' => 1];
        $this->assertTrue(_test_verificar_permiso('productos_crear'));
        $this->assertTrue(_test_verificar_permiso('cualquier_cosa'));
    }

    public function testVerificarPermisoStaffConPermiso()
    {
        $_SESSION = [
            'tienda_id' => 1,
            'staff_id' => 3,
            'staff_permisos' => ['productos_crear' => true, 'pedidos_gestionar' => true],
        ];
        $this->assertTrue(_test_verificar_permiso('productos_crear'));
        $this->assertTrue(_test_verificar_permiso('pedidos_gestionar'));
    }

    public function testVerificarPermisoStaffSinPermiso()
    {
        $_SESSION = [
            'tienda_id' => 1,
            'staff_id' => 3,
            'staff_permisos' => ['productos_ver' => true],
        ];
        $this->assertFalse(_test_verificar_permiso('productos_crear'));
        $this->assertFalse(_test_verificar_permiso('productos_eliminar'));
        $this->assertFalse(_test_verificar_permiso('pedidos_gestionar'));
    }

    public function testVerificarPermisoStaffPermisosVacios()
    {
        $_SESSION = [
            'tienda_id' => 1,
            'staff_id' => 3,
            'staff_permisos' => [],
        ];
        $this->assertFalse(_test_verificar_permiso('productos_crear'));
    }

    public function testVerificarPermisoStaffSinSessionPermisos()
    {
        $_SESSION = [
            'tienda_id' => 1,
            'staff_id' => 3,
        ];
        $this->assertFalse(_test_verificar_permiso('productos_crear'));
    }

    // ── plan_limite (interacción con sesión) ──

    public function testPlanLimiteStarterPredeterminado()
    {
        unset($_SESSION['plan']);
        $this->assertSame(1, plan_limite('staff'));
        $this->assertSame(0, plan_limite('api_keys'));
        $this->assertFalse(plan_limite('marca_blanca'));
    }

    public function testPlanLimitePro()
    {
        $_SESSION['plan'] = 'pro';
        $this->assertSame(3, plan_limite('staff'));
        $this->assertSame(0, plan_limite('api_keys'));
        $this->assertTrue(plan_limite('personalizacion'));
        $this->assertFalse(plan_limite('marca_blanca'));
    }

    public function testPlanLimiteBusiness()
    {
        $_SESSION['plan'] = 'business';
        $this->assertSame(10, plan_limite('staff'));
        $this->assertSame(3, plan_limite('tiendas'));
        $this->assertSame(5, plan_limite('api_keys'));
        $this->assertTrue(plan_limite('marca_blanca'));
        $this->assertTrue(plan_limite('personalizacion'));
    }

    public function testPlanLimiteEnterprise()
    {
        $_SESSION['plan'] = 'enterprise';
        $this->assertSame(999, plan_limite('staff'));
        $this->assertSame(999, plan_limite('tiendas'));
        $this->assertSame(999, plan_limite('api_keys'));
    }

    public function testPlanLimiteCaracteristicaInvalida()
    {
        $_SESSION['plan'] = 'pro';
        $this->assertSame(0, plan_limite('no_existe'));
    }

    // ── verificar_limite_plan ──

    public function testVerificarLimitePlanDentroDelLimite()
    {
        // No debería llamar a mostrar_error (que hace exit)
        $_SESSION['plan'] = 'pro';
        // Si el actual es menor que el máximo, no pasa nada
        verificar_limite_plan('staff', 1, 'test');
        $this->assertTrue(true);
    }

    // ── Rate limiting ──

    public function testRegistrarYLimpiarIntentosLogin()
    {
        $mockPdo = $this->getMockPdo();

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $mockPdo->method('prepare')
            ->willReturnCallback(function ($sql) use ($stmtInsert, $stmtDelete) {
                if (str_contains($sql, 'INSERT INTO login_attempts')) return $stmtInsert;
                if (str_contains($sql, 'DELETE FROM login_attempts')) return $stmtDelete;
                return $this->createMock(PDOStatement::class);
            });

        registrar_intento_login($mockPdo, 'test_tipo');
        limpiar_intentos_login($mockPdo, 'test_tipo');
        $this->assertTrue(true);
    }

    // ── js_escape / js_string para payloads comunes ──

    public function testJsEscapeParaToast()
    {
        $msg = "Producto guardado correctamente";
        $result = js_escape($msg);
        $this->assertStringContainsString('Producto guardado', $result);
    }

    public function testJsStringParaOnclick()
    {
        $result = js_string("Eliminar producto?");
        $this->assertSame("Eliminar producto?", $result);
    }

    // ── ip_normalizada ──

    public function testIpNormalizadaIPv6()
    {
        $_SERVER['REMOTE_ADDR'] = '::ffff:192.168.1.1';
        $this->assertSame('::ffff:192.168.1.1', ip_normalizada());
    }

    // ── imagen_url ──

    public function testImagenUrlConCDN()
    {
        putenv('CDN_URL=https://cdn.ejemplo.com');
        $result = imagen_url('imagenes/test.png');
        $this->assertSame('https://cdn.ejemplo.com/imagenes/test.png', $result);
        putenv('CDN_URL');
    }

    // ── Helpers ──

    private function getMockPdo()
    {
        $mock = $this->createMock(PDO::class);
        return $mock;
    }
}