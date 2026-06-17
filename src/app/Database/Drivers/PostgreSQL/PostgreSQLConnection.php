<?php

declare(strict_types=1);

namespace App\Database\Drivers\PostgreSQL;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Contracts\Table;
use PDO;
use Throwable;

final class PostgreSQLConnection implements DatabaseConnection
{
    private PDO $pdo;

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

    public function execute(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
