<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$envFile = $argv[1] ?? '.env';
$compose = $argv[2] ?? 'docker compose';

$choices = [
    '1' => [
        'label' => 'JSON local',
        'updates' => ['DB_CONNECTION' => 'json', 'DB_JSON_PATH' => 'storage/database.json'],
        'command' => ['up', '--build', 'php'],
    ],
    '2' => [
        'label' => 'SQLite local',
        'updates' => ['DB_CONNECTION' => 'sqlite', 'DB_SQLITE_PATH' => 'storage/database.sqlite'],
        'command' => ['up', '--build', 'php'],
    ],
    '3' => [
        'label' => 'MySQL Docker',
        'updates' => [
            'DB_CONNECTION' => 'mysql',
            'DB_MYSQL_HOST' => 'mysql',
            'DB_MYSQL_PORT' => '3306',
            'DB_MYSQL_DATABASE' => 'base',
            'DB_MYSQL_USERNAME' => 'base',
            'DB_MYSQL_PASSWORD' => 'base',
            'DB_MYSQL_CHARSET' => 'utf8mb4',
            'DB_MYSQL_ROOT_PASSWORD' => 'root',
        ],
        'command' => ['--profile', 'mysql', 'up', '--build', 'php', 'mysql'],
    ],
    '4' => [
        'label' => 'PostgreSQL Docker',
        'updates' => [
            'DB_CONNECTION' => 'pgsql',
            'DB_PGSQL_HOST' => 'postgres',
            'DB_PGSQL_PORT' => '5432',
            'DB_PGSQL_DATABASE' => 'base',
            'DB_PGSQL_USERNAME' => 'base',
            'DB_PGSQL_PASSWORD' => 'base',
        ],
        'command' => ['--profile', 'postgres', 'up', '--build', 'php', 'postgres'],
    ],
];

echo PHP_EOL;
echo 'Escolha o banco para subir com Docker + ngrok local:' . PHP_EOL;

foreach ($choices as $number => $choice) {
    echo "  {$number}) {$choice['label']}" . PHP_EOL;
}

echo PHP_EOL . 'Opcao [1]: ';

$selected = trim((string) fgets(STDIN));
$selected = $selected === '' ? '1' : $selected;

if (!isset($choices[$selected])) {
    fwrite(STDERR, "Opcao invalida.\n");
    exit(1);
}

$choice = $choices[$selected];

initEnv($envFile);
writeEnv($envFile, $choice['updates']);

echo PHP_EOL;
echo "Banco selecionado: {$choice['label']}" . PHP_EOL;
echo "Arquivo {$envFile} atualizado." . PHP_EOL;
echo 'Ngrok local vai apontar para http://localhost:' . envValue($envFile, 'PHP_PORT', '8000') . PHP_EOL;
echo PHP_EOL;

if (!commandExists('ngrok')) {
    fwrite(STDERR, 'Ngrok nao encontrado no PATH. Instale/configure ou rode manualmente: ngrok http http://localhost:' . envValue($envFile, 'PHP_PORT', '8000') . PHP_EOL);
    exit(1);
}

$ngrok = startNgrok((int) envValue($envFile, 'PHP_PORT', '8000'));
$command = implode(' ', array_map('escapeCommandPart', [...splitCommand($compose), ...$choice['command']]));
passthru($command, $exitCode);

if (is_resource($ngrok)) {
    proc_terminate($ngrok);
}

exit($exitCode);

function escapeCommandPart(string $part): string
{
    if (preg_match('/^[a-zA-Z0-9_.:\\/-]+$/', $part)) {
        return $part;
    }

    return escapeshellarg($part);
}

/** @return array<int, string> */
function splitCommand(string $command): array
{
    $parts = preg_split('/\s+/', trim($command));

    return array_values(array_filter($parts ?: [], fn (string $part): bool => $part !== ''));
}

function startNgrok(int $port): mixed
{
    $command = 'ngrok http http://localhost:' . $port;
    echo "Iniciando ngrok local: {$command}" . PHP_EOL;

    $process = @proc_open($command, [
        0 => ['file', 'php://stdin', 'r'],
        1 => ['file', 'php://stdout', 'w'],
        2 => ['file', 'php://stderr', 'w'],
    ], $pipes);

    if (!is_resource($process)) {
        echo "Nao foi possivel iniciar ngrok automaticamente. Rode manualmente: {$command}" . PHP_EOL;
        return null;
    }

    return $process;
}

function envValue(string $path, string $key, string $default): string
{
    if (!is_file($path)) {
        return $default;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return $default;
    }

    foreach ($lines as $line) {
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=\s*(.*)$/', $line, $matches)) {
            return trim(trim($matches[1]), "\"'") ?: $default;
        }
    }

    return $default;
}

function commandExists(string $command): bool
{
    $check = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
        ? 'where ' . escapeshellarg($command)
        : 'command -v ' . escapeshellarg($command);

    exec($check, $output, $exitCode);

    return $exitCode === 0;
}
