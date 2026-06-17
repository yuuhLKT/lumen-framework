<?php

declare(strict_types=1);

namespace App\Database;

use App\Database\Contracts\DatabaseConnection;
use RuntimeException;

final class MigrationRunner
{
    public function __construct(private readonly DatabaseConnection $connection)
    {
    }

    /** @return array<int, string> */
    public function run(string $path): array
    {
        $files = $this->files($path);
        $executed = $this->executedMigrations();
        $ran = [];

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                continue;
            }

            $migration = require $file;

            if (!is_callable($migration)) {
                throw new RuntimeException("Migration [{$name}] deve retornar uma função.");
            }

            $this->connection->transaction(function () use ($migration, $name): void {
                $migration($this->connection);
                $this->connection->table('migrations')->insert([
                    'name' => $name,
                    'executed_at' => date(DATE_ATOM),
                ]);
            });

            $ran[] = $name;
        }

        return $ran;
    }

    /** @return array<int, string> */
    private function files(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $files = glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        return $files;
    }

    /** @return array<int, string> */
    private function executedMigrations(): array
    {
        return array_map(
            fn (array $row): string => (string) ($row['name'] ?? ''),
            $this->connection->table('migrations')->all(),
        );
    }
}
