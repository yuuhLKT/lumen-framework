<?php

declare(strict_types=1);

namespace App\Database\Schema;

final class ForeignKeyDefinition
{
    public ?string $onDelete = null;

    public ?string $onUpdate = null;

    public function __construct(
        public readonly string $column,
        public string $table,
        public readonly string $references = 'id',
    ) {
    }

    public function on(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = strtoupper($action);

        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = strtoupper($action);

        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('cascade');
    }

    public function restrictOnDelete(): self
    {
        return $this->onDelete('restrict');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('set null');
    }

    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('cascade');
    }
}
