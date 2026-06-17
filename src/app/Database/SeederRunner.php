<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Contracts\DatabaseConnection;
use RuntimeException;

final class SeederRunner
{
    public function __construct(private readonly DatabaseConnection $connection)
    {
    }

    /** @return array<int, string> */
    public function run(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        $ran = [];

        foreach ($files as $file) {
            $name = basename($file);
            $seeder = require $file;

            if (!is_callable($seeder)) {
                throw new RuntimeException("Seeder [{$name}] deve retornar uma função.");
            }

            $this->connection->transaction(fn () => $seeder($this->connection));
            $ran[] = $name;
        }

        return $ran;
    }
}
