#!/usr/bin/env bash
set -euo pipefail

root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
stage="${1:?Usage: stage-cpanel-deploy.sh NEW_STAGING_DIRECTORY}"
if [[ -e "$stage" ]]; then
  printf 'Staging path already exists: %s\n' "$stage" >&2
  exit 1
fi
test -f "$root/cms/vendor/autoload.php"

mkdir -p "$stage/portfolio-app/bootstrap" "$stage/portfolio-app/database" "$stage/public_html"
for folder in app config resources routes; do
  cp -a "$root/cms/$folder" "$stage/portfolio-app/"
done
cp "$root/cms/bootstrap/"{app,providers}.php "$stage/portfolio-app/bootstrap/"
cp "$root/cms/"{composer.json,composer.lock} "$stage/portfolio-app/"
cp -a "$root/cms/database/migrations" "$stage/portfolio-app/database/"
cp -a "$root/cms/vendor" "$stage/vendor"
sha256sum "$root/cms/composer.lock" | cut -d ' ' -f 1 > "$stage/vendor-lock.sha256"

for folder in assets css js; do
  cp -a "$root/cms/public/$folder" "$stage/public_html/"
  # The existing cPanel packager gives top-level asset sources precedence.
  cp -a "$root/$folder/." "$stage/public_html/$folder/"
done
cp "$root/cms/public/index.cpanel.php" "$stage/public_html/index.php"
cp "$root/cms/public/favicon.ico" "$stage/public_html/"
if [[ -d "$root/cms/lang" ]]; then
  cp -a "$root/cms/lang" "$stage/portfolio-app/"
fi

if find "$stage" \( -type l -o -name '.env' -o -name '.env.*' -o -name '*.sql' \) -print | grep .; then
  echo 'The staging tree contains a link, environment file, or SQL file.' >&2
  exit 1
fi
