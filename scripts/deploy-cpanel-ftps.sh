#!/usr/bin/env bash
set -euo pipefail

stage="${1:?Usage: deploy-cpanel-ftps.sh STAGING_DIRECTORY}"
: "${FTPS_HOST:?Set FTPS_HOST}" "${FTPS_USERNAME:?Set FTPS_USERNAME}" "${FTPS_PASSWORD:?Set FTPS_PASSWORD}"
port="${FTPS_PORT:-21}"
if [[ ! "$FTPS_HOST" =~ ^[A-Za-z0-9.-]+$ || ! "$FTPS_USERNAME" =~ ^[A-Za-z0-9._@+-]+$ ||
      ! "$port" =~ ^[0-9]{1,5}$ || "10#$port" -lt 1 || "10#$port" -gt 65535 ]]; then
  echo 'Use a hostname without a scheme, a cPanel FTP login, and a valid port.' >&2
  exit 1
fi

cd -- "$stage"
test -f portfolio-app/composer.lock && test -f public_html/index.php && test -f vendor-lock.sha256
export LFTP_PASSWORD="$FTPS_PASSWORD"

run_ftps() {
  lftp --norc -f /dev/stdin <<LFTP
set cmd:fail-exit yes
set net:max-retries 2
set net:timeout 20s
set ftp:ssl-allow yes
set ftp:ssl-force yes
set ftp:ssl-protect-data yes
set ftp:ssl-protect-list yes
set ssl:verify-certificate yes
set xfer:use-temp-file yes
open --env-password -u "$FTPS_USERNAME" -p "$port" "ftp://$FTPS_HOST"
$1
bye
LFTP
}

# Refuse to create a new tree in the wrong account or FTP home.
run_ftps 'cd /portfolio-app/vendor/composer
cd /public_html/css'

# Upgrade dependencies before application code when the locked versions change.
if ! run_ftps 'get /portfolio-app/.az-vendor-lock -o previous-vendor-lock' 2>/dev/null ||
   ! cmp -s previous-vendor-lock vendor-lock.sha256; then
  run_ftps 'mirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 vendor/ /portfolio-app/vendor/'
else
  # Keep generated autoload maps current when an application class moves or is added.
  run_ftps 'mirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 vendor/composer/ /portfolio-app/vendor/composer/'
fi

run_ftps 'mirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 public_html/ /public_html/
mirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 portfolio-app/ /portfolio-app/
cd /portfolio-app/bootstrap/cache
rm -f config.php routes-v7.php packages.php services.php
put vendor-lock.sha256 -o /portfolio-app/.az-vendor-lock'
