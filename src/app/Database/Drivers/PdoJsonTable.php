<?php

declare(strict_types=1);

namespace App\Database\Drivers;

use App\Database\Contracts\Table;
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

    public function all(): array
    {
        $statement = $this->pdo->query("SELECT id, data FROM {$this->table} ORDER BY id ASC");

        return array_map(fn (array $row): array => $this->hydrate($row), $statement->fetchAll());
    }

    public function find(int|string $id): ?array
    {
        $statement = $this->pdo->prepare("SELECT id, data FROM {$this->table} WHERE id = :id LIMIT 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function insert(array $data): array
    {
        unset($data['id']);

        $id = $this->insertAndReturnId($data);

        return ['id' => $id, ...$data];
    }

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

    abstract protected function createTableIfMissing(): void;

    /** @param array<string, mixed> $data */
    abstract protected function insertAndReturnId(array $data): int;

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
