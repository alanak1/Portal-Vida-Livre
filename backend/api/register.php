<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

if (request_method() !== 'POST') {
    error_response('Metodo nao permitido.', [], 405);
}

require_csrf();

$data = request_data();
$name = sanitize_name((string) ($data['name'] ?? ''));
$email = normalize_email((string) ($data['email'] ?? ''));
$password = (string) ($data['password'] ?? '');
$passwordConfirmation = (string) ($data['password_confirmation'] ?? '');
$errors = [];

if ($name === '' || string_length($name) < 3) {
    add_error($errors, 'name', 'Informe seu nome com pelo menos 3 caracteres.');
} elseif (string_length($name) > 120) {
    add_error($errors, 'name', 'O nome deve ter no maximo 120 caracteres.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    add_error($errors, 'email', 'Informe um e-mail valido.');
}

foreach (password_strength_errors($password) as $message) {
    add_error($errors, 'password', $message);
}

if ($passwordConfirmation === '') {
    add_error($errors, 'password_confirmation', 'Confirme sua senha.');
} elseif (!hash_equals($password, $passwordConfirmation)) {
    add_error($errors, 'password_confirmation', 'A confirmacao deve ser igual a senha.');
}

if ($email !== '' && find_user_by_email($email) !== null) {
    add_error($errors, 'email', 'Este e-mail ja esta cadastrado.');
}

if (has_errors($errors)) {
    error_response('Verifique os campos informados.', $errors, 422);
}

try {
    $userId = create_user($name, $email, $password);
    $user = find_user_by_id($userId);
} catch (\Throwable $throwable) {
    error_response('Nao foi possivel concluir o cadastro agora.', [], 500);
}

success_response('Cadastro realizado com sucesso.', [
    'user' => $user !== null ? user_public_data($user) : [
        'id' => $userId,
        'name' => $name,
        'email' => $email,
        'created_at' => null,
    ],
], 201);

