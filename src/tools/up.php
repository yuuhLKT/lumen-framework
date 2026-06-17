<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$envFile = $argv[1] ?? '.env';
$compose = $argv[2] ?? 'docker compose';
$runner = $argv[3] ?? 'docker';
$profileFlag = null;

foreach (array_slice($argv, 4) as $extra) {
    if (str_starts_with($extra, '--profile=')) {
        $profileFlag = substr($extra, strlen('--profile='));
    }
}

$isLocal = $runner === 'local';
$mysqlHost = $isLocal ? '127.0.0.1' : 'mysql';
$pgHost = $isLocal ? '127.0.0.1' : 'postgres';

$choices = [
    '1' => [
        'label' => $isLocal ? 'JSON local (arquivo)' : 'JSON local (em container)',
        'updates' => ['DB_CONNECTION' => 'json', 'DB_JSON_PATH' => 'storage/database.json'],
        'local_only' => true,
    ],
    '2' => [
        'label' => $isLocal ? 'SQLite local (arquivo)' : 'SQLite local (em container)',
        'updates' => ['DB_CONNECTION' => 'sqlite', 'DB_SQLITE_PATH' => 'storage/database.sqlite'],
        'local_only' => true,
    ],
    '3' => [
        'label' => 'MySQL (' . ($isLocal ? 'servidor na maquina' : 'container docker') . ')',
        'updates' => [
            'DB_CONNECTION' => 'mysql',
            'DB_MYSQL_HOST' => $mysqlHost,
            'DB_MYSQL_PORT' => '3306',
            'DB_MYSQL_DATABASE' => 'base',
            'DB_MYSQL_USERNAME' => 'base',
            'DB_MYSQL_PASSWORD' => 'base',
            'DB_MYSQL_CHARSET' => 'utf8mb4',
            'DB_MYSQL_ROOT_PASSWORD' => 'root',
        ],
        'local_only' => false,
        'profile' => 'mysql',
    ],
    '4' => [
        'label' => 'PostgreSQL (' . ($isLocal ? 'servidor na maquina' : 'container docker') . ')',
        'updates' => [
            'DB_CONNECTION' => 'pgsql',
            'DB_PGSQL_HOST' => $pgHost,
            'DB_PGSQL_PORT' => '5432',
            'DB_PGSQL_DATABASE' => 'base',
            'DB_PGSQL_USERNAME' => 'base',
            'DB_PGSQL_PASSWORD' => 'base',
        ],
        'local_only' => false,
        'profile' => 'postgres',
    ],
];

if ($profileFlag !== null) {
    $filtered = array_filter($choices, fn (array $choice): bool => ($choice['profile'] ?? null) === $profileFlag);

    if ($filtered === []) {
        fwrite(STDERR, "Perfil desconhecido: {$profileFlag}\n");
        exit(1);
    }

    $choice = array_values($filtered)[0];
    $selected = array_key_first($filtered);
} else {
    echo PHP_EOL;
    echo "Escolha o banco para subir (runner: {$runner}):" . PHP_EOL;

    foreach ($choices as $number => $choice) {
        echo "  {$number}) {$choice['label']}" . PHP_EOL;
    }

    echo PHP_EOL . 'Opção [1]: ';

    $selected = trim((string) fgets(STDIN));
    $selected = $selected === '' ? '1' : $selected;

    if (!isset($choices[$selected])) {
        fwrite(STDERR, "Opção inválida.\n");
        exit(1);
    }

    $choice = $choices[$selected];
}

initEnv($envFile);
writeEnv($envFile, $choice['updates']);

echo PHP_EOL;
echo "Banco selecionado: {$choice['label']}" . PHP_EOL;
echo "Arquivo {$envFile} atualizado." . PHP_EOL;

if ($isLocal) {
    echo PHP_EOL;
    echo 'Modo local: iniciando PHP embutido.' . PHP_EOL;

    if (!($choice['local_only'] ?? false)) {
        $databaseHost = $choice['updates']['DB_CONNECTION'] === 'pgsql' ? $pgHost : $mysqlHost;
        echo "Garanta que o servidor de banco local está rodando em {$databaseHost}." . PHP_EOL;
    }

    $port = envValue($envFile, 'PHP_PORT', '8000');
    $address = '0.0.0.0:' . $port;
    $command = implode(' ', array_map('escapeCommandPart', [PHP_BINARY, '-S', $address, '-t', 'public']));

    echo 'App local em: http://localhost:' . $port . PHP_EOL;
    echo PHP_EOL;

    passthru($command, $exitCode);
    exit($exitCode);
}

echo 'Ngrok local vai apontar para http://localhost:' . envValue($envFile, 'PHP_PORT', '8000') . PHP_EOL;
echo PHP_EOL;

if (!commandExists('ngrok')) {
    fwrite(STDERR, 'Ngrok não encontrado no PATH. Instale/configure ou rode manualmente: ngrok http http://localhost:' . envValue($envFile, 'PHP_PORT', '8000') . PHP_EOL);
    exit(1);
}

$composeArgs = ['up', '--build', 'php'];

if (isset($choice['profile'])) {
    array_unshift($composeArgs, '--profile', $choice['profile']);
    $composeArgs[] = $choice['profile'];
}

$ngrok = startNgrok((int) envValue($envFile, 'PHP_PORT', '8000'));
$command = implode(' ', array_map('escapeCommandPart', [...splitCommand($compose), ...$composeArgs]));
passthru($command, $exitCode);

if (is_resource($ngrok)) {
    proc_terminate($ngrok);
}

exit($exitCode);

function escapeCommandPart(string $part): string
{
    if (preg_match('/^[a-zA-Z0-9_.:\/-]+$/', $part)) {
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
        echo "Não foi possível iniciar ngrok automaticamente. Rode manualmente: {$command}" . PHP_EOL;
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
