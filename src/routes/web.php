<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

$router = new Router();

$router->get('/health', [HealthController::class, 'show']);

return $router;
