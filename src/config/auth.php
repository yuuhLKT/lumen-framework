<?php

declare(strict_types=1);

$tokens = array_filter([
    env('DEV_BEARER_TOKEN'),
], fn (mixed $token): bool => trim((string) $token) !== '');

return [
    'tokens' => array_values($tokens),
];
