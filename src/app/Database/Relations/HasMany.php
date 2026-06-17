<?php

declare(strict_types=1);

namespace App\Database\Relations;

/**
 * Relacionamento um-para-muitos.
 *
 * Exemplo: um usuário tem muitos posts.
 * foreignKey = 'user_id' (campo no repository relacionado).
 */
final class HasMany extends Relation
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $data[$this->foreignKey] = $this->parentValue();

        return $this->related->insert($data);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        if ($this->parentValue() === null) {
            return [];
        }

        return $this->related->query()
            ->where($this->foreignKey, $this->parentValue())
            ->get();
    }
}
