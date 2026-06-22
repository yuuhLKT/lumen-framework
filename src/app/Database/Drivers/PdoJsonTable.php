<?php

declare(strict_types=1);

namespace App\Database\Drivers;

use App\Database\Contracts\QueryBuilder;
use App\Database\Contracts\Table;
use App\Database\QueryBuilders\PdoJsonQueryBuilder;
use InvalidArgumentException;
use PDO;

abstract class PdoJsonTable implements Table
{
    protected string $table;

    public function __construct(protected readonly PDO $pdo, string $name)
    {
        $this->table = $this->validateTableName($name);
        $this->createTableIfMissing();
    }

    public function query(): QueryBuilder
    {
        return $this->createQueryBuilder();
    }

    abstract protected function createQueryBuilder(): PdoJsonQueryBuilder;

    public function all(): array
    {
        if (!$this->usesJsonPayload()) {
            $statement = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY id ASC");

            if ($statement === false) {
                return [];
            }

            return $statement->fetchAll();
        }

        $statement = $this->pdo->query("SELECT id, data FROM {$this->table} ORDER BY id ASC");

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return array_map(fn (array $row): array => $this->hydrate($row), $rows);
    }

    public function find(int|string $id): ?array
    {
        if (!$this->usesJsonPayload()) {
            $statement = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $row : null;
        }

        $statement = $this->pdo->prepare("SELECT id, data FROM {$this->table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->hydrate($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function insert(array $data): array
    {
        unset($data['id']);

        if (!$this->usesJsonPayload()) {
            $id = $this->insertColumnsAndReturnId($data);

            return $this->find($id) ?? ['id' => $id, ...$data];
        }

        $id = $this->insertAndReturnId($data);

        return ['id' => $id, ...$data];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array
    {
        if (!$this->usesJsonPayload()) {
            $current = $this->find($id);

            if ($current === null) {
                return null;
            }

            unset($data['id']);

            if ($data === []) {
                return $current;
            }

            $columns = $this->realColumnNames();
            $assignments = [];
            $bindings = ['id' => $id];

            foreach ($data as $column => $value) {
                if (!in_array($column, $columns, true)) {
                    continue;
                }

                $assignments[] = "{$column} = :{$column}";
                $bindings[$column] = $this->normalizeValue($value);
            }

            if ($assignments === []) {
                return $current;
            }

            $statement = $this->pdo->prepare("UPDATE {$this->table} SET " . implode(', ', $assignments) . ' WHERE id = :id');
            $statement->execute($bindings);

            return $this->find($id);
        }

        $current = $this->find($id);

        if ($current === null) {
            return null;
        }

        unset($current['id'], $data['id']);

        $updated = [...$current, ...$data];
        $statement = $this->pdo->prepare("UPDATE {$this->table} SET data = :data WHERE id = :id");
        $statement->execute([
            'id' => $id,
            'data' => $this->encode($updated),
        ]);

        return ['id' => (int) $id, ...$updated];
    }

    public function delete(int|string $id): bool
    {
        $statement = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function where(string $field, mixed $value): array
    {
        if (!$this->usesJsonPayload()) {
            $this->validateTableName($field);
            $statement = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$field} = :value ORDER BY id ASC");
            $statement->execute(['value' => $this->normalizeValue($value)]);

            return $statement->fetchAll();
        }

        return array_values(array_filter(
            $this->all(),
            fn (array $row): bool => ($row[$field] ?? null) === $value,
        ));
    }

    abstract protected function createTableIfMissing(): void;

    /** @param array<string, mixed> $data */
    abstract protected function insertAndReturnId(array $data): int;

    /** @return array<int, string> */
    abstract protected function columnNames(): array;

    /** @param array<string, mixed> $data */
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
            $statement = $this->pdo->prepare("INSERT INTO {$this->table} DEFAULT VALUES");
            $statement->execute();

            return (int) $this->pdo->lastInsertId();
        }

        $placeholders = array_map(fn (string $column): string => ':' . $column, $insert);
        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (" . implode(', ', $insert) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $statement->execute($bindings);

        return (int) $this->pdo->lastInsertId();
    }

    protected function usesJsonPayload(): bool
    {
        $columns = $this->columnNames();

        return in_array('data', $columns, true) && count($columns) <= 2;
    }

    /** @return array<int, string> */
    protected function realColumnNames(): array
    {
        return array_values(array_filter(
            $this->columnNames(),
            fn (string $column): bool => $column !== 'id',
        ));
    }

    protected function normalizeValue(mixed $value): mixed
    {
        if (is_array($value) || is_object($value)) {
            return $this->encode((array) $value);
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function hydrate(array $row): array
    {
        $data = json_decode((string) $row['data'], true);
        $data = is_array($data) ? $data : [];

        return ['id' => (int) $row['id'], ...$data];
    }

    /** @param array<string, mixed> $data */
    protected function encode(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '{}' : $json;
    }

    protected function validateTableName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Nome de tabela invalido. Use apenas letras, numeros e underscore.');
        }

        return $name;
    }
}
