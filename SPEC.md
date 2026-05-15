# Green Cash — Design Specification
**Date:** 2026-05-08
**Status:** Approved
**Stack:** Procedural PHP, Bootstrap 5, MySQL, PDO, PHPMailer
**Reference project:** CocoFinance (same stack, patterns reused not copied)

---

## 1. Overview

Green Cash is a standalone salary advance loan business. It operates as an independent PHP web application on a separate cPanel hosting account. It has three portals:

1. **Public site** — applicants apply for salary advance loans
2. **Admin panel** — staff manage applications, brokers, reports
3. **Broker portal** — registered brokers submit applications on behalf of clients

**Only one loan product:** Salary Advance — R1,000 to R10,000, repaid in 1 month.

---

## 2. Branding & Design

- **Business name:** Green Cash
- **Tagline:** Get Your Salary In Advance — Today
- **Style:** Modern fintech — clean, minimal, white background, green primary
- **Primary colour:** `#2E7D32` (deep green)
- **Accent colour:** `#66BB6A` (light green)
- **Font:** Inter or Poppins via Google Fonts
- **Icons:** Bootstrap Icons or Feather Icons
- **Future options:** Warm & trustworthy (B), Bold & energetic (C) — documented for later

---

## 3. Project Structure

```
greencash/
├── index.php
├── apply.php
├── application-submitted.php
├── track-application.php
├── login.php
├── logout.php
├── verify-otp.php
├── forgot-password.php
├── reset-password.php
├── calculator.php
├── contact.php
├── privacy-policy.php
├── terms-and-conditions.php
├── broker-portal-login.php
├── broker-signup.php
│
├── admin/
│   ├── index.php
│   ├── applications.php
│   ├── application-details.php
│   ├── brokers.php
│   ├── broker-details.php
│   ├── users.php
│   ├── reports.php
│   ├── settings.php
│   ├── audit-log.php
│   ├── notifications.php
│   ├── logout.php
│   └── ajax/
│       ├── update-status.php
│       ├── export-applications.php
│       └── get-notifications.php
│
├── broker-portal/
│   ├── index.php
│   ├── apply.php
│   ├── applications.php
│   ├── application-details.php
│   ├── profile.php
│   └── ajax/
│       ├── update-profile.php
│       └── get-notifications.php
│
├── includes/
│   ├── config.php
│   ├── header.php
│   ├── footer.php
│   ├── admin-header.php
│   ├── admin-footer.php
│   ├── broker-header.php
│   ├── broker-footer.php
│   ├── email_templates.php
│   ├── mailer.php
│   └── sms.php
│
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   └── js/
│       ├── main.js
│       └── theme-toggle.js
│
├── config/
│   └── (env config, no database.php — use config.php)
├── uploads/
├── cron/
│   └── cleanup.php
├── database/
│   └── greencash.sql
├── manifest.json
├── sw.js
├── robots.txt
├── .htaccess
└── 404.php
```

---

## 4. Database Schema

**Database:** `greencash` (utf8mb4_unicode_ci)

### Tables

#### `users` — Admin accounts
```sql
id, first_name, last_name, email (unique), phone, password,
role ENUM('admin'), status ENUM('active','inactive'),
email_verified_at, created_at, updated_at
```

#### `salary_advance_applications` — All loan applications
```sql
id, reference_number (unique), source ENUM('public','broker'),
broker_id (FK → credit_brokers, nullable),
first_name, last_name, other_names, id_number, email, phone,
address, city, province, zip_code,
employer_name, employer_contact, job_title, employment_duration,
employment_status ENUM('employed','self_employed','contract'),
salary_amount DECIMAL(15,2), next_payday_date DATE,
loan_amount DECIMAL(15,2), repayment_date DATE,
rent DECIMAL(15,2) DEFAULT 0, food DECIMAL(15,2) DEFAULT 0,
transport DECIMAL(15,2) DEFAULT 0, other_expenses DECIMAL(15,2) DEFAULT 0,
status ENUM('pending','under_review','approved','rejected','disbursed') DEFAULT 'pending',
notes TEXT, reviewed_by (FK → users, nullable), reviewed_at,
submitted_at, created_at, updated_at
```

#### `application_documents`
```sql
id, application_id (FK), document_type, file_name, file_path, uploaded_at
```

#### `credit_brokers` — Broker accounts
```sql
id, broker_code (unique), first_name, last_name,
email (unique), phone, id_number,
company_name, company_registration, province, city,
experience_years, why_join, id_document_path,
password_hash (nullable), username VARCHAR(50) UNIQUE NULL,
status ENUM('pending','approved','active','inactive','suspended') DEFAULT 'pending',
commission_rate DECIMAL(5,2) DEFAULT 5.00,
total_referrals INT DEFAULT 0, total_commission DECIMAL(15,2) DEFAULT 0,
last_login, created_at, updated_at
```

#### `broker_clients`
```sql
id, broker_id (FK), first_name, last_name, email, phone, id_number,
address, city, province, status ENUM('active','inactive'), created_at, updated_at
```

#### `otp_verifications` — Admin 2FA
```sql
id, user_id (FK → users), otp_code VARCHAR(6), expires_at, used TINYINT DEFAULT 0,
created_at
```

#### `rate_limits` — IP + account lockout
```sql
id, identifier (IP or broker email), attempt_type, attempts INT DEFAULT 0,
locked_until, last_attempt_at, created_at, updated_at
```

#### `broker_activity_log`
```sql
id, broker_id (FK, nullable), action, details TEXT, ip_address, created_at
```

#### `admin_activity_log`
```sql
id, admin_id (FK → users), action, details TEXT, ip_address, created_at
```

#### `broker_notifications`
```sql
id, broker_id (FK), title, message TEXT, is_read TINYINT DEFAULT 0,
application_id (nullable), created_at
```

---

## 5. Frontend (Public Site)

### index.php
- Sticky navbar: Logo + nav links + "Apply Now" CTA (green button)
- Hero: Bold headline, subtext, loan amount preview, "Apply Now" button
- How It Works: 3-step process cards
- Live loan calculator widget
- Trust signals: fast approval, secure, NCR registered
- Footer: links, contact number, social icons

### apply.php — Multi-step salary advance form
Step 1: Personal Info (name, ID number, email, phone, address, province)
Step 2: Employment Info (employer, job title, employment status, duration, employer contact)
Step 3: Financial Info (salary amount, next payday date, loan amount slider R1k–R10k, expenses)
Step 4: Documents (ID copy, latest payslip, 3 months bank statements)
Step 5: Review & Submit

- Duplicate guard: same ID number within 5 min → blocked
- CSRF token on form
- Server-side validation + sanitization
- On success → redirect to application-submitted.php with reference number

### track-application.php
- Form: reference number + ID number
- Shows: status badge, submitted date, last updated, timeline steps

### calculator.php
- Amount slider (R1,000–R10,000)
- Fixed 1-month term
- Shows: repayment amount, service fee, total cost
- "Apply Now" CTA button

---

## 6. Admin Panel

### Auth
- Login: email + password → OTP sent to email → verify OTP → dashboard
- Account lockout: 10 failed attempts = 30 min lock
- `requireAdmin()` guard on every admin page

### Pages
- **index.php**: Stats cards + Chart.js charts + recent applications table
- **applications.php**: Full list, filter by status/date/source/province, search, CSV export, bulk status update
- **application-details.php**: Full details, document preview modal, affordability calc, status change with email notification, notes, audit trail
- **brokers.php**: List with approve button (pending → approved), filter by status
- **broker-details.php**: Full broker info, activate account button (approved → active), commission history
- **users.php**: Admin user management
- **reports.php**: Monthly summaries, broker leaderboard, disbursement totals, CSV export
- **audit-log.php**: All admin actions with timestamp + IP
- **settings.php**: Site settings, email config
- **notifications.php**: System notifications

### Broker Lifecycle (Admin Side)
1. Broker signs up → `pending`
2. Admin clicks Approve on `brokers.php` → status → `approved`, email sent (tells broker: email=username, ID=password)
3. Admin clicks Activate Account on `broker-details.php` → status → `active`, password_hash set to `password_hash($id_number)`, email sent (account live + login link)

---

## 7. Broker Portal

### Auth
- Login: email + SA ID number as password
- Account lockout: 10 failed attempts = 30 min lock
- IP rate limit: 5 attempts / 15 min
- Failed logins logged to `broker_activity_log`
- `requireBroker()` guard on every broker page

### Pages
- **index.php**: Stats cards (my apps, pending, approved, commission earned) + recent submissions + notifications bell
- **apply.php**: Salary advance form for a client, broker_code auto-filled, saves to `broker_clients`
- **applications.php**: List of broker's own submissions, filter/search, status badges
- **application-details.php**: Read-only view, document preview, status timeline, commission earned
- **profile.php**: Broker details, broker code (read-only), cannot change ID number

### broker-signup.php (Public)
- Registration form → creates `pending` credit_broker record → admin reviews

---

## 8. Security

| Layer | Implementation |
|---|---|
| CSRF | `csrfField()` on all forms, token verified on every POST + AJAX |
| Session | `session_regenerate_id(true)` on login |
| Admin lockout | 10 failed attempts = 30 min (`rate_limits` table) |
| Broker lockout | 10 attempts / 30 min + IP: 5 attempts / 15 min |
| Password hashing | `password_hash()` bcrypt throughout |
| File uploads | `finfo` MIME check + extension whitelist + `realpath()` path traversal |
| Open redirect | `redirect()` strips external URLs |
| Security headers | `.htaccess`: X-Frame-Options, HSTS, CSP, X-Content-Type-Options, Referrer-Policy |
| Upload dirs | `0750` permissions |
| Admin 2FA | Email OTP, 10-min expiry, `otp_verifications` table |
| SQL injection | PDO prepared statements only |
| XSS | `htmlspecialchars()` on output, `sanitize()` on input |

**Cron:** `cron/cleanup.php` every 30 min — expire OTPs, rate limits, password reset tokens.

---

## 9. Email Notifications

Triggered by status changes on applications:
- Application received (confirmation to applicant)
- Application approved
- Application rejected
- Application disbursed

Broker lifecycle emails:
- Broker approved (login credentials info)
- Broker activated (account live + login link)

Admin 2FA OTP email.

---

## 10. Key Constants & Helpers (includes/config.php)

```php
define('APP_NAME', 'Green Cash');
define('APP_URL', 'https://www.greencash.co.za');
define('CURRENCY', 'R');
define('MIN_LOAN_AMOUNT', 1000);
define('MAX_LOAN_AMOUNT', 10000);
define('LOAN_TERM_MONTHS', 1);
define('ADMIN_EMAIL', 'admin@greencash.co.za');

// Helpers (same pattern as CocoFinance):
sanitize($input)         // trim + htmlspecialchars
formatCurrency($amount)  // "R 1,000.00"
csrfField()              // hidden CSRF input + sets $_SESSION['csrf_token']
verifyCsrf()             // validates POST csrf_token
redirect($url)           // internal redirect only
setFlash($type, $msg)    // $_SESSION flash message
getFlash()               // retrieve + clear flash
requireAdmin()           // redirect to login if not admin
requireBroker()          // redirect to broker login if not broker
generateReference()      // "GC-YYYYMMDD-XXXXX"
```

---

## 11. Deployment

- Separate cPanel hosting account
- Deploy via cPanel Git Version Control (same pattern as CocoFinance)
- `.cpanel.yml` with rsync, exclude node_modules/tests/docs
- MySQL database created via cPanel → run `database/greencash.sql`
- Set up cron: `php /home/<user>/public_html/cron/cleanup.php` every 30 min
- Environment config in `includes/env.php` (DB credentials, mail config, app URL)
