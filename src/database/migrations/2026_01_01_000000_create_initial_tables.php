<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return [
    'up' => function (DatabaseConnection $db): void {
        $users = $db->table('users');
        $tokens = $db->table('auth_tokens');
    },
    'down' => function (DatabaseConnection $db): void {
        // Tabelas JSON/SQLite nao suportam DROP direto via Table.
        // Para MySQL/PostgreSQL, remova manualmente se necessario.
    },
];
