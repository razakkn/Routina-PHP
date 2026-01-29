param(
    [string]$FtpServer = $env:ROUTINA_FTP_SERVER,
    [string]$FtpUser = $env:ROUTINA_FTP_USER,
    [string]$FtpPass = $env:ROUTINA_FTP_PASS,
    [string]$RemoteRoot = $(if ($env:ROUTINA_FTP_REMOTE_ROOT) { $env:ROUTINA_FTP_REMOTE_ROOT } else { 'htdocs' }),
    [switch]$PublicToRoot,
    [switch]$IncludeConfig,
    [int]$Retries = 2,
    [int]$ThrottleMs = 150,
    [string]$LocalBase = $(Resolve-Path (Join-Path $PSScriptRoot '..')).Path
)

# FTP Upload Script for Routina PHP (no embedded credentials)
#
# Usage:
#   $env:ROUTINA_FTP_SERVER = 'example'
#   $env:ROUTINA_FTP_USER   = 'example'
#   $env:ROUTINA_FTP_PASS   = 'example'
#   powershell -ExecutionPolicy Bypass -File scripts/ftp_upload.ps1

$ErrorActionPreference = 'Stop'

function Require-Command([string]$name) {
    if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
        throw "Required command not found: $name"
    }
}

Require-Command curl.exe

if (-not $FtpServer) { throw 'Missing FTP server. Set ROUTINA_FTP_SERVER.' }
if (-not $FtpUser) { throw 'Missing FTP username. Set ROUTINA_FTP_USER.' }

if (-not $FtpPass) {
    $secure = Read-Host 'FTP password' -AsSecureString
    $FtpPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    )
}

if (-not $FtpPass) { throw 'Missing FTP password. Provide ROUTINA_FTP_PASS or enter when prompted.' }

$ftpBase = "ftp://$FtpServer/$RemoteRoot"

Write-Host "Uploading from: $LocalBase" -ForegroundColor Cyan
Write-Host "Uploading to:   $ftpBase" -ForegroundColor Cyan
if ($PublicToRoot) {
    Write-Host 'Mode: public/ folder uploads to remote root (shared hosting docroot).' -ForegroundColor DarkGray
} else {
    Write-Host 'Mode: mirror repo paths (public/ stays under public/).' -ForegroundColor DarkGray
}
if ($IncludeConfig) {
    Write-Host 'Including config/ in upload (use with care).' -ForegroundColor DarkYellow
} else {
    Write-Host 'Skipping config/ in upload (safer default).' -ForegroundColor DarkGray
}

# Upload PHP/CSS/JS/.htaccess while skipping local-only artifacts.
$files = Get-ChildItem -Path $LocalBase -Recurse -Include '*.php','*.css','*.js','.htaccess' | Where-Object {
    $_.FullName -notmatch '\\\.git\\' -and
    $_.FullName -notmatch '\\\.vscode\\' -and
    $_.Name -ne 'database.sqlite' -and
    $_.FullName -notmatch '\\storage\\cache\\' -and
    $_.FullName -notmatch '\\storage\\logs\\'
}

if (-not $IncludeConfig) {
    $files = $files | Where-Object { $_.FullName -notmatch '\\config\\' }
}

$total = $files.Count
$current = 0

foreach ($file in $files) {
    $current++
    $base = $LocalBase.TrimEnd('\')
    $full = $file.FullName
    if ($full.StartsWith($base + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
        $relativePath = $full.Substring($base.Length + 1)
    } else {
        # Fallback: best-effort name (should be rare)
        $relativePath = $file.Name
    }
    $relativePath = $relativePath.Replace('\', '/')

    $remotePath = $relativePath
    if ($PublicToRoot -and $remotePath.StartsWith('public/')) {
        $remotePath = $remotePath.Substring(7)
    }

    $remoteUrl = "$ftpBase/$remotePath"

    Write-Host "[$current/$total] Uploading: $relativePath" -ForegroundColor Yellow

    if ($ThrottleMs -gt 0) {
        Start-Sleep -Milliseconds $ThrottleMs
    }

    $attempt = 0
    $curlExit = 1
    $curlOut = $null

    while ($attempt -le $Retries) {
        $attempt++

        # curl writes progress to stderr, which PowerShell may treat as an error record.
        # Temporarily relax error handling and capture output.
        $prevErr = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        $curlOut = & curl.exe --silent --show-error --ftp-pasv -T $file.FullName --user "${FtpUser}:${FtpPass}" --ftp-create-dirs $remoteUrl 2>&1
        $curlExit = $LASTEXITCODE
        $ErrorActionPreference = $prevErr

        if ($curlExit -eq 0) {
            break
        }

        if ($attempt -le $Retries) {
            Start-Sleep -Milliseconds ([Math]::Min(2000, 200 * [Math]::Pow(2, $attempt)))
        }
    }

    if ($curlExit -eq 0) {
        Write-Host '  OK' -ForegroundColor Green
    } else {
        Write-Host "  FAILED (curl exit $curlExit)" -ForegroundColor Red
        Write-Host "  Remote: $remoteUrl" -ForegroundColor DarkGray
        if ($curlOut) {
            Write-Host ("  " + ($curlOut | Out-String).Trim()) -ForegroundColor DarkGray
        }
    }
}

Write-Host "`nUpload complete." -ForegroundColor Cyan
Write-Host 'Reminder: keep credentials out of git; rotate if previously committed.' -ForegroundColor DarkGray
