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
            'command' => PHP_BINARY . ' vendor/bin/phpunit --colors=never',
        ],
        'analyse' => [
            'name' => 'analyse',
            'command' => PHP_BINARY . ' vendor/bin/phpstan analyse app routes public bootstrap config tools --level=7 --memory-limit=512M --no-progress',
        ],
        'lint' => [
            'name' => 'lint',
            'command' => PHP_BINARY . ' tools/lint.php',
        ],
        'format' => [
            'name' => 'format',
            'command' => PHP_BINARY . ' vendor/bin/php-cs-fixer fix --allow-risky=yes',
        ],
        'format-check' => [
            'name' => 'format-check',
            'command' => PHP_BINARY . ' vendor/bin/php-cs-fixer fix --allow-risky=yes --dry-run --diff',
        ],
        'qa' => [
            'name' => 'qa',
            'command' => '',
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
            'qa' => 'Executa lint, format-check, analyse e test.',
            default => 'Comando de qualidade.',
        };
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        if ($this->type === 'qa') {
            foreach (['lint', 'format-check', 'analyse', 'test'] as $type) {
                echo "\n> {$type}\n";
                $exitCode = (new self($type))->run([]);

                if ($exitCode !== 0) {
                    return $exitCode;
                }
            }

            return 0;
        }

        $command = self::$types[$this->type]['command'];

        passthru($command, $exitCode);

        return $exitCode;
    }
}
