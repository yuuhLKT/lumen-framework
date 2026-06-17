<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Contracts\QueryBuilder;
use App\Database\Contracts\Table;
use App\Database\Database;
use App\Database\Relations\BelongsTo;
use App\Database\Relations\BelongsToMany;
use App\Database\Relations\HasMany;
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

    public function query(): QueryBuilder
    {
        return $this->getTable()->query();
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

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|null
     */
    public function findBy(array $criteria): ?array
    {
        return $this->whereAll($criteria)[0] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function insert(array $data): array
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
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
        return $this->query()->where($field, $value)->get();
    }

    public function exists(string $field, mixed $value): bool
    {
        return $this->findOne($field, $value) !== null;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<int, array<string, mixed>>
     */
    public function whereAll(array $criteria): array
    {
        $query = $this->query();

        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->get();
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /** @return array<string, mixed>|null */
    public function first(): ?array
    {
        return $this->query()->orderBy('id')->first();
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        return $this->query()->orderBy('id')->paginate($page, $perPage);
    }

    /**
     * @param class-string<BaseRepository> $relatedClass
     */
    protected function hasMany(string $relatedClass, string $foreignKey): HasMany
    {
        return new HasMany($this->resolveRepository($relatedClass), $foreignKey);
    }

    /**
     * @param class-string<BaseRepository> $relatedClass
     */
    protected function belongsTo(string $relatedClass, string $foreignKey): BelongsTo
    {
        return new BelongsTo($this->resolveRepository($relatedClass), $foreignKey);
    }

    /**
     * @param class-string<BaseRepository> $relatedClass
     * @param class-string<BaseRepository> $pivotClass
     */
    protected function belongsToMany(
        string $relatedClass,
        string $pivotClass,
        string $foreignKey,
        string $relatedKey,
    ): BelongsToMany {
        return new BelongsToMany(
            $this->resolveRepository($relatedClass),
            $this->resolveRepository($pivotClass),
            $foreignKey,
            $relatedKey,
        );
    }

    protected function getTable(): Table
    {
        return $this->tableInstance ??= ($this->connection ?? Database::connection())->table($this->table);
    }

    /**
     * @param class-string<BaseRepository> $class
     */
    private function resolveRepository(string $class): BaseRepository
    {
        return new $class($this->connection);
    }
}
