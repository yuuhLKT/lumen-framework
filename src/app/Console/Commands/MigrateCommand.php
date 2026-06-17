<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Database\MigrationRunner;

final class MigrateCommand implements Command
{
    public function name(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Executa as migrations pendentes.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $runner = new MigrationRunner(db());
        $ran = $runner->run(base_path('database/migrations'));

        echo $ran === []
            ? "Nenhuma migration pendente.\n"
            : "Migrations executadas:\n- " . implode("\n- ", $ran) . "\n";

        return 0;
    }
}
