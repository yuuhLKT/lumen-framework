param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $ArgsList
)

$ErrorActionPreference = 'Stop'

function Show-Usage {
    'Usage:'
    '  powershell -File tools/env.ps1 --init [.env] [template]'
    '  powershell -File tools/env.ps1 [.env] KEY VALUE [KEY VALUE ...]'
}

function Initialize-EnvFile([string] $Path, [string] $Template) {
    if (Test-Path -LiteralPath $Path -PathType Leaf) {
        return
    }

    if ([string]::IsNullOrWhiteSpace($Template)) {
        if (Test-Path -LiteralPath '.env.docker.example' -PathType Leaf) {
            $Template = '.env.docker.example'
        } else {
            $Template = '.env.example'
        }
    }

    if (Test-Path -LiteralPath $Template -PathType Leaf) {
        Copy-Item -LiteralPath $Template -Destination $Path
    } else {
        New-Item -ItemType File -Path $Path | Out-Null
    }
}

function Format-EnvValue([string] $Value) {
    if ($Value -match '[\s#=]') {
        return '"' + $Value.Replace('"', '\"') + '"'
    }

    return $Value
}

function Set-EnvValue([string] $Path, [string] $Key, [string] $Value) {
    if ($Key -notmatch '^[A-Z_][A-Z0-9_]*$') {
        [Console]::Error.WriteLine("Chave invalida: $Key")
        exit 1
    }

    $formatted = Format-EnvValue $Value
    $line = "$Key=$formatted"
    $lines = if (Test-Path -LiteralPath $Path -PathType Leaf) { @(Get-Content -LiteralPath $Path) } else { @() }
    $found = $false

    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index] -match "^\s*$([regex]::Escape($Key))\s*=") {
            $lines[$index] = $line
            $found = $true
        }
    }

    if (-not $found) {
        $lines += $line
    }

    Set-Content -LiteralPath $Path -Value $lines
    "$Key=$Value"
}

if ($ArgsList.Count -eq 0 -or $ArgsList[0] -eq '-h' -or $ArgsList[0] -eq '--help') {
    Show-Usage
    exit 0
}

if ($ArgsList[0] -eq '--init') {
    $path = if ($ArgsList.Count -ge 2) { $ArgsList[1] } else { '.env' }
    $template = if ($ArgsList.Count -ge 3) { $ArgsList[2] } else { '' }
    Initialize-EnvFile $path $template
    "Arquivo $path pronto."
    exit 0
}

$envPath = $ArgsList[0]
$pairs = @($ArgsList | Select-Object -Skip 1)

if ($pairs.Count -lt 2 -or ($pairs.Count % 2) -ne 0) {
    [Console]::Error.WriteLine('Informe pares KEY VALUE.')
    exit 1
}

Initialize-EnvFile $envPath ''

for ($index = 0; $index -lt $pairs.Count; $index += 2) {
    Set-EnvValue $envPath $pairs[$index] $pairs[$index + 1]
}
