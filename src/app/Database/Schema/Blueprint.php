<?php

declare(strict_types=1);

namespace App\Database\Schema;

final class Blueprint
{
    /** @var array<int, ColumnDefinition> */
    private array $columns = [];

    /** @var array<int, array{type: string, columns: array<int, string>, name: string|null}> */
    private array $indexes = [];

    public function __construct(public readonly string $table)
    {
    }

    /** @return array<int, ColumnDefinition> */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return array<int, array{type: string, columns: array<int, string>, name: string|null}> */
    public function indexes(): array
    {
        return $this->indexes;
    }

    public function id(string $column = 'id'): ColumnDefinition
    {
        return $this->bigIncrements($column);
    }

    public function increments(string $column): ColumnDefinition
    {
        return $this->add($column, 'integer')->unsigned()->autoIncrement()->primary();
    }

    public function bigIncrements(string $column): ColumnDefinition
    {
        return $this->add($column, 'bigInteger')->unsigned()->autoIncrement()->primary();
    }

    public function foreignId(string $column): ColumnDefinition
    {
        return $this->unsignedBigInteger($column);
    }

    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->add($column, 'string', ['length' => $length]);
    }

    public function char(string $column, int $length = 255): ColumnDefinition
    {
        return $this->add($column, 'char', ['length' => $length]);
    }

    public function text(string $column): ColumnDefinition
    {
        return $this->add($column, 'text');
    }

    public function mediumText(string $column): ColumnDefinition
    {
        return $this->add($column, 'mediumText');
    }

    public function longText(string $column): ColumnDefinition
    {
        return $this->add($column, 'longText');
    }

    public function integer(string $column): ColumnDefinition
    {
        return $this->add($column, 'integer');
    }

    public function unsignedInteger(string $column): ColumnDefinition
    {
        return $this->integer($column)->unsigned();
    }

    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->add($column, 'bigInteger');
    }

    public function unsignedBigInteger(string $column): ColumnDefinition
    {
        return $this->bigInteger($column)->unsigned();
    }

    public function smallInteger(string $column): ColumnDefinition
    {
        return $this->add($column, 'smallInteger');
    }

    public function tinyInteger(string $column): ColumnDefinition
    {
        return $this->add($column, 'tinyInteger');
    }

    public function boolean(string $column): ColumnDefinition
    {
        return $this->add($column, 'boolean');
    }

    public function decimal(string $column, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->add($column, 'decimal', ['total' => $total, 'places' => $places]);
    }

    public function float(string $column): ColumnDefinition
    {
        return $this->add($column, 'float');
    }

    public function double(string $column): ColumnDefinition
    {
        return $this->add($column, 'double');
    }

    public function json(string $column): ColumnDefinition
    {
        return $this->add($column, 'json');
    }

    public function date(string $column): ColumnDefinition
    {
        return $this->add($column, 'date');
    }

    public function dateTime(string $column): ColumnDefinition
    {
        return $this->add($column, 'dateTime');
    }

    public function timestamp(string $column): ColumnDefinition
    {
        return $this->add($column, 'timestamp');
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function uuid(string $column): ColumnDefinition
    {
        return $this->add($column, 'uuid');
    }

    public function binary(string $column): ColumnDefinition
    {
        return $this->add($column, 'binary');
    }

    /** @param array<int, string>|string $columns */
    public function unique(array|string $columns, ?string $name = null): void
    {
        $this->indexes[] = ['type' => 'unique', 'columns' => (array) $columns, 'name' => $name];
    }

    /** @param array<int, string>|string $columns */
    public function index(array|string $columns, ?string $name = null): void
    {
        $this->indexes[] = ['type' => 'index', 'columns' => (array) $columns, 'name' => $name];
    }

    /** @param array<string, mixed> $options */
    private function add(string $column, string $type, array $options = []): ColumnDefinition
    {
        $definition = new ColumnDefinition($column, $type, $options);
        $this->columns[] = $definition;

        return $definition;
    }
}
