<?php

declare(strict_types=1);

namespace App\Database\Drivers;

use App\Database\Contracts\DatabaseConnection;
use PDO;
use Throwable;

abstract class PdoConnection implements DatabaseConnection
{
    protected PDO $pdo;

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
}
