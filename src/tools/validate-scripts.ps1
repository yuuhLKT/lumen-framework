$ErrorActionPreference = 'Stop'

[scriptblock]::Create((Get-Content -Raw tools/env.ps1)) | Out-Null
[scriptblock]::Create((Get-Content -Raw tools/up-docker.ps1)) | Out-Null
