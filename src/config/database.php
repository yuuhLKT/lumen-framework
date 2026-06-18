<?php

declare(strict_types=1);

return [
    // Use DB_CONNECTION=json, sqlite, mysql, pgsql ou postgres.
    'default' => env('DB_CONNECTION', 'json'),

    'connections' => [
        'json' => [
            'path' => path_from_base((string) env('DB_JSON_PATH', 'storage/database.json')),
        ],

        'sqlite' => [
            'path' => path_from_base((string) env('DB_SQLITE_PATH', 'storage/database.sqlite')),
        ],

        'mysql' => [
            'host' => env('DB_MYSQL_HOST', '127.0.0.1'),
            'port' => env('DB_MYSQL_PORT', '3306'),
            'database' => env('DB_MYSQL_DATABASE', 'base'),
            'username' => env('DB_MYSQL_USERNAME', 'root'),
            'password' => env('DB_MYSQL_PASSWORD', ''),
            'charset' => env('DB_MYSQL_CHARSET', 'utf8mb4'),
        ],

        'pgsql' => [
            'host' => env('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => env('DB_PGSQL_PORT', '5432'),
            'database' => env('DB_PGSQL_DATABASE', 'base'),
            'username' => env('DB_PGSQL_USERNAME', 'postgres'),
            'password' => env('DB_PGSQL_PASSWORD', ''),
        ],
    ],
];
