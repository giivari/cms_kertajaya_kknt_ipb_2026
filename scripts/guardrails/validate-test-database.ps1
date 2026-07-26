param(
    [string]$ProjectRoot = '.',
    [string]$EnvTestingPath = '.env.testing',
    [string]$PhpUnitPath = 'phpunit.xml',
    [string]$ConfigPath = '.guardrails.local.json'
)
$ErrorActionPreference = 'Stop'
$resolvedEnv = Join-Path $ProjectRoot $EnvTestingPath
$resolvedPhpUnit = Join-Path $ProjectRoot $PhpUnitPath
$resolvedConfig = Join-Path $ProjectRoot $ConfigPath

$keys = @('APP_ENV', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_URL', 'DATABASE_URL')
$values = @{}
foreach ($k in $keys) { $values[$k] = @() }

if (!(Test-Path $resolvedEnv)) { exit 40 }
$envContent = Get-Content $resolvedEnv
foreach ($line in $envContent) {
    if ($line -match '^([A-Z0-9_]+)=(.*)$') {
        $key = $matches[1]
        $val = $matches[2].Trim()
        if ($key -in $keys -and $val -ne '') {
            $values[$key] += $val
        }
    }
}

if (Test-Path $resolvedPhpUnit) {
    try {
        [xml]$xml = Get-Content $resolvedPhpUnit -ErrorAction Stop
        foreach ($env in $xml.phpunit.php.env) {
            $key = $env.name
            $val = $env.value
            if ($key -in $keys -and $val -ne '') {
                $values[$key] += $val
            }
        }
    } catch { exit 40 }
}

foreach ($k in $keys) {
    $envVal = [Environment]::GetEnvironmentVariable($k)
    if ($envVal -ne $null -and $envVal -ne '') { $values[$k] += $envVal }
}

if ($values['DB_URL'].Count -gt 0 -or $values['DATABASE_URL'].Count -gt 0) { exit 40 }

foreach ($k in @('APP_ENV', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE')) {
    $unique = @($values[$k] | Select-Object -Unique)
    if ($unique.Count -gt 1) { exit 40 }
    $values[$k] = if ($unique.Count -eq 1) { $unique[0] } else { $null }
}

if ($values['APP_ENV'] -ne 'testing') { exit 40 }
if (!$values['DB_CONNECTION'] -or !$values['DB_HOST'] -or !$values['DB_PORT'] -or !$values['DB_DATABASE']) { exit 40 }

if (!(Test-Path $resolvedConfig)) { exit 40 }
try {
    $config = Get-Content $resolvedConfig -Raw | ConvertFrom-Json
    if (!$config.testDatabasePatterns -or !$config.protectedDatabaseNames) { exit 40 }
    
    $testMatched = $false
    foreach ($pat in $config.testDatabasePatterns) { if ($values['DB_DATABASE'] -match $pat) { $testMatched = $true; break } }
    if (!$testMatched) { exit 40 }
    
    if ($config.protectedDatabaseNames -contains $values['DB_DATABASE']) { exit 40 }
} catch { exit 40 }

exit 0