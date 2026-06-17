<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders\SQLite;

use App\Database\QueryBuilders\PdoJsonQueryBuilder;

final class SQLiteQueryBuilder extends PdoJsonQueryBuilder
{
    protected function jsonValueExpression(string $field): string
    {
        return "json_extract(data, '$.{$field}')";
    }
}
