<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;

final class Auth
{
    public static function check(Request $request): bool
    {
        return self::authenticate($request) !== null || self::checkStaticToken($request);
    }

    /** @return array<string, mixed>|null */
    public static function authenticate(Request $request): ?array
    {
        $user = (new AuthService())->userFromToken($request->bearerToken());
        $request->setUser($user);

        return $user;
    }

    public static function logout(Request $request): void
    {
        (new AuthService())->logout($request->bearerToken());
        $request->setUser(null);
    }

    private static function checkStaticToken(Request $request): bool
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
