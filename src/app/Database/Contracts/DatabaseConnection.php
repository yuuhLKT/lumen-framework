<?php

declare(strict_types=1);

namespace App\Database\Contracts;

interface DatabaseConnection
{
    public function name(): string;

    public function table(string $name): Table;

    public function execute(string $sql): void;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function transaction(callable $callback): mixed;
}
