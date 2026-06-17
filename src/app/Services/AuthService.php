<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\HttpException;
use App\Repositories\AuthTokenRepository;
use App\Repositories\UserRepository;
use App\Support\HttpStatus;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuthTokenRepository $tokens = new AuthTokenRepository(),
    ) {
    }

    /** @param array{name: string, email: string, password: string} $data */
    public function register(array $data): array
    {
        $email = strtolower(trim($data['email']));

        if ($this->users->findByEmail($email) !== null) {
            throw new HttpException('Email ja cadastrado.', HttpStatus::CONFLICT);
        }

        $user = $this->users->insert([
            'name' => trim($data['name']),
            'email' => $email,
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => date(DATE_ATOM),
            'updated_at' => date(DATE_ATOM),
        ]);

        $token = $this->tokens->createForUser((int) $user['id']);

        return $this->authResponse($user, $token['plain_text_token']);
    }

    /** @param array{email: string, password: string} $data */
    public function login(array $data): array
    {
        $user = $this->users->findByEmail($data['email']);

        if ($user === null || !password_verify($data['password'], (string) ($user['password_hash'] ?? ''))) {
            throw new HttpException('Credenciais inválidas.', HttpStatus::UNAUTHORIZED);
        }

        $token = $this->tokens->createForUser((int) $user['id']);

        return $this->authResponse($user, $token['plain_text_token']);
    }

    /** @return array<string, mixed>|null */
    public function userFromToken(?string $plainTextToken): ?array
    {
        if ($plainTextToken === null) {
            return null;
        }

        $token = $this->tokens->findValidToken($plainTextToken);

        if ($token === null) {
            return null;
        }

        $user = $this->users->find((int) $token['user_id']);

        if ($user === null) {
            return null;
        }

        $this->tokens->markAsUsed($token);

        return $this->publicUser($user);
    }

    public function logout(?string $plainTextToken): void
    {
        if ($plainTextToken === null) {
            return;
        }

        $token = $this->tokens->findValidToken($plainTextToken);

        if ($token !== null) {
            $this->tokens->revoke($token);
        }
    }

    /** @param array<string, mixed> $user */
    private function authResponse(array $user, string $plainTextToken): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $plainTextToken,
            'user' => $this->publicUser($user),
        ];
    }

    /** @param array<string, mixed> $user */
    private function publicUser(array $user): array
    {
        unset($user['password_hash']);

        return $user;
    }
}
