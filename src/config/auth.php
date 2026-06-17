<?php

declare(strict_types=1);

$tokens = array_filter([
    env('AUTH_TOKEN'),
    ...explode(',', (string) env('AUTH_TOKENS', '')),
], fn (mixed $token): bool => trim((string) $token) !== '');

return [
    'tokens' => array_values($tokens),
];
