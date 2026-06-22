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
        echo "Lumen PHP - CLI\n\n";
        echo "Comandos disponiveis:\n\n";

        $groups = [];
        $groupNames = [];

        foreach (array_keys($this->app->commands()) as $name) {
            if (str_contains($name, ':')) {
                $groupNames[] = explode(':', $name, 2)[0];
            }
        }

        foreach ($this->app->commands() as $name => $command) {
            if ($name === 'list') {
                continue;
            }

            $group = str_contains($name, ':') || in_array($name, $groupNames, true)
                ? explode(':', $name, 2)[0]
                : 'geral';
            $groups[$group][$name] = $command;
        }

        foreach ($groups as $group => $commands) {
            echo "{$group}:\n";

            foreach ($commands as $name => $command) {
                echo sprintf("  %-20s %s\n", $name, $command->description());
            }

            echo "\n";
        }

        return 0;
    }
}
