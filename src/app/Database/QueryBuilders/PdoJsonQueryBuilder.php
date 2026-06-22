<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders;

use PDO;
use PDOStatement;

/**
 * Query builder para drivers PDO que armazenam os dados como JSON na coluna `data`.
 *
 * Cada driver implementa apenas a forma de extrair um valor do JSON
 * (json_extract, JSON_EXTRACT/JSON_UNQUOTE, data->>'campo', etc.).
 */
abstract class PdoJsonQueryBuilder extends BaseQueryBuilder
{
    public function __construct(
        protected readonly PDO $pdo,
        protected readonly string $table,
        protected readonly bool $jsonPayload = true,
    ) {
    }

    /**
     * Retorna a expressão SQL para ler um campo do JSON.
     */
    abstract protected function jsonValueExpression(string $field): string;

    public function get(): array
    {
        $where = $this->buildWhere();
        $having = $this->buildHaving();
        $statement = $this->pdo->prepare($this->buildSql());

        $this->bindValues($statement, $where['bindings']);
        $this->bindValues($statement, $having['bindings']);

        $statement->execute();

        return $this->hydrateRows($statement->fetchAll());
    }

    public function count(): int
    {
        $where = $this->buildWhere();
        $joins = $this->buildJoins();
        $sql = "SELECT COUNT(*) FROM {$this->table} t0{$joins}{$where['sql']}";
        $statement = $this->pdo->prepare($sql);
        $this->bindValues($statement, $where['bindings']);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    /**
     * Monta a instrução SELECT completa.
     */
    protected function buildSql(): string
    {
        $select = $this->buildSelect();
        $joins = $this->buildJoins();
        $where = $this->buildWhere();
        $groupBy = $this->buildGroupBy();
        $having = $this->buildHaving();
        $orderBy = $this->buildOrderBy();
        $limit = $this->buildLimit();

        return "SELECT {$select} FROM {$this->table} t0{$joins}{$where['sql']}{$groupBy}{$having['sql']}{$orderBy}{$limit}";
    }

    /**
     * Monta a lista de colunas do SELECT.
     */
    protected function buildSelect(): string
    {
        if ($this->columns === ['*'] || $this->columns === []) {
            return $this->jsonPayload ? 't0.id, t0.data' : 't0.*';
        }

        $parts = [];

        foreach ($this->columns as $column) {
            $expression = $this->resolveAliasedColumn($column, 't0');
            $parts[] = $column === 'id' ? "{$expression} AS id" : "{$expression} AS {$column}";
        }

        return implode(', ', $parts);
    }

    /**
     * Monta as cláusulas JOIN.
     */
    protected function buildJoins(): string
    {
        if ($this->joins === []) {
            return '';
        }

        $sql = '';

        foreach ($this->joins as $join) {
            $type = strtoupper($join['type']);
            $alias = $join['alias'];
            $table = $join['table'];

            $leftColumn = $this->resolveJoinColumn($join['leftColumn'], 't0', [$table => $alias]);
            $rightColumn = $this->resolveJoinColumn($join['rightColumn'], $alias, [$table => $alias]);

            $sql .= " {$type} JOIN {$table} {$alias} ON {$leftColumn} = {$rightColumn}";
        }

        return $sql;
    }

    /**
     * Monta a cláusula WHERE com AND/OR e placeholders nomeados.
     *
     * @return array{sql: string, bindings: array<string, mixed>}
     */
    protected function buildWhere(): array
    {
        return $this->buildConditions($this->wheres, 'WHERE');
    }

    /**
     * Monta a cláusula HAVING.
     *
     * @return array{sql: string, bindings: array<string, mixed>}
     */
    protected function buildHaving(): array
    {
        return $this->buildConditions($this->havings, 'HAVING');
    }

    /**
     * Monta condições genéricas (WHERE ou HAVING).
     *
     * @param array<int, array{field: string, operator: string, value: mixed, boolean: string}> $conditionsList
     * @return array{sql: string, bindings: array<string, mixed>}
     */
    protected function buildConditions(array $conditionsList, string $clause): array
    {
        if ($conditionsList === []) {
            return ['sql' => '', 'bindings' => []];
        }

        $conditions = [];
        $bindings = [];
        $index = 0;

        foreach ($conditionsList as $condition) {
            $param = strtolower($clause[0]) . $index;
            $field = $condition['field'];
            $operator = $condition['operator'];
            $boolean = $condition['boolean'];

            $column = $this->resolveAliasedColumn($field, 't0');

            if ($operator === 'IN' || $operator === 'NOT IN') {
                $placeholders = [];

                foreach ((array) $condition['value'] as $i => $value) {
                    $placeholder = ":{$param}_{$i}";
                    $placeholders[] = $placeholder;
                    $bindings[$placeholder] = $value;
                }

                $conditions[] = [
                    'sql' => "{$column} {$operator} (" . implode(', ', $placeholders) . ')',
                    'boolean' => $boolean,
                ];
            } else {
                $placeholder = ":{$param}";
                $conditions[] = [
                    'sql' => "{$column} {$operator} {$placeholder}",
                    'boolean' => $boolean,
                ];
                $bindings[$placeholder] = $condition['value'];
            }

            ++$index;
        }

        $sql = ' ' . $clause . ' ';

        foreach ($conditions as $i => $condition) {
            if ($i > 0) {
                $sql .= $condition['boolean'] === 'OR' ? ' OR ' : ' AND ';
            }

            $sql .= $condition['sql'];
        }

        return [
            'sql' => $sql,
            'bindings' => $bindings,
        ];
    }

    /**
     * Monta a cláusula GROUP BY.
     */
    protected function buildGroupBy(): string
    {
        if ($this->groups === []) {
            return '';
        }

        $parts = array_map(
            fn (string $column): string => $this->resolveAliasedColumn($column, 't0'),
            $this->groups,
        );

        return ' GROUP BY ' . implode(', ', $parts);
    }

    /**
     * Monta a cláusula ORDER BY.
     */
    protected function buildOrderBy(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $parts = [];

        foreach ($this->orders as $order) {
            $column = $this->resolveAliasedColumn($order['field'], 't0');
            $parts[] = "{$column} {$order['direction']}";
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }

    /**
     * Monta a cláusula LIMIT/OFFSET.
     */
    protected function buildLimit(): string
    {
        if ($this->limit === null) {
            return '';
        }

        $sql = " LIMIT {$this->limit}";

        if ($this->offset !== null && $this->offset > 0) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Resolve uma coluna qualificada ou simples para uma expressão SQL (sem alias AS).
     *
     * Exemplos:
     *   id          -> t0.id
     *   name        -> json_extract(t0.data, '$.name')
     *   posts.title -> json_extract(j0.data, '$.title')
     */
    protected function resolveAliasedColumn(string $column, string $defaultAlias): string
    {
        $alias = $defaultAlias;
        $field = $column;

        if (str_contains($column, '.')) {
            [$table, $field] = explode('.', $column, 2);
            $alias = $this->aliasForTable($table) ?? $defaultAlias;
        }

        if ($field === 'id') {
            return "{$alias}.id";
        }

        if (!$this->jsonPayload) {
            return "{$alias}.{$field}";
        }

        return $this->jsonValueExpressionForAlias($field, $alias);
    }

    /**
     * Resolve uma coluna para uso em condições JOIN (sem alias AS).
     *
     * @param array<string, string> $joinAliases
     */
    protected function resolveJoinColumn(string $column, string $defaultAlias, array $joinAliases): string
    {
        $alias = $defaultAlias;
        $field = $column;

        if (str_contains($column, '.')) {
            [$table, $field] = explode('.', $column, 2);
            $alias = $joinAliases[$table] ?? $defaultAlias;
        }

        if ($field === 'id') {
            return "{$alias}.id";
        }

        if (!$this->jsonPayload) {
            return "{$alias}.{$field}";
        }

        return $this->jsonValueExpressionForAlias($field, $alias);
    }

    /**
     * Retorna o alias SQL de uma tabela conhecida.
     */
    protected function aliasForTable(string $table): ?string
    {
        if ($table === $this->table) {
            return 't0';
        }

        foreach ($this->joins as $join) {
            if ($join['table'] === $table) {
                return $join['alias'];
            }
        }

        return null;
    }

    /**
     * Extrai um valor JSON prefixado com um alias de tabela.
     */
    protected function jsonValueExpressionForAlias(string $field, string $alias): string
    {
        return str_replace('data', "{$alias}.data", $this->jsonValueExpression($field));
    }

    /**
     * Faz o bind seguro dos valores nos placeholders.
     *
     * @param array<string, mixed> $bindings
     */
    protected function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $statement->bindValue($key, $value, $type);
        }
    }

    /**
     * Hidrata registros quando o SELECT é curinga (*).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function hydrateRows(array $rows): array
    {
        if ($this->columns === ['*'] || $this->columns === []) {
            if (!$this->jsonPayload) {
                return $rows;
            }

            return array_map(fn (array $row): array => $this->hydrate($row), $rows);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function hydrate(array $row): array
    {
        $data = json_decode((string) $row['data'], true);
        $data = is_array($data) ? $data : [];

        return ['id' => (int) $row['id'], ...$data];
    }
}
