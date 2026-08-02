param(
    [ValidateSet('Up', 'Status', 'Down', 'Destroy')]
    [string]$Action = 'Status',
    [switch]$ConfirmDestroy
)

$ErrorActionPreference = 'Stop'
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$expectedRoot = [System.IO.Path]::GetFullPath('C:\ZeroWaste')
if ($projectRoot -ne $expectedRoot) {
    throw "Este script solamente puede ejecutarse desde C:\ZeroWaste."
}

$composeFile = Join-Path $projectRoot 'docker-compose.staging.yml'
$stateRoot = Join-Path $projectRoot '.local-staging'
$passwordFile = Join-Path $stateRoot 'postgres_password.txt'
$projectName = 'zerowaste-local-staging'
$stagingPort = if ($env:STAGING_DB_PORT) { $env:STAGING_DB_PORT } else { '55432' }
if ($stagingPort -notmatch '^\d{2,5}$') { throw 'STAGING_DB_PORT no es válido.' }

function Invoke-StagingCompose {
    & docker compose --project-name $projectName --file $composeFile @args
    if ($LASTEXITCODE -ne 0) { throw "docker compose terminó con código $LASTEXITCODE" }
}

function Initialize-StagingSecret {
    if (-not (Test-Path -LiteralPath $stateRoot)) {
        New-Item -ItemType Directory -Path $stateRoot | Out-Null
    }
    if (-not (Test-Path -LiteralPath $passwordFile)) {
        $bytes = New-Object byte[] 48
        $rng = [Security.Cryptography.RandomNumberGenerator]::Create()
        try {
            $rng.GetBytes($bytes)
        } finally {
            $rng.Dispose()
        }
        $password = [Convert]::ToBase64String($bytes).Replace('+', '-').Replace('/', '_')
        [System.IO.File]::WriteAllText($passwordFile, $password, [System.Text.UTF8Encoding]::new($false))
    }
}

switch ($Action) {
    'Up' {
        Initialize-StagingSecret
        Invoke-StagingCompose up --detach --wait postgres_staging
        Write-Output 'STAGING_POSTGRES=READY'
        Write-Output 'HOST=127.0.0.1'
        Write-Output "PORT=$stagingPort"
        Write-Output 'DATABASE=zerowaste_staging'
        Write-Output 'USER=zerowaste_staging'
    }
    'Status' {
        if (-not (Test-Path -LiteralPath $passwordFile)) {
            Write-Output 'STAGING_POSTGRES=NOT_INITIALIZED'
            exit 0
        }
        Invoke-StagingCompose ps
    }
    'Down' {
        if (-not (Test-Path -LiteralPath $passwordFile)) {
            Write-Output 'STAGING_POSTGRES=NOT_INITIALIZED'
            exit 0
        }
        Invoke-StagingCompose down
        Write-Output 'STAGING_POSTGRES=STOPPED_DATA_PRESERVED'
    }
    'Destroy' {
        if (-not $ConfirmDestroy) {
            throw 'Destroy requiere -ConfirmDestroy porque elimina el volumen local de staging.'
        }
        if (Test-Path -LiteralPath $passwordFile) {
            Invoke-StagingCompose down --volumes
        }
        $resolvedState = [System.IO.Path]::GetFullPath($stateRoot)
        if ($resolvedState -ne [System.IO.Path]::GetFullPath('C:\ZeroWaste\.local-staging')) {
            throw "Ruta de limpieza inesperada: $resolvedState"
        }
        if (Test-Path -LiteralPath $resolvedState) {
            Remove-Item -LiteralPath $resolvedState -Recurse -Force
        }
        Write-Output 'STAGING_POSTGRES=DESTROYED'
    }
}
