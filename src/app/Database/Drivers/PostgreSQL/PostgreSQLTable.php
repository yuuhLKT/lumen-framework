<?php

declare(strict_types=1);

namespace App\Database\Drivers\PostgreSQL;

use App\Database\Drivers\PdoJsonTable;
use App\Database\QueryBuilders\PdoJsonQueryBuilder;
use App\Database\QueryBuilders\PostgreSQL\PostgreSQLQueryBuilder;

final class PostgreSQLTable extends PdoJsonTable
{
    protected function createQueryBuilder(): PdoJsonQueryBuilder
    {
        return new PostgreSQLQueryBuilder($this->pdo, $this->table);
    }

    protected function createTableIfMissing(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (id SERIAL PRIMARY KEY, data JSONB NOT NULL)");
    }

    protected function insertAndReturnId(array $data): int
    {
        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (data) VALUES (:data) RETURNING id");
        $statement->execute(['data' => $this->encode($data)]);

        return (int) $statement->fetchColumn();
    }
}
