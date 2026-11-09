# Install AZ Portfolio CMS on cPanel (no SSH)

This package is a **Laravel** app with a bilingual public site and owner-only `/admin`.  
All PHP dependencies are already inside the ZIP. You do **not** run Composer, npm, Artisan, or migrations on the server.

## What you need in cPanel

Check these before uploading:

1. **PHP 8.2 or newer** (MultiPHP / Select PHP Version). Laravel 13 needs PHP 8.2+.
2. Extensions enabled: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `gd`, `zip`.
3. **MySQL or MariaDB** database you can create in cPanel (utf8mb4).
4. Domain document root is **`public_html`** (common shared hosting). This package does **not** need a symlink.

Compatibility assumption if your host versions are unknown: modern cPanel with PHP 8.2+ and MariaDB 10.3+ / MySQL 5.7+.

## Files you received

- `portfolio-cpanel.zip` — production files
- `portfolio-final.sql` — schema + seeded portfolio content (no admin password)
- `tools/make-owner-hash.php` — run **on your PC** to create the first owner SQL

## 1. Create the database

1. cPanel → **MySQL® Databases**
2. Create a database (example: `user_portfolio`)
3. Create a database user with a strong password
4. Add the user to the database with **ALL PRIVILEGES**
5. Note the full names cPanel shows (often prefixed), host (`localhost`), username, password

## 2. Import SQL

1. Open **phpMyAdmin**
2. Select the empty database
3. **Import** → choose `portfolio-final.sql` → Go
4. Confirm tables exist (`projects`, `publications`, `content_entries`, `migrations`, etc.)
5. Confirm the `users` table is **empty** (no default login)

## 3. Upload and extract the ZIP

1. Upload `portfolio-cpanel.zip` to your home directory (the folder that contains `public_html`), not only inside `public_html`
2. Extract it so you get:

```text
/home/YOUR_ACCOUNT/
  portfolio-app/     ← Laravel application + vendor/
  public_html/       ← web root (index.php, css, js, assets, media)
```

If your account already has a `public_html` with an old site:

1. Back up and empty (or rename) the old `public_html`
2. Move the extracted `public_html/*` into your real `public_html`
3. Keep `portfolio-app` as a **sibling** of `public_html` (same parent folder)

## 4. Create `.env` on the server

In File Manager open `portfolio-app/` and create a file named `.env` (copy from `.env.example` if present).

Set at least:

```env
APP_NAME="AZ Portfolio"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://YOUR-DOMAIN.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error
```

### Generate `APP_KEY` on your PC

On your computer (with PHP):

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Paste the result as `APP_KEY=base64:...` in `.env`.

Do **not** leave `APP_DEBUG=true` in production.

## 5. Folder permissions

In File Manager, make these writable (usually `0755` dirs / `0644` files; if uploads fail, set the dirs to `0775` or `0777` temporarily):

- `portfolio-app/storage` (and all subfolders)
- `portfolio-app/bootstrap/cache`
- `public_html/media/published`

## 6. Create the first owner (phpMyAdmin)

On your PC:

```bash
php tools/make-owner-hash.php you@example.com 'YourStrongPasswordHere'
```

Copy the printed `INSERT INTO users ...` statement into phpMyAdmin → SQL → Go.

Then open:

`https://YOUR-DOMAIN.com/admin/login`

## 7. Verify installation

1. Public home loads: `/`
2. Persian route works: `/fa` and `/fa/contact` (RTL + Persian copy)
3. Project URL works: `/work/personal-portfolio-website`
4. View source contains `og:title`, `canonical`, `hreflang`, and `AZ_BOOTSTRAP`
5. `/sitemap.xml` and `/robots.txt` load
6. Login to `/admin`
7. Edit a content string → **Save draft** → confirm public page unchanged
8. Click **Publish live** → confirm public page updates after refresh
9. Upload a CV PDF under Resume → Publish → confirm download link appears after publish
10. Confirm draft media is not directly guessable under a public drafts URL (drafts live outside `public_html`)

## Day-to-day publishing

- Edit content/projects/SEO/resume in `/admin`
- Changes stay in **draft** until you click **Publish live**
- Publish is atomic (one publication snapshot becomes current)
- Use **Rollback** on the dashboard to restore a previous snapshot

## Backup before upgrades

1. Download a full home-directory backup (or at least `portfolio-app` + `public_html`)
2. In phpMyAdmin → Export your database
3. Then replace files / import an upgrade SQL only if one is provided

## Host checks that only you can confirm in cPanel

- Exact PHP version and enabled extensions
- That the domain really points at `public_html`
- That the DB user is limited to this database
- HTTPS / Force HTTPS for secure admin cookies
- Disk quota large enough for `vendor/` + images
