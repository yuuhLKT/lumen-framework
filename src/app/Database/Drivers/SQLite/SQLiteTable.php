<?php

declare(strict_types=1);

namespace App\Database\Drivers\SQLite;

use App\Database\Contracts\QueryBuilder;
use App\Database\Contracts\Table;
use App\Database\QueryBuilders\PdoJsonQueryBuilder;
use App\Database\QueryBuilders\SQLite\SQLiteQueryBuilder;
use InvalidArgumentException;
use PDO;

final class SQLiteTable implements Table
{
    private string $table;

    public function __construct(private readonly PDO $pdo, string $name)
    {
        $this->table = $this->validateTableName($name);
        $this->createTableIfMissing();
    }

    public function query(): QueryBuilder
    {
        return $this->createQueryBuilder();
    }

    protected function createQueryBuilder(): PdoJsonQueryBuilder
    {
        return new SQLiteQueryBuilder($this->pdo, $this->table);
    }

    public function all(): array
    {
        $statement = $this->pdo->query("SELECT id, data FROM {$this->table} ORDER BY id ASC");

        if ($statement === false) {
            return [];
        }

        $rows = $statement->fetchAll();

        return array_map(fn (array $row): array => $this->hydrate($row), $rows);
    }

    public function find(int|string $id): ?array
    {
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

        $statement = $this->pdo->prepare("INSERT INTO {$this->table} (data) VALUES (:data)");
        $statement->execute(['data' => $this->encode($data)]);

        return ['id' => (int) $this->pdo->lastInsertId(), ...$data];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array
    {
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
        return array_values(array_filter(
            $this->all(),
            fn (array $row): bool => ($row[$field] ?? null) === $value,
        ));
    }

    private function createTableIfMissing(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table} (id INTEGER PRIMARY KEY AUTOINCREMENT, data TEXT NOT NULL)");
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function hydrate(array $row): array
    {
        $data = json_decode((string) $row['data'], true);
        $data = is_array($data) ? $data : [];

        return ['id' => (int) $row['id'], ...$data];
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? '{}' : $json;
    }

    private function validateTableName(string $name): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException('Nome de tabela invalido. Use apenas letras, numeros e underscore.');
        }

        return $name;
    }
}
