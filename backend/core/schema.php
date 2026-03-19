<?php

declare(strict_types=1);

function server_db(): \PDO
{
    static $pdo = null;

    if ($pdo instanceof \PDO) {
        return $pdo;
    }

    $config = load_config('database');
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $config['host'],
        (int) $config['port'],
        $config['charset']
    );

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

    return $pdo;
}

function ensure_database_exists(): void
{
    $config = load_config('database');
    $database = str_replace('`', '``', (string) $config['database']);
    $charset = (string) $config['charset'];
    $collation = (string) ($config['collation'] ?? ($charset . '_unicode_ci'));

    $sql = sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET %s COLLATE %s',
        $database,
        $charset,
        $collation
    );

    server_db()->exec($sql);
}

function schema_file_path(): string
{
    return dirname(__DIR__) . '/database/schema.sql';
}

function split_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $lines = preg_split('/\R/', $sql) ?: [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '--')) {
            continue;
        }

        $buffer .= $line . "\n";

        if (str_ends_with(rtrim($line), ';')) {
            $statement = trim($buffer);

            if ($statement !== '') {
                $statements[] = $statement;
            }

            $buffer = '';
        }
    }

    $buffer = trim($buffer);

    if ($buffer !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}

function run_schema(): void
{
    ensure_database_exists();

    $sql = file_get_contents(schema_file_path());

    if ($sql === false) {
        throw new \RuntimeException('Nao foi possivel ler o schema.sql.');
    }

    foreach (split_sql_statements($sql) as $statement) {
        db()->exec($statement);
    }
}

