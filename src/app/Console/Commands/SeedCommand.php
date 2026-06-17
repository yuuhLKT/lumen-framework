<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use App\Database\SeederRunner;

final class SeedCommand implements Command
{
    public function name(): string
    {
        return 'seed';
    }

    public function description(): string
    {
        return 'Executa os seeders.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $runner = new SeederRunner(db());
        $ran = $runner->run(base_path('database/seeders'));

        echo $ran === []
            ? "Nenhum seeder encontrado.\n"
            : "Seeders executados:\n- " . implode("\n- ", $ran) . "\n";

        return 0;
    }
}
