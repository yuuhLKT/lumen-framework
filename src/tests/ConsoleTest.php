<?php

declare(strict_types=1);

namespace Tests;

use App\Console\Application;
use App\Console\Command;
use App\Console\Commands\ListCommand;
use App\Console\Commands\QualityCommand;
use PHPUnit\Framework\TestCase;

final class ConsoleTest extends TestCase
{
    public function testApplicationRunsRegisteredCommand(): void
    {
        $app = new Application();
        $app->register(new class () implements Command {
            public function name(): string
            {
                return 'hello';
            }

            public function description(): string
            {
                return 'Says hello.';
            }

            public function run(array $args): int
            {
                echo 'hello ' . ($args[0] ?? 'world');

                return 0;
            }
        });

        ob_start();
        $exitCode = $app->run(['base.php', 'hello', 'Yuri']);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode);
        self::assertSame('hello Yuri', $output);
    }

    public function testApplicationReturnsErrorForUnknownCommand(): void
    {
        $app = new Application();

        $exitCode = $app->run(['base.php', 'missing']);

        self::assertSame(1, $exitCode);
    }

    public function testListCommandPrintsRegisteredCommands(): void
    {
        $app = new Application();
        $app->register(new ListCommand($app));
        $app->register(new QualityCommand('qa'));

        ob_start();
        $exitCode = $app->run(['base.php', 'list']);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('qa', $output);
        self::assertStringContainsString('Executa lint, format-check, analyse e test.', $output);
    }

    public function testQualityCommandNamesAndDescriptions(): void
    {
        $command = new QualityCommand('qa');

        self::assertSame('qa', $command->name());
        self::assertSame('Executa lint, format-check, analyse e test.', $command->description());
    }
}
