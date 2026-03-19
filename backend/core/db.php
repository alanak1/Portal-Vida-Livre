<?php

declare(strict_types=1);

function db(): \PDO
{
    static $pdo = null;

    if ($pdo instanceof \PDO) {
        return $pdo;
    }

    $config = load_config('database');

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        (int) $config['port'],
        $config['database'],
        $config['charset']
    );

    try {
        $pdo = new \PDO(
            $dsn,
            (string) $config['username'],
            (string) $config['password'],
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (\PDOException $exception) {
        throw new \RuntimeException('Nao foi possivel conectar ao banco de dados.', 0, $exception);
    }

    return $pdo;
}

