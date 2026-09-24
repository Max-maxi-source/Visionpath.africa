<?php

use PHPMailer\PHPMailer\PHPMailer;

$autoload = __DIR__ . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new RuntimeException('PHPMailer is not installed. Run composer install in the project directory.');
}
require_once $autoload;

function send_verification_email(string $recipient, string $name, string $code): void
{
    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = getenv('SMTP_HOST') ?: '127.0.0.1';
    $mailer->SMTPAuth = true;
    $mailer->Username = getenv('SMTP_USER') ?: '';
    $mailer->Password = getenv('SMTP_PASS') ?: '';
    $mailer->Port = (int) (getenv('SMTP_PORT') ?: 587);
    $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom(getenv('SMTP_FROM_EMAIL') ?: $mailer->Username, getenv('SMTP_FROM_NAME') ?: 'VisionPath Africa');
    $mailer->addAddress($recipient, $name);
    $mailer->isHTML(true);
    $mailer->Subject = 'Verify your VisionPath Africa account';
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $mailer->Body = "<div style=\"font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#183153\"><h1>VisionPath Africa</h1><p>Hello {$safe_name},</p><p>Use this verification code to activate your account:</p><p style=\"font-size:32px;font-weight:bold;letter-spacing:8px\">{$code}</p><p>This code expires in 15 minutes. If you did not create an account, you can ignore this email.</p></div>";
    $mailer->AltBody = "Hello {$name}, your VisionPath Africa verification code is {$code}. It expires in 15 minutes.";
    $mailer->send();
}