<?php

declare(strict_types=1);

namespace App\Database\Drivers;

use App\Database\Contracts\DatabaseConnection;
use App\Database\Schema\Blueprint;
use App\Database\Schema\ColumnDefinition;
use PDO;
use RuntimeException;
use Throwable;

abstract class PdoConnection implements DatabaseConnection
{
    protected PDO $pdo;

    public function execute(string $sql): void
    {
        $this->pdo->exec($sql);
    }

    public function create(string $name, callable $callback): void
    {
        $blueprint = new Blueprint($this->validateIdentifier($name));
        $callback($blueprint);

        $columns = array_map(
            fn (ColumnDefinition $column): string => $this->compileColumn($column),
            $blueprint->columns(),
        );

        foreach ($blueprint->columns() as $column) {
            if ($column->foreign !== null) {
                $columns[] = $this->compileForeignKey($column);
            }
        }

        $this->execute("CREATE TABLE {$blueprint->table} (" . implode(', ', $columns) . ')');

        $this->createIndexes($blueprint);
    }

    public function alter(string $name, callable $callback): void
    {
        $blueprint = new Blueprint($this->validateIdentifier($name));
        $callback($blueprint);

        foreach ($blueprint->columns() as $column) {
            $this->execute("ALTER TABLE {$blueprint->table} ADD COLUMN " . $this->compileColumn($column));
        }

        $this->createIndexes($blueprint);
    }

    public function drop(string $name): void
    {
        $this->execute('DROP TABLE ' . $this->validateIdentifier($name));
    }

    public function dropIfExists(string $name): void
    {
        $this->execute('DROP TABLE IF EXISTS ' . $this->validateIdentifier($name));
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }

    protected function compileColumn(ColumnDefinition $column): string
    {
        $name = $this->validateIdentifier($column->name);

        if ($column->autoIncrement && $column->primary) {
            return "{$name} " . $this->autoIncrementType($column);
        }

        $sql = "{$name} " . $this->columnType($column);

        if ($column->primary) {
            $sql .= ' PRIMARY KEY';
        }

        if (!$column->nullable && !$column->primary) {
            $sql .= ' NOT NULL';
        }

        if ($column->unique) {
            $sql .= ' UNIQUE';
        }

        if ($column->hasDefault) {
            $sql .= ' DEFAULT ' . $this->defaultValue($column->default);
        }

        return $sql;
    }

    protected function autoIncrementType(ColumnDefinition $column): string
    {
        return match ($this->name()) {
            'mysql' => $column->type === 'bigInteger' ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => $column->type === 'bigInteger' ? 'BIGSERIAL PRIMARY KEY' : 'SERIAL PRIMARY KEY',
            default => 'INTEGER PRIMARY KEY AUTOINCREMENT',
        };
    }

    protected function columnType(ColumnDefinition $column): string
    {
        $unsigned = $this->name() === 'mysql' && $column->unsigned ? ' UNSIGNED' : '';

        return match ($column->type) {
            'string' => 'VARCHAR(' . (int) $column->options['length'] . ')',
            'char' => 'CHAR(' . (int) $column->options['length'] . ')',
            'text' => 'TEXT',
            'mediumText' => $this->name() === 'mysql' ? 'MEDIUMTEXT' : 'TEXT',
            'longText' => $this->name() === 'mysql' ? 'LONGTEXT' : 'TEXT',
            'integer' => ($this->name() === 'pgsql' ? 'INTEGER' : 'INT') . $unsigned,
            'bigInteger' => 'BIGINT' . $unsigned,
            'smallInteger' => 'SMALLINT' . $unsigned,
            'tinyInteger' => ($this->name() === 'mysql' ? 'TINYINT' : 'SMALLINT') . $unsigned,
            'boolean' => $this->name() === 'mysql' ? 'TINYINT(1)' : 'BOOLEAN',
            'decimal' => 'DECIMAL(' . (int) $column->options['total'] . ', ' . (int) $column->options['places'] . ')',
            'float' => $this->name() === 'pgsql' ? 'REAL' : 'FLOAT',
            'double' => $this->name() === 'pgsql' ? 'DOUBLE PRECISION' : 'DOUBLE',
            'json' => match ($this->name()) {
                'mysql' => 'JSON',
                'pgsql' => 'JSONB',
                default => 'TEXT',
            },
            'date' => 'DATE',
            'dateTime' => $this->name() === 'pgsql' ? 'TIMESTAMP' : 'DATETIME',
            'timestamp' => 'TIMESTAMP',
            'uuid' => $this->name() === 'pgsql' ? 'UUID' : 'CHAR(36)',
            'binary' => $this->name() === 'pgsql' ? 'BYTEA' : ($this->name() === 'mysql' ? 'BLOB' : 'BLOB'),
            default => throw new RuntimeException("Tipo de coluna [{$column->type}] nao suportado."),
        };
    }

    protected function compileForeignKey(ColumnDefinition $column): string
    {
        $foreign = $column->foreign;

        if ($foreign === null || $foreign->table === '') {
            throw new RuntimeException("Chave estrangeira da coluna [{$column->name}] sem tabela de destino.");
        }

        $sql = 'FOREIGN KEY (' . $this->validateIdentifier($foreign->column) . ') REFERENCES '
            . $this->validateIdentifier($foreign->table) . ' (' . $this->validateIdentifier($foreign->references) . ')';

        if ($foreign->onDelete !== null) {
            $sql .= ' ON DELETE ' . $foreign->onDelete;
        }

        if ($foreign->onUpdate !== null) {
            $sql .= ' ON UPDATE ' . $foreign->onUpdate;
        }

        return $sql;
    }

    protected function createIndexes(Blueprint $blueprint): void
    {
        foreach ($blueprint->columns() as $column) {
            if ($column->index) {
                $this->createIndex($blueprint->table, [$column->name], 'index', null);
            }
        }

        foreach ($blueprint->indexes() as $index) {
            $this->createIndex($blueprint->table, $index['columns'], $index['type'], $index['name']);
        }
    }

    /** @param array<int, string> $columns */
    protected function createIndex(string $table, array $columns, string $type, ?string $name): void
    {
        $safeTable = $this->validateIdentifier($table);
        $safeColumns = array_map(fn (string $column): string => $this->validateIdentifier($column), $columns);
        $name ??= $safeTable . '_' . implode('_', $safeColumns) . '_' . $type;
        $unique = $type === 'unique' ? 'UNIQUE ' : '';

        $this->execute("CREATE {$unique}INDEX " . $this->validateIdentifier($name) . " ON {$safeTable} (" . implode(', ', $safeColumns) . ')');
    }

    protected function defaultValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $upper = strtoupper((string) $value);

        if (in_array($upper, ['CURRENT_TIMESTAMP', 'CURRENT_DATE', 'CURRENT_TIME'], true)) {
            return $upper;
        }

        return $this->pdo->quote((string) $value) ?: "'" . str_replace("'", "''", (string) $value) . "'";
    }

    protected function validateIdentifier(string $identifier): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new RuntimeException('Identificador de banco invalido. Use apenas letras, numeros e underscore.');
        }

        return $identifier;
    }
}
