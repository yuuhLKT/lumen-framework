<?php

declare(strict_types=1);

namespace App\Database\Relations;

/**
 * Relacionamento pertence-a.
 *
 * Exemplo: um post pertence a um usuário.
 * foreignKey = 'user_id' (campo no registro atual).
 */
final class BelongsTo extends Relation
{
    /**
     * @param array<string, mixed> $child
     */
    public function for(array $child): static
    {
        return $this->forId($child[$this->foreignKey] ?? null);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        if ($this->parentValue() === null) {
            return null;
        }

        return $this->related->find($this->parentValue());
    }
}
