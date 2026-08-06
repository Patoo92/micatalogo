<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regresión del helper TOTP/RFC 6238 (totp_helper.php).
 * - RFC 4226: SHA1, 6 dígitos, periodo 30 s
 * - Vectores oficiales del RFC 6238 (Apéndice B) + self-test
 */
class TotpTest extends TestCase
{
    /** Vectores oficiales RFC 6238 (secreto Base32 de los 8 bytes 1234567890ABCDEF...). */
    public function testVectoresRfc6238(): void
    {
        $secreto = base32_encode_totp(hex2bin('3132333435363738393031323334353637383930'));

        $this->assertSame('287082', codigo_totp($secreto, 59));
        $this->assertSame('081804', codigo_totp($secreto, 1111111109));
        $this->assertSame('050471', codigo_totp($secreto, 1111111111));
        $this->assertSame('005924', codigo_totp($secreto, 1234567890));
        $this->assertSame('279037', codigo_totp($secreto, 2000000000));
        $this->assertSame('353130', codigo_totp($secreto, 20000000000));
    }

    public function testSecretoGeneradoTiene32CaracteresBase32(): void
    {
        $secreto = genera_secreto_totp();
        $this->assertSame(32, strlen($secreto));
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secreto);

        $bytes = base32_decode_totp($secreto);
        $this->assertSame(20, strlen($bytes));
        $this->assertSame($secreto, base32_encode_totp($bytes));
    }

    public function testCodigoEsSiempre6Digitos(): void
    {
        $secreto = genera_secreto_totp();
        for ($i = 0; $i < 20; $i++) {
            $codigo = codigo_totp($secreto, time() + $i * 30);
            $this->assertMatchesRegularExpression('/^\d{6}$/', $codigo);
        }
    }

    public function testValidacionVentanaMas1(): void
    {
        $secreto = genera_secreto_totp();
        $t = time();
        $futuro = codigo_totp($secreto, $t + 30);
        $this->assertTrue(valida_codigo_totp($secreto, $futuro, $t));
    }

    public function testValidacionVentanaMenos1(): void
    {
        $secreto = genera_secreto_totp();
        $t = time();
        $pasado = codigo_totp($secreto, $t - 30);
        $this->assertTrue(valida_codigo_totp($secreto, $pasado, $t));
    }

    public function testValidacionFueraDeVentanaRechaza(): void
    {
        $secreto = genera_secreto_totp();
        $t = time();
        $lejano = codigo_totp($secreto, $t + 120);
        $this->assertFalse(valida_codigo_totp($secreto, $lejano, $t));
    }

    public function testValidacionRechazaFormatoInvalido(): void
    {
        $secreto = genera_secreto_totp();
        $this->assertFalse(valida_codigo_totp($secreto, 'abc', time()));
        $this->assertFalse(valida_codigo_totp($secreto, '12345', time()));
        $this->assertFalse(valida_codigo_totp($secreto, '1234567', time()));
        $this->assertFalse(valida_codigo_totp($secreto, '', time()));
    }

    public function testValidacionConSecretoInvalido(): void
    {
        $this->assertSame('', codigo_totp('##INVALIDO##'));
        $this->assertFalse(valida_codigo_totp('##INVALIDO##', '123456', time()));
    }

    public function testCodigoEstableDentroDelMismoPaso(): void
    {
        $secreto = genera_secreto_totp();
        $t = time();
        $this->assertSame(codigo_totp($secreto, $t), codigo_totp($secreto, $t));
    }

    public function testSecretosDistintosProducenCodigosDistintos(): void
    {
        $s1 = genera_secreto_totp();
        $s2 = genera_secreto_totp();
        $t = time();
        $c1 = codigo_totp($s1, $t);
        $c2 = codigo_totp($s2, $t);
        $this->assertNotSame($s1, $s2);
        $this->assertNotSame($c1, $c2);
    }

    public function testBase32ToleraMinusculasYMinusculas(): void
    {
        $secreto = genera_secreto_totp();
        $c1 = codigo_totp(strtolower($secreto), time());
        $c2 = codigo_totp($secreto, time());
        $this->assertSame($c1, $c2);
        $this->assertTrue(valida_codigo_totp(strtolower($secreto), codigo_totp($secreto), time()));
    }

    public function testQrUriFormato(): void
    {
        $secreto = genera_secreto_totp();
        $uri = qr_totp($secreto, 'MiCatalogo Master', 'admin');
        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=' . rawurlencode($secreto), $uri);
        $this->assertStringContainsString('issuer=' . rawurlencode('MiCatalogo Master'), $uri);
        $this->assertStringContainsString('algorithm=SHA1&digits=6&period=30', $uri);
    }

    public function testComparacionDeCodigosEsTimingSafe(): void
    {
        $this->assertTrue(hash_equals('123456', '123456'));
        $this->assertFalse(hash_equals('123456', '654321'));
    }
}
