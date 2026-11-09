$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$cms = Join-Path $root "cms"
$dist = Join-Path $root "dist"
$stage = Join-Path $dist "stage"
$zip = Join-Path $root "portfolio-cpanel.zip"
$sql = Join-Path $root "portfolio-final.sql"

Write-Host "Cleaning stage..."
if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
New-Item -ItemType Directory -Force -Path (Join-Path $stage "portfolio-app"), (Join-Path $stage "public_html") | Out-Null

Write-Host "Copying Laravel app (excluding heavy/dev noise)..."
$excludeDirs = @('.git', 'node_modules', 'tests', '.phpunit.cache', 'storage\logs', 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views')
# Robocopy app
robocopy $cms (Join-Path $stage "portfolio-app") /E /XD .git node_modules tests .phpunit.cache /XF .env /NFL /NDL /NJH /NJS /nc /ns /np | Out-Null

# Ensure runtime dirs exist empty
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

# Remove local .env from package if copied
$envPath = Join-Path $stage "portfolio-app\.env"
if (Test-Path $envPath) { Remove-Item $envPath -Force }

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

# Keep a minimal public folder inside portfolio-app for artisan/local tooling
# but public_html is the real web root on cPanel.

Write-Host "Dumping SQL (no users passwords)..."
$mysql = "C:\xampp\mysql\bin\mysqldump.exe"
if (-not (Test-Path $mysql)) { $mysql = "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe" }
& $mysql -u root --default-character-set=utf8mb4 --routines=false --triggers=false --no-tablespaces portfolio_cms | Out-File -Encoding utf8 $sql

# Strip CREATE DATABASE / USE / DEFINER and wipe users data inserts
$sqlText = Get-Content $sql -Raw
$sqlText = $sqlText -replace '(?m)^CREATE DATABASE.*\r?\n', ''
$sqlText = $sqlText -replace '(?m)^USE .*?\r?\n', ''
$sqlText = $sqlText -replace 'DEFINER=`[^`]+`@`[^`]+`', ''
# Replace users INSERT block with empty table note
$sqlText = $sqlText -replace '(?s)(LOCK TABLES `users` WRITE;).*?(UNLOCK TABLES;)', '$1`r`n/*!40000 ALTER TABLE `users` DISABLE KEYS */;`r`n/*!40000 ALTER TABLE `users` ENABLE KEYS */;`r`n$2'
# Also remove any INSERT INTO `users`
$sqlText = [regex]::Replace($sqlText, '(?m)^INSERT INTO `users`.*?;\r?\n', '')
$header = @"
-- AZ Portfolio CMS
-- Import into an empty database via phpMyAdmin.
-- Character set: utf8mb4
-- No CREATE DATABASE / no DEFINER / no default admin password.
-- Create the first owner with tools/make-owner-hash.php (see INSTALL-CPANEL.md).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

"@
$footer = "`r`nSET FOREIGN_KEY_CHECKS=1;`r`n"
Set-Content -Path $sql -Value ($header + $sqlText + $footer) -Encoding utf8

Write-Host "Zipping with tar..."
if (Test-Path $zip) { Remove-Item $zip -Force }
Push-Location $stage
tar -a -cf $zip portfolio-app public_html
Pop-Location

Write-Host "Done:"
Write-Host "  $zip"
Write-Host "  $sql"
Get-Item $zip, $sql | Select-Object FullName, Length
