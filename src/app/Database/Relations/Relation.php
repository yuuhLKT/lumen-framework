<?php

declare(strict_types=1);

namespace App\Database\Relations;

use App\Repositories\BaseRepository;

/**
 * Classe base para relacionamentos entre repositories.
 */
abstract class Relation
{
    protected mixed $parentId = null;

    public function __construct(
        protected readonly BaseRepository $related,
        protected readonly string $foreignKey,
        protected readonly string $ownerKey = 'id',
    ) {
    }

    /**
     * Define o registro pai a partir de um array.
     *
     * @param array<string, mixed> $parent
     */
    public function for(array $parent): static
    {
        return $this->forId($parent[$this->ownerKey] ?? null);
    }

    /**
     * Define o id do registro pai.
     */
    public function forId(int|string|null $id): static
    {
        $clone = clone $this;
        $clone->parentId = $id;

        return $clone;
    }

    /**
     * Retorna os registros relacionados.
     *
     * Cada tipo de relacionamento define seu próprio formato:
     * HasMany/BelongsToMany retornam array<int, array<string, mixed>>;
     * BelongsTo retorna array<string, mixed>|null.
     *
     * @return mixed
     */
    abstract public function get(): mixed;

    /**
     * @return mixed
     */
    public function all(): mixed
    {
        return $this->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $result = $this->get();

        if ($result === null) {
            return null;
        }

        if (is_array($result) && array_is_list($result)) {
            return $result[0] ?? null;
        }

        return is_array($result) ? $result : null;
    }

    public function count(): int
    {
        return count($this->get());
    }

    protected function parentValue(): mixed
    {
        return $this->parentId;
    }
}
