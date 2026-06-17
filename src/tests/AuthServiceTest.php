<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Exceptions\HttpException;
use App\Repositories\AuthTokenRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private string $path;

    protected function tearDown(): void
    {
        if (isset($this->path)) {
            @unlink($this->path);
        }
    }

    public function testRegistersUserAndReturnsPlainTextTokenOnce(): void
    {
        $auth = $this->authService();

        $response = $auth->register([
            'name' => 'Yuri Luiz',
            'email' => 'YURI@example.com',
            'password' => 'password123',
        ]);

        self::assertSame('Bearer', $response['token_type']);
        self::assertIsString($response['access_token']);
        self::assertSame('yuri@example.com', $response['user']['email']);
        self::assertArrayNotHasKey('password_hash', $response['user']);

        $raw = json_decode((string) file_get_contents($this->path), true);
        self::assertIsArray($raw);
        self::assertNotSame($response['access_token'], $raw['auth_tokens'][0]['token_hash']);
    }

    public function testAuthenticatesAndRevokesToken(): void
    {
        $auth = $this->authService();
        $registered = $auth->register([
            'name' => 'Yuri Luiz',
            'email' => 'yuri@example.com',
            'password' => 'password123',
        ]);

        $user = $auth->userFromToken((string) $registered['access_token']);

        self::assertSame('yuri@example.com', $user['email'] ?? null);

        $auth->logout((string) $registered['access_token']);

        self::assertNull($auth->userFromToken((string) $registered['access_token']));
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $this->expectException(HttpException::class);

        $auth = $this->authService();
        $auth->register([
            'name' => 'Yuri Luiz',
            'email' => 'yuri@example.com',
            'password' => 'password123',
        ]);

        $auth->login([
            'email' => 'yuri@example.com',
            'password' => 'wrong-password',
        ]);
    }

    private function authService(): AuthService
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-auth-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($this->path);

        return new AuthService(
            new UserRepository($connection),
            new AuthTokenRepository($connection),
        );
    }
}
