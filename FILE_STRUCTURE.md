cna you# Green Cash — Annotated File Structure

Every file in the project, with its purpose and key implementation notes.

---

## Root Files

| File | Purpose | Notes |
|---|---|---|
| `index.php` | Public homepage | Hero, how it works, calculator widget, trust signals |
| `apply.php` | Salary advance application form | Multi-step, CSRF, duplicate guard, file uploads |
| `application-submitted.php` | Post-submit confirmation | Shows reference number, next steps |
| `track-application.php` | Application status tracker | Lookup by reference + ID number |
| `login.php` | Admin login page | Email + password → OTP flow |
| `verify-otp.php` | Admin OTP verification | 10-min expiry, 3 attempts max |
| `logout.php` | Destroy admin session | Redirect to login.php |
| `forgot-password.php` | Password reset request | Sends reset token via email |
| `reset-password.php` | Password reset form | Validates token, updates password |
| `calculator.php` | Standalone loan calculator | R1k–R10k slider, 1-month term, shows fees |
| `contact.php` | Contact form | Optional — sends email to admin |
| `privacy-policy.php` | Static legal page | Green Cash specific content |
| `terms-and-conditions.php` | Static legal page | Green Cash specific content |
| `broker-portal-login.php` | Broker login | Email + ID number auth, lockout logic |
| `broker-signup.php` | Broker registration | Creates pending broker record |
| `404.php` | Custom 404 error page | Styled with Green Cash theme |
| `serve-document.php` | Secure file serving proxy | Streams uploaded files through PHP with auth check + realpath validation — see `snippets/security-pattern.md` |
| `manifest.json` | PWA manifest | name: Green Cash, theme_color: #2E7D32 |
| `sw.js` | Service worker | Cache static assets for PWA |
| `robots.txt` | Crawler instructions | Allow public, disallow admin/broker-portal |
| `sitemap.xml` | SEO sitemap | Public pages only |
| `.htaccess` | Apache config | Security headers, URL rewriting, error pages |

---

## admin/ Directory

| File | Purpose | Notes |
|---|---|---|
| `index.php` | Admin dashboard | Stats cards, Chart.js charts, recent applications |
| `applications.php` | Applications list | Filter, search, CSV export, bulk update |
| `application-details.php` | Single application view | Full details, doc preview, status change, notes |
| `brokers.php` | Broker list | Approve button (pending→approved) |
| `broker-details.php` | Single broker view | Activate button (approved→active), commission |
| `users.php` | Admin user management | Add/deactivate admin accounts |
| `reports.php` | Reports & analytics | Monthly summaries, leaderboard, CSV export |
| `settings.php` | Site settings | Email config, general settings |
| `audit-log.php` | Admin action log | All admin actions, timestamp, IP |
| `notifications.php` | System notifications | Inbox for admin alerts |
| `logout.php` | Destroy admin session | |

### admin/ajax/

| File | Purpose | Notes |
|---|---|---|
| `update-status.php` | Change application status | CSRF verified, logs action, sends email |
| `export-applications.php` | CSV export | Filtered export, set proper CSV headers |
| `get-notifications.php` | Fetch notifications | Returns JSON, used by notification bell |

---

## broker-portal/ Directory

| File | Purpose | Notes |
|---|---|---|
| `index.php` | Broker dashboard | Stats, recent submissions, notifications |
| `apply.php` | Submit application for client | Broker code auto-filled, saves to broker_clients |
| `applications.php` | Broker's submissions list | Filter by status, date, search by name/ref |
| `application-details.php` | Single application (read-only) | Status timeline, doc preview, commission earned |
| `profile.php` | Broker profile | Read-only — ID number cannot be changed |
| `logout.php` | Destroy broker session | Redirect to broker-portal-login.php |

### broker-portal/ajax/

| File | Purpose | Notes |
|---|---|---|
| `get-notifications.php` | Fetch broker notifications | Returns JSON unread count + list |

---

## includes/ Directory

| File | Purpose | Notes |
|---|---|---|
| `env.php` | Environment config | DB host/user/pass/name, SMTP config, APP_URL — never commit secrets |
| `config.php` | Core application file | PDO connection, constants, ALL helper functions |
| `header.php` | Public site HTML head + navbar | Loads Bootstrap 5, green theme CSS |
| `footer.php` | Public site footer | Links, contact, copyright |
| `admin-header.php` | Admin panel header + sidebar | Dark green sidebar, nav items |
| `admin-footer.php` | Admin panel footer | Close tags, JS scripts |
| `broker-header.php` | Broker portal header | Similar to admin but broker-branded |
| `broker-footer.php` | Broker portal footer | |
| `email_templates.php` | All email functions | sendApplicationConfirmation, sendStatusChangeEmail, etc. |
| `mailer.php` | PHPMailer setup | SMTP configuration, base send function |
| `sms.php` | SMS stub | Placeholder — logs to file, not active |
| `verification_helpers.php` | OTP helpers | Contains `generateOTP()`. Optionally also put `checkRateLimit()`, `incrementRateLimit()`, `resetRateLimit()` here if you prefer not to keep them in config.php. If you keep all helpers in config.php, this file is not needed — skip it. |

---

## assets/ Directory

| File | Purpose | Notes |
|---|---|---|
| `css/style.css` | Public site styles | Green fintech theme, CSS custom properties |
| `css/admin.css` | Admin + broker shared styles | Dark sidebar, cards, tables, badges |
| `js/main.js` | Public site JS | Multi-step form logic, calculator, form validation |
| `js/theme-toggle.js` | Dark/light mode | Saves preference to localStorage, toggles data-theme |

### CSS Custom Properties (style.css)
```css
:root {
    --gc-primary: #2E7D32;
    --gc-primary-light: #66BB6A;
    --gc-primary-dark: #1B5E20;
    --gc-white: #ffffff;
    --gc-text: #212529;
    --gc-muted: #6c757d;
    --gc-border: #dee2e6;
    --gc-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
```

---

## config/ Directory

| File | Purpose | Notes |
|---|---|---|
| (empty or env only) | Do not put database.php here | Use includes/env.php instead |

---

## cron/ Directory

| File | Purpose | Notes |
|---|---|---|
| `cleanup.php` | Scheduled cleanup | Delete expired OTPs, rate limits, password reset tokens |

Run every 30 minutes via cPanel cron:
`php /home/<cpanel_user>/public_html/cron/cleanup.php`

---

## database/ Directory

| File | Purpose | Notes |
|---|---|---|
| `greencash.sql` | Full DB schema | Run once on new server to create all tables |

---

## uploads/ Directory

Permissions: `0750`
Contains: ID documents, payslips, bank statements uploaded with applications.
Never serve directly — use a PHP proxy script with auth check and realpath validation.

---

## Key Relationships

```
users (admins)
    └── reviews salary_advance_applications
    └── logs to admin_activity_log

credit_brokers
    ├── submits salary_advance_applications (source='broker')
    ├── has broker_clients
    ├── receives broker_notifications
    └── logs to broker_activity_log

salary_advance_applications
    ├── has application_documents
    └── reviewed_by → users

otp_verifications → users (admin 2FA)
rate_limits (identifier = IP or broker email)
```
