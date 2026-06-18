<?php

declare(strict_types=1);

namespace App\Database\Drivers\PostgreSQL;

use App\Database\Contracts\Table;
use App\Database\Drivers\PdoConnection;
use PDO;

final class PostgreSQLConnection extends PdoConnection
{
    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            (string) ($config['host'] ?? '127.0.0.1'),
            (string) ($config['port'] ?? '5432'),
            (string) ($config['database'] ?? 'base'),
        );

        $this->pdo = new PDO($dsn, (string) ($config['username'] ?? 'postgres'), (string) ($config['password'] ?? ''));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function name(): string
    {
        return 'pgsql';
    }

    public function table(string $name): Table
    {
        return new PostgreSQLTable($this->pdo, $name);
    }
}
