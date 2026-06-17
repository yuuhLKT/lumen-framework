<?php

declare(strict_types=1);

namespace App\Database\Contracts;

/**
 * Contrato para construção de consultas fluentes sobre uma tabela.
 *
 * Todas as implementações devem permitir encadear métodos, retornando a própria
 * instância (static) até a execução via get(), first(), count() ou paginate().
 */
interface QueryBuilder
{
    /**
     * Define as colunas que devem ser retornadas.
     *
     * @param array<int, string>|string $columns Coluna(s) desejadas. Use ['*'] para todas.
     */
    public function select(array|string $columns = ['*']): static;

    /**
     * Adiciona uma condição WHERE com operador opcional.
     *
     * Operadores suportados: =, !=, <>, <, >, <=, >=, LIKE.
     */
    public function where(string $field, mixed $value, string $operator = '='): static;

    /**
     * Adiciona uma condição WHERE com OR.
     */
    public function orWhere(string $field, mixed $value, string $operator = '='): static;

    /**
     * Atalho para where($field, $value, '!=').
     */
    public function whereNot(string $field, mixed $value): static;

    /**
     * Atalho para orWhere($field, $value, '!=').
     */
    public function orWhereNot(string $field, mixed $value): static;

    /**
     * Filtra valores dentro de uma lista.
     *
     * @param array<int, mixed> $values
     */
    public function whereIn(string $field, array $values): static;

    /**
     * Filtra valores fora de uma lista.
     *
     * @param array<int, mixed> $values
     */
    public function whereNotIn(string $field, array $values): static;

    /**
     * Filtra com padrão LIKE (use % como curinga).
     */
    public function whereLike(string $field, string $value): static;

    /**
     * Ordena os resultados.
     *
     * @param string $direction asc ou desc
     */
    public function orderBy(string $field, string $direction = 'asc'): static;

    /**
     * Agrupa resultados por uma ou mais colunas.
     *
     * @param array<int, string>|string $columns
     */
    public function groupBy(array|string $columns): static;

    /**
     * Adiciona uma condição HAVING.
     *
     * @param mixed $value
     */
    public function having(string $field, mixed $value, string $operator = '='): static;

    /**
     * Define o limite de registros retornados.
     */
    public function limit(int $limit): static;

    /**
     * Define o deslocamento dos resultados.
     */
    public function offset(int $offset): static;

    /**
     * Adiciona um JOIN entre a tabela atual e outra tabela.
     *
     * @param string $table Nome da tabela a ser unida.
     * @param string $leftColumn Coluna da tabela atual (ex: users.id ou id).
     * @param string $rightColumn Coluna da tabela unida (ex: posts.user_id ou user_id).
     * @param string $type Tipo do join: inner, left, right.
     */
    public function join(string $table, string $leftColumn, string $rightColumn, string $type = 'inner'): static;

    /**
     * Atalho para join($table, $leftColumn, $rightColumn, 'left').
     */
    public function leftJoin(string $table, string $leftColumn, string $rightColumn): static;

    /**
     * Atalho para join($table, $leftColumn, $rightColumn, 'right').
     */
    public function rightJoin(string $table, string $leftColumn, string $rightColumn): static;

    /**
     * Executa a consulta e retorna todos os registros.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array;

    /**
     * Executa a consulta e retorna o primeiro registro.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array;

    /**
     * Retorna a quantidade de registros que atendem aos filtros.
     */
    public function count(): int;

    /**
     * Retorna os resultados paginados.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function paginate(int $page = 1, int $perPage = 15): array;
}
