<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Drivers\Json\JsonConnection;
use App\Database\Drivers\MySQL\MySQLConnection;
use App\Database\Drivers\PostgreSQL\PostgreSQLConnection;
use App\Database\Drivers\SQLite\SQLiteConnection;
use InvalidArgumentException;

final class Database
{
    /** @var array<string, DatabaseConnection> */
    private static array $connections = [];

    public static function connection(?string $name = null): DatabaseConnection
    {
        $name ??= (string) config('database.default', 'json');
        $name = $name === 'postgres' ? 'pgsql' : $name;

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $connectionConfig = config('database.connections.' . $name);

        if (!is_array($connectionConfig)) {
            throw new InvalidArgumentException("Conexão de banco [{$name}] não configurada.");
        }

        return self::$connections[$name] = match ($name) {
            'json' => new JsonConnection((string) $connectionConfig['path']),
            'sqlite' => new SQLiteConnection((string) $connectionConfig['path']),
            'mysql' => new MySQLConnection($connectionConfig),
            'pgsql' => new PostgreSQLConnection($connectionConfig),
            default => throw new InvalidArgumentException("Driver de banco [{$name}] não suportado."),
        };
    }
}
