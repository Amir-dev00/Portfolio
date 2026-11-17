#!/usr/bin/env bash
set -euo pipefail

phase="${1:?Usage: deploy-cpanel-ftps.sh read|apply|finish STAGING_DIRECTORY}"
stage="${2:?Set the staging directory}"
: "${FTPS_HOST:?Set FTPS_HOST}" "${FTPS_USERNAME:?Set FTPS_USERNAME}" "${FTPS_PASSWORD:?Set FTPS_PASSWORD}"
port="${FTPS_PORT:-21}"
if [[ ! "$FTPS_HOST" =~ ^[A-Za-z0-9.-]+$ || ! "$FTPS_USERNAME" =~ ^[A-Za-z0-9._@+-]+$ ||
      ! "$port" =~ ^[0-9]{1,5}$ || "10#$port" -lt 1 || "10#$port" -gt 65535 ]]; then
  echo 'Use a hostname without a scheme, a cPanel FTP login, and a valid port.' >&2
  exit 1
fi

mkdir -p -- "$stage"
cd -- "$stage"
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

fetch_optional() {
  if run_ftps "get /portfolio-app/$1 -o $2" 2>download-error; then
    return
  fi
  if grep -Eq '550.*([Nn]o such file|[Nn]ot found|[Dd]oes not exist)' download-error; then
    return
  fi
  cat download-error >&2
  exit 1
}

case "$phase" in
  read)
    # Check the existing installation before interpreting a missing checkpoint.
    run_ftps 'cd /portfolio-app/vendor/composer
cd /public_html/css'
    fetch_optional .az-deploy-state.json remote-state.json
    if [[ ! -e remote-state.json ]]; then
      fetch_optional composer.json live-composer.json
      fetch_optional composer.lock live-composer.lock
    fi
    ;;
  apply)
    remote_state=/portfolio-app/.az-deploy-state.json
    commands="put pending.json -o $remote_state"
    if [[ "${INSTALL_VENDOR:-false}" == true ]]; then
      commands+=$'\nmirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 vendor/ /portfolio-app/vendor/'
    fi
    if [[ -d files/public_html ]]; then
      commands+=$'\nmirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 files/public_html/ /public_html/'
    fi
    if [[ -d files/portfolio-app ]]; then
      commands+=$'\nmirror --reverse --no-perms --no-symlinks --transfer-all --parallel=4 files/portfolio-app/ /portfolio-app/'
    fi
    if [[ -s delete.lftp ]]; then
      commands+=$'\n'"$(cat delete.lftp)"
    fi
    if [[ "${CLEAR_APP_CACHE:-false}" == true ]]; then
      commands+=$'\ncd /portfolio-app/bootstrap/cache\nrm -f config.php routes-v7.php packages.php services.php'
    fi
    run_ftps "$commands"
    ;;
  finish)
    run_ftps 'put complete.json -o /portfolio-app/.az-deploy-state.json'
    ;;
  *)
    echo 'Unknown deployment phase.' >&2
    exit 1
    ;;
esac
