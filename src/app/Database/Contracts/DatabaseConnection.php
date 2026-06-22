<?php

declare(strict_types=1);

namespace App\Database\Contracts;

interface DatabaseConnection
{
    public function name(): string;

    public function table(string $name): Table;

    public function create(string $name, callable $callback): void;

    public function alter(string $name, callable $callback): void;

    public function drop(string $name): void;

    public function dropIfExists(string $name): void;

    public function execute(string $sql): void;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function transaction(callable $callback): mixed;
}
