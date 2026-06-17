<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class ServeCommand implements Command
{
    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Inicia o servidor embutido do PHP.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';
        $address = "{$host}:{$port}";

        echo "Servidor iniciado em http://{$address}\n";
        echo "Pressione Ctrl+C para parar.\n\n";

        passthru(PHP_BINARY . ' -S ' . escapeshellarg($address) . ' -t public', $exitCode);

        return $exitCode;
    }
}
