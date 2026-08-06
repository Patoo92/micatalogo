<?php
/**
 * TOTP / MFA (RFC 6238) sin dependencias externas.
 *
 * - genera_secreto_totp():   secreto Base32 aleatorio (160 bits) para Google Authenticator.
 * - codigo_totp():           código de 6 dígitos para un secreto y una marca de tiempo.
 * - valida_codigo_totp():    valida con ventana +/-1 paso (30s) para tolerancia de reloj.
 * - qr_totp():               URI otpauth:// para generar el QR en la UI.
 *
 * El secreto se guarda en Base32 (sin padding '=') en la columna totp_secret.
 */
if (defined('TOTP_HELPER_LOADED')) return;
define('TOTP_HELPER_LOADED', true);

/** Alfabeto Base32 RFC 4648 (sin padding). */
const TOTP_BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

/**
 * Genera un secreto TOTP aleatorio de 20 bytes (160 bits) en Base32 sin padding.
 * Devuelve una string de 32 caracteres Base32.
 */
function genera_secreto_totp() {
    $bytes = random_bytes(20);
    return base32_encode_totp($bytes);
}

/**
 * Codifica bytes a Base32 sin padding (RFC 4648).
 */
function base32_encode_totp($bytes) {
    $alfabeto = TOTP_BASE32_ALPHABET;
    $bits = '';
    $len = strlen($bytes);
    for ($i = 0; $i < $len; $i++) {
        $bits .= str_pad(decbin(ord($bytes[$i])), 8, '0', STR_PAD_LEFT);
    }
    $salida = '';
    for ($i = 0; $i < strlen($bits); $i += 5) {
        $chunk = substr($bits, $i, 5);
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $salida .= $alfabeto[bindec($chunk)];
    }
    return $salida;
}

/**
 * Decodifica Base32 (sin padding, tolera minúsculas y padding opcional) a bytes.
 * Devuelve string binaria o '' si el formato es inválido.
 */
function base32_decode_totp($secreto) {
    $secreto = strtoupper(rtrim(trim((string)$secreto), '='));
    if ($secreto === '') return '';
    $alfabeto = TOTP_BASE32_ALPHABET;
    $bits = '';
    $len = strlen($secreto);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alfabeto, $secreto[$i]);
        if ($pos === false) return '';
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    // Descartar bits sobrantes (padding implícito)
    $bytes = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
        $bytes .= chr(bindec(substr($bits, $i, 8)));
    }
    return $bytes;
}

/**
 * Genera el código TOTP de 6 dígitos para un secreto Base32 en un momento dado.
 * @param string $secreto Secreto Base32 (sin padding).
 * @param int|null $tiempo Marca de tiempo unix; si es null usa time().
 * @return string Código de 6 dígitos con cero a la izquierda.
 */
function codigo_totp($secreto, $tiempo = null) {
    $clave = base32_decode_totp($secreto);
    if ($clave === '') return '';
    $t = ($tiempo ?? time());
    $contador = intdiv((int)$t, 30);
    $bin = pack('N', $contador);
    // RFC 4226: HMAC sobre contador de 64 bits (8 bytes)
    $mensaje = "\x00\x00\x00\x00" . $bin;
    $hash = hash_hmac('sha1', $mensaje, $clave, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $parte = substr($hash, $offset, 4);
    $valor = (unpack('N', $parte)[1] & 0x7FFFFFFF) % 1000000;
    return str_pad((string)$valor, 6, '0', STR_PAD_LEFT);
}

/**
 * Valida un código de 6 dígitos contra un secreto con ventana de ±1 paso (30 s).
 * @param string $secreto Secreto Base32.
 * @param string $codigo Código de 6 dígitos enviado por el usuario.
 * @param int|null $tiempo Marca de tiempo unix (time() por defecto).
 * @return bool
 */
function valida_codigo_totp($secreto, $codigo, $tiempo = null) {
    $codigo = trim((string)$codigo);
    if (!preg_match('/^\d{6}$/', $codigo)) return false;
    $t = $tiempo ?? time();
    for ($offset = -1; $offset <= 1; $offset++) {
        if (hash_equals(codigo_totp($secreto, $t + $offset * 30), $codigo)) {
            return true;
        }
    }
    return false;
}

/**
 * Genera la URI otpauth:// para el QR (scheme compatible con Google Authenticator).
 * @param string $secreto Secreto Base32.
 * @param string $emisor Nombre que verá el usuario (ej: "MiCatalogo Master").
 * @param string $cuenta Identificador de cuenta (ej: usuario o email).
 * @return string
 */
function qr_totp($secreto, $emisor, $cuenta) {
    $emisor = rawurlencode($emisor);
    $cuenta = rawurlencode($cuenta);
    $secreto = rawurlencode($secreto);
    $extra = '';
    if ($emisor !== '') {
        $extra .= '&issuer=' . $emisor;
    }
    return "otpauth://totp/{$emisor}:{$cuenta}?secret={$secreto}&issuer={$emisor}&algorithm=SHA1&digits=6&period=30";
}
