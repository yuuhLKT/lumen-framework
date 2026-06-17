<?php

declare(strict_types=1);

return [
    'timezone' => env('APP_TIMEZONE', 'America/Sao_Paulo'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
];
