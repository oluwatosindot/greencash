# Step 3: Admin Authentication — Design Spec

**Date:** 2026-05-16
**Status:** Approved

---

## Overview

Build the admin authentication system: email + password login, 6-digit OTP 2FA, session management, account lockout, and password reset via emailed token. Email delivery is stubbed for local development (OTP and reset link written to PHP error log and shown in a dev notice on-page). PHPMailer replaces the stub in Step 7.

---

## Files

```
greencash/
├── login.php                    (admin login — email + password form)
├── verify-otp.php               (6-digit OTP entry)
├── forgot-password.php          (request password reset link)
├── reset-password.php           (set new password via token)
├── admin/
│   └── logout.php               (destroy session → redirect to login.php)
└── includes/
    └── config.php               (add: checkRateLimit, incrementRateLimit, resetRateLimit, generateOTP)
```

No new DB tables. All required columns (`users.reset_token`, `users.reset_token_expires`, `otp_verifications`, `rate_limits`) exist in the schema from Step 1.

---

## Page Layout

`login.php`, `verify-otp.php`, `forgot-password.php`, and `reset-password.php` use a **minimal self-contained HTML wrapper**:
- Bootstrap 5 via CDN
- Centered card on a light-gray background (`#f8f9fa`)
- GreenCash brand mark (icon + name) above the card
- No public nav — these are pre-auth pages; showing "Apply Now" or "Broker Login" to staff is inappropriate

`admin/logout.php` has no HTML output — it destroys the session and redirects.

---

## Config.php Additions

Four helper functions added to `includes/config.php`:

```php
/**
 * Returns true if identifier is currently rate-limited/locked, false if OK.
 */
function checkRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): bool

/**
 * Increments attempt counter; locks identifier once maxAttempts is reached.
 * Uses ON DUPLICATE KEY UPDATE — single atomic query.
 */
function incrementRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): void

/**
 * Zeroes counter and clears locked_until on successful login.
 */
function resetRateLimit(PDO $pdo, string $identifier, string $type): void

/**
 * Returns a zero-padded 6-digit string using random_int(0, 999999).
 */
function generateOTP(): string
```

Full implementations from `snippets/auth-pattern.md` — use verbatim.

Note: `requireAdmin()` is already present in `includes/config.php` from Step 1 — no action required.

---

## `env.php` Addition

Add to `includes/env.php`:
```php
define('APP_ENV', 'local'); // set to 'production' before deploy
```

The OTP dev notice on `verify-otp.php` only renders when `APP_ENV === 'local'`.

---

## Login Flow

### `login.php`

POST handler (runs before HTML output):

1. `verifyCsrf()`
2. `$ip = getClientIp()`
3. Check IP rate limit: `checkRateLimit($pdo, $ip, 'ip_login', 5, 15)` — if locked, set `$error` and skip to render
4. Fetch user: `SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1`
5. Check per-account rate limit: `checkRateLimit($pdo, $email, 'admin_login', 10, 30)` — if locked, set `$error`
6. `password_verify($password, $user['password'])`:
   - **Fail:** `incrementRateLimit` on `ip_login` always; `incrementRateLimit` on `admin_login` only if `$user` was found (no email to key on when user doesn't exist). `$error = 'Invalid email or password.'`
   - **Success:**
     - `resetRateLimit` on both
     - `UPDATE otp_verifications SET used = 1 WHERE user_id = ?` (invalidate existing OTPs)
     - `generateOTP()` → insert into `otp_verifications` with `expires_at = NOW() + 10 minutes`
     - `error_log("GREENCASH DEV OTP for {$user['email']}: $otp")`
     - `$_SESSION['otp_user_id'] = $user['id']`
     - `redirect('/verify-otp.php')`

Generic error message used for both "user not found" and "wrong password" to prevent user enumeration.

### `verify-otp.php`

Guard: `if (empty($_SESSION['otp_user_id'])) redirect('/login.php');`

POST handler:

1. `verifyCsrf()`
2. Check OTP rate limit: `checkRateLimit($pdo, $userId, 'otp_verify', 5, 15)` — if locked: clear `$_SESSION['otp_user_id']`, redirect to `/login.php` with error flash ("Too many failed OTP attempts. Please log in again.")
3. Query: `SELECT * FROM otp_verifications WHERE user_id = ? AND otp_code = ? AND used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1`
4. **Fail:** `incrementRateLimit($pdo, $userId, 'otp_verify', 5, 15)`; `$error = 'Invalid or expired OTP. Please try again.'`
5. **Success:**
   - `resetRateLimit($pdo, $userId, 'otp_verify')`
   - `UPDATE otp_verifications SET used = 1 WHERE id = ?`
   - Fetch full user record
   - `session_regenerate_id(true)`
   - Set session: `admin_id`, `admin_name`, `admin_email`, `admin_role`
   - `unset($_SESSION['otp_user_id'])`
   - `redirect('/admin/index.php')`

**Dev notice (only when `APP_ENV === 'local'`):** yellow Bootstrap alert on the page:
```
⚠ DEV MODE — OTP was written to the PHP error log (not shown here).
```
The OTP value itself is not rendered in HTML (it's in the error log only) — this avoids it appearing in browser history or server access logs.

---

## `admin/logout.php`

```php
<?php
require_once '../includes/config.php';
// No requireAdmin() guard — destroying a non-existent session is harmless.
// config.php calls session_start() so the session is active before destroy.
session_destroy();
redirect('/login.php');
```

Accessed via GET link in admin header. No CSRF protection — CSRF logout risk is low (worst case: staff member gets logged out unexpectedly), and a POST form adds complexity for minimal security gain.

---

## Password Reset

### `forgot-password.php`

POST handler:

1. `verifyCsrf()`
2. `$email = trim($_POST['email'] ?? '')`; validate required + `filter_var(FILTER_VALIDATE_EMAIL)`
3. Fetch user by email (no error if not found — same response either way)
4. If user exists (user record fetched in step 3 — `$user['id']` is available):
   - `$token = bin2hex(random_bytes(32))` (raw token — sent in URL, never stored)
   - `$hash = hash('sha256', $token)` (stored in DB — never the raw token)
   - `$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'))`
   - `UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?` — uses `$user['id']`
   - `error_log("GREENCASH DEV RESET LINK: " . APP_URL . "/reset-password.php?token=$token")`
5. Always: `setFlash('info', 'If that email is registered, a password reset link has been sent.')` → `redirect('forgot-password.php')`

### `reset-password.php`

**GET (token validation):**
- `$hash = hash('sha256', $_GET['token'] ?? '')`
- `SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1`
- If not found: show error card with link back to `forgot-password.php` — do not render the form

**POST handler:**
1. `verifyCsrf()`
2. Re-validate token using same query (guards against token swap between GET and POST)
3. Validate: password required, min 8 chars, matches confirmation
4. `password_hash($newPassword, PASSWORD_BCRYPT)`
5. `UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?`
6. `setFlash('success', 'Password updated. Please log in.')` → `redirect('login.php')`

---

## Rate Limit Reference

| Context | Key | Max attempts | Lock duration |
|---|---|---|---|
| `ip_login` | IP address | 5 | 15 minutes |
| `admin_login` | Email address | 10 | 30 minutes |
| `otp_verify` | User ID (string) | 5 | 15 minutes |

`ip_login` and `admin_login` are checked and incremented on password failure; both reset on success. `otp_verify` is checked and incremented on OTP failure; reset on OTP success. A locked `otp_verify` clears the session and sends the user back to `login.php`.

---

## Stub → Production Swap (Step 7)

In Step 7, replace the two `error_log()` calls with PHPMailer:
- `sendAdminOTP($user, $otp)` — sends OTP email using the branded template
- `sendPasswordResetEmail($user, $token)` — sends reset link email

The `APP_ENV` check on the dev notice in `verify-otp.php` should also be removed or kept gated.

---

## Done Condition

1. `http://localhost/greencash/login.php` loads with no PHP errors
2. Wrong password 5× from same IP → "Too many attempts from your location" message
3. Correct credentials → redirected to `verify-otp.php`; OTP visible in PHP error log (`tail -f` or XAMPP error log)
4. Correct OTP → redirected to `admin/index.php` (stub page acceptable for now)
5. Expired OTP → "Invalid or expired OTP" error shown
6. `admin/logout.php` destroys session, redirects to `login.php`
7. `forgot-password.php` shows same success message for registered and unregistered emails
8. Reset link in error log → `reset-password.php?token=...` → password updated → can log in with new password
9. Expired/invalid reset token → error shown, link back to `forgot-password.php`

---

## Local → Production Notes

- Set `APP_ENV = 'production'` in `env.php` before deploy (removes dev OTP notice)
- Replace `error_log()` stubs with PHPMailer in Step 7
- Default admin password (`changeme123`) must be changed before go-live
