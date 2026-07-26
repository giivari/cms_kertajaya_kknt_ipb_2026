param (
    [switch]$ValidateOnly
)
try {
    $ErrorActionPreference = 'Stop'
    $cwd = Get-Location
    $gitRoot = (git rev-parse --show-toplevel) -replace '/', '\'
    if ($cwd.Path -ne $gitRoot) { exit 60 }

    if (!(Test-Path 'package.json') -or !(Test-Path 'package-lock.json')) { exit 60 }
    if (!(Get-Command npm -ErrorAction SilentlyContinue)) { exit 60 }
    if (!(Test-Path 'node_modules/.bin/vite') -and !(Test-Path 'node_modules/.bin/vite.cmd')) { exit 60 }

    $pkgHash = (Get-FileHash 'package.json' -Algorithm SHA256).Hash
    $lockHash = (Get-FileHash 'package-lock.json' -Algorithm SHA256).Hash

    if ($ValidateOnly) { exit 0 }
    
    cmd.exe /c "npm run build"
    if ($LASTEXITCODE -ne 0) { exit 60 }

} catch {
    exit 60
} finally {
    if (!$ValidateOnly -and $pkgHash -and $lockHash) {
        try {
            $newPkgHash = (Get-FileHash 'package.json' -Algorithm SHA256).Hash
            $newLockHash = (Get-FileHash 'package-lock.json' -Algorithm SHA256).Hash
            if ($pkgHash -ne $newPkgHash -or $lockHash -ne $newLockHash) { exit 60 }
        } catch { exit 60 }
    }
}
exit 0