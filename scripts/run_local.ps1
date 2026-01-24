param(
  [string]$BindHost = '127.0.0.1',
  [int]$Port = 8080
)

$ErrorActionPreference = 'Stop'

function Require-Command([string]$name) {
  if (-not (Get-Command $name -ErrorAction SilentlyContinue)) {
    throw "Required command not found: $name"
  }
}

Require-Command php

Write-Host "Checking PHP extensions..." -ForegroundColor Cyan
$mods = php -m
if (-not ($mods -contains 'pdo_pgsql')) {
  throw "PHP extension pdo_pgsql is not loaded. Enable it in php.ini and restart PHP." 
}

Write-Host "Initializing / migrating database schema..." -ForegroundColor Cyan
php setup_database.php

Write-Host "Starting dev server on http://${BindHost}:$Port" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop." -ForegroundColor DarkGray
php -S "${BindHost}:$Port" -t public
