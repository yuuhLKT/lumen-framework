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
        $this->ensureMigrationsTable();
        $files = $this->files($path);
        $executed = $this->executedMigrations();
        $ran = [];

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $executed, true)) {
                continue;
            }

            $migration = $this->loadMigration($file, $name);

            $this->connection->transaction(function () use ($migration, $name): void {
                $migration['up']($this->connection);
                $this->connection->table('migrations')->insert([
                    'name' => $name,
                    'executed_at' => date(DATE_ATOM),
                ]);
            });

            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Reverte as ultimas migrations executadas.
     *
     * @return array<int, string>
     */
    public function rollback(string $path, int $steps = 1): array
    {
        $this->ensureMigrationsTable();
        $executed = $this->executedMigrations();

        if ($executed === []) {
            return [];
        }

        $toRevert = array_slice(array_reverse($executed), 0, $steps);
        $reverted = [];

        foreach ($toRevert as $name) {
            $file = $path . DIRECTORY_SEPARATOR . $name;

            if (!is_file($file)) {
                throw new RuntimeException("Arquivo da migration [{$name}] nao encontrado.");
            }

            $migration = $this->loadMigration($file, $name);

            if ($migration['down'] === null) {
                continue;
            }

            $this->connection->transaction(function () use ($migration, $name): void {
                $migration['down']($this->connection);
                $this->connection->table('migrations')->delete(
                    (int) ($this->connection->table('migrations')->where('name', $name)[0]['id'] ?? 0),
                );
            });

            $reverted[] = $name;
        }

        return $reverted;
    }

    /**
     * @return array{up: callable, down: callable|null}
     */
    private function loadMigration(string $file, string $name): array
    {
        $migration = require $file;

        if (is_callable($migration)) {
            return ['up' => $migration, 'down' => null];
        }

        if (is_array($migration) && is_callable($migration['up'] ?? null)) {
            return [
                'up' => $migration['up'],
                'down' => is_callable($migration['down'] ?? null) ? $migration['down'] : null,
            ];
        }

        throw new RuntimeException("Migration [{$name}] deve retornar uma funcao ou um array com 'up'.");
    }

    /**
     * Retorna o status das migrations.
     *
     * @return array{all: array<int, string>, executed: array<int, string>, pending: array<int, string>, lastExecuted: array<int, string>}
     */
    public function status(string $path, int $lastLimit = 3): array
    {
        $this->ensureMigrationsTable();
        $all = array_map(
            fn (string $file): string => basename($file),
            $this->files($path),
        );

        $executed = $this->executedMigrations();
        $pending = array_values(array_diff($all, $executed));
        $lastExecuted = array_slice(array_reverse($executed), 0, $lastLimit);

        return [
            'all' => $all,
            'executed' => $executed,
            'pending' => $pending,
            'lastExecuted' => $lastExecuted,
        ];
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

    private function ensureMigrationsTable(): void
    {
        $this->connection->table('migrations');
    }
}
