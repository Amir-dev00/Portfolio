# AZ Portfolio — Laravel (source of truth)

This Laravel app is the **canonical** portfolio: public Blade site, bilingual EN/FA, admin CMS, and cPanel packaging.

The root `index.html` is a static fallback mirror for design reference. Prefer running **this** `cms/` app.

## Requirements

- **PHP 8.3+** (Laravel 13)
- Composer 2
- MySQL / MariaDB
- Extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `gd`, `zip`

## Local setup (first time)

```bash
cd cms
composer install
copy .env.example .env   # Windows
# cp .env.example .env   # macOS/Linux
php artisan key:generate
```

Edit `.env` for local MySQL (example):

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
SESSION_SECURE_COOKIE=false   # local runs over plain http

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolio_cms
DB_USERNAME=root
DB_PASSWORD=
```

Create the database, then either:

```bash
# A) Import the packaged SQL (no Artisan migrate needed)
#    phpMyAdmin / mysql client → import ../portfolio-final.sql

# B) Or migrate + seed from this machine
php artisan migrate --force
php artisan db:seed --force
```

Create the owner account (prompts for a password, min 12 chars; refuses to touch an existing user):

```bash
php artisan portfolio:create-owner you@example.com
```

## One-command start (after setup)

```bash
cd cms
php artisan serve
```

Open:

- Site: http://127.0.0.1:8000/
- Persian: http://127.0.0.1:8000/fa
- Admin: http://127.0.0.1:8000/admin/login

## Sync static assets from repo root

If you edit root `css/`, `js/`, or `assets/`, copy into `public/`:

```powershell
# From repo root (Windows)
robocopy css cms\public\css /E
robocopy js cms\public\js /E
robocopy assets cms\public\assets /E
```

## cPanel package

From repo root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\build-cpanel-package.ps1
```

Outputs under `dist/`:

- `portfolio-cpanel.zip`
- `portfolio-final.sql`
- `INSTALL-CPANEL.md`
