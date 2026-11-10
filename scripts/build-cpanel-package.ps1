$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$cms = Join-Path $root "cms"
$dist = Join-Path $root "dist"
$stage = Join-Path $dist "stage"
$zip = Join-Path $dist "portfolio-cpanel.zip"
$sql = Join-Path $dist "portfolio-final.sql"
$sqlRoot = Join-Path $root "portfolio-final.sql"
$installSrc = Join-Path $root "INSTALL-CPANEL.md"
$installDst = Join-Path $dist "INSTALL-CPANEL.md"

Write-Host "Syncing root frontend assets into cms/public..."
foreach ($pair in @(
  @{s='css'; d='public\css'},
  @{s='js'; d='public\js'},
  @{s='assets'; d='public\assets'}
)) {
  $src = Join-Path $root $pair.s
  $dst = Join-Path $cms $pair.d
  if (Test-Path $src) {
    robocopy $src $dst /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
  }
}

if (-not (Test-Path (Join-Path $cms "vendor\autoload.php"))) {
  throw "cms/vendor missing. Run: cd cms; composer install --no-dev --optimize-autoloader"
}

Write-Host "Cleaning stage..."
if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
New-Item -ItemType Directory -Force -Path (Join-Path $stage "portfolio-app"), (Join-Path $stage "public_html") | Out-Null

Write-Host "Copying Laravel app (excluding heavy/dev noise)..."
robocopy $cms (Join-Path $stage "portfolio-app") /E `
  /XD .git node_modules tests .phpunit.cache .idea .vscode `
  /XF .env .env.backup .env.production *.log phpunit.xml AGENTS.md CLAUDE.md .phpunit.result.cache `
  /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null

# Strip local runtime state: compiled views, sessions, caches, logs, local helper files
# (e.g. storage/app/owner-sql.txt holds a local password hash), and bootstrap caches.
$app = Join-Path $stage "portfolio-app"
# On cPanel the web root is public_html (built below); don't ship a second copy of the assets.
foreach ($d in @('css','js','assets','media')) {
  $p = Join-Path $app "public\$d"
  if (Test-Path $p) { Remove-Item -Recurse -Force $p }
}
foreach ($dir in @(
  "storage\framework\views",
  "storage\framework\sessions",
  "storage\framework\cache",
  "storage\framework\testing",
  "storage\logs",
  "storage\debugbar"
)) {
  $p = Join-Path $app $dir
  if (Test-Path $p) {
    Get-ChildItem $p -Recurse -File | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force
  }
}
Get-ChildItem (Join-Path $app "storage\app") -File -ErrorAction SilentlyContinue |
  Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force
Get-ChildItem (Join-Path $app "bootstrap\cache") -File -Filter *.php -ErrorAction SilentlyContinue | Remove-Item -Force
$publicStorage = Join-Path $app "storage\app\public"
if (Test-Path $publicStorage) {
  Get-ChildItem $publicStorage -Recurse -File | Where-Object { $_.Name -ne ".gitignore" } | Remove-Item -Force
}

$runtime = @(
  "storage\app\public",
  "storage\app\private",
  "storage\framework\cache",
  "storage\framework\sessions",
  "storage\framework\views",
  "storage\logs",
  "bootstrap\cache"
)
foreach ($r in $runtime) {
  New-Item -ItemType Directory -Force -Path (Join-Path $stage "portfolio-app\$r") | Out-Null
}

$envPath = Join-Path $stage "portfolio-app\.env"
if (Test-Path $envPath) { Remove-Item $envPath -Force }

# Ensure production .env.example ships with empty APP_KEY
Copy-Item (Join-Path $cms ".env.example") (Join-Path $stage "portfolio-app\.env.example") -Force

Write-Host "Building public_html..."
$ph = Join-Path $stage "public_html"
Copy-Item (Join-Path $cms "public\index.cpanel.php") (Join-Path $ph "index.php") -Force
Copy-Item (Join-Path $cms "public\.htaccess") (Join-Path $ph ".htaccess") -Force
foreach ($d in @('css','js','assets','media')) {
  $src = Join-Path $cms "public\$d"
  if (Test-Path $src) {
    robocopy $src (Join-Path $ph $d) /E /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null
  }
}

Write-Host "Dumping SQL (no user passwords)..."
$mysql = "C:\xampp\mysql\bin\mysqldump.exe"
if (-not (Test-Path $mysql)) { $mysql = "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe" }
if (-not (Test-Path $mysql)) { throw "mysqldump not found" }

& $mysql -u root --default-character-set=utf8mb4 --routines=false --triggers=false --no-tablespaces portfolio_cms |
  Out-File -Encoding utf8 $sqlRoot

$sqlText = Get-Content $sqlRoot -Raw
$sqlText = $sqlText -replace '(?m)^CREATE DATABASE.*\r?\n', ''
$sqlText = $sqlText -replace '(?m)^USE .*?\r?\n', ''
$sqlText = $sqlText -replace 'DEFINER=`[^`]+`@`[^`]+`', ''
# Schema only for accounts and runtime tables: no users, password hashes, sessions, cache or jobs ship.
foreach ($t in @('users','sessions','password_reset_tokens','cache','cache_locks','jobs','job_batches','failed_jobs')) {
  $sqlText = [regex]::Replace($sqlText, "(?s)LOCK TABLES ``$t`` WRITE;.*?UNLOCK TABLES;\r?\n", '')
  $sqlText = [regex]::Replace($sqlText, "(?m)^INSERT INTO ``$t``.*\r?\n", '')
}
$header = @"
-- AZ Portfolio CMS
-- Import into an empty database via phpMyAdmin.
-- Character set: utf8mb4
-- No CREATE DATABASE / no DEFINER / no default admin password.
-- Create the first owner with tools/make-owner-hash.php (see INSTALL-CPANEL.md).
-- Requires PHP 8.3+ on the host (Laravel 13).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

"@
$footer = "`r`nSET FOREIGN_KEY_CHECKS=1;`r`n"
Set-Content -Path $sqlRoot -Value ($header + $sqlText + $footer) -Encoding utf8
Copy-Item $sqlRoot $sql -Force
Copy-Item $installSrc $installDst -Force
New-Item -ItemType Directory -Force -Path (Join-Path $dist "tools") | Out-Null
Copy-Item (Join-Path $root "tools\make-owner-hash.php") (Join-Path $dist "tools\make-owner-hash.php") -Force
$upgradesDst = Join-Path $dist "upgrades"
New-Item -ItemType Directory -Force -Path $upgradesDst | Out-Null
Copy-Item (Join-Path $cms "database\upgrades\*.sql") $upgradesDst -Force

Write-Host "Verifying stage has no secrets/runtime state..."
$bad = Get-ChildItem $stage -Recurse -File -Force | Where-Object {
  $_.Name -eq ".env" -or $_.Name -like "*.log" -or $_.Name -eq "owner-sql.txt" -or
  $_.FullName -match '\\storage\\framework\\(views|sessions)\\[^\\]+\.php$' -or
  $_.FullName -match '\\bootstrap\\cache\\[^\\]+\.php$'
}
if ($bad) { $bad | ForEach-Object { Write-Host "  BAD: $($_.FullName)" }; throw "Stage contains files that must not ship." }

Write-Host "Zipping with tar..."
if (Test-Path $zip) { Remove-Item $zip -Force }
Push-Location $stage
tar -a -cf $zip portfolio-app public_html
Pop-Location

# Also keep a copy at repo root for older docs that mention it there
Copy-Item $zip (Join-Path $root "portfolio-cpanel.zip") -Force

Write-Host "Done:"
Get-Item $zip, $sql, $installDst | Format-Table FullName, Length -AutoSize
