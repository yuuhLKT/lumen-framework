<?php

declare(strict_types=1);

namespace App\Database\Relations;

use App\Repositories\BaseRepository;

/**
 * Relacionamento muitos-para-muitos via tabela pivô.
 *
 * Exemplo: usuários e roles.
 * pivotTable = 'role_user'
 * foreignKey = 'user_id' (chave do modelo atual na pivô)
 * relatedKey = 'role_id' (chave do modelo relacionado na pivô)
 */
final class BelongsToMany extends Relation
{
    public function __construct(
        BaseRepository $related,
        private readonly BaseRepository $pivot,
        string $foreignKey,
        private readonly string $relatedKey,
    ) {
        parent::__construct($related, $foreignKey);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        if ($this->parentValue() === null) {
            return [];
        }

        $pivotRows = $this->pivot->query()
            ->where($this->foreignKey, $this->parentValue())
            ->get();

        $relatedIds = array_map(
            fn (array $row): int|string => $row[$this->relatedKey] ?? 0,
            $pivotRows,
        );

        if ($relatedIds === []) {
            return [];
        }

        return $this->related->query()
            ->whereIn('id', $relatedIds)
            ->get();
    }

    /**
     * Cria um relacionamento na tabela pivô.
     */
    public function attach(int|string $relatedId): void
    {
        if ($this->parentValue() === null) {
            return;
        }

        $this->pivot->insert([
            $this->foreignKey => $this->parentValue(),
            $this->relatedKey => $relatedId,
        ]);
    }

    /**
     * Remove um relacionamento da tabela pivô.
     */
    public function detach(int|string $relatedId): void
    {
        if ($this->parentValue() === null) {
            return;
        }

        $rows = $this->pivot->query()
            ->where($this->foreignKey, $this->parentValue())
            ->where($this->relatedKey, $relatedId)
            ->get();

        foreach ($rows as $row) {
            if (isset($row['id'])) {
                $this->pivot->delete($row['id']);
            }
        }
    }

    /**
     * Substitui os relacionamentos pelos ids informados.
     *
     * @param array<int, int|string> $relatedIds
     */
    public function sync(array $relatedIds): void
    {
        if ($this->parentValue() === null) {
            return;
        }

        $currentRows = $this->pivot->query()
            ->where($this->foreignKey, $this->parentValue())
            ->get();

        foreach ($currentRows as $row) {
            if (isset($row['id'])) {
                $this->pivot->delete($row['id']);
            }
        }

        foreach ($relatedIds as $relatedId) {
            $this->pivot->insert([
                $this->foreignKey => $this->parentValue(),
                $this->relatedKey => $relatedId,
            ]);
        }
    }
}
