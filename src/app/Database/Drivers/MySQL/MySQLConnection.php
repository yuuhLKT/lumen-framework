<?php

declare(strict_types=1);

namespace App\Database\Drivers\MySQL;

use App\Database\Contracts\Table;
use App\Database\Drivers\PdoConnection;
use PDO;

final class MySQLConnection extends PdoConnection
{
    /** @param array<string, mixed> $config */
    public function __construct(array $config)
    {
        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            (string) ($config['host'] ?? '127.0.0.1'),
            (string) ($config['port'] ?? '3306'),
            (string) ($config['database'] ?? 'base'),
            $charset,
        );

        $this->pdo = new PDO($dsn, (string) ($config['username'] ?? 'root'), (string) ($config['password'] ?? ''));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function name(): string
    {
        return 'mysql';
    }

    public function table(string $name): Table
    {
        return new MySQLTable($this->pdo, $name);
    }
}
