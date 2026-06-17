<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JsonConnectionTest extends TestCase
{
    public function testRollsBackJsonTransaction(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-json-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $connection->table('users')->insert(['name' => 'Before']);

        try {
            $connection->transaction(function (JsonConnection $connection): void {
                $connection->table('users')->insert(['name' => 'During']);

                throw new RuntimeException('fail');
            });
        } catch (RuntimeException) {
        }

        self::assertCount(1, $connection->table('users')->all());

        @unlink($path);
    }
}
