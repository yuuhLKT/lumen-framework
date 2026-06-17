<?php

declare(strict_types=1);

namespace App\Database\Drivers\Json;

use App\Database\Contracts\QueryBuilder;
use App\Database\Contracts\Table;
use App\Database\QueryBuilders\ArrayQueryBuilder;

final class JsonTable implements Table
{
    public function __construct(
        private readonly JsonConnection $connection,
        private readonly string $name,
    ) {
    }

    public function query(): QueryBuilder
    {
        return new ArrayQueryBuilder($this->connection, $this->name);
    }

    public function all(): array
    {
        $database = $this->connection->read();

        return array_values($database[$this->name] ?? []);
    }

    public function find(int|string $id): ?array
    {
        foreach ($this->all() as $row) {
            if ((string) ($row['id'] ?? '') === (string) $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function insert(array $data): array
    {
        $database = $this->connection->read();
        $rows = $database[$this->name] ?? [];

        unset($data['id']);

        $row = ['id' => $this->nextId($rows), ...$data];
        $rows[] = $row;
        $database[$this->name] = $rows;

        $this->connection->write($database);

        return $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array
    {
        $database = $this->connection->read();
        $rows = $database[$this->name] ?? [];

        foreach ($rows as $index => $row) {
            if ((string) ($row['id'] ?? '') !== (string) $id) {
                continue;
            }

            unset($data['id']);

            $rows[$index] = [...$row, ...$data, 'id' => $row['id']];
            $database[$this->name] = $rows;
            $this->connection->write($database);

            return $rows[$index];
        }

        return null;
    }

    public function delete(int|string $id): bool
    {
        $database = $this->connection->read();
        $rows = $database[$this->name] ?? [];
        $remaining = array_values(array_filter(
            $rows,
            fn (array $row): bool => (string) ($row['id'] ?? '') !== (string) $id,
        ));

        if (count($remaining) === count($rows)) {
            return false;
        }

        $database[$this->name] = $remaining;
        $this->connection->write($database);

        return true;
    }

    public function where(string $field, mixed $value): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $row): bool => ($row[$field] ?? null) === $value,
        ));
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function nextId(array $rows): int
    {
        $ids = array_map(fn (array $row): int => (int) ($row['id'] ?? 0), $rows);

        return (empty($ids) ? 0 : max($ids)) + 1;
    }
}
