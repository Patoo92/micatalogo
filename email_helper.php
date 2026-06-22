<?php
require_once 'C:\xampp\micatalogo-config\email.php';
require_once __DIR__ . '/vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/SMTP.php';
require_once __DIR__ . '/vendor/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviar_email($para, $asunto, $cuerpo_html) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
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
