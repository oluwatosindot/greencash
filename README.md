# Green Cash — Project Blueprint

**Business:** Green Cash
**Product:** Salary Advance Loans (R1,000 – R10,000, 1-month repayment)
**Stack:** Procedural PHP 8+, Bootstrap 5, MySQL 8, PDO, PHPMailer
**Style:** Modern fintech — white + green (#2E7D32 primary, #66BB6A accent), Inter/Poppins font
**Reference:** Built from CocoFinance patterns (do NOT copy-paste CocoFinance code — use patterns)

---

## How to Use This Blueprint

This folder contains everything needed to build Green Cash from scratch in a new project window.
You can use **Claude**, **Windsurf**, or any AI coding agent with these files.

### Files in This Blueprint

| File | Purpose |
|---|---|
| `README.md` | This file — start here |
| `SPEC.md` | Full design specification (what to build) |
| `IMPLEMENTATION_GUIDE.md` | Step-by-step build order for AI agents |
| `FILE_STRUCTURE.md` | Annotated file tree — every file explained |
| `database/greencash.sql` | Full MySQL schema — run this first |
| `snippets/config-pattern.md` | How to write includes/config.php |
| `snippets/auth-pattern.md` | Auth, session, CSRF, lockout patterns |
| `snippets/apply-form-pattern.md` | Multi-step loan application form pattern |
| `snippets/admin-pattern.md` | Admin panel page pattern |
| `snippets/broker-auth-pattern.md` | Broker login (email + ID number) pattern |
| `snippets/email-pattern.md` | Email notification pattern (PHPMailer) |
| `snippets/security-pattern.md` | Security headers, file uploads, redirect guard |

---

## How to Split Work Between Claude and Windsurf

### Rule of thumb:
- **Claude** = anything that involves logic, security, databases, or could break the site if wrong
- **Windsurf** = anything visual, repetitive, or structural that follows a clear pattern

---

### Tasks for Claude (use Claude Code CLI or claude.ai/code)

These tasks require careful reasoning, security awareness, and architectural decisions:

| Step | Task | Why Claude |
|---|---|---|
| Step 1 | Write `includes/config.php` (PDO, helpers, constants) | Core file — errors here break everything |
| Step 1 | Write `.htaccess` (security headers, HTTPS redirect) | Security-critical, easy to get wrong |
| Step 1 | Write `cron/cleanup.php` | Needs correct SQL logic |
| Step 3 | Admin login + OTP 2FA (`login.php`, `verify-otp.php`) | Security-critical auth flow |
| Step 3 | Rate limiting + account lockout logic | Complex SQL + session logic |
| Step 3 | Password reset flow | Token security |
| Step 4 | Server-side form validation + file upload handling | Security — MIME check, path traversal |
| Step 4 | DB insert with duplicate guard + reference generation | Needs retry loop for collisions |
| Step 5 | `admin/ajax/update-status.php` | CSRF + DB + email trigger in one |
| Step 7 | All email functions in `email_templates.php` | PHPMailer config + HTML templates |
| Step 8 | Broker login with lockout (`broker-portal-login.php`) | Security-critical auth |
| Step 10 | Full security hardening review | Needs to verify everything end-to-end |

**How to use Claude for these:**
> "Read `snippets/config-pattern.md` and build `includes/config.php` for the Green Cash project."

---

### Tasks for Windsurf (open Windsurf in the greencash/ project folder)

These tasks are visual, structural, or follow a clear repeatable pattern:

| Step | Task | Why Windsurf |
|---|---|---|
| Step 2 | `index.php` homepage layout | HTML/Bootstrap — visual, no logic |
| Step 2 | `calculator.php` page | JS slider + display, simple layout |
| Step 2 | `contact.php`, `privacy-policy.php`, `terms.php` | Static pages, copy-paste structure |
| Step 2 | `404.php` | Simple styled page |
| Step 4 | Multi-step form HTML (`apply.php`) | Bootstrap form HTML — follow `snippets/apply-form-pattern.md` |
| Step 4 | `application-submitted.php`, `track-application.php` | Simple display pages |
| Step 5 | `admin/applications.php` list page HTML | Table layout — follow `snippets/admin-pattern.md` |
| Step 5 | `admin/application-details.php` layout | Card layout, tabs, document modal |
| Step 6 | `admin/brokers.php`, `admin/reports.php`, `admin/audit-log.php` | Repetitive table pages |
| Step 6 | `admin/settings.php`, `admin/notifications.php` | Simple form/list pages |
| Step 9 | All broker-portal pages (index, applications, profile) | Follow same pattern as admin pages |
| Step 11 | `assets/css/style.css` (green fintech theme) | Pure CSS, visual |
| Step 11 | `assets/css/admin.css` (dark sidebar theme) | Pure CSS |
| Step 11 | `assets/js/main.js` (multi-step form JS, slider) | Front-end JS only |
| Step 11 | `manifest.json`, `sw.js`, `sitemap.xml`, `robots.txt` | Config files, no logic |

**How to use Windsurf for these:**
> "Create `admin/applications.php`. Read `FILE_STRUCTURE.md` for what it should do and `snippets/admin-pattern.md` for the code pattern to follow."

---

### Recommended build order (alternating):

```
1. Claude  → Step 1: Foundation (config.php, .htaccess, env.php, DB)
2. Windsurf → Step 2: Public pages (homepage, calculator, static pages)
3. Claude  → Step 3: Admin auth (login, OTP, rate limiting, password reset)
4. Windsurf → Step 4 (HTML): Multi-step apply form layout
4. Claude  → Step 4 (PHP): Form handler, validation, file uploads, DB insert
5. Windsurf → Step 5 (HTML): Admin applications list + details page layout
5. Claude  → Step 5 (PHP): Status update AJAX, CSV export logic
6. Windsurf → Step 6: Remaining admin pages (brokers, reports, audit, settings)
7. Claude  → Step 7: All email notification functions
8. Claude  → Step 8: Broker login auth + lockout
9. Windsurf → Step 9: All broker portal page layouts
10. Claude  → Step 10: Security hardening review
11. Windsurf → Step 11: CSS theme, JS, PWA files
```

---

## Quick Start for a New Session

When starting fresh, give the AI agent this context prompt:

```
I am building a new PHP web application called "Green Cash" — a salary advance loan business.
Read the SPEC.md file for full requirements.
Read the IMPLEMENTATION_GUIDE.md for the build order.
Read FILE_STRUCTURE.md for what files to create.
Check the snippets/ folder for code patterns to follow.
Start with Step 1 in IMPLEMENTATION_GUIDE.md.
```

---

## Key Facts to Remember

- **Only one loan type:** Salary Advance (no other loan categories)
- **Loan amounts:** R1,000 to R10,000
- **Loan term:** 1 month (fixed — no other options)
- **Reference format:** `GC-YYYYMMDD-XXXXX`
- **Currency:** ZAR, displayed as `R 1,000.00`
- **Admin login:** email + password + OTP (2FA)
- **Broker login:** email + SA ID number as password (no separate password)
- **Broker lifecycle:** pending → approved → active (admin controls)
- **Database name:** `greencash`
- **No framework** — pure procedural PHP, no Laravel, no Symfony
- **Bootstrap 5** for all UI
- **PDO** for all database queries (no mysqli, no ORM)
