param (
    [switch]$ValidateOnly,
    [string[]]$TestPaths = @(),
    [string]$ProjectRoot = '.',
    [string]$ConfigPath = '.guardrails.local.json',
    [string]$EnvTestingPath = '.env.testing',
    [string]$PhpUnitPath = 'phpunit.xml'
)
$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$validator = Join-Path $scriptDir "validate-test-database.ps1"

try {
    $projRootInfo = Resolve-Path $ProjectRoot -ErrorAction Stop
    $canonicalProjRoot = $projRootInfo.ProviderPath
} catch { exit 10 }

$gitRootOut = git -C $canonicalProjRoot rev-parse --show-toplevel 2>$null
if ($LASTEXITCODE -ne 0 -or !$gitRootOut) { exit 10 }
$canonicalGitRoot = $gitRootOut -replace '/', '\'
if (!$canonicalProjRoot.Equals($canonicalGitRoot, [System.StringComparison]::OrdinalIgnoreCase)) { exit 10 }

& $validator -ProjectRoot $canonicalProjRoot -ConfigPath $ConfigPath -EnvTestingPath $EnvTestingPath -PhpUnitPath $PhpUnitPath
if ($LASTEXITCODE -ne 0) { exit 40 }

try {
    $testsDirInfo = Resolve-Path (Join-Path $canonicalProjRoot 'tests') -ErrorAction Stop
    $testsDir = $testsDirInfo.ProviderPath
} catch { exit 10 }

foreach ($p in $TestPaths) {
    if ($p -match '^-') { exit 10 }
    if ($p -match '[*?\[\]]') { exit 10 }
    if (($p -split '[/\\]') -contains '..') { exit 10 }
    if ([System.IO.Path]::IsPathRooted($p)) { exit 10 }
    
    try {
        $resolvedPathInfo = Resolve-Path (Join-Path $canonicalProjRoot $p) -ErrorAction Stop
        $resolved = $resolvedPathInfo.ProviderPath
        
        # Check containment
        $isExact = $resolved.Equals($testsDir, [System.StringComparison]::OrdinalIgnoreCase)
        $isSub = $resolved.StartsWith($testsDir + [System.IO.Path]::DirectorySeparatorChar, [System.StringComparison]::OrdinalIgnoreCase)
        if (!$isExact -and !$isSub) { exit 10 }
        
        # Check reparse point (symlink escape)
        $item = Get-Item $resolved -ErrorAction Stop
        if ($item.Attributes -match 'ReparsePoint') { exit 10 }
        
        # Check parent hierarchy up to tests dir for reparse points
        $parent = $item.Parent
        while ($parent -and $parent.FullName.Length -ge $testsDir.Length) {
            if ($parent.Attributes -match 'ReparsePoint') { exit 10 }
            $parent = $parent.Parent
        }
    } catch { exit 10 }
}

if ($ValidateOnly) { exit 0 }

if (!(Get-Command php -ErrorAction SilentlyContinue)) { exit 10 }
if (!(Test-Path (Join-Path $canonicalProjRoot 'artisan'))) { exit 10 }

try {
    Push-Location $canonicalProjRoot
    $args = @('artisan', 'test') + $TestPaths
    php $args
    exit $LASTEXITCODE
} finally {
    Pop-Location
}