<?php

declare(strict_types=1);

namespace App\Database\Drivers\MySQL;

use App\Database\Drivers\PdoJsonTable;

final class MySQLTable extends PdoJsonTable
{
    protected function createTableIfMissing(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, data JSON NOT NULL)");
    }

    protected function insertAndReturnId(array $data): int
    {
        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (data) VALUES (:data)");
        $statement->execute(['data' => $this->encode($data)]);

        return (int) $this->pdo->lastInsertId();
    }
}
