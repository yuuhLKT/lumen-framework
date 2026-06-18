<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\Command;

final class DoctorCommand implements Command
{
    public function name(): string
    {
        return 'doctor';
    }

    public function description(): string
    {
        return 'Checa o ambiente de desenvolvimento.';
    }

    /**
     * @param array<int, string> $args
     */
    public function run(array $args): int
    {
        echo "Doctor\n";
        echo str_repeat('-', 40) . "\n";

        $this->checkPhpVersion();
        $this->checkExtension('pdo_sqlite', 'opcional para SQLite');
        $this->checkExtension('pdo_mysql', 'opcional para MySQL');
        $this->checkExtension('pdo_pgsql', 'opcional para PostgreSQL');
        $this->checkBinary('composer', 'recomendado para testes e qualidade');
        $this->checkBinary('docker', 'opcional para o menu Docker do Makefile');
        $this->checkFile('.env', 'crie com make env');
        $this->checkFile('vendor/autoload.php', 'rode make deps ou composer install');

        echo str_repeat('-', 40) . "\n";
        echo "Concluido.\n";

        return 0;
    }

    private function checkPhpVersion(): void
    {
        $version = PHP_VERSION;
        $required = '8.1';
        $ok = version_compare($version, $required, '>=');

        $this->line('PHP', $version, $ok ? null : "requerido >= {$required}");
    }

    private function checkExtension(string $extension, string $hint): void
    {
        $ok = extension_loaded($extension);

        $this->line($extension, $ok ? 'carregada' : 'ausente', $ok ? null : $hint);
    }

    private function checkBinary(string $binary, string $hint): void
    {
        $path = $this->findBinary($binary);

        if ($binary === 'docker' && $path !== null) {
            $path = $this->runCheck(escapeshellarg($path) . ' info >/dev/null 2>&1') ? $path : null;
        }

        $ok = $path !== null;

        $this->line($binary, $ok ? $path : 'nao encontrado', $ok ? null : $hint);
    }

    private function findBinary(string $binary): ?string
    {
        $windowsExtensions = ['', '.exe', '.bat'];
        $path = getenv('PATH');

        if ($path === false) {
            $path = getenv('Path');
        }

        if ($path === false) {
            return null;
        }

        $separator = PHP_OS_FAMILY === 'Windows' ? ';' : ':';

        foreach (explode($separator, $path) as $dir) {
            foreach ($windowsExtensions as $extension) {
                $candidate = $dir . DIRECTORY_SEPARATOR . $binary . $extension;

                if (is_executable($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function runCheck(string $command): bool
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return false;
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $start = microtime(true);
        $timeout = 2.0;

        while (true) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                break;
            }

            if (microtime(true) - $start > $timeout) {
                proc_terminate($process);

                break;
            }

            usleep(10000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $status['exitcode'] === 0;
    }

    private function checkFile(string $file, string $hint): void
    {
        $ok = is_file($file);

        $this->line($file, $ok ? 'encontrado' : 'ausente', $ok ? null : $hint);
    }

    private function line(string $label, string $value, ?string $hint): void
    {
        if ($hint === null) {
            echo sprintf("%-22s %-20s [OK]\n", $label . ':', $value);

            return;
        }

        echo sprintf("%-22s %-20s [--] %s\n", $label . ':', $value, $hint);
    }
}
