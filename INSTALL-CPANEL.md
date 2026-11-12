# Install AZ Portfolio on cPanel (no SSH, no terminal)

The ZIP contains the complete Laravel app with all PHP dependencies (`vendor/`) already installed.
You do **not** run Composer, npm, Artisan, or migrations on the server. Everything is done with
cPanel File Manager and phpMyAdmin.

Files delivered in `dist/`:

| File | Purpose |
| --- | --- |
| `portfolio-cpanel.zip` | `portfolio-app/` (Laravel, outside the web root) + `public_html/` (web root) |
| `portfolio-final.sql` | Full schema + published portfolio content. **No user accounts.** |
| `INSTALL-CPANEL.md` | This guide |
| `tools/make-owner-hash.php` | Run on **your PC** to generate the SQL for the first admin owner |

---

## 0. Requirements (check in cPanel first)

1. **PHP 8.3 or newer** (cPanel → MultiPHP Manager / Select PHP Version). Laravel 13 does **not** run on PHP 8.2.
2. PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `curl`, `gd`, `zip`, `dom`.
3. MySQL 5.7+ or MariaDB 10.3+ (utf8mb4).
4. The domain's document root is `public_html`.
5. An SSL certificate (cPanel → SSL/TLS Status → AutoSSL). The admin login uses secure cookies and needs HTTPS.

---

## Fresh installation

### 1. Create the database

1. cPanel → **MySQL® Databases**.
2. Create a database (e.g. `account_portfolio`).
3. Create a database user with a strong, generated password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Write down the full (prefixed) database name, user name, and password.

### 2. Import the SQL

1. cPanel → **phpMyAdmin** → select the new, **empty** database.
2. **Import** → choose `portfolio-final.sql` → **Go**.
3. Confirm tables such as `projects`, `publications`, `content_entries`, `media_assets`, `migrations` exist.
4. The `users` table is intentionally empty: there is no default login.

> Only import `portfolio-final.sql` into an **empty** database. It recreates tables and would replace
> existing content. For an existing site, follow **Upgrading an existing installation** below.

### 3. Upload the files

1. cPanel → **File Manager** → open your home directory (`/home/ACCOUNT/`, the folder that **contains** `public_html`).
2. Upload `portfolio-cpanel.zip` there and **Extract** it. You get:

```text
/home/ACCOUNT/
  portfolio-app/   <- Laravel app, vendor/, storage/ (NOT web-accessible)
  public_html/     <- web root: index.php, .htaccess, css/, js/, assets/, media/
```

If `public_html` already contains an old site, back it up (Compress → download), empty it, and then move
the extracted `public_html/*` files into it, including the hidden `.htaccess` (File Manager → Settings →
*Show Hidden Files*). `portfolio-app` must stay a **sibling** of `public_html`. No symlinks are needed.

### 4. Generate `APP_KEY` on your PC

`APP_KEY` encrypts sessions and cookies. Generate it locally; never reuse a key from another site and never commit it to Git.

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

No PHP on your PC? From the project folder, `php artisan key:generate --show` does the same.

### 5. Create `portfolio-app/.env`

In File Manager, open `portfolio-app/`, copy `.env.example` to `.env`, then **Edit** it:

```env
APP_NAME="AZ Portfolio"
APP_ENV=production
APP_KEY=base64:PASTE_THE_KEY_FROM_STEP_4
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=account_portfolio
DB_USERNAME=account_portfoliouser
DB_PASSWORD=the-database-password

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error

# Optional SMS.ir owner alert (see “Owner SMS via SMS.ir” below)
SMSIR_ENABLED=false
SMSIR_API_KEY=
SMSIR_LINE_NUMBER=
SMSIR_OWNER_MOBILE=
```

- `APP_DEBUG` must stay `false` in production; `true` exposes stack traces and configuration.
- `APP_URL` must be the exact `https://` address; canonical URLs, hreflang, and the sitemap use it.
- Then right-click `.env` → **Change Permissions** → `0640` (or `0600` if your host runs PHP as your user, which is standard with suPHP/LSAPI/PHP-FPM).

### 6. Permissions

cPanel hosts run PHP as your own account user, so the defaults that File Manager creates are already correct:

| Path | Permission |
| --- | --- |
| Folders (incl. `portfolio-app/storage/**`, `portfolio-app/bootstrap/cache`, `public_html/media/published`) | `0755` |
| Files | `0644` |
| `portfolio-app/.env` | `0640` or `0600` |

Never use `0777`. If uploads or publishing fail with a permissions error, the folder is usually owned by a
different user (e.g. it was created by another tool); contact your host to reset ownership to your account instead of widening permissions.

### 7. Create the first admin owner

On **your PC**, in the folder where you saved `tools/make-owner-hash.php`:

```bash
php make-owner-hash.php you@example.com
```

It asks for a password (minimum 12 characters) and prints an `INSERT INTO users ...` statement.
In phpMyAdmin → your database → **SQL**, paste it and click **Go**. Don't save that output anywhere.

The statement fails harmlessly if the email already exists, so it can never overwrite an existing account.

### 8. Enable HTTPS redirect (recommended)

After the SSL certificate is active, edit `public_html/.htaccess` and uncomment the three lines under
`# Force HTTPS`. (Or enable cPanel → Domains → *Force HTTPS Redirect*.)

### 9. Verify

1. `https://your-domain.com/` shows the AZ preloader (always English), then the homepage slider.
2. `/work`, `/about`, `/contact`, `/work/personal-portfolio-website` load directly, survive refresh, and can be shared.
3. `/fa`, `/fa/work`, `/fa/about`, `/fa/contact` show Persian, right-to-left layout.
4. An unknown URL (e.g. `/does-not-exist`) shows the AZ 404 page.
5. `/sitemap.xml` and `/robots.txt` load.
6. View page source: `<title>`, `meta description`, `canonical`, `hreflang`, `og:*` are present.
7. `https://your-domain.com/.env` and `https://your-domain.com/portfolio-app/.env` return 403/404 (the app is outside the web root).
8. `/admin/login` → sign in → edit a text → **Save draft** → the public site is unchanged → **Publish live** → the public site updates.
9. Admin → Resume → upload a CV PDF → publish → the CV download link serves the new file.
10. Admin → Dashboard → **Rollback** to the previous publication restores the old content.

---

## Upgrading an existing installation (keeps your data and admin accounts)

Your content, projects, media and admin users live in the **database**, `portfolio-app/storage/app/`,
`portfolio-app/.env`, and `public_html/media/published/`. An upgrade replaces code only.

1. **Back up**: cPanel → Backup (home directory) and phpMyAdmin → Export (your database).
2. **Do not import `portfolio-final.sql`** into an existing database; it would replace your content.
   Database changes ship as separate files in the `upgrades/` folder next to the ZIP. They only add missing
   rows/tables and never change or delete existing content, and are safe to run twice.
   For the self-hosted calendar also run **`upgrades/2026_11_10_native_booking.sql`**
   (after the earlier `upgrades/2026_11_10_booking.sql` texts if you never imported them), then
   **`upgrades/2026_11_12_booking_owner_sms.sql`** for optional SMS.ir owner-alert columns:
   phpMyAdmin → your database → **SQL** → paste each file's contents → **Go**.
3. Extract the new ZIP into a temporary folder (e.g. `/home/ACCOUNT/upgrade/`).
4. Replace code, keeping your data:
   - `portfolio-app/`: replace everything **except** `.env` and `storage/app/` (your uploaded/private media).
   - `public_html/`: replace `index.php`, `.htaccess`, `css/`, `js/`, `assets/`; **keep** `media/published/`.
5. Delete the files inside `portfolio-app/bootstrap/cache/` (Laravel rebuilds them) and inside
   `portfolio-app/storage/framework/views/` (compiled templates).
6. In `/admin`, click **Publish live** once so the new texts reach the public site.
7. Run the **Verify** checklist above.

To roll back a bad upgrade, restore the home-directory backup files; the database stays untouched unless you ran an upgrade SQL, in which case restore the database export as well.

---

## Day-to-day publishing

- Edit content, projects, SEO, profile, social links, and resume in `/admin`.
- Changes stay in **draft** until **Publish live**; publishing switches the site to the new snapshot in one step.
- **Rollback** on the dashboard restores any previous snapshot.

## Self-hosted fonts (Manrope + Estedad)

Text fonts ship inside `public_html/assets/fonts/` — no Google Fonts install step on the server:

| Path | Role |
| --- | --- |
| `assets/fonts/manrope/manrope-variable.woff2` | English + AZ preloader (always) |
| `assets/fonts/manrope/manrope-latin-ext-variable.woff2` | Latin Extended subset (unicode-range) |
| `assets/fonts/estedad/estedad-variable.woff2` | Persian |
| `assets/fonts/.htaccess` | `font/woff2` MIME + 1-year cache |

`css/typography.css` declares both faces with `font-display: swap`. Manrope is preloaded on every page; Estedad is preloaded only when the first paint language is Persian.

**Icon fonts** (Material Icons CDN, Font Awesome / Rhye icon webfonts under `assets/fonts/`) are separate from text typography and are intentional.

After unzipping, open DevTools → Network → Font and confirm the WOFF2 files return **200** (not 404).

---

## "Book a Call" setup (self-hosted)

Scheduling runs entirely on your Laravel + MySQL host — **no Cal.com, Calendly, or Google Calendar**.

1. Import `portfolio-app/database/upgrades/2026_11_10_native_booking.sql` in phpMyAdmin (creates `bookings`, `availability_rules`, `blocked_slots`).
2. `/admin` → **Bookings** → set weekly hours, duration, buffers, blocked dates (defaults: Sat–Wed mornings/afternoons, Asia/Tehran).
3. `/admin` → **Site content** → keep **Book a Call** enabled; edit EN/FA labels if you like → **Publish live**.
4. Open `/contact` → **Book a Call**: pick a date/time, submit details. The appointment is stored in MySQL and listed under **Admin → Bookings**.
5. Visitors can download a local `.ics` invite. Optional SMTP notifications use your host mail settings; bookings still succeed if mail is offline.

Untick **Enabled** in Site content / Booking settings to show the original “Email me” primary button instead.

### Owner SMS via SMS.ir (optional)

After a booking is **confirmed and saved** in MySQL, the app can send one short SMS to your phone with **only** the booking reference and the appointment date/time in **Asia/Tehran**. Visitor contact details stay in Admin → Bookings only. Date/time browsing and failed form posts never send SMS. No queue worker or SSH cron is required — the request runs inline with a short timeout.

1. Existing installs: in phpMyAdmin run **`upgrades/2026_11_12_booking_owner_sms.sql`** (adds SMS tracking columns). Fresh `portfolio-final.sql` installs already include the columns.
2. In the [SMS.ir](https://sms.ir/) panel, create an API key and note your sending line number.
3. Edit `portfolio-app/.env` (never commit real keys):

```env
SMSIR_ENABLED=true
SMSIR_API_KEY=your-panel-api-key
SMSIR_LINE_NUMBER=3000xxxxxxxx
SMSIR_OWNER_MOBILE=09xxxxxxxxx
```

4. Save `.env`, then place a real test booking from `/contact`.
5. Check **Admin → Bookings →** the booking: **Owner SMS** should show `accepted` (provider OK), `rejected` (provider refused), or `uncertain` (timeout/network — the app will **not** auto-retry that booking, to avoid duplicates). The booking row remains either way.
6. Leave `SMSIR_ENABLED=false` (or blank keys) to keep calendar + email-only behavior.

## Things only your host can confirm

- The PHP version and extensions actually active for the domain.
- That the domain's document root is `public_html`.
- SSL is active (secure cookies require it; without HTTPS you cannot log in with `SESSION_SECURE_COOKIE=true`).
- Disk quota (the package is about 40 MB extracted with `vendor/` and images).
