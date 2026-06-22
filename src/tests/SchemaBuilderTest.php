<?php

declare(strict_types=1);

namespace Tests;

use App\Database\Drivers\Json\JsonConnection;
use App\Database\Drivers\SQLite\SQLiteConnection;
use App\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

final class SchemaBuilderTest extends TestCase
{
    public function testSQLiteCreatesTableWithRealColumns(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-schema-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);

        $connection->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $columns = $connection->table('users')->query()->select(['name', 'email', 'active'])->get();
        self::assertSame([], $columns);

        $user = $connection->table('users')->insert([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'active' => true,
            'created_at' => date(DATE_ATOM),
            'updated_at' => date(DATE_ATOM),
        ]);

        self::assertSame(1, $user['id']);
        self::assertSame('Alice', $user['name']);
        self::assertSame('alice@example.com', $connection->table('users')->where('email', 'alice@example.com')[0]['email'] ?? null);

        $connection->dropIfExists('users');
        @unlink($path);
    }

    public function testSQLiteAlterAddsColumns(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-schema-alter-' . uniqid('', true) . '.sqlite';
        $connection = new SQLiteConnection($path);

        $connection->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
        });

        $connection->alter('posts', function (Blueprint $table): void {
            $table->text('body')->nullable();
        });

        $post = $connection->table('posts')->insert(['title' => 'Hello', 'body' => 'World']);

        self::assertSame('World', $post['body']);

        $connection->dropIfExists('posts');
        @unlink($path);
    }

    public function testJsonDriverKeepsRowsAndSchemaMetadata(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-schema-' . uniqid('', true) . '.json';
        $connection = new JsonConnection($path);

        $connection->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
        });

        $user = $connection->table('users')->insert(['email' => 'alice@example.com']);

        self::assertSame(1, $user['id']);
        self::assertSame('alice@example.com', $connection->table('users')->find(1)['email'] ?? null);

        $connection->dropIfExists('users');
        @unlink($path);
    }
}
