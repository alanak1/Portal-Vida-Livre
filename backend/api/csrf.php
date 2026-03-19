<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

if (request_method() !== 'GET') {
    error_response('Metodo nao permitido.', [], 405);
}

success_response('Token CSRF gerado com sucesso.', [
    'csrf_token' => get_csrf_token(),
]);

