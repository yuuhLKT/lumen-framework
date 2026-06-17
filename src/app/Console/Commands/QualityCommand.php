<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;
use InvalidArgumentException;

final class QualityCommand implements Command
{
    private string $type;

    /** @var array<string, array{name: string, command: string}> */
    private static array $types = [
        'test' => [
            'name' => 'test',
            'command' => '.\vendor\bin\phpunit --colors=never',
        ],
        'analyse' => [
            'name' => 'analyse',
            'command' => '.\vendor\bin\phpstan analyse app routes public bootstrap tools --level=7 --memory-limit=512M --no-progress',
        ],
        'lint' => [
            'name' => 'lint',
            'command' => 'php tools/lint.php',
        ],
        'format' => [
            'name' => 'format',
            'command' => '.\vendor\bin\php-cs-fixer fix --allow-risky=yes',
        ],
        'format-check' => [
            'name' => 'format-check',
            'command' => '.\vendor\bin\php-cs-fixer fix --allow-risky=yes --dry-run --diff',
        ],
    ];

    public function __construct(string $type)
    {
        if (!isset(self::$types[$type])) {
            throw new InvalidArgumentException("Tipo de quality [{$type}] invalido.");
        }

        $this->type = $type;
    }

    public function name(): string
    {
        return self::$types[$this->type]['name'];
    }

    public function description(): string
    {
        return match ($this->type) {
            'test' => 'Executa os testes PHPUnit.',
            'analyse' => 'Executa o PHPStan.',
            'lint' => 'Executa o lint proprio.',
            'format' => 'Formata o codigo com PHP CS Fixer.',
            'format-check' => 'Verifica formatacao com PHP CS Fixer (dry-run).',
            default => 'Comando de qualidade.',
        };
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        $command = self::$types[$this->type]['command'];

        passthru($command, $exitCode);

        return $exitCode;
    }
}
