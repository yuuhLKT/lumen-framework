<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Contracts\Table;
use App\Database\Database;
use InvalidArgumentException;

abstract class BaseRepository
{
    protected string $table = '';

    private ?Table $tableInstance = null;

    public function __construct(private readonly ?DatabaseConnection $connection = null)
    {
        if ($this->table === '') {
            throw new InvalidArgumentException('Defina a propriedade $table no repository.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function findAll(): array
    {
        return $this->getTable()->all();
    }

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array
    {
        return $this->getTable()->find($id);
    }

    /** @return array<string, mixed>|null */
    public function findOne(string $field, mixed $value): ?array
    {
        return $this->where($field, $value)[0] ?? null;
    }

    /** @param array<string, mixed> $criteria */
    public function findBy(array $criteria): ?array
    {
        return $this->whereAll($criteria)[0] ?? null;
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data): array
    {
        return $this->getTable()->insert($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int|string $id, array $data): ?array
    {
        return $this->getTable()->update($id, $data);
    }

    public function delete(int|string $id): bool
    {
        return $this->getTable()->delete($id);
    }

    /** @return array<int, array<string, mixed>> */
    public function where(string $field, mixed $value): array
    {
        return $this->getTable()->where($field, $value);
    }

    public function exists(string $field, mixed $value): bool
    {
        return $this->findOne($field, $value) !== null;
    }

    /** @param array<string, mixed> $criteria */
    public function whereAll(array $criteria): array
    {
        return array_values(array_filter(
            $this->findAll(),
            fn (array $row): bool => $this->matches($row, $criteria),
        ));
    }

    public function count(): int
    {
        return count($this->findAll());
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        return $this->findAll()[0] ?? null;
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $rows = $this->findAll();
        $total = count($rows);

        return [
            'data' => array_slice($rows, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    protected function getTable(): Table
    {
        return $this->tableInstance ??= ($this->connection ?? Database::connection())->table($this->table);
    }

    /** @param array<string, mixed> $criteria */
    private function matches(array $row, array $criteria): bool
    {
        foreach ($criteria as $field => $value) {
            if (($row[$field] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }
}
