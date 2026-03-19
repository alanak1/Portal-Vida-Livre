<?php

declare(strict_types=1);

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function success_response(string $message, array $data = [], int $status = 200): never
{
    json_response([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $status);
}

function error_response(string $message, array $errors = [], int $status = 400): never
{
    json_response([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ], $status);
}

