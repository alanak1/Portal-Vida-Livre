<?php

declare(strict_types=1);

function get_csrf_token(): string
{
    ensure_session_started();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function rotate_csrf_token(): string
{
    ensure_session_started();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    return (string) $_SESSION['csrf_token'];
}

function csrf_token_from_request(): string
{
    $data = request_data();

    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return trim((string) $_SERVER['HTTP_X_CSRF_TOKEN']);
    }

    return trim((string) ($data['csrf_token'] ?? ''));
}

function validate_csrf_token(string $token): bool
{
    ensure_session_started();

    $sessionToken = $_SESSION['csrf_token'] ?? null;

    if (!is_string($sessionToken) || $sessionToken === '' || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function require_csrf(): void
{
    if (!validate_csrf_token(csrf_token_from_request())) {
        error_response(
            'Requisicao invalida. Atualize a pagina e tente novamente.',
            ['csrf' => ['Token CSRF invalido ou ausente.']],
            419
        );
    }
}

