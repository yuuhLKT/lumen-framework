<?php

declare(strict_types=1);

use App\Core\ErrorHandler;
use App\Core\Request;

require_once __DIR__ . '/../bootstrap/app.php';

$router = require __DIR__ . '/../routes/web.php';

try {
    $response = $router->dispatch(Request::capture());
} catch (Throwable $exception) {
    $response = ErrorHandler::handle($exception);
}

$response->send();
