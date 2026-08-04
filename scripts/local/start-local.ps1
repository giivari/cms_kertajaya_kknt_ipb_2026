[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$postgresBin = 'C:\laragon\bin\postgresql\pgsql\bin'
$pgCtl = Join-Path $postgresBin 'pg_ctl.exe'
$psql = Join-Path $postgresBin 'psql.exe'
$postgresData = 'C:\Users\givar\KULIAH\WEB_KKN\_archive\database-recovery\db-recovery-20260801\data'
$credentialPath = 'C:\Users\givar\KULIAH\WEB_KKN\_archive\database-recovery\db-recovery-20260801\credentials\recovery_admin.dpapi.xml'
$databaseHost = '127.0.0.1'
$databasePort = 5434
$databaseName = 'village_cms_local_working_20260802'
$databaseUser = 'recovery_admin'
$laravelPort = 8015
$vitePort = 5173
$dashboardUrl = "http://127.0.0.1:$laravelPort/desa-dashboard"
$logDirectory = Join-Path $projectRoot 'storage\logs'
$runtimeStatePath = Join-Path $logDirectory 'local-development-processes.json'
$postgresLog = Join-Path $logDirectory 'local-postgresql.log'

function Test-TcpPort {
    param(
        [Parameter(Mandatory)]
        [string] $HostName,

        [Parameter(Mandatory)]
        [int] $Port,

        [int] $TimeoutMilliseconds = 750
    )

    $client = [System.Net.Sockets.TcpClient]::new()

    try {
        $connection = $client.BeginConnect($HostName, $Port, $null, $null)

        if (-not $connection.AsyncWaitHandle.WaitOne($TimeoutMilliseconds, $false)) {
            return $false
        }

        $client.EndConnect($connection)

        return $true
    } catch {
        return $false
    } finally {
        $client.Dispose()
    }
}

function Wait-ForPort {
    param(
        [Parameter(Mandatory)]
        [int] $Port,

        [int] $TimeoutSeconds = 30
    )

    $deadline = [DateTime]::UtcNow.AddSeconds($TimeoutSeconds)

    do {
        if (Test-TcpPort -HostName '127.0.0.1' -Port $Port) {
            return
        }

        Start-Sleep -Milliseconds 300
    } while ([DateTime]::UtcNow -lt $deadline)

    throw "Port $Port belum siap setelah $TimeoutSeconds detik."
}

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
        return $null
    }

    return $process
}

function Assert-LocalEnvironment {
    $envPath = Join-Path $projectRoot '.env'

    if (-not (Test-Path -LiteralPath $envPath)) {
        throw '.env tidak ditemukan.'
    }

    $requiredValues = [ordered]@{
        DB_CONNECTION = 'pgsql'
        DB_HOST = $databaseHost
        DB_PORT = [string] $databasePort
        DB_DATABASE = $databaseName
        DB_USERNAME = $databaseUser
    }
    $content = [System.IO.File]::ReadAllText($envPath)

    foreach ($item in $requiredValues.GetEnumerator()) {
        $match = [regex]::Match($content, '(?m)^' + [regex]::Escape($item.Key) + '=(.*?)\r?$')

        if ((-not $match.Success) -or ($match.Groups[1].Value.Trim('"', "'") -ne $item.Value)) {
            throw ".env belum diarahkan ke database lokal yang benar: $($item.Key)."
        }
    }
}

function Test-WorkingDatabase {
    $credential = Import-Clixml -LiteralPath $credentialPath
    $plainPassword = [System.Net.NetworkCredential]::new('', $credential.Password).Password
    $env:PGPASSWORD = $plainPassword

    try {
        $result = (& $psql -h $databaseHost -p $databasePort -U $databaseUser -d $databaseName -X -tAc 'SELECT current_database();' | Out-String).Trim()

        if (($LASTEXITCODE -ne 0) -or ($result -ne $databaseName)) {
            throw 'Database kerja tidak dapat diverifikasi.'
        }
    } finally {
        Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
        $plainPassword = $null
        $credential = $null
    }
}

function Stop-StartedProcessTree {
    param(
        [Parameter(Mandatory)]
        [System.Diagnostics.Process] $Process
    )

    & taskkill.exe /PID $Process.Id /T /F 2>$null | Out-Null
}

foreach ($requiredPath in @($pgCtl, $psql, $postgresData, $credentialPath)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Komponen lokal tidak ditemukan: $requiredPath"
    }
}

New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
Assert-LocalEnvironment

$postgresRunning = $false
& $pgCtl status -D $postgresData *> $null

if ($LASTEXITCODE -eq 0) {
    $postgresRunning = $true
} elseif (Test-TcpPort -HostName $databaseHost -Port $databasePort) {
    throw "Port $databasePort dipakai proses lain; cluster recovery tidak akan dinyalakan ulang."
}

if ($postgresRunning) {
    Write-Host "PostgreSQL recovery sudah aktif pada port $databasePort."
} else {
    Write-Host "Menyalakan PostgreSQL recovery pada port $databasePort..."
    & $pgCtl start -D $postgresData -l $postgresLog -o "-p $databasePort -h $databaseHost" -w

    if ($LASTEXITCODE -ne 0) {
        throw "PostgreSQL gagal dinyalakan. Periksa $postgresLog"
    }
}

Wait-ForPort -Port $databasePort
Test-WorkingDatabase
Write-Host "Database $databaseName siap."

$existingState = $null

if (Test-Path -LiteralPath $runtimeStatePath) {
    try {
        $existingState = Get-Content -LiteralPath $runtimeStatePath -Raw | ConvertFrom-Json
    } catch {
        throw "File status proses tidak valid: $runtimeStatePath"
    }
}

$laravelProcess = $null
$viteProcess = $null

if ($null -ne $existingState) {
    $laravelProcess = Get-RecordedProcess -Record $existingState.laravel
    $viteProcess = Get-RecordedProcess -Record $existingState.vite

    if (($null -eq $laravelProcess) -and ($null -eq $viteProcess)) {
        Remove-Item -LiteralPath $runtimeStatePath -Force
        $existingState = $null
    }
}

if ((Test-TcpPort -HostName '127.0.0.1' -Port $laravelPort) -and ($null -eq $laravelProcess)) {
    throw "Port Laravel $laravelPort sudah dipakai proses yang tidak direkam start-local."
}

if ((Test-TcpPort -HostName '127.0.0.1' -Port $vitePort) -and ($null -eq $viteProcess)) {
    throw "Port Vite $vitePort sudah dipakai proses yang tidak direkam start-local."
}

$newProcesses = [System.Collections.Generic.List[System.Diagnostics.Process]]::new()

try {
    if ($null -eq $laravelProcess) {
        $php = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe'

        if (-not (Test-Path -LiteralPath $php)) {
            throw "PHP tidak ditemukan: $php"
        }

        $laravelProcess = Start-Process -FilePath $php `
            -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', "--port=$laravelPort") `
            -WorkingDirectory $projectRoot `
            -WindowStyle Hidden `
            -RedirectStandardOutput (Join-Path $logDirectory 'local-laravel.out.log') `
            -RedirectStandardError (Join-Path $logDirectory 'local-laravel.err.log') `
            -PassThru
        $newProcesses.Add($laravelProcess)
        Write-Host "Laravel sedang dinyalakan pada port $laravelPort..."
    } else {
        Write-Host "Laravel yang direkam masih aktif pada port $laravelPort."
    }

    if ($null -eq $viteProcess) {
        $npm = (Get-Command npm.cmd -ErrorAction Stop).Source
        $viteProcess = Start-Process -FilePath $npm `
            -ArgumentList @('run', 'dev', '--', '--host', '127.0.0.1', '--port', [string] $vitePort) `
            -WorkingDirectory $projectRoot `
            -WindowStyle Hidden `
            -RedirectStandardOutput (Join-Path $logDirectory 'local-vite.out.log') `
            -RedirectStandardError (Join-Path $logDirectory 'local-vite.err.log') `
            -PassThru
        $newProcesses.Add($viteProcess)
        Write-Host "Vite sedang dinyalakan pada port $vitePort..."
    } else {
        Write-Host "Vite yang direkam masih aktif pada port $vitePort."
    }

    Wait-ForPort -Port $laravelPort
    Wait-ForPort -Port $vitePort

    $state = [ordered]@{
        project_root = $projectRoot
        created_at_utc = [DateTime]::UtcNow.ToString('o')
        laravel = [ordered]@{
            pid = $laravelProcess.Id
            started_at_utc = $laravelProcess.StartTime.ToUniversalTime().ToString('o')
            port = $laravelPort
        }
        vite = [ordered]@{
            pid = $viteProcess.Id
            started_at_utc = $viteProcess.StartTime.ToUniversalTime().ToString('o')
            port = $vitePort
        }
    }
    $state | ConvertTo-Json -Depth 4 | Set-Content -LiteralPath $runtimeStatePath -Encoding utf8
} catch {
    foreach ($process in $newProcesses) {
        if (-not $process.HasExited) {
            Stop-StartedProcessTree -Process $process
        }
    }

    throw
}

Start-Process $dashboardUrl
Write-Host ''
Write-Host 'Lingkungan lokal siap:'
Write-Host "- Dashboard: $dashboardUrl"
Write-Host "- PostgreSQL: $databaseHost`:$databasePort"
Write-Host "- Database: $databaseName"
Write-Host "- Log: $logDirectory"
Write-Host 'Gunakan scripts\local\stop-local.ps1 untuk mematikan layanan.'
