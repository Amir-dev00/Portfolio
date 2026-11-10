# Amirhossein Zamanzade — Portfolio

Cinematic personal portfolio with bilingual EN/FA support.

## Source of truth

**`cms/`** is the Laravel + Blade application (public site + `/admin` CMS).

- Local docs: [`cms/README.md`](cms/README.md)
- cPanel install: [`INSTALL-CPANEL.md`](INSTALL-CPANEL.md)
- Packaged outputs: `dist/portfolio-cpanel.zip`, `dist/portfolio-final.sql`

Root `index.html` + `css/` + `js/` + `assets/` remain as the design/source frontend assets that are synced into `cms/public` for Laravel.

## Quick local run

```bash
cd cms
composer install
cp .env.example .env   # or copy on Windows
php artisan key:generate
# configure DB_* in .env, create DB, import portfolio-final.sql (or migrate --seed)
php artisan serve
```

Requires **PHP 8.3+**.

## Production (cPanel, no SSH)

Build on your PC:

```powershell
cd cms
composer install --no-dev --optimize-autoloader
cd ..
powershell -ExecutionPolicy Bypass -File scripts\build-cpanel-package.ps1
```

Then follow `dist/INSTALL-CPANEL.md` (upload ZIP, import SQL, create `.env`, set permissions).
