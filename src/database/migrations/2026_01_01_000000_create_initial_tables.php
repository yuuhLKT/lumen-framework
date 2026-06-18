<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return [
    'up' => function (DatabaseConnection $db): void {
        // Os drivers desta base criam a tabela quando ela e acessada.
        $db->table('users');
        $db->table('auth_tokens');
    },
    'down' => function (DatabaseConnection $db): void {
        // Tabelas JSON/SQLite nao suportam DROP direto via Table.
        // Para MySQL/PostgreSQL, remova manualmente se necessario.
    },
];
