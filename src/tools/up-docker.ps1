param(
    [string] $EnvFile = '.env',
    [string] $Compose = 'docker compose',
    [string] $ProfileFlag = ''
)

$ErrorActionPreference = 'Stop'

function Invoke-EnvTool([string[]] $ToolArgs) {
    & powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools/env.ps1 @ToolArgs | Out-Null
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

function Get-SelectedChoice {
    if ($ProfileFlag -eq '--profile=mysql') {
        return 'd3'
    }

    if ($ProfileFlag -eq '--profile=pgsql' -or $ProfileFlag -eq '--profile=postgres') {
        return 'd4'
    }

    ''
    'Escolha o ambiente/banco para subir:'
    'Docker:'
    '  d1) JSON local (em container)'
    '  d2) SQLite local (em container)'
    '  d3) MySQL (container docker)'
    '  d4) PostgreSQL (container docker)'
    'PHP local (sem Docker):'
    '  l1) JSON local (arquivo)'
    '  l2) SQLite local (arquivo)'
    '  l3) MySQL (servidor na maquina)'
    '  l4) PostgreSQL (servidor na maquina)'
    ''

    $selected = Read-Host 'Opcao [d1]'
    if ([string]::IsNullOrWhiteSpace($selected)) {
        $selected = 'd1'
    }

    if ($selected -notin @('d1', 'd2', 'd3', 'd4', 'l1', 'l2', 'l3', 'l4')) {
        [Console]::Error.WriteLine('Opcao invalida.')
        exit 1
    }

    return $selected
}

function Get-EnvValue([string] $Path, [string] $Key, [string] $Default) {
    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        return $Default
    }

    foreach ($line in Get-Content -LiteralPath $Path) {
        if ($line -match "^\s*$([regex]::Escape($Key))\s*=\s*(.*)$") {
            $value = $Matches[1].Trim().Trim('"', "'")
            if (-not [string]::IsNullOrWhiteSpace($value)) {
                return $value
            }
        }
    }

    return $Default
}

function Split-Command([string] $Command) {
    return @($Command -split '\s+' | Where-Object { $_ -ne '' })
}

$choice = Get-SelectedChoice
$mode = $choice.Substring(0, 1)
$number = $choice.Substring(1, 1)
$profileArgs = @()
$services = @('php')

Invoke-EnvTool @('--init', $EnvFile, '.env.docker.example')

switch ($choice) {
    'd1' {
        $label = 'JSON local (em container)'
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'json', 'DB_JSON_PATH', 'storage/database.json')
    }
    'd2' {
        $label = 'SQLite local (em container)'
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'sqlite', 'DB_SQLITE_PATH', 'storage/database.sqlite')
    }
    'd3' {
        $label = 'MySQL (container docker)'
        $profileArgs = @('--profile', 'mysql')
        $services = @('php', 'mysql')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'mysql', 'DB_MYSQL_HOST', 'mysql', 'DB_MYSQL_PORT', '3306', 'DB_MYSQL_DATABASE', 'base', 'DB_MYSQL_USERNAME', 'base', 'DB_MYSQL_PASSWORD', 'base', 'DB_MYSQL_CHARSET', 'utf8mb4', 'DB_MYSQL_ROOT_PASSWORD', 'root')
    }
    'd4' {
        $label = 'PostgreSQL (container docker)'
        $profileArgs = @('--profile', 'postgres')
        $services = @('php', 'postgres')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'pgsql', 'DB_PGSQL_HOST', 'postgres', 'DB_PGSQL_PORT', '5432', 'DB_PGSQL_DATABASE', 'base', 'DB_PGSQL_USERNAME', 'base', 'DB_PGSQL_PASSWORD', 'base')
    }
    'l1' {
        $label = 'JSON local (arquivo)'
        Invoke-EnvTool @('--init', $EnvFile, '.env.example')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'json', 'DB_JSON_PATH', 'storage/database.json')
    }
    'l2' {
        $label = 'SQLite local (arquivo)'
        Invoke-EnvTool @('--init', $EnvFile, '.env.example')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'sqlite', 'DB_SQLITE_PATH', 'storage/database.sqlite')
    }
    'l3' {
        $label = 'MySQL (servidor na maquina)'
        Invoke-EnvTool @('--init', $EnvFile, '.env.example')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'mysql', 'DB_MYSQL_HOST', '127.0.0.1', 'DB_MYSQL_PORT', '3306', 'DB_MYSQL_DATABASE', 'base', 'DB_MYSQL_USERNAME', 'base', 'DB_MYSQL_PASSWORD', 'base', 'DB_MYSQL_CHARSET', 'utf8mb4', 'DB_MYSQL_ROOT_PASSWORD', 'root')
    }
    'l4' {
        $label = 'PostgreSQL (servidor na maquina)'
        Invoke-EnvTool @('--init', $EnvFile, '.env.example')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'pgsql', 'DB_PGSQL_HOST', '127.0.0.1', 'DB_PGSQL_PORT', '5432', 'DB_PGSQL_DATABASE', 'base', 'DB_PGSQL_USERNAME', 'base', 'DB_PGSQL_PASSWORD', 'base')
    }
}

$phpPort = Get-EnvValue $EnvFile 'PHP_PORT' '8000'
''
"Banco selecionado: $label"
"Arquivo $EnvFile atualizado."

if ($mode -eq 'l') {
    ''
    'Modo local: iniciando PHP embutido.'
    "App local em: http://localhost:$phpPort"
    ''
    & php -S "0.0.0.0:$phpPort" -t public
    exit $LASTEXITCODE
}

"Ngrok local vai apontar para http://localhost:$phpPort"
''

$ngrok = $null
if (Get-Command ngrok -ErrorAction SilentlyContinue) {
    $ngrok = Start-Process -FilePath 'ngrok' -ArgumentList @('http', "http://localhost:$phpPort") -PassThru
} else {
    [Console]::Error.WriteLine("Ngrok nao encontrado no PATH. Rode manualmente: ngrok http http://localhost:$phpPort")
}

try {
    $composeParts = Split-Command $Compose
    $command = $composeParts[0]
    $composeArgs = @($composeParts | Select-Object -Skip 1) + $profileArgs + @('up', '--build') + $services
    & $command @composeArgs
    exit $LASTEXITCODE
} finally {
    if ($null -ne $ngrok -and -not $ngrok.HasExited) {
        Stop-Process -Id $ngrok.Id -ErrorAction SilentlyContinue
    }
}
