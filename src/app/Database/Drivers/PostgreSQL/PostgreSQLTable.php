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
        return new PostgreSQLQueryBuilder($this->pdo, $this->table, $this->usesJsonPayload());
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

    protected function insertColumnsAndReturnId(array $data): int
    {
        $columns = $this->realColumnNames();
        $insert = [];
        $bindings = [];

        foreach ($data as $column => $value) {
            if (!in_array($column, $columns, true)) {
                continue;
            }

            $insert[] = $column;
            $bindings[$column] = $this->normalizeValue($value);
        }

        if ($insert === []) {
            $statement = $this->pdo->prepare("INSERT INTO {$this->table} DEFAULT VALUES RETURNING id");
            $statement->execute();

            return (int) $statement->fetchColumn();
        }

        $placeholders = array_map(fn (string $column): string => ':' . $column, $insert);
        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (" . implode(', ', $insert) . ') VALUES (' . implode(', ', $placeholders) . ') RETURNING id');
        $statement->execute($bindings);

        return (int) $statement->fetchColumn();
    }

    protected function columnNames(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = :table ORDER BY ordinal_position',
        );
        $statement->execute(['table' => $this->table]);

        return array_map(fn (array $row): string => (string) $row['column_name'], $statement->fetchAll());
    }
}
