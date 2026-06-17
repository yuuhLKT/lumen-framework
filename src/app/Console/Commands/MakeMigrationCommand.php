<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use RuntimeException;

final class MakeMigrationCommand implements Command
{
    public function name(): string
    {
        return 'make:migration';
    }

    public function description(): string
    {
        return 'Cria um novo arquivo de migration.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $name = $args[0] ?? '';

        if ($name === '') {
            fwrite(STDERR, "Informe o nome da migration.\n");

            return 1;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $dir = base_path('database/migrations');
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException("Nao foi possivel criar o diretorio [{$dir}].");
        }

        $stub = <<<'PHP'
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return [
    'up' => function (DatabaseConnection $connection): void {
        // $connection->table('example')->insert([...]);
    },
    'down' => function (DatabaseConnection $connection): void {
        // $connection->table('example')->delete(...);
    },
];
PHP;

        file_put_contents($path, $stub);

        echo "Criada: {$path}\n";

        return 0;
    }
}
