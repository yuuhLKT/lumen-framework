<?php

declare(strict_types=1);

/**
 * Small .env updater used by Makefile. It intentionally supports only KEY=value
 * lines because this project uses a simple .env loader too.
 */

if (realpath($argv[0] ?? '') === realpath(__FILE__)) {
    $args = $argv;
    array_shift($args);

    if ($args === [] || in_array($args[0], ['-h', '--help'], true)) {
        echo "Usage:\n";
        echo "  php tools/env.php --init [.env] [template]\n";
        echo "  php tools/env.php [.env] KEY VALUE [KEY VALUE ...]\n";
        exit(0);
    }

    if ($args[0] === '--init') {
        $path = $args[1] ?? '.env';
        $template = $args[2] ?? null;
        initEnv($path, $template);
        echo "Arquivo {$path} pronto.\n";
        exit(0);
    }

    $path = array_shift($args);

    if (count($args) < 2 || count($args) % 2 !== 0) {
        fwrite(STDERR, "Informe pares KEY VALUE.\n");
        exit(1);
    }

    initEnv($path);

    $updates = [];

    for ($index = 0; $index < count($args); $index += 2) {
        $key = trim($args[$index]);
        $value = $args[$index + 1];

        if ($key === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
            fwrite(STDERR, "Chave inválida: {$key}\n");
            exit(1);
        }

        $updates[$key] = $value;
    }

    writeEnv($path, $updates);

    foreach ($updates as $key => $value) {
        echo "{$key}={$value}\n";
    }
}

function initEnv(string $path, ?string $template = null): void
{
    if (is_file($path)) {
        return;
    }

    if ($template === null) {
        $template = is_file('.env.docker.example') ? '.env.docker.example' : '.env.example';
    }

    if (!is_file($template)) {
        file_put_contents($path, '');
        return;
    }

    if (!copy($template, $path)) {
        fwrite(STDERR, "Não foi possível criar {$path} a partir de {$template}.\n");
        exit(1);
    }
}

/** @param array<string, string> $updates */
function writeEnv(string $path, array $updates): void
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        fwrite(STDERR, "Não foi possível ler {$path}.\n");
        exit(1);
    }

    $seen = [];

    foreach ($lines as $index => $line) {
        if (!preg_match('/^\s*([A-Z_][A-Z0-9_]*)\s*=/', $line, $matches)) {
            continue;
        }

        $key = $matches[1];

        if (!array_key_exists($key, $updates)) {
            continue;
        }

        $lines[$index] = $key . '=' . formatEnvValue($updates[$key]);
        $seen[$key] = true;
    }

    foreach ($updates as $key => $value) {
        if (isset($seen[$key])) {
            continue;
        }

        $lines[] = $key . '=' . formatEnvValue($value);
    }

    $content = implode(PHP_EOL, $lines) . PHP_EOL;

    if (file_put_contents($path, $content) === false) {
        fwrite(STDERR, "Não foi possível salvar {$path}.\n");
        exit(1);
    }
}

function formatEnvValue(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (preg_match('/[\s#=]/', $value)) {
        return '"' . str_replace('"', '\\"', $value) . '"';
    }

    return $value;
}
