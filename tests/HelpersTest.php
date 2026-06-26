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
}
