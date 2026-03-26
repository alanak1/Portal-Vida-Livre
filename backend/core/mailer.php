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

function create_smtp_mailer(): \PHPMailer\PHPMailer\PHPMailer
{
    $autoload = mailer_autoload_path();

    if (!file_exists($autoload)) {
        throw new \RuntimeException('Dependencias do backend nao instaladas.');
    }

    require_once $autoload;

    $config = load_config('mail');

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

    return $mail;
}

function deliver_email(array $user, string $subject, string $html, string $text): void
{
    $mail = create_smtp_mailer();
    $mail->addAddress((string) $user['email'], (string) $user['name']);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $html;
    $mail->AltBody = $text;
    $mail->send();
}

function send_password_reset_email(array $user, string $token): void
{
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

    deliver_email($user, $subject, $html, $text);
}

function send_email_verification_email(array $user, string $token): void
{
    $verificationUrl = frontend_url('confirmar-email.html?token=' . urlencode($token));
    $subject = 'Confirme seu cadastro - Portal Vida Livre';
    $html = '
        <p>Ola, ' . htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') . '.</p>
        <p>Seu cadastro no Portal Vida Livre foi criado com sucesso.</p>
        <p>Para liberar o acesso, confirme seu e-mail no link abaixo:</p>
        <p><a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') . '">Confirmar cadastro</a></p>
        <p>Se voce nao solicitou este cadastro, ignore este e-mail.</p>
        <p>O link expira em 24 horas.</p>
    ';
    $text = "Ola, {$user['name']}.\n\nConfirme seu cadastro acessando o link abaixo:\n{$verificationUrl}\n\nO link expira em 24 horas.";

    deliver_email($user, $subject, $html, $text);
}

