# Deploy AZ Portfolio to cPanel (`itsamirzamani.ir`)

This guide is for **shared hosting with no SSH / no terminal**. Every hosting step uses cPanel File Manager, Domains / MultiPHP, and phpMyAdmin. Composer, Artisan, and packaging commands run only on **your PC**.

Intended public site: `https://itsamirzamani.ir`  
Admin: `https://itsamirzamani.ir/admin/login`

Related files in this repository:

| File | Role |
| --- | --- |
| `scripts/build-cpanel-package.ps1` | Builds `dist/portfolio-cpanel.zip` + SQL from the local app |
| `portfolio-final.sql` / `dist/portfolio-final.sql` | Fresh-install schema + published portfolio content |
| `tools/make-owner-hash.php` | Creates the SQL `INSERT` for the first admin (run on your PC) |
| `dist/upgrades/*.sql` | Additive SQL for **existing** installs only |
| `INSTALL-CPANEL.md` | Longer companion notes (fonts, booking extras) |

---

## 0. Requirements (check in cPanel first)

### PHP

Laravel 13 in this repo requires **PHP 8.3 or newer** (`composer.json`: `"php": "^8.3"`).

cPanel → **MultiPHP Manager** / **Select PHP Version** → set the domain to **8.3+** (8.4 is fine if offered).

### PHP extensions

Enable (or confirm) at least:

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `gd`, `zip`, `dom`, `bcmath`, `intl` (if listed).

Composer also pulls `dompdf/dompdf` (PDF) and Laravel’s usual stack; GD is used for media handling.

### Database

MySQL 5.7+ or MariaDB 10.3+ with **utf8mb4**.

### Document root

The domain document root must be **`public_html`**. The layout below keeps Laravel’s app **outside** the web root.

### SSL

cPanel → **SSL/TLS Status** → AutoSSL for `itsamirzamani.ir` (and `www` if used). Admin cookies need HTTPS in production.

---

## 1. Prepare the package on your PC (local only)

From the repository root:

```powershell
cd cms
composer install --no-dev --optimize-autoloader
cd ..
powershell -ExecutionPolicy Bypass -File scripts\build-cpanel-package.ps1
```

What the script does:

1. Syncs root `css/`, `js/`, and `assets/` into `cms/public/` (editable frontend source → public mirrors).
2. Stages `portfolio-app/` (Laravel + `vendor/`, **without** a second copy of public assets) and `public_html/` (web root).
3. Writes `dist/portfolio-cpanel.zip`, `dist/portfolio-final.sql`, copies `INSTALL-CPANEL.md`, `tools/make-owner-hash.php`, and `dist/upgrades/*.sql`.

If you already have a current `portfolio-final.sql` at the repo root (regenerated after content/image updates), the script refreshes it from the local MySQL database named `portfolio_cms` when mysqldump is available.

**Upload these from `dist/` (or repo root copies):**

- `portfolio-cpanel.zip`
- `portfolio-final.sql` — **fresh install only**
- `tools/make-owner-hash.php` (keep on your PC; you only paste its SQL output into phpMyAdmin)
- For upgrades: `upgrades/*.sql` as needed

Do **not** upload your local `.env`, `storage/logs`, or API keys.

---

## 2. Fresh installation (empty hosting account / empty DB)

### 2.1 Create the database

1. cPanel → **MySQL® Databases**.
2. Create a database (example name shape: `account_portfolio`).
3. Create a DB user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Note the **full prefixed** database name, username, and password.

### 2.2 Import `portfolio-final.sql` (fresh DB only)

1. phpMyAdmin → select the **empty** database (already selected — do not create another).
2. **Import** → choose `portfolio-final.sql` → **Go**.
3. Confirm tables such as `projects`, `publications`, `content_entries`, `media_assets`, `migrations` exist.
4. `users` is intentionally empty (no default admin).

> **Never** import `portfolio-final.sql` into a database that already has live content. It recreates tables and would wipe production data. For updates, see [§4 Upgrade checklist](#4-upgrade-checklist-existing-installation).

### 2.3 Upload and extract the ZIP

1. File Manager → open the account home that **contains** `public_html` (e.g. `/home/ACCOUNT/`).
2. Upload `portfolio-cpanel.zip` there → **Extract**.
3. You should get:

```text
/home/ACCOUNT/
  portfolio-app/     ← Laravel app, vendor/, storage/  (NOT web-accessible)
  public_html/       ← document root: index.php, .htaccess, css/, js/, assets/, media/
```

If `public_html` already has an old site: back it up (Compress → download), empty it (including hidden files), then move the extracted `public_html/*` into it. Show hidden files so `.htaccess` is copied.

`portfolio-app` must stay a **sibling** of `public_html`. No symlinks required.

### 2.4 Generate `APP_KEY` on your PC

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Or: `cd cms` → `php artisan key:generate --show`.

Never reuse a key from another site. Never commit it.

### 2.5 Create `portfolio-app/.env`

In File Manager: copy `.env.example` → `.env` inside `portfolio-app/`, then edit:

```env
APP_NAME="AZ Portfolio"
APP_ENV=production
APP_KEY=base64:PASTE_THE_KEY_FROM_STEP_2_4
APP_DEBUG=false
APP_URL=https://itsamirzamani.ir

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR_PREFIXED_DATABASE_NAME
DB_USERNAME=YOUR_PREFIXED_DATABASE_USER
DB_PASSWORD=YOUR_DATABASE_PASSWORD

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error

# Optional — portfolio assistant (AvalAI / OpenAI-compatible)
ASSISTANT_ENABLED=false
OPENAI_API_KEY=
OPENAI_MODEL=gpt-6-luna
OPENAI_RESPONSES_URL=https://api.avalai.ir/v1/responses

# Optional — SMS.ir owner alert for bookings
SMSIR_ENABLED=false
SMSIR_API_KEY=
SMSIR_LINE_NUMBER=
SMSIR_OWNER_MOBILE=
```

- Keep `APP_DEBUG=false`.
- `APP_URL` must be the exact `https://` origin used in the browser (canonical / hreflang / sitemap).
- Permissions on `.env`: `0640` or `0600`.

### 2.6 Writable directories

On typical cPanel (PHP runs as your user), File Manager defaults are enough:

| Path | Permission |
| --- | --- |
| Folders under `portfolio-app/storage/**`, `portfolio-app/bootstrap/cache` | `0755` |
| `public_html/media/published` (and children) | `0755` |
| Files | `0644` |
| `portfolio-app/.env` | `0640` / `0600` |

Never use `0777`.

### 2.7 Public media layout

Published CMS files are served from the web root:

```text
public_html/media/published/img/...
public_html/assets/img/...          ← bundled site images (home, logos, project galleries)
public_html/assets/cv/...           ← CV PDF when published there
public_html/css/  public_html/js/
```

Private / draft library files stay under `portfolio-app/storage/app/` (not web-accessible). Publishing copies approved media into `public_html/media/published/`.

### 2.8 Create the first admin (on your PC)

```bash
php tools/make-owner-hash.php you@example.com
```

Enter a password (≥ 12 characters). Paste the printed `INSERT INTO users ...` into phpMyAdmin → **SQL** → **Go**.  
If the email already exists, the insert fails harmlessly (it will not overwrite an account).

### 2.9 Force HTTPS

After AutoSSL is active: Domains → **Force HTTPS Redirect**, or uncomment the HTTPS block in `public_html/.htaccess`.

---

## 3. First-deploy checklist

1. `https://itsamirzamani.ir/` shows the AZ preloader, then Home (EN by default).
2. `/work`, `/about`, `/ask`, `/contact` load and survive refresh.
3. Open a project gallery (e.g. `/work/novin-fitnet`) — covers and gallery WebPs load (no broken images).
4. `/fa`, `/fa/work`, `/fa/about`, `/fa/ask`, `/fa/contact` are Persian RTL.
5. Contact → **Book a Call** opens the booking UI (availability may be empty until you set hours in Admin → Bookings).
6. `/admin/login` works with the owner you inserted.
7. `https://itsamirzamani.ir/.env` and paths under `/portfolio-app/` are **not** publicly readable (404/403).
8. `/sitemap.xml` and `/robots.txt` load.
9. Optional: enable the assistant in `.env`, then test Ask / Home Miro chat.

---

## 4. Upgrade checklist (existing installation)

Your live content lives in the **database**, `portfolio-app/.env`, `portfolio-app/storage/app/`, and `public_html/media/published/`.

### 4.1 Back up first

1. cPanel → **Backup** / **Backup Wizard** — full or home directory backup.
2. phpMyAdmin → select the live database → **Export** → Quick / SQL → download.  
   Keep this file until you confirm the upgrade. **Do not** replace this backup with `portfolio-final.sql`.

### 4.2 Database changes (additive only)

**Do not import `portfolio-final.sql` into production.**

If the new release ships SQL under `dist/upgrades/`, run only the files you have not applied yet, in filename order, via phpMyAdmin → **SQL**. Those scripts are written to add missing tables/rows and to be safe to re-run where noted in each file’s header.

After booking-related upgrades, open **Admin → Bookings** / **Site content** and **Publish live** if copy changed.

### 4.3 Replace code, keep data

1. Extract the new ZIP to a temporary folder (e.g. `/home/ACCOUNT/upgrade/`).
2. Replace `portfolio-app/**` **except** `.env` and `storage/app/`.
3. Replace `public_html/index.php`, `.htaccess`, `css/`, `js/`, `assets/`.  
   **Keep** `public_html/media/published/` (merge new bundled assets; do not delete visitor-published media unless intentional).
4. Delete compiled caches: files inside `portfolio-app/bootstrap/cache/` and `portfolio-app/storage/framework/views/` (leave the folders).
5. In `/admin`, click **Publish live** once if the release expects a new public snapshot.

### 4.4 Verify

Re-run the [first-deploy checklist](#3-first-deploy-checklist). If something fails, restore the home-directory backup and, if you ran upgrade SQL, restore the database export.

---

## 5. Image / asset policy (for packagers)

- Site **content** images ship as **WebP**, each under **100 KB**, under `public_html/assets/img/` and `public_html/media/published/`.
- **SVG** vectors stay SVG (`quote-*.svg`, `default-skin.svg`, etc.).
- **PNG / ICO exceptions** (icon integration — do not convert away):
  - `public_html/favicon.ico`
  - `assets/img/content/favicon-*.png`, `apple-icon-*.png`, `android-icon-*.png`, `ms-icon-*.png`
  - `assets/img/amir/favicon.png`
  - Tiny Rhye UI chrome: `assets/img/general/default-skin.png`, `select_black.png`
- Re-optimize locally with `php tools/optimize-content-webp.php` before packaging when images change.

---

## 6. Optional features after go-live

- **Book a Call**: Admin → Bookings (hours, buffers). Details also in `INSTALL-CPANEL.md`.
- **Assistant (Miro)**: set `ASSISTANT_ENABLED=true` and provider keys in `.env`.
- **CV**: Admin → Resume → upload PDF → Publish live.

---

## 7. Rollback

- Bad **code** upgrade: restore the File Manager / home backup of `portfolio-app` and `public_html`.
- Bad **SQL** upgrade: restore the phpMyAdmin export taken in §4.1.
- Content mistake: Admin → Dashboard → **Rollback** to a previous publication (no SQL needed).
