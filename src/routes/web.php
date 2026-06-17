<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Core\Router;

$router = new Router();

$router->get('/health', [HealthController::class, 'show']);

$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->get('/auth/me', [AuthController::class, 'me'])->auth();
$router->post('/auth/logout', [AuthController::class, 'logout'])->auth();

return $router;
