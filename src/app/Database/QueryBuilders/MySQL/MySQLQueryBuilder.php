<?php

declare(strict_types=1);

namespace App\Database\QueryBuilders\MySQL;

use App\Database\QueryBuilders\PdoJsonQueryBuilder;

final class MySQLQueryBuilder extends PdoJsonQueryBuilder
{
    protected function jsonValueExpression(string $field): string
    {
        return "JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$field}'))";
    }
}
