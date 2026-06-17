<?php

declare(strict_types=1);

namespace App\Database\Drivers\SQLite;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Contracts\Table;
use PDO;
use RuntimeException;
use Throwable;

final class SQLiteConnection implements DatabaseConnection
{
    private PDO $pdo;

    public function __construct(private readonly string $path)
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Nao foi possivel criar a pasta [{$directory}].");
        }

        $this->pdo = new PDO('sqlite:' . $this->path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function name(): string
    {
        return 'sqlite';
    }

    public function table(string $name): Table
    {
        return new SQLiteTable($this->pdo, $name);
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
