<?php

declare(strict_types=1);

namespace App\Database\Schema;

final class ColumnDefinition
{
    public bool $nullable = false;

    public bool $unique = false;

    public bool $index = false;

    public bool $primary = false;

    public bool $autoIncrement = false;

    public bool $unsigned = false;

    public mixed $default = null;

    public bool $hasDefault = false;

    public ?string $after = null;

    public ?ForeignKeyDefinition $foreign = null;

    /** @param array<string, mixed> $options */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $options = [],
    ) {
    }

    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;

        return $this;
    }

    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    public function index(): self
    {
        $this->index = true;

        return $this;
    }

    public function primary(): self
    {
        $this->primary = true;

        return $this;
    }

    public function autoIncrement(): self
    {
        $this->autoIncrement = true;

        return $this;
    }

    public function unsigned(): self
    {
        $this->unsigned = true;

        return $this;
    }

    public function after(string $column): self
    {
        $this->after = $column;

        return $this;
    }

    public function constrained(?string $table = null, string $column = 'id'): self
    {
        $table ??= str_ends_with($this->name, '_id') ? substr($this->name, 0, -3) . 's' : $this->name . 's';
        $this->foreign = new ForeignKeyDefinition($this->name, $table, $column);

        return $this;
    }

    public function references(string $column): ForeignKeyDefinition
    {
        $this->foreign = new ForeignKeyDefinition($this->name, '', $column);

        return $this->foreign;
    }
}
