<?php
require_once __DIR__ . '/../helpers.php';

class HelpersTest extends PHPUnit\Framework\TestCase
{
    protected $tempDir;

    protected function tearDown(): void
    {
        if ($this->tempDir && is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        putenv('STRIPE_SECRET_KEY');
    }

    // ── Existing ──

    public function testRutaImagenCreaDirectorio()
    {
        $ruta = ruta_imagen(9999);
        $this->tempDir = $ruta;
        $this->assertDirectoryExists($ruta);
    }

    public function testImagenDefecto()
    {
        $url = imagen_defecto();
        $this->assertStringStartsWith('https://', $url);
    }

    public function testVerificarRateLimitSinIntentos()
    {
        $this->assertTrue(verificar_rate_limit($this->getMockPdo(0), 'test', 5, 15));
    }

    public function testVerificarRateLimitAlcanzado()
    {
        $this->assertFalse(verificar_rate_limit($this->getMockPdo(5), 'test', 5, 15));
    }

    private function getMockPdo($count)
    {
        $mock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn($count);
        $mock->method('prepare')->willReturn($stmtMock);
        return $mock;
    }

    public function testGenerarThumbnailConArchivoInexistente()
    {
        $resultado = generar_thumbnail('no-existe.jpg', 'thumb.jpg', 100, 100);
        $this->assertFalse($resultado);
    }

    // ── CSRF ──

    public function testCsrfTokenGeneratesAndPersists()
    {
        if (!isset($_SESSION)) $_SESSION = [];
        $_SESSION['_csrf'] = null;
        $token = csrf_token();
        $this->assertSame($token, $_SESSION['_csrf']);
        $this->assertNotEmpty($token);
    }

    public function testVerificarCsrfExitoso()
    {
        if (!isset($_SESSION)) $_SESSION = [];
        $token = csrf_token();
        $this->assertTrue(verificar_csrf($token));
    }

    public function testVerificarCsrfFallido()
    {
        if (!isset($_SESSION)) $_SESSION = [];
        $this->assertFalse(verificar_csrf(''));
        $this->assertFalse(verificar_csrf('invalid'));
    }

    // ── js_escape ──

    public function testJsEscapeNull()
    {
        $this->assertSame('""', js_escape(null));
    }

    public function testJsEscapeString()
    {
        $result = js_escape("hello & 'world'");
        $this->assertStringContainsString('hello', $result);
        $this->assertStringContainsString('world', $result);
    }

    public function testJsEscapeSpecialChars()
    {
        $result = js_escape("<script>alert('xss')</script>");
        $this->assertStringNotContainsString('<script>', $result);
    }

    // ── js_string ──

    public function testJsStringQuotes()
    {
        $result = js_string("it's \"nice\"");
        $this->assertStringContainsString("\\'", $result);
        // js_string does NOT escape double quotes
    }

    // ── ip_normalizada ──

    public function testIpNormalizadaLocalhost()
    {
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertSame('127.0.0.1', ip_normalizada());
    }

    public function testIpNormalizadaIPv4()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertSame('192.168.1.1', ip_normalizada());
    }

    // ── plan_limite ──

    public function testPlanLimiteStaff()
    {
        $_SESSION['plan'] = 'pro';
        $this->assertSame(3, plan_limite('staff'));
    }

    public function testPlanLimiteBusiness()
    {
        $_SESSION['plan'] = 'business';
        $this->assertSame(5, plan_limite('api_keys'));
    }

    public function testPlanLimiteEnterprise()
    {
        $_SESSION['plan'] = 'enterprise';
        $this->assertSame(999, plan_limite('tiendas'));
    }

    public function testPlanLimiteDefaultStarter()
    {
        unset($_SESSION['plan']);
        $this->assertSame(1, plan_limite('staff'));
    }

    // ── imagen_url ──

    public function testImagenUrlVacia()
    {
        $this->assertSame(imagen_defecto(), imagen_url(''));
    }

    public function testImagenUrlHttp()
    {
        $url = 'https://ejemplo.com/img.jpg';
        $this->assertSame($url, imagen_url($url));
    }

    public function testImagenUrlLocal()
    {
        $this->assertStringContainsString('imagenes', imagen_url('imagenes/test.png'));
    }

    // ── _getenv / _env_path ──

    public function testGetenvNoExiste()
    {
        $this->assertSame('', _getenv('VARIABLE_QUE_NO_EXISTE'));
    }

    public function testGetenvConDefault()
    {
        $this->assertSame('default', _getenv('VARIABLE_QUE_NO_EXISTE', 'default'));
    }

    // ── Pagos online ──

    public function testConfirmarPagoProductoSinLineasPendientesEsIdempotente()
    {
        // Simula la SEGUNDA llamada con el mismo código: el SELECT ya no
        // encuentra filas en pago_estado='pendiente' porque la primera
        // llamada ya las marcó como 'pagado'. La función debe devolver
        // aplicado=false SIN llegar a tocar stock.
        $pdo = $this->createMock(PDO::class);
        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetchAll')->willReturn([]);

        $pdo->method('prepare')->willReturn($stmtSelect);
        // beginTransaction/commit no deberían llamarse en este camino, pero
        // se permiten sin expectativa estricta para no acoplar el test a la
        // implementación interna más de lo necesario.
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);

        $resultado = confirmar_pago_producto($pdo, 'PED-20260710-AAAAAA', 'pi_test_123');

        $this->assertFalse($resultado['aplicado']);
        $this->assertSame(0, $resultado['lineas']);
        $this->assertSame(0, $resultado['fallos']);
    }

    public function testConfirmarPagoProductoDescuentaStockYMarcaLineas()
    {
        // Simula el camino feliz: 2 líneas pendientes, ambas con stock
        // disponible. Debe reportar aplicado=true, lineas=2, fallos=0.
        $lineasPendientes = [
            ['id' => 1, 'producto_id' => 10, 'tienda_id' => 5],
            ['id' => 2, 'producto_id' => 10, 'tienda_id' => 5],
        ];

        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetchAll')->willReturn($lineasPendientes);

        $stmtUpdateStock = $this->createMock(PDOStatement::class);
        $stmtUpdateStock->method('execute')->willReturn(true);
        // rowCount() > 0 simula que el UPDATE con WHERE stock>=1 sí afectó
        // una fila — es decir, había stock disponible.
        $stmtUpdateStock->method('rowCount')->willReturn(1);

        $stmtUpdatePagado = $this->createMock(PDOStatement::class);
        $stmtUpdatePagado->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        // prepare() se llama 3 veces con queries distintas (SELECT, UPDATE
        // stock, UPDATE pago_estado) — willReturnOnConsecutiveCalls respeta
        // el orden real en que confirmar_pago_producto() las prepara.
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $stmtSelect,
            $stmtUpdateStock,
            $stmtUpdatePagado
        );

        $resultado = confirmar_pago_producto($pdo, 'PED-20260710-BBBBBB', 'pi_test_456');

        $this->assertTrue($resultado['aplicado']);
        $this->assertSame(2, $resultado['lineas']);
        $this->assertSame(0, $resultado['fallos']);
    }

    public function testConfirmarPagoProductoCuentaFallosSinStockDisponible()
    {
        // Simula que el producto se quedó sin stock ENTRE que se creó el
        // checkout y se confirmó el pago (la ventana de riesgo que el WHERE
        // stock>=1 está pensado para cubrir). rowCount()=0 en el UPDATE de
        // stock debe contarse como fallo, no romper la ejecución.
        $lineasPendientes = [
            ['id' => 1, 'producto_id' => 10, 'tienda_id' => 5],
        ];

        $stmtSelect = $this->createMock(PDOStatement::class);
        $stmtSelect->method('fetchAll')->willReturn($lineasPendientes);

        $stmtUpdateStock = $this->createMock(PDOStatement::class);
        $stmtUpdateStock->method('execute')->willReturn(true);
        // rowCount()=0 → el WHERE stock>=1 no afectó ninguna fila: sin stock.
        $stmtUpdateStock->method('rowCount')->willReturn(0);

        $stmtUpdatePagado = $this->createMock(PDOStatement::class);
        $stmtUpdatePagado->method('execute')->willReturn(true);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('beginTransaction')->willReturn(true);
        $pdo->method('commit')->willReturn(true);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls(
            $stmtSelect,
            $stmtUpdateStock,
            $stmtUpdatePagado
        );

        $resultado = confirmar_pago_producto($pdo, 'PED-20260710-CCCCCC', 'pi_test_789');

        // aplicado sigue siendo true (el pedido SÍ se marca como pagado —
        // el cliente pagó — pero se registra que 1 línea no tenía stock
        // para que quien revise el error_log del webhook lo note).
        $this->assertTrue($resultado['aplicado']);
        $this->assertSame(1, $resultado['lineas']);
        $this->assertSame(1, $resultado['fallos']);
    }

    public function testMarcarPagoFallidoEsIdempotente()
    {
        // Primera llamada: sí hay una fila en 'pendiente' que pasa a 'fallido'.
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(1);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertTrue(marcar_pago_fallido($pdo, 'PED-20260710-DDDDDD'));
    }

    public function testMarcarPagoFallidoSinFilasPendientesDevuelveFalse()
    {
        // Segunda llamada con el mismo código (o un código ya confirmado
        // como pagado): el WHERE pago_estado='pendiente' no encuentra nada,
        // rowCount()=0, y la función debe devolver false sin error.
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(0);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertFalse(marcar_pago_fallido($pdo, 'PED-20260710-EEEEEE'));
    }

    public function testPagosOnlineHabilitadosPlanBusinessConStripeConfigurado()
    {
        putenv('STRIPE_SECRET_KEY=sk_test_dummy');
        $tienda = ['plan' => 'business'];
        $this->assertTrue(pagos_online_habilitados($tienda));
    }

    public function testPagosOnlineHabilitadosPlanEnterpriseConStripeConfigurado()
    {
        putenv('STRIPE_SECRET_KEY=sk_test_dummy');
        $tienda = ['plan' => 'enterprise'];
        $this->assertTrue(pagos_online_habilitados($tienda));
    }

    public function testPagosOnlineHabilitadosPlanStarterSiempreFalse()
    {
        // Aunque Stripe esté configurado, un plan Starter no debe ver el
        // botón — el gate es plan Y env var, no solo env var.
        putenv('STRIPE_SECRET_KEY=sk_test_dummy');
        $tienda = ['plan' => 'starter'];
        $this->assertFalse(pagos_online_habilitados($tienda));
    }

    public function testPagosOnlineHabilitadosPlanBusinessSinStripeConfigurado()
    {
        // Plan correcto pero sin la env var: debe seguir false.
        $tienda = ['plan' => 'business'];
        $this->assertFalse(pagos_online_habilitados($tienda));
    }

    public function testPagosOnlineHabilitadosTiendaNula()
    {
        $this->assertFalse(pagos_online_habilitados(null));
    }
}
