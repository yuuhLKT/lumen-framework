<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders;

use App\Database\Contracts\QueryBuilder;
use InvalidArgumentException;

/**
 * Implementação base de um query builder fluente.
 *
 * Reúne o estado comum da consulta (colunas, where, order, limit, etc.) e
 * fornece operações em memória para drivers não-SQL. Classes filhas devem
 * implementar get() e count() de acordo com o driver de banco.
 */
abstract class BaseQueryBuilder implements QueryBuilder
{
    /** @var array<int, string> */
    protected array $columns = ['*'];

    /**
     * @var array<int, array{field: string, operator: string, value: mixed, boolean: string}>
     */
    protected array $wheres = [];

    /** @var array<int, array{field: string, direction: string}> */
    protected array $orders = [];

    /** @var array<int, string> */
    protected array $groups = [];

    /**
     * @var array<int, array{field: string, operator: string, value: mixed, boolean: string}>
     */
    protected array $havings = [];

    /**
     * @var array<int, array{table: string, leftColumn: string, rightColumn: string, type: string, alias: string}>
     */
    protected array $joins = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    /**
     * Define as colunas que devem ser retornadas.
     *
     * @param array<int, string>|string $columns
     */
    public function select(array|string $columns = ['*']): static
    {
        $this->columns = is_array($columns) ? $columns : [$columns];

        return $this;
    }

    /**
     * Adiciona uma condição WHERE com AND.
     */
    public function where(string $field, mixed $value, string $operator = '='): static
    {
        $this->addCondition($this->wheres, $field, $value, $operator, 'AND');

        return $this;
    }

    /**
     * Adiciona uma condição WHERE com OR.
     */
    public function orWhere(string $field, mixed $value, string $operator = '='): static
    {
        $this->addCondition($this->wheres, $field, $value, $operator, 'OR');

        return $this;
    }

    /**
     * Atalho para where($field, $value, '!=').
     */
    public function whereNot(string $field, mixed $value): static
    {
        return $this->where($field, $value, '!=');
    }

    /**
     * Atalho para orWhere($field, $value, '!=').
     */
    public function orWhereNot(string $field, mixed $value): static
    {
        return $this->orWhere($field, $value, '!=');
    }

    /**
     * Filtra valores dentro de uma lista (WHERE IN).
     *
     * @param array<int, mixed> $values
     */
    public function whereIn(string $field, array $values): static
    {
        $this->wheres[] = [
            'field' => $field,
            'operator' => 'IN',
            'value' => array_values($values),
            'boolean' => 'AND',
        ];

        return $this;
    }

    /**
     * Filtra valores fora de uma lista (WHERE NOT IN).
     *
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $field, array $values): static
    {
        $this->wheres[] = [
            'field' => $field,
            'operator' => 'NOT IN',
            'value' => array_values($values),
            'boolean' => 'OR',
        ];

        return $this;
    }

    /**
     * Filtra com padrão LIKE (use % como curinga).
     */
    public function whereLike(string $field, string $value): static
    {
        return $this->where($field, $value, 'LIKE');
    }

    /**
     * Ordena os resultados por uma coluna.
     */
    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Direção [{$direction}] inválida. Use asc ou desc.");
        }

        $this->orders[] = [
            'field' => $field,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Agrupa resultados por uma ou mais colunas.
     *
     * @param array<int, string>|string $columns
     */
    public function groupBy(array|string $columns): static
    {
        $this->groups = array_merge($this->groups, is_array($columns) ? $columns : [$columns]);

        return $this;
    }

    /**
     * Adiciona uma condição HAVING.
     */
    public function having(string $field, mixed $value, string $operator = '='): static
    {
        $this->addCondition($this->havings, $field, $value, $operator, 'AND');

        return $this;
    }

    /**
     * Adiciona um JOIN entre a tabela atual e outra tabela.
     */
    public function join(string $table, string $leftColumn, string $rightColumn, string $type = 'inner'): static
    {
        $type = strtolower($type);
        $allowed = ['inner', 'left', 'right'];

        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException("Tipo de join [{$type}] inválido. Use inner, left ou right.");
        }

        $this->joins[] = [
            'table' => $table,
            'leftColumn' => $leftColumn,
            'rightColumn' => $rightColumn,
            'type' => $type,
            'alias' => 'j' . count($this->joins),
        ];

        return $this;
    }

    /**
     * Atalho para join($table, $leftColumn, $rightColumn, 'left').
     */
    public function leftJoin(string $table, string $leftColumn, string $rightColumn): static
    {
        return $this->join($table, $leftColumn, $rightColumn, 'left');
    }

    /**
     * Atalho para join($table, $leftColumn, $rightColumn, 'right').
     */
    public function rightJoin(string $table, string $leftColumn, string $rightColumn): static
    {
        return $this->join($table, $leftColumn, $rightColumn, 'right');
    }

    /**
     * Define o limite de registros retornados.
     */
    public function limit(int $limit): static
    {
        $this->limit = max(0, $limit);

        return $this;
    }

    /**
     * Define o deslocamento dos resultados.
     */
    public function offset(int $offset): static
    {
        $this->offset = max(0, $offset);

        return $this;
    }

    /**
     * Executa a consulta e retorna o primeiro registro.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * Retorna os resultados paginados com metadados.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function paginate(int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $total = $this->count();

        return [
            'data' => $this->limit($perPage)->offset(($page - 1) * $perPage)->get(),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * Adiciona uma condição genérica em uma lista de condições.
     *
     * @param array<int, array{field: string, operator: string, value: mixed, boolean: string}> $target
     */
    protected function addCondition(
        array &$target,
        string $field,
        mixed $value,
        string $operator,
        string $boolean,
    ): void {
        $operator = strtoupper($operator);
        $allowed = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE'];

        if (!in_array($operator, $allowed, true)) {
            throw new InvalidArgumentException("Operador [{$operator}] não suportado.");
        }

        $target[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];
    }

    /**
     * Verifica se uma linha satisfaz todas as condições WHERE/HAVING.
     *
     * @param array<string, mixed> $row
     * @param array<int, array{field: string, operator: string, value: mixed, boolean: string}> $conditions
     */
    protected function matchesConditions(array $row, array $conditions): bool
    {
        $result = null;

        foreach ($conditions as $condition) {
            $fieldValue = $row[$condition['field']] ?? null;
            $operator = $condition['operator'];
            $expected = $condition['value'];

            $match = match ($operator) {
                '=' => $fieldValue == $expected,
                '!=', '<>' => $fieldValue != $expected,
                '<' => $fieldValue < $expected,
                '>' => $fieldValue > $expected,
                '<=' => $fieldValue <= $expected,
                '>=' => $fieldValue >= $expected,
                'LIKE' => $this->likeMatch((string) $fieldValue, (string) $expected),
                'IN' => in_array($fieldValue, $expected, true),
                'NOT IN' => !in_array($fieldValue, $expected, true),
                default => false,
            };

            if ($result === null) {
                $result = $match;

                continue;
            }

            $result = $condition['boolean'] === 'OR'
                ? ($result || $match)
                : ($result && $match);
        }

        return $result ?? true;
    }

    /**
     * Alias para matchesConditions com as condições WHERE.
     *
     * @param array<string, mixed> $row
     */
    protected function matchesWheres(array $row): bool
    {
        return $this->matchesConditions($row, $this->wheres);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function applyOrder(array $rows): array
    {
        if ($this->orders === []) {
            return $rows;
        }

        usort($rows, function (array $a, array $b): int {
            foreach ($this->orders as $order) {
                $fieldA = $a[$order['field']] ?? null;
                $fieldB = $b[$order['field']] ?? null;

                $comparison = $this->compareValues($fieldA, $fieldB);

                if ($comparison !== 0) {
                    return $order['direction'] === 'DESC' ? -$comparison : $comparison;
                }
            }

            return 0;
        });

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function applyLimitAndOffset(array $rows): array
    {
        $offset = $this->offset ?? 0;
        $limit = $this->limit;

        if ($limit === null) {
            return array_slice($rows, $offset);
        }

        return array_slice($rows, $offset, $limit);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function applySelect(array $rows): array
    {
        if ($this->columns === ['*'] || $this->columns === []) {
            return $rows;
        }

        return array_map(fn (array $row): array => $this->pluckColumns($row), $rows);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function pluckColumns(array $row): array
    {
        $selected = [];

        foreach ($this->columns as $column) {
            if (array_key_exists($column, $row)) {
                $selected[$column] = $row[$column];
            }
        }

        return $selected;
    }

    /**
     * Executa a consulta em memória (usada pelo driver JSON).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function executeInMemory(array $rows): array
    {
        $rows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $this->matchesWheres($row),
        ));

        if ($this->groups !== []) {
            $rows = $this->applyGroupBy($rows);
        }

        $rows = $this->applyOrder($rows);
        $rows = $this->applyLimitAndOffset($rows);

        return $this->applySelect($rows);
    }

    /**
     * Agrupa registros em memória por igualdade dos campos definidos.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function applyGroupBy(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = implode('|', array_map(
                fn (string $column): string => (string) ($row[$column] ?? ''),
                $this->groups,
            ));

            if (!isset($groups[$key])) {
                $groups[$key] = $row;
            }
        }

        $grouped = array_values($groups);

        if ($this->havings !== []) {
            $grouped = array_values(array_filter(
                $grouped,
                fn (array $row): bool => $this->matchesConditions($row, $this->havings),
            ));
        }

        return $grouped;
    }

    private function likeMatch(string $value, string $pattern): bool
    {
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';

        return preg_match($regex, $value) === 1;
    }

    private function compareValues(mixed $a, mixed $b): int
    {
        if ($a === $b) {
            return 0;
        }

        if (is_numeric($a) && is_numeric($b)) {
            return $a <=> $b;
        }

        return strcmp((string) $a, (string) $b);
    }
}
