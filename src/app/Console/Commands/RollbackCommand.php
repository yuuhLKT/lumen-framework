<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Database\MigrationRunner;

final class RollbackCommand implements Command
{
    public function name(): string
    {
        return 'migrate:rollback';
    }

    public function description(): string
    {
        return 'Reverte as ultimas migrations executadas.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $steps = isset($args[0]) && is_numeric($args[0]) ? (int) $args[0] : 1;
        $runner = new MigrationRunner(db());
        $reverted = $runner->rollback(base_path('database/migrations'), $steps);

        echo $reverted === []
            ? "Nenhuma migration para reverter.\n"
            : "Migrations revertidas:\n- " . implode("\n- ", $reverted) . "\n";

        return 0;
    }
}
