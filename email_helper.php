<?php
if (defined('EMAIL_HELPER_LOADED')) return;
define('EMAIL_HELPER_LOADED', true);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';

// SMTP desde variables de entorno (getenv o .env). Sin secretos hardcodeados.
define('SMTP_HOST', _getenv('SMTP_HOST', 'smtp-relay.brevo.com'));
define('SMTP_PORT', (int)_getenv('SMTP_PORT', '587'));
define('SMTP_USER', _getenv('SMTP_USER'));
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', _getenv('SMTP_FROM'));
define('SMTP_FROM_NAME', _getenv('SMTP_FROM_NAME', 'micatalogo'));

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviar_email($para, $asunto, $cuerpo_html, $from_name = null) {
    if (empty(SMTP_USER) || empty(SMTP_PASS)) {
        error_log("enviar_email: SMTP_USER/SMTP_PASS no configurados en el entorno — email a {$para} NO enviado");
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, $from_name ?: SMTP_FROM_NAME);
        $mail->addAddress($para);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error enviando email: " . $mail->ErrorInfo);
        return false;
    }
}
