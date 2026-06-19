<?php

declare(strict_types=1);

namespace Tests;

use App\Console\Application;
use App\Console\Command;
use App\Console\Commands\DoctorCommand;
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
        $exitCode = $app->run(['lumen.php', 'hello', 'Yuri']);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode);
        self::assertSame('hello Yuri', $output);
    }

    public function testApplicationReturnsErrorForUnknownCommand(): void
    {
        $app = new Application();

        $exitCode = $app->run(['lumen.php', 'missing']);

        self::assertSame(1, $exitCode);
    }

    public function testListCommandPrintsRegisteredCommands(): void
    {
        $app = new Application();
        $app->register(new ListCommand($app));
        $app->register(new QualityCommand('qa'));
        $app->register(new DoctorCommand());

        ob_start();
        $exitCode = $app->run(['lumen.php', 'list']);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('qa', $output);
        self::assertStringContainsString('Executa lint, format-check, analyse e test.', $output);
        self::assertStringContainsString('doctor', $output);
    }

    public function testQualityCommandNamesAndDescriptions(): void
    {
        $command = new QualityCommand('qa');

        self::assertSame('qa', $command->name());
        self::assertSame('Executa lint, format-check, analyse e test.', $command->description());
    }

    public function testQualityCommandSkipsTestsWhenDirectoryMissing(): void
    {
        $originalDir = getcwd();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-test-' . bin2hex(random_bytes(4));
        mkdir($tempDir);
        mkdir($tempDir . DIRECTORY_SEPARATOR . 'vendor');
        chdir($tempDir);

        try {
            $command = new QualityCommand('test');

            ob_start();
            $exitCode = $command->run([]);
            $output = ob_get_clean();

            self::assertSame(0, $exitCode);
            self::assertStringContainsString('Pasta tests/ nao encontrada.', $output);
        } finally {
            chdir($originalDir);
            rmdir($tempDir . DIRECTORY_SEPARATOR . 'vendor');
            rmdir($tempDir);
        }
    }

    public function testQualityCommandRequiresVendor(): void
    {
        $originalDir = getcwd();
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-qa-' . bin2hex(random_bytes(4));
        mkdir($tempDir);
        chdir($tempDir);

        try {
            $command = new QualityCommand('test');

            ob_start();
            $exitCode = $command->run([]);
            $output = ob_get_clean();

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('Pasta vendor/ nao encontrada.', $output);
        } finally {
            chdir($originalDir);
            rmdir($tempDir);
        }
    }

    public function testDoctorCommandRunsAndReturnsZero(): void
    {
        $command = new DoctorCommand();

        ob_start();
        $exitCode = $command->run([]);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Doctor', $output);
        self::assertStringContainsString('PHP', $output);
    }
}
