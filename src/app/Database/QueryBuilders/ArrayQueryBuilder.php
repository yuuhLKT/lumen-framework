<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders;

use App\Database\Drivers\Json\JsonConnection;

/**
 * Query builder para o driver JSON (armazenamento em arquivo).
 *
 * Todas as operações são executadas em memória sobre o array da tabela.
 */
final class ArrayQueryBuilder extends BaseQueryBuilder
{
    public function __construct(
        private readonly JsonConnection $connection,
        private readonly string $table,
    ) {
    }

    public function get(): array
    {
        $rows = $this->loadRows();
        $rows = $this->applyJoins($rows);

        return $this->executeInMemory($rows);
    }

    public function count(): int
    {
        $rows = $this->loadRows();
        $rows = $this->applyJoins($rows);

        return count(array_filter($rows, fn (array $row): bool => $this->matchesWheres($row)));
    }

    /**
     * Carrega as linhas da tabela principal.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadRows(): array
    {
        $database = $this->connection->read();

        return array_values($database[$this->table] ?? []);
    }

    /**
     * Aplica os joins configurados sobre as linhas em memória.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function applyJoins(array $rows): array
    {
        foreach ($this->joins as $join) {
            $database = $this->connection->read();
            $otherRows = array_values($database[$join['table']] ?? []);
            $rows = $this->joinRows($rows, $otherRows, $join);
        }

        return $rows;
    }

    /**
     * Executa um único join em memória.
     *
     * @param array<int, array<string, mixed>> $leftRows
     * @param array<int, array<string, mixed>> $rightRows
     * @param array{table: string, leftColumn: string, rightColumn: string, type: string, alias: string} $join
     * @return array<int, array<string, mixed>>
     */
    private function joinRows(array $leftRows, array $rightRows, array $join): array
    {
        $type = $join['type'];
        $prefix = $join['table'] . '_';

        if ($type === 'right') {
            [$leftRows, $rightRows] = [$rightRows, $leftRows];
            $type = 'left';
        }

        $result = [];

        foreach ($leftRows as $leftRow) {
            $leftValue = $this->resolveColumnValue($leftRow, $join['leftColumn']);
            $matched = false;

            foreach ($rightRows as $rightRow) {
                $rightValue = $this->resolveColumnValue($rightRow, $join['rightColumn']);

                if ((string) $leftValue === (string) $rightValue) {
                    $result[] = [...$leftRow, ...$this->prefixKeys($rightRow, $prefix)];
                    $matched = true;
                }
            }

            if (!$matched && $type === 'left') {
                $result[] = [...$leftRow, ...$this->nullPrefixed($rightRows[0] ?? [], $prefix)];
            }
        }

        return $result;
    }

    /**
     * Retorna o valor de uma coluna, ignorando prefixo de tabela se presente.
     *
     * @param array<string, mixed> $row
     */
    private function resolveColumnValue(array $row, string $column): mixed
    {
        $parts = explode('.', $column);
        $key = end($parts);

        return $row[$key] ?? null;
    }

    /**
     * Adiciona um prefixo em todas as chaves do array.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function prefixKeys(array $row, string $prefix): array
    {
        $prefixed = [];

        foreach ($row as $key => $value) {
            $prefixed[$prefix . $key] = $value;
        }

        return $prefixed;
    }

    /**
     * Cria um array com as mesmas chaves prefixadas, mas valores null.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function nullPrefixed(array $row, string $prefix): array
    {
        $result = [];

        foreach (array_keys($row) as $key) {
            $result[$prefix . $key] = null;
        }

        return $result;
    }
}
