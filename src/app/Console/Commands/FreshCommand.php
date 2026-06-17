<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class FreshCommand implements Command
{
    public function name(): string
    {
        return 'fresh';
    }

    public function description(): string
    {
        return 'Executa migrations e seeders (fresh).';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $migrate = new MigrateCommand();
        $seed = new SeedCommand();

        $migrate->run([]);
        $seed->run([]);

        return 0;
    }
}
