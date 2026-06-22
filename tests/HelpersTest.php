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
    }

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
        $this->assertTrue(verificar_rate_limit($this->getMockPdo(), 'test', 5, 15));
    }

    private function getMockPdo()
    {
        $mock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn(0);
        $mock->method('prepare')->willReturn($stmtMock);
        return $mock;
    }

    public function testGenerarThumbnailConArchivoInexistente()
    {
        $resultado = generar_thumbnail('no-existe.jpg', 'thumb.jpg', 100, 100);
        $this->assertFalse($resultado);
    }
}
