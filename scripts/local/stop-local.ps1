[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$pgCtl = 'C:\laragon\bin\postgresql\pgsql\bin\pg_ctl.exe'
$postgresData = 'C:\Users\givar\KULIAH\WEB_KKN\village-cms-db-recovery-20260801\data'
$runtimeStatePath = Join-Path $projectRoot 'storage\logs\local-development-processes.json'

function Get-RecordedProcess {
    param(
        [Parameter(Mandatory)]
        [pscustomobject] $Record
    )

    $process = Get-Process -Id ([int] $Record.pid) -ErrorAction SilentlyContinue

    if ($null -eq $process) {
        return $null
    }

    try {
        $actualStartTime = $process.StartTime.ToUniversalTime().ToString('o')
    } catch {
        return $null
    }

    if ($actualStartTime -ne [string] $Record.started_at_utc) {
        throw "PID $($Record.pid) telah dipakai proses lain; proses tersebut tidak dihentikan."
    }

    return $process
}

function Stop-RecordedProcessTree {
    param(
        [Parameter(Mandatory)]
        [string] $Name,

        [Parameter(Mandatory)]
        [pscustomobject] $Record
    )

    $process = Get-RecordedProcess -Record $Record

    if ($null -eq $process) {
        Write-Host "$Name sudah berhenti."
        return
    }

    Write-Host "Menghentikan $Name (PID $($process.Id))..."
    & taskkill.exe /PID $process.Id /T 2>$null | Out-Null
    Start-Sleep -Milliseconds 750

    $remaining = Get-RecordedProcess -Record $Record

    if ($null -ne $remaining) {
        & taskkill.exe /PID $remaining.Id /T /F 2>$null | Out-Null
        Start-Sleep -Milliseconds 500
    }

    if ($null -ne (Get-RecordedProcess -Record $Record)) {
        throw "$Name tidak dapat dihentikan dengan aman."
    }
}

if (Test-Path -LiteralPath $runtimeStatePath) {
    try {
        $state = Get-Content -LiteralPath $runtimeStatePath -Raw | ConvertFrom-Json
    } catch {
        throw "File status proses tidak valid: $runtimeStatePath"
    }

    Stop-RecordedProcessTree -Name 'Vite' -Record $state.vite
    Stop-RecordedProcessTree -Name 'Laravel' -Record $state.laravel
    Remove-Item -LiteralPath $runtimeStatePath -Force
} else {
    Write-Host 'Tidak ada proses Laravel/Vite yang direkam start-local.'
}

if (-not (Test-Path -LiteralPath $pgCtl)) {
    throw "pg_ctl tidak ditemukan: $pgCtl"
}

& $pgCtl status -D $postgresData *> $null

if ($LASTEXITCODE -eq 0) {
    Write-Host 'Menghentikan cluster PostgreSQL recovery port 5434 secara clean...'
    & $pgCtl stop -D $postgresData -m fast -w

    if ($LASTEXITCODE -ne 0) {
        throw 'Cluster PostgreSQL recovery gagal dihentikan.'
    }
} else {
    Write-Host 'Cluster PostgreSQL recovery sudah berhenti.'
}

Write-Host 'Lingkungan lokal sudah dimatikan. PostgreSQL Laragon port 5432 tidak disentuh.'
