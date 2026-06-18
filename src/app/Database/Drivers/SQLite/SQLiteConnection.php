<?php

declare(strict_types=1);

namespace App\Database\Drivers\SQLite;

use App\Database\Contracts\Table;
use App\Database\Drivers\PdoConnection;
use PDO;
use RuntimeException;

final class SQLiteConnection extends PdoConnection
{
    public function __construct(private readonly string $path)
    {
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException("Não foi possível criar a pasta [{$directory}].");
        }

        $this->pdo = new PDO('sqlite:' . $this->path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function name(): string
    {
        return 'sqlite';
    }

    public function table(string $name): Table
    {
        return new SQLiteTable($this->pdo, $name);
    }
}
