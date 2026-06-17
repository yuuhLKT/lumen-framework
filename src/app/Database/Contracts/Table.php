<?php

declare(strict_types=1);

namespace App\Database\Contracts;

interface Table
{
    public function query(): QueryBuilder;

    /** @return array<int, array<string, mixed>> */
    public function all(): array;

    /** @return array<string, mixed>|null */
    public function find(int|string $id): ?array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function insert(array $data): array;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int|string $id, array $data): ?array;

    public function delete(int|string $id): bool;

    /** @return array<int, array<string, mixed>> */
    public function where(string $field, mixed $value): array;
}
