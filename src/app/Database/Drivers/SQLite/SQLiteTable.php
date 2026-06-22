<?php

declare(strict_types=1);

namespace App\Database\Drivers\SQLite;

use App\Database\Drivers\PdoJsonTable;
use App\Database\QueryBuilders\PdoJsonQueryBuilder;
use App\Database\QueryBuilders\SQLite\SQLiteQueryBuilder;

final class SQLiteTable extends PdoJsonTable
{
    protected function createQueryBuilder(): PdoJsonQueryBuilder
    {
        return new SQLiteQueryBuilder($this->pdo, $this->table, $this->usesJsonPayload());
    }

    protected function createTableIfMissing(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT NOT NULL)");
    }

    protected function insertAndReturnId(array $data): int
    {
        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (data) VALUES (:data)");
        $statement->execute(['data' => $this->encode($data)]);

        return (int) $this->pdo->lastInsertId();
    }

    protected function columnNames(): array
    {
        $statement = $this->pdo->query("PRAGMA table_info({$this->table})");

        if ($statement === false) {
            return [];
        }

        return array_map(fn (array $row): string => (string) $row['name'], $statement->fetchAll());
    }
}
