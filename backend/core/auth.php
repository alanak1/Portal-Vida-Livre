<?php

declare(strict_types=1);

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = is_secure_request();

    session_name('portal_vida_livre_session');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function user_public_data(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'created_at' => $user['created_at'] ?? null,
    ];
}

function find_user_by_email(string $email): ?array
{
    $statement = db()->prepare(
        'SELECT id, name, email, password_hash, created_at
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    return is_array($user) ? $user : null;
}

function find_user_by_id(int $id): ?array
{
    $statement = db()->prepare(
        'SELECT id, name, email, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $user = $statement->fetch();

    return is_array($user) ? $user : null;
}

function create_user(string $name, string $email, string $password): int
{
    $statement = db()->prepare(
        'INSERT INTO users (name, email, password_hash)
         VALUES (:name, :email, :password_hash)'
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

function login_user(array $user): array
{
    ensure_session_started();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['csrf_token']);

    return user_public_data($user);
}

function logout_user(): void
{
    ensure_session_started();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function current_user(): ?array
{
    ensure_session_started();

    $userId = $_SESSION['user_id'] ?? null;

    if (!is_int($userId) && !ctype_digit((string) $userId)) {
        return null;
    }

    $user = find_user_by_id((int) $userId);

    if ($user === null) {
        unset($_SESSION['user_id']);
        return null;
    }

    return user_public_data($user);
}

function create_password_reset_token(int $userId): string
{
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $pdo = db();

    $pdo->beginTransaction();

    try {
        $deleteStatement = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
        $deleteStatement->execute(['user_id' => $userId]);

        $insertStatement = $pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)'
        );
        $insertStatement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $pdo->commit();
    } catch (\Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }

    return $rawToken;
}

function find_password_reset_token(string $rawToken): ?array
{
    if ($rawToken === '') {
        return null;
    }

    $statement = db()->prepare(
        'SELECT prt.id, prt.user_id, prt.expires_at, prt.used_at, u.name, u.email
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.id = prt.user_id
         WHERE prt.token_hash = :token_hash
         LIMIT 1'
    );
    $statement->execute([
        'token_hash' => hash('sha256', $rawToken),
    ]);
    $record = $statement->fetch();

    if (!is_array($record)) {
        return null;
    }

    if ($record['used_at'] !== null) {
        return null;
    }

    if (strtotime((string) $record['expires_at']) < time()) {
        return null;
    }

    return $record;
}

function invalidate_user_reset_tokens(int $userId): void
{
    $statement = db()->prepare(
        'UPDATE password_reset_tokens
         SET used_at = NOW()
         WHERE user_id = :user_id
           AND used_at IS NULL'
    );
    $statement->execute(['user_id' => $userId]);
}

function update_user_password(int $userId, string $password): void
{
    $statement = db()->prepare(
        'UPDATE users
         SET password_hash = :password_hash,
             updated_at = NOW()
         WHERE id = :id'
    );
    $statement->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'id' => $userId,
    ]);
}
