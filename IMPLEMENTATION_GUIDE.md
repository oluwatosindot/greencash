# Green Cash — Implementation Guide

Build in this exact order. Each step can be done independently by an AI agent.
Reference the `snippets/` folder for patterns at each step.

---

## Step 1: Project Foundation
**Reference:** `snippets/config-pattern.md`

1. Create project folder `greencash/`
2. Set up `.htaccess` (security headers + URL rewriting)
3. Create `includes/env.php` — DB credentials, mail config, APP_URL
4. Create `includes/config.php` — DB connection (PDO), constants, all helper functions
5. Create `includes/header.php` and `includes/footer.php` — public site layout (Bootstrap 5, green theme)
6. Create `includes/admin-header.php` / `admin-footer.php` — admin panel layout (dark green sidebar)
7. Create `includes/broker-header.php` / `broker-footer.php` — broker portal layout
8. Run `database/greencash.sql` to create all tables
9. Create `uploads/` directory with 0750 permissions
10. Create `cron/cleanup.php` — expire OTPs, rate limits, password resets

**Done when:** You can `<?php require_once 'includes/config.php'; ?>` without errors.

---

## Step 2: Homepage & Public Pages
**Reference:** `SPEC.md` Section 5

1. `index.php` — Homepage (hero, how it works, calculator widget, trust signals, footer)
2. `calculator.php` — Standalone loan calculator (slider R1k–R10k, 1-month term, shows repayment + fee)
3. `contact.php` — Contact form
4. `privacy-policy.php` — Static page
5. `terms-and-conditions.php` — Static page
6. `404.php` — Custom 404 page

**Done when:** Homepage loads with green theme, calculator works.

---

## Step 3: Admin Authentication
**Reference:** `snippets/auth-pattern.md`

1. `login.php` — Email + password form → validate → send OTP email → redirect to verify-otp.php
2. `verify-otp.php` — OTP input → verify → set session → redirect to admin/index.php
3. `admin/logout.php` — Destroy session → redirect to login.php
4. `forgot-password.php` + `reset-password.php` — Password reset via email token
5. Add `requireAdmin()` function to config.php
6. Add account lockout logic (10 attempts / 30 min) using `rate_limits` table
7. `session_regenerate_id(true)` on every successful login

**Done when:** Admin can log in with 2FA, locked out after 10 fails, can reset password.

---

## Step 4: Public Application Form
**Reference:** `snippets/apply-form-pattern.md`

1. `apply.php` — Multi-step salary advance form:
   - Step 1: Personal Info
   - Step 2: Employment Info
   - Step 3: Financial Info (salary, next payday date, loan amount, expenses)
   - Step 4: Documents (ID copy, payslip, 3 months bank statements)
   - Step 5: Review & Submit
2. Add CSRF token to form
3. Add duplicate submission guard (same ID number within 5 min → blocked)
4. Add server-side validation for all fields
5. Add file upload handling (MIME check + extension whitelist + realpath check)
6. On success: insert to `salary_advance_applications`, generate reference `GC-YYYYMMDD-XXXXX`, send confirmation email
7. Redirect to `application-submitted.php` with reference number
8. `track-application.php` — lookup by reference + ID number, show status timeline
9. `serve-document.php` — secure file proxy (see `snippets/security-pattern.md`). Required by admin and broker document preview modals. Must include `requireAdmin()` or `requireBroker()` at the top and realpath validation before streaming the file.

**Done when:** Full application submits, saves to DB, confirmation email sent, applicant can track status, documents serve securely through the proxy.

---

## Step 5: Admin Panel — Applications
**Reference:** `snippets/admin-pattern.md`, `SPEC.md` Section 6

1. `admin/index.php` — Dashboard: stats cards + Chart.js charts (applications over time, approval rate) + recent applications table
2. `admin/applications.php` — List all applications, filter by status/date/source/province, search, CSV export, bulk status update
3. `admin/application-details.php` — Full application view:
   - All applicant details
   - Document preview modal
   - Affordability calculation (salary vs expenses vs loan amount)
   - Status change dropdown (pending/under_review/approved/rejected/disbursed)
   - Email notification triggered on status change
   - Admin notes
   - Audit trail of all actions
4. `admin/ajax/update-status.php` — AJAX endpoint for status change (CSRF verified)
5. `admin/ajax/export-applications.php` — CSV export

**Done when:** Admin can view, filter, update status, and export applications.

---

## Step 6: Admin Panel — Brokers & Users
**Reference:** `SPEC.md` Section 6

1. `admin/brokers.php` — List brokers, filter by status, approve button (pending → approved)
2. `admin/broker-details.php` — Full broker view, activate account button (approved → active, sets password_hash from ID number), commission history
3. `admin/users.php` — Admin user list, add/deactivate admin accounts
4. `admin/reports.php` — Monthly summaries, broker leaderboard, disbursement totals, CSV export
5. `admin/audit-log.php` — Admin action log
6. `admin/settings.php` — Site settings
7. `admin/notifications.php` — System notifications

**Done when:** Full broker lifecycle works (pending → approved → active), commission tracking works.

---

## Step 7: Email Notifications
**Reference:** `snippets/email-pattern.md`

Add all email templates to `includes/email_templates.php`:

1. `sendApplicationConfirmation($application)` — to applicant on submit
2. `sendStatusChangeEmail($application, $newStatus)` — to applicant on approval/rejection/disbursement
3. `sendAdminOTP($user, $otp)` — admin 2FA
4. `sendBrokerApprovalEmail($broker)` — tells broker: email=username, ID=password
5. `sendBrokerActivationEmail($broker)` — account live + login link
6. `sendPasswordResetEmail($user, $token)` — admin password reset

Use PHPMailer (install via Composer: `composer require phpmailer/phpmailer`).
All emails: HTML + plain text fallback, Green Cash branding.

**Done when:** All status changes trigger correct emails, admin OTP email works.

---

## Step 8: Broker Portal Auth
**Reference:** `snippets/broker-auth-pattern.md`

1. `broker-signup.php` — Registration form → insert `pending` broker → admin reviews
2. `broker-portal-login.php` — Email + SA ID number → `password_verify($id_number, $password_hash)` → set `$_SESSION['broker_id']` → redirect to broker-portal/index.php
3. Add account lockout (10 attempts / 30 min) + IP rate limit (5 / 15 min) using `rate_limits`
4. Log failed logins to `broker_activity_log`
5. Add `requireBroker()` to config.php
6. `broker-portal/logout.php`

**Done when:** Broker can log in with email + ID number, locked out after 10 fails.

---

## Step 9: Broker Portal Pages
**Reference:** `SPEC.md` Section 7

1. `broker-portal/index.php` — Dashboard: stats cards, recent submissions, notifications bell
2. `broker-portal/apply.php` — Submit salary advance form for a client (broker_code auto-filled, saves to `broker_clients`)
3. `broker-portal/applications.php` — Broker's submissions list, filter/search
4. `broker-portal/application-details.php` — Read-only view, document preview, status timeline, commission earned
5. `broker-portal/profile.php` — Broker details (read-only ID number)
6. `broker-portal/ajax/get-notifications.php` — Unread notifications count + list

**Done when:** Broker can log in, submit applications, track their submissions, see commission.

---

## Step 10: Security Hardening
**Reference:** `snippets/security-pattern.md`

1. Verify CSRF on ALL POST forms and AJAX endpoints
2. Verify `session_regenerate_id(true)` on all login flows
3. Verify all file uploads use MIME check + extension whitelist + realpath
4. Verify `redirect()` strips external URLs
5. Add `.htaccess` security headers (X-Frame-Options, HSTS, CSP, X-Content-Type-Options, Referrer-Policy)
6. Set `uploads/` to 0750 permissions
7. Verify all DB queries use PDO prepared statements
8. Verify all output uses `htmlspecialchars()`
9. Test rate limiting and lockout flows

**Done when:** Security checklist fully verified.

---

## Step 11: PWA & Final Polish
1. `manifest.json` — PWA manifest (name: Green Cash, colors: #2E7D32)
2. `sw.js` — Service worker (cache static assets)
3. `assets/js/theme-toggle.js` — Light/dark mode toggle
4. `sitemap.xml` + `robots.txt`
5. `includes/sms.php` — SMS stub (placeholder, not active)
6. `.cpanel.yml` — Deployment config for cPanel Git Version Control
7. Final cross-browser test, mobile responsiveness check

**Done when:** Site is production-ready, deploys cleanly to cPanel.

---

## Build Checklist Summary

- [ ] Step 1: Project Foundation
- [ ] Step 2: Homepage & Public Pages
- [ ] Step 3: Admin Authentication (2FA)
- [ ] Step 4: Public Application Form
- [ ] Step 5: Admin — Applications
- [ ] Step 6: Admin — Brokers & Users
- [ ] Step 7: Email Notifications
- [ ] Step 8: Broker Auth
- [ ] Step 9: Broker Portal Pages
- [ ] Step 10: Security Hardening
- [ ] Step 11: PWA & Final Polish
