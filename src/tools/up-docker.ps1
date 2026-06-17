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
        return '3'
    }

    if ($ProfileFlag -eq '--profile=pgsql' -or $ProfileFlag -eq '--profile=postgres') {
        return '4'
    }

    ''
    'Escolha o banco para subir (runner: docker):'
    '  1) JSON local (em container)'
    '  2) SQLite local (em container)'
    '  3) MySQL (container docker)'
    '  4) PostgreSQL (container docker)'
    ''

    $selected = Read-Host 'Opcao [1]'
    if ([string]::IsNullOrWhiteSpace($selected)) {
        $selected = '1'
    }

    if ($selected -notin @('1', '2', '3', '4')) {
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
$profileArgs = @()
$services = @('php')

Invoke-EnvTool @('--init', $EnvFile, '.env.docker.example')

switch ($choice) {
    '1' {
        $label = 'JSON local (em container)'
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'json', 'DB_JSON_PATH', 'storage/database.json')
    }
    '2' {
        $label = 'SQLite local (em container)'
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'sqlite', 'DB_SQLITE_PATH', 'storage/database.sqlite')
    }
    '3' {
        $label = 'MySQL (container docker)'
        $profileArgs = @('--profile', 'mysql')
        $services = @('php', 'mysql')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'mysql', 'DB_MYSQL_HOST', 'mysql', 'DB_MYSQL_PORT', '3306', 'DB_MYSQL_DATABASE', 'base', 'DB_MYSQL_USERNAME', 'base', 'DB_MYSQL_PASSWORD', 'base', 'DB_MYSQL_CHARSET', 'utf8mb4', 'DB_MYSQL_ROOT_PASSWORD', 'root')
    }
    '4' {
        $label = 'PostgreSQL (container docker)'
        $profileArgs = @('--profile', 'postgres')
        $services = @('php', 'postgres')
        Invoke-EnvTool @($EnvFile, 'DB_CONNECTION', 'pgsql', 'DB_PGSQL_HOST', 'postgres', 'DB_PGSQL_PORT', '5432', 'DB_PGSQL_DATABASE', 'base', 'DB_PGSQL_USERNAME', 'base', 'DB_PGSQL_PASSWORD', 'base')
    }
}

$phpPort = Get-EnvValue $EnvFile 'PHP_PORT' '8000'
''
"Banco selecionado: $label"
"Arquivo $EnvFile atualizado."
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
