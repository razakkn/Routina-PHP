param(
    [string[]]$Files = @(
        'views/account/profile.php',
        'src/Controllers/ProfileController.php'
    )
)

if (-not $env:routina_ftp_server -or -not $env:routina_ftp_user -or -not $env:routina_ftp_pass) {
    Write-Error "Please set routina_ftp_server, routina_ftp_user and routina_ftp_pass environment variables before running."
    exit 2
}

$server = $env:routina_ftp_server
$user = $env:routina_ftp_user
$pass = $env:routina_ftp_pass

$base = Get-Location

foreach ($f in $Files) {
    $local = Join-Path $base $f
    if (-not (Test-Path $local)) {
        Write-Error "Local file not found: $local"
        exit 3
    }

    $remote = "ftp://$server/htdocs/$f"
    Write-Host "Uploading $f -> $remote"

    $cmd = "curl -T `"$local`" -u `"$user`:`"$pass`" `"$remote`" --ftp-create-dirs --silent --show-error --fail"
    $proc = Start-Process -FilePath pwsh -ArgumentList "-NoProfile -Command $cmd" -NoNewWindow -Wait -PassThru
    if ($proc.ExitCode -ne 0) {
        Write-Error "Upload failed for $f (exit $($proc.ExitCode)). Check credentials and server access."
        exit $proc.ExitCode
    }
}

Write-Host "All files uploaded."
exit 0
