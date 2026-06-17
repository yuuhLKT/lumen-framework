<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Database\MigrationRunner;

final class MigrationsListCommand implements Command
{
    public function name(): string
    {
        return 'migrations:list';
    }

    public function description(): string
    {
        return 'Lista migrations pendentes e as ultimas executadas.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $limit = isset($args[0]) && is_numeric($args[0]) ? (int) $args[0] : 3;
        $runner = new MigrationRunner(db());
        $status = $runner->status(base_path('database/migrations'), $limit);

        echo 'Migrations encontradas: ' . count($status['all']) . "\n";
        echo 'Executadas: ' . count($status['executed']) . "\n";
        echo 'Pendentes: ' . count($status['pending']) . "\n\n";

        if ($status['pending'] !== []) {
            echo "Pendentes:\n";

            foreach ($status['pending'] as $name) {
                echo "  - {$name}\n";
            }

            echo "\n";
        }

        if ($status['lastExecuted'] !== []) {
            echo "Ultimas {$limit} executadas:\n";

            foreach ($status['lastExecuted'] as $name) {
                echo "  - {$name}\n";
            }

            echo "\n";
        }

        if ($status['all'] !== [] && $status['pending'] === [] && $status['lastExecuted'] === []) {
            echo "Nenhuma migration executada ainda.\n\n";
        }

        return 0;
    }
}
