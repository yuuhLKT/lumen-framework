<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use InvalidArgumentException;
use RuntimeException;

final class MakeCommand implements Command
{
    private ?string $type;

    private ?string $targetDir = null;

    private ?string $namespace = null;

    private ?string $suffix = null;

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
        'dto' => [
            'dir' => 'app/DTO',
            'namespace' => 'App\\DTO',
            'suffix' => 'DTO',
        ],
        'test' => [
            'dir' => 'tests',
            'namespace' => 'Tests',
            'suffix' => 'Test',
        ],
    ];

    public function __construct(?string $type = null)
    {
        $this->type = $type;

        if ($type === null) {
            return;
        }

        if (!isset(self::$types[$type])) {
            throw new InvalidArgumentException("Tipo de make [{$type}] invalido.");
        }

        $config = self::$types[$type];
        $this->targetDir = $config['dir'];
        $this->namespace = $config['namespace'];
        $this->suffix = $config['suffix'];
    }

    public function name(): string
    {
        if ($this->type === null) {
            return 'make';
        }

        return 'make:' . $this->type;
    }

    public function description(): string
    {
        if ($this->type === null) {
            return 'Agrupa os geradores e permite criar varios arquivos.';
        }

        return "Cria um novo {$this->type}.";
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        if ($this->type === null) {
            return $this->runInteractive($args);
        }

        $name = $args[0] ?? '';

        if ($name === '') {
            fwrite(STDERR, "Informe o nome do {$this->type}.\n");

            return 1;
        }

        $name = $this->normalizeName($name);
        $path = base_path((string) $this->targetDir) . DIRECTORY_SEPARATOR . $name . '.php';

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
        $suffix = (string) $this->suffix;
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);

        if (!str_ends_with(strtolower($name), strtolower($suffix))) {
            $name .= $suffix;
        }

        return $name;
    }

    private function stub(string $name): string
    {
        $namespace = (string) $this->namespace;

        return match ($this->type) {
            'repository' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nfinal class {$name} extends BaseRepository\n{\n    protected string \$table = '';\n}\n",
            'controller' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse App\\Core\\Controller;\n\nfinal class {$name} extends Controller\n{\n    //\n}\n",
            'middleware' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse App\\Core\\Request;\nuse App\\Core\\Response;\nuse Closure;\n\nfinal class {$name} implements Middleware\n{\n    public function handle(Request \$request, Closure \$next): Response\n    {\n        return \$next(\$request);\n    }\n}\n",
            'dto' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nfinal readonly class {$name} extends BaseDTO\n{\n}\n",
            'test' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$namespace};\n\nuse PHPUnit\\Framework\\TestCase;\n\nfinal class {$name} extends TestCase\n{\n    public function testExample(): void\n    {\n        self::assertTrue(true);\n    }\n}\n",
            default => throw new InvalidArgumentException("Stub nao implementado para [{$this->type}]."),
        };
    }

    /**
     * @param array<int, string> $args
     */
    private function runInteractive(array $args): int
    {
        $selectedTypes = $args === [] ? $this->askTypes() : $this->parseTypes($args);

        if ($selectedTypes === []) {
            fwrite(STDERR, "Nenhum generator selecionado.\n");

            return 1;
        }

        $exitCode = 0;

        foreach ($selectedTypes as $type) {
            $name = $this->askName($type);

            if ($name === '') {
                fwrite(STDERR, "Nome nao informado para {$type}.\n");
                $exitCode = 1;
                continue;
            }

            $command = $type === 'migration' ? new MakeMigrationCommand() : new self($type);
            $result = $command->run([$name]);

            if ($result !== 0) {
                $exitCode = $result;
            }
        }

        return $exitCode;
    }

    /**
     * @return array<int, string>
     */
    private function askTypes(): array
    {
        $availableTypes = $this->availableTypes();

        echo "O que voce quer criar?\n";

        foreach ($availableTypes as $index => $type) {
            echo sprintf("  %d) %s\n", $index + 1, $type);
        }

        echo 'Selecione um ou mais numeros separados por virgula: ';

        return $this->parseTypes([trim((string) fgets(STDIN))]);
    }

    /**
     * @param array<int, string> $args
     * @return array<int, string>
     */
    private function parseTypes(array $args): array
    {
        $availableTypes = $this->availableTypes();
        $selectedTypes = [];

        foreach ($args as $arg) {
            foreach (explode(',', $arg) as $rawType) {
                $rawType = strtolower(trim($rawType));

                if ($rawType === '') {
                    continue;
                }

                $type = ctype_digit($rawType)
                    ? ($availableTypes[((int) $rawType) - 1] ?? null)
                    : $rawType;

                if ($type === null || !in_array($type, $availableTypes, true)) {
                    fwrite(STDERR, "Generator invalido: {$rawType}\n");
                    continue;
                }

                if (!in_array($type, $selectedTypes, true)) {
                    $selectedTypes[] = $type;
                }
            }
        }

        return $selectedTypes;
    }

    private function askName(string $type): string
    {
        echo "Nome para {$type}: ";

        return trim((string) fgets(STDIN));
    }

    /**
     * @return array<int, string>
     */
    private function availableTypes(): array
    {
        return [...array_keys(self::$types), 'migration'];
    }
}
