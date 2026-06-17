<?php

declare(strict_types=1);

namespace App\Console;

final class Application
{
    /** @var array<string, Command> */
    private array $commands = [];

    public function register(Command $command): self
    {
        $this->commands[$command->name()] = $command;

        return $this;
    }

    /**
     * @param array<int, string> $argv
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';
        $args = array_slice($argv, 2);

        if ($name === '--help' || $name === '-h') {
            $name = 'list';
        }

        if (!isset($this->commands[$name])) {
            fwrite(STDERR, "Comando desconhecido: {$name}\n");
            fwrite(STDERR, "Rode 'php base list' para ver os comandos disponiveis.\n");

            return 1;
        }

        return $this->commands[$name]->run($args);
    }

    /**
     * @return array<string, Command>
     */
    public function commands(): array
    {
        return $this->commands;
    }
}
