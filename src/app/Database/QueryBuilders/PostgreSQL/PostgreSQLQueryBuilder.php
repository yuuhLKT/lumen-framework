<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders\PostgreSQL;

use App\Database\QueryBuilders\PdoJsonQueryBuilder;

final class PostgreSQLQueryBuilder extends PdoJsonQueryBuilder
{
    protected function jsonValueExpression(string $field): string
    {
        return "data->>'{$field}'";
    }
}
