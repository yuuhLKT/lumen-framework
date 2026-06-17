<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(Request $request): bool
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return false;
        }

        foreach (self::tokens() as $validToken) {
            if (hash_equals($validToken, $token)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    public static function tokens(): array
    {
        $config = require base_path('config/auth.php');
        $tokens = $config['tokens'] ?? [];

        if (!is_array($tokens)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn (mixed $token): string => trim((string) $token), $tokens),
            fn (string $token): bool => $token !== '',
        ));
    }
}
