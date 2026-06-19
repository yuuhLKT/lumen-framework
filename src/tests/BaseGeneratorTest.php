<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class BaseGeneratorTest extends TestCase
{
    public function testGeneratorCreatesProjectWithoutTestsAndWithAuth(): void
    {
        $projectName = 'generated_auth_' . bin2hex(random_bytes(4));
        $projectDir = $this->generatedProjectPath($projectName);

        try {
            $this->runGenerator($projectName, "s\n");

            self::assertDirectoryExists($projectDir);
            self::assertFileDoesNotExist($projectDir . '/AGENTS.md');
            self::assertDirectoryDoesNotExist($projectDir . '/tests');
            self::assertFileExists($projectDir . '/app/Controllers/AuthController.php');
            self::assertFileExists($projectDir . '/app/Core/Auth.php');
            self::assertStringContainsString('/auth/login', (string) file_get_contents($projectDir . '/routes/web.php'));
            self::assertStringContainsString('public function auth()', (string) file_get_contents($projectDir . '/app/Core/Router.php'));
            self::assertStringContainsString('"autoload-dev"', (string) file_get_contents($projectDir . '/composer.json'));
            self::assertFileExists($projectDir . '/.gitattributes');
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    public function testGeneratorCreatesProjectWithoutAuthOrTests(): void
    {
        $projectName = 'generated_plain_' . bin2hex(random_bytes(4));
        $projectDir = $this->generatedProjectPath($projectName);

        try {
            $this->runGenerator($projectName, "n\n");

            self::assertDirectoryExists($projectDir);
            self::assertFileDoesNotExist($projectDir . '/AGENTS.md');
            self::assertDirectoryDoesNotExist($projectDir . '/tests');
            self::assertFileDoesNotExist($projectDir . '/app/Controllers/AuthController.php');
            self::assertFileDoesNotExist($projectDir . '/app/Core/Auth.php');
            self::assertFileDoesNotExist($projectDir . '/app/Http/Middleware/AuthMiddleware.php');
            self::assertFileDoesNotExist($projectDir . '/config/auth.php');
            self::assertFileDoesNotExist($projectDir . '/docs/autenticacao.md');
            self::assertStringNotContainsString('/auth/', (string) file_get_contents($projectDir . '/routes/web.php'));
            self::assertStringNotContainsString('AuthMiddleware', (string) file_get_contents($projectDir . '/app/Core/Router.php'));
            self::assertStringContainsString('public function auth()', (string) file_get_contents($projectDir . '/app/Core/Router.php'));
            self::assertStringContainsString('Mini Auth was not included in this project.', (string) file_get_contents($projectDir . '/app/Core/Router.php'));
            self::assertStringNotContainsString('users', (string) file_get_contents($projectDir . '/database/migrations/2026_01_01_000000_create_initial_tables.php'));
            self::assertStringNotContainsString('auth_tokens', (string) file_get_contents($projectDir . '/database/migrations/2026_01_01_000000_create_initial_tables.php'));
            self::assertStringNotContainsString('DEV_BEARER_TOKEN', (string) file_get_contents($projectDir . '/.env'));
            self::assertStringNotContainsString('"autoload-dev"', (string) file_get_contents($projectDir . '/composer.json'));
            self::assertStringNotContainsString('"Tests\\\\"', (string) file_get_contents($projectDir . '/composer.json'));
            self::assertFileExists($projectDir . '/.gitattributes');
        } finally {
            $this->removeDirectory($projectDir);
        }
    }

    private function runGenerator(string $projectName, string $authAnswer): void
    {
        $baseDir = dirname(__DIR__, 2);
        $composerBin = $this->makeFakeComposerBin();
        $env = $_ENV;
        $env['PATH'] = $composerBin . PATH_SEPARATOR . (string) getenv('PATH');

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open('bash lumen.sh', $descriptorSpec, $pipes, $baseDir, $env);

        if (!is_resource($process)) {
            self::markTestSkipped('Nao foi possivel iniciar bash para testar lumen.sh.');
        }

        fwrite($pipes[0], $projectName . "\n" . $authAnswer);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, "lumen.sh falhou.\nSTDOUT:\n{$output}\nSTDERR:\n{$errorOutput}");
    }

    private function makeFakeComposerBin(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'base-generator-' . bin2hex(random_bytes(4));
        mkdir($dir);

        $composer = $dir . DIRECTORY_SEPARATOR . 'composer';
        file_put_contents($composer, "#!/usr/bin/env sh\nexit 1\n");
        chmod($composer, 0755);

        return $dir;
    }

    private function generatedProjectPath(string $projectName): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $projectName;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
