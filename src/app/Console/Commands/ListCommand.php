<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Application;
use App\Console\Command;

final class ListCommand implements Command
{
    public function __construct(private readonly Application $app)
    {
    }

    public function name(): string
    {
        return 'list';
    }

    public function description(): string
    {
        return 'Lista todos os comandos disponiveis.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        echo "Base PHP - CLI\n\n";
        echo "Comandos disponiveis:\n\n";

        foreach ($this->app->commands() as $name => $command) {
            if ($name === 'list') {
                continue;
            }

            echo sprintf("  %-20s %s\n", $name, $command->description());
        }

        echo "\n";

        return 0;
    }
}
