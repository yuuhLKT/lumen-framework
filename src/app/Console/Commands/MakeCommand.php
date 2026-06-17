<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use InvalidArgumentException;
use RuntimeException;

final class MakeCommand implements Command
{
    private string $type;

    private string $targetDir;

    private string $namespace;

    private string $suffix;

    /** @var array<string, array{dir: string, namespace: string, suffix: string}> */
    private static array $types = [
        'repository' => [
            'dir' => 'app/Repositories',
            'namespace' => 'App\\Repositories',
            'suffix' => 'Repository',
        ],
        'controller' => [
            'dir' => 'app/Controllers',
            'namespace' => 'App\\Controllers',
            'suffix' => 'Controller',
        ],
        'middleware' => [
            'dir' => 'app/Http/Middleware',
            'namespace' => 'App\\Http\\Middleware',
            'suffix' => 'Middleware',
        ],
    ];

    public function __construct(string $type)
    {
        if (!isset(self::$types[$type])) {
            throw new InvalidArgumentException("Tipo de make [{$type}] invalido.");
        }

        $config = self::$types[$type];
        $this->type = $type;
        $this->targetDir = $config['dir'];
        $this->namespace = $config['namespace'];
        $this->suffix = $config['suffix'];
    }

    public function name(): string
    {
        return 'make:' . $this->type;
    }

    public function description(): string
    {
        return "Cria um novo {$this->type}.";
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $name = $args[0] ?? '';

        if ($name === '') {
            fwrite(STDERR, "Informe o nome do {$this->type}.\n");

            return 1;
        }

        $name = $this->normalizeName($name);
        $path = base_path($this->targetDir) . DIRECTORY_SEPARATOR . $name . '.php';

        if (is_file($path)) {
            fwrite(STDERR, "Arquivo ja existe: {$path}\n");

            return 1;
        }

        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException("Nao foi possivel criar o diretorio [{$dir}].");
        }

        $content = $this->stub($name);

        file_put_contents($path, $content);

        echo "Criado: {$path}\n";

        return 0;
    }

    private function normalizeName(string $name): string
    {
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);

        if (!str_ends_with($name, $this->suffix)) {
            $name .= $this->suffix;
        }

        return $name;
    }

    private function stub(string $name): string
    {
        return match ($this->type) {
            'repository' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->namespace};\n\nfinal class {$name} extends BaseRepository\n{\n    protected string \$table = '';\n}\n",
            'controller' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->namespace};\n\nuse App\\Core\\Controller;\n\nfinal class {$name} extends Controller\n{\n    //\n}\n",
            'middleware' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$this->namespace};\n\nuse App\\Core\\Request;\nuse App\\Core\\Response;\nuse Closure;\n\nfinal class {$name} implements Middleware\n{\n    public function handle(Request \$request, Closure \$next): Response\n    {\n        return \$next(\$request);\n    }\n}\n",
            default => throw new InvalidArgumentException("Stub nao implementado para [{$this->type}]."),
        };
    }
}
