<?php

declare(strict_types=1);

function mailer_autoload_path(): string
{
    return dirname(__DIR__) . '/vendor/autoload.php';
}

function smtp_encryption(string $value): string
{
    $value = strtolower($value);

    return $value === 'ssl'
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
}

function send_password_reset_email(array $user, string $token): void
{
    $autoload = mailer_autoload_path();

    if (!file_exists($autoload)) {
        throw new \RuntimeException('Dependencias do backend nao instaladas.');
    }

    require_once $autoload;

    $config = load_config('mail');
    $resetUrl = frontend_url('redefinir-senha.html?token=' . urlencode($token));
    $subject = 'Redefinicao de senha - Portal Vida Livre';
    $html = '
        <p>Ola, ' . htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') . '.</p>
        <p>Recebemos um pedido para redefinir sua senha.</p>
        <p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Redefinir senha</a></p>
        <p>Se voce nao fez essa solicitacao, ignore este e-mail.</p>
        <p>O link expira em 1 hora.</p>
    ';
    $text = "Ola, {$user['name']}.\n\nAcesse o link para redefinir sua senha:\n{$resetUrl}\n\nO link expira em 1 hora.";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->SMTPSecure = smtp_encryption((string) $config['encryption']);
    $mail->Port = (int) $config['port'];
    $mail->setFrom((string) $config['from_email'], (string) $config['from_name']);
    $mail->addAddress((string) $user['email'], (string) $user['name']);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text;
    $mail->send();
}

