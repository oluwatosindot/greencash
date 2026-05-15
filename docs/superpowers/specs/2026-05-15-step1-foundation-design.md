# Step 1: Project Foundation — Design Spec

**Date:** 2026-05-15
**Status:** Approved
**Approach:** Option B — Full foundation as specced

---

## Overview

Build the complete infrastructure layer that every subsequent step depends on. No public-facing pages are created here — only the wiring that makes all pages possible: environment config, DB connection, constants, helper functions, layout shells, upload directory, and cron cleanup.

---

## Files to Create

```
greencash/
├── .htaccess
├── .gitignore                   (update existing — confirm env.php entry present)
├── includes/
│   ├── env.php                  (gitignored — local secrets)
│   ├── env.example.php          (committed — template with placeholder strings)
│   ├── config.php               (PDO, constants, all helpers)
│   ├── header.php               (public site layout — Bootstrap 5, green theme)
│   ├── footer.php               (public site footer)
│   ├── admin-header.php         (admin panel — dark sidebar, CSRF meta tag)
│   ├── admin-footer.php         (Chart.js CDN)
│   ├── broker-header.php        (broker portal nav, notifications bell placeholder)
│   └── broker-footer.php
├── uploads/
│   └── .gitkeep                 (create uploads/ directory first, then .gitkeep)
└── cron/
    └── cleanup.php
```

---

## Environment Config

### `includes/env.php` (gitignored)

Holds all secrets and environment-specific values:

- `DB_HOST` — `'localhost'`
- `DB_NAME` — `'greencash'`
- `DB_USER` — `'root'`
- `DB_PASS` — `''` (XAMPP default)
- `APP_URL` — `'http://localhost/greencash'`
- `APP_NAME` — `'Green Cash'`
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_USER`, `MAIL_PASS`, `MAIL_FROM_NAME`, `MAIL_FROM_EMAIL`
- `ADMIN_EMAIL` — `'admin@greencash.co.za'`

### `includes/env.example.php` (committed to git)

Identical structure to `env.php` but with placeholder strings — never real credentials. Use `'your_db_user'`, `'your_db_password'`, `'your_mail_password'`, etc. even though local `env.php` uses the XAMPP defaults. This file is the setup reference for production deployment and must never contain real values.

---

## `includes/config.php`

The single most important file. Every page starts with `require_once 'includes/config.php'`.

### Responsibilities (in order)
1. Start session (if not already started)
2. Load `env.php`
3. Open PDO connection with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, emulate prepares off
4. Define application constants
5. Define all helper functions

### Constants

| Constant | Value |
|---|---|
| `APP_VERSION` | `'1.0.0'` |
| `CURRENCY` | `'R'` |
| `MIN_LOAN_AMOUNT` | `1000` |
| `MAX_LOAN_AMOUNT` | `10000` |
| `LOAN_TERM_MONTHS` | `1` |
| `UPLOAD_DIR` | `__DIR__ . '/../uploads/'` (no realpath — apply realpath at call sites) |
| `ABSPATH` | `realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR` |

### Helper Functions

| Function | Signature | Purpose |
|---|---|---|
| `sanitize` | `(string): string` | `trim` + `htmlspecialchars` ENT_QUOTES UTF-8 |
| `formatCurrency` | `(float): string` | `"R 1,000.00"` format |
| `redirect` | `(string): void` | Internal redirects only — strips external URLs |
| `setFlash` | `(string, string): void` | Write flash message to session |
| `getFlash` | `(): ?array` | Read and clear flash message |
| `csrfField` | `(): string` | Generate token + return hidden input HTML |
| `verifyCsrf` | `(): void` | Verify POST token, rotate after success, die 403 on fail |
| `requireAdmin` | `(): void` | Redirect to `/login.php` if no admin session |
| `isAdmin` | `(): bool` | Check admin session exists |
| `requireBroker` | `(): void` | Redirect to `/broker-portal-login.php` if no broker session |
| `isBroker` | `(): bool` | Check broker session exists |
| `generateReference` | `(): string` | `GC-YYYYMMDD-XXXXXX` (**6 uppercase hex chars** — authoritative; the snippet comment and IMPLEMENTATION_GUIDE say 5, but the snippet code uses `substr(..., 0, 6)` — 6 is correct) |
| `generateBrokerCode` | `(): string` | `GCBR` + 6 random uppercase hex chars |
| `logAdminAction` | `(PDO, int, string, string): void` | Insert to `admin_activity_log` |
| `logBrokerAction` | `(PDO, ?int, string, string): void` | Insert to `broker_activity_log` |
| `getClientIp` | `(): string` | Handles Cloudflare + proxy headers |

**CSRF design note:** Session-scoped token (one per session, not per request). Token is rotated after each successful `verifyCsrf()` call. Primary protection comes from `session_regenerate_id()` on login.

**Reference collision note:** `generateReference()` callers must wrap DB inserts in a retry loop (max 3 attempts), catching PDO error code `23000` (duplicate key) only.

---

## `.htaccess`

### Rules (in order)
1. `Options -Indexes` — disable directory listing
2. `Options +FollowSymLinks`
3. PHP error display off, log errors on
4. HTTPS redirect — **commented out** with `# PRODUCTION: uncomment before deploy`
5. `ErrorDocument 404 /404.php` and `ErrorDocument 500 /500.php`
6. Block access to sensitive file extensions: `.sql`, `.log`, `.md`, `.json`, `.lock`, `.yml`, `.env`, `.gitignore`
7. Block direct access to directories: `includes`, `config`, `uploads`, `vendor`, `cron`, `database`
8. Security headers via `mod_headers`:
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: geolocation=(), microphone=(), camera=()`
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload`
   - `Content-Security-Policy` — allow self + CDN (jsdelivr, googleapis, googletagmanager)
9. `uploads/` directory block: disable PHP engine, deny script execution

---

## Layout Shells

### Public (`header.php` / `footer.php`)

**`header.php`:**
- `<!DOCTYPE html>` through `<body>`
- Bootstrap 5 CSS via CDN (jsdelivr)
- Google Fonts — Inter
- `assets/css/style.css` link (file created in Step 11, link present from Step 1)
- Navbar: Green Cash logo (text), nav links (Home, Apply Now, Track Application), Bootstrap green theme
- Flash message display block (reads `getFlash()`)
- Open `<main class="container py-4">`

**`footer.php`:**
- Close `</main>`
- Footer: copyright, links (Privacy Policy, Terms, Contact)
- Bootstrap 5 JS bundle via CDN
- `assets/js/main.js` script tag (file created later, link present from Step 1)
- Close `</body></html>`

### Admin (`admin-header.php` / `admin-footer.php`)

**`admin-header.php`:**
- Full HTML open through `<body>`
- Bootstrap 5 CSS CDN
- `<meta name="csrf-token">` with current session CSRF token (for AJAX calls)
- Dark green sidebar: Green Cash logo, nav links (Dashboard, Applications, Brokers, Users, Reports, Audit Log, Settings)
- Top bar: page title area, logged-in admin name, logout link
- Flash message block
- Open main content `<div>`

**`admin-footer.php`:**
- Close content `</div>`
- Bootstrap 5 JS CDN
- Chart.js CDN
- Close `</body></html>`

### Broker (`broker-header.php` / `broker-footer.php`)

**`broker-header.php`:**
- Full HTML open
- Bootstrap 5 CSS CDN
- Navbar: Green Cash Broker Portal logo, nav links (Dashboard, Submit Application, My Applications, Profile), notifications bell icon (placeholder badge), logout
- Flash message block
- Open `<main class="container py-4">`

**`broker-footer.php`:**
- Close `</main>`
- Footer: minimal, Green Cash branding
- Bootstrap 5 JS CDN
- Close `</body></html>`

---

## `uploads/.gitkeep`

Empty file. Ensures the `uploads/` directory exists in git while `uploads/*` is ignored.

---

## `cron/cleanup.php`

Runs every 30 minutes via cPanel cron: `php /home/<user>/public_html/cron/cleanup.php`

Four operations:
1. `DELETE FROM otp_verifications WHERE expires_at < NOW()` — expired OTPs only (used=1 rows kept until natural expiry for audit trail)
2. `DELETE FROM rate_limits WHERE locked_until IS NULL AND last_attempt_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)` — old unlocked records
3. `DELETE FROM rate_limits WHERE locked_until IS NOT NULL AND locked_until < NOW()` — expired locks
4. `UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE reset_token_expires < NOW()` — expired reset tokens

Outputs timestamped completion message to stdout for cron log.

---

## Database Setup

Import `database/greencash.sql` into XAMPP MySQL:
- Via phpMyAdmin: create database `greencash`, import the SQL file
- Or via CLI: `mysql -u root greencash < database/greencash.sql`

This is a manual step — not scripted.

---

## Done Condition

Create a scratch `test.php` at project root containing:
```php
<?php
require_once 'includes/config.php';
$page_title = 'Test';
include 'includes/header.php';
echo '<p>Public layout OK</p>';
include 'includes/footer.php';
```
Loading `http://localhost/greencash/test.php` must return HTTP 200 with no PHP warnings in the error log and no unclosed HTML tags. Repeat for `admin-header.php`/`admin-footer.php` and `broker-header.php`/`broker-footer.php` pairs. Running `php cron/cleanup.php` from the terminal must print a timestamped completion line and exit cleanly. Delete `test.php` before committing.

---

## Local → Production Swap Checklist

Before deploying to cPanel:
- [ ] `env.php` — update DB credentials, `APP_URL`, SMTP settings
- [ ] Confirm `includes/env.php` is gitignored and not present in the repository
- [ ] `.htaccess` — uncomment the HTTPS redirect block
- [ ] Confirm `database/greencash.sql` imported to the production database
- [ ] `uploads/` — set permissions to 0750
- [ ] Verify `uploads/.htaccess` blocking PHP execution survived the deploy
- [ ] Set up cron job in cPanel for `cleanup.php` every 30 minutes
