<?php

declare(strict_types=1);

namespace App\Repositories;

final class AuthTokenRepository extends BaseRepository
{
    protected string $table = 'auth_tokens';

    /** @return array<string, mixed>|null */
    public function findValidToken(string $plainTextToken): ?array
    {
        $token = $this->findOne('token_hash', self::hashToken($plainTextToken));

        if ($token === null || ($token['revoked_at'] ?? null) !== null) {
            return null;
        }

        return $token;
    }

    /** @return array{plain_text_token: string, token: array<string, mixed>} */
    public function createForUser(int|string $userId, string $name = 'default'): array
    {
        $plainTextToken = bin2hex(random_bytes(32));
        $now = date(DATE_ATOM);

        $token = $this->insert([
            'user_id' => (int) $userId,
            'name' => $name,
            'token_hash' => self::hashToken($plainTextToken),
            'created_at' => $now,
            'last_used_at' => null,
            'revoked_at' => null,
        ]);

        return [
            'plain_text_token' => $plainTextToken,
            'token' => $token,
        ];
    }

    /** @param array<string, mixed> $token */
    public function markAsUsed(array $token): void
    {
        $this->update((int) $token['id'], ['last_used_at' => date(DATE_ATOM)]);
    }

    /** @param array<string, mixed> $token */
    public function revoke(array $token): void
    {
        $this->update((int) $token['id'], ['revoked_at' => date(DATE_ATOM)]);
    }

    public static function hashToken(string $plainTextToken): string
    {
        return hash('sha256', $plainTextToken);
    }
}
