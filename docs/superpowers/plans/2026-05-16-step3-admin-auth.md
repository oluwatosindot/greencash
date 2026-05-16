# Step 3: Admin Authentication — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build admin authentication — email/password login, 6-digit OTP 2FA, rate-limited lockout, and password reset via emailed token stub.

**Architecture:** Five self-contained PHP files at the root level plus `admin/logout.php`. Auth pages use a minimal Bootstrap 5 CDN layout (no shared public header/footer). Four rate-limit helper functions are appended to `includes/config.php`. OTP and reset-link email is stubbed via `error_log()` for local dev; PHPMailer replaces it in Step 7.

**Tech Stack:** PHP 8+, PDO/MySQL, Bootstrap 5 CDN, Bootstrap Icons CDN. No framework.

---

## Chunk 1: Helpers, Login, OTP, Logout

---

### Task 1: Add rate-limit helpers + generateOTP to config.php; add APP_ENV to env.php

**Files:**
- Modify: `includes/config.php` (append four functions at the end)
- Modify: `includes/env.php` (append one constant)

---

- [ ] **Step 1: Append APP_ENV to `includes/env.php`**

Open `includes/env.php` and add this line at the bottom:

```php
define('APP_ENV', 'local'); // set to 'production' before deploy
```

- [ ] **Step 2: Append four helper functions to `includes/config.php`**

Add these four functions at the end of `includes/config.php`, after `getClientIp()`:

```php
/**
 * Returns true if identifier is currently rate-limited / locked out, false if OK.
 */
function checkRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): bool {
    $stmt = $pdo->prepare(
        "SELECT attempts, locked_until FROM rate_limits
         WHERE identifier = ? AND attempt_type = ? LIMIT 1"
    );
    $stmt->execute([$identifier, $type]);
    $row = $stmt->fetch();

    if (!$row) return false;

    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        return true; // still locked
    }

    return false;
}

/**
 * Increment attempt counter; lock identifier once maxAttempts is reached.
 * Single atomic upsert — ON DUPLICATE KEY UPDATE.
 * NOTE: MySQL evaluates IF() against the pre-increment value of `attempts`.
 * So `attempts + 1 >= $maxAttempts` correctly locks on the Nth attempt
 * (e.g. old=9, 9+1=10 >= 10 → lock fires on the 10th attempt as intended).
 */
function incrementRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): void {
    $pdo->prepare(
        "INSERT INTO rate_limits (identifier, attempt_type, attempts, last_attempt_at)
         VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
            attempts        = attempts + 1,
            last_attempt_at = NOW(),
            locked_until    = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)"
    )->execute([$identifier, $type, $maxAttempts, $lockMinutes]);
}

/**
 * Reset rate limit counter and lock on successful login.
 */
function resetRateLimit(PDO $pdo, string $identifier, string $type): void {
    $pdo->prepare(
        "UPDATE rate_limits SET attempts = 0, locked_until = NULL
         WHERE identifier = ? AND attempt_type = ?"
    )->execute([$identifier, $type]);
}

/**
 * Generate a cryptographically random 6-digit OTP string (zero-padded).
 */
function generateOTP(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
```

- [ ] **Step 3: Verify PHP syntax**

Run from the `greencash/` directory:

```bash
php -l includes/config.php
php -l includes/env.php
```

Expected output:
```
No syntax errors detected in includes/config.php
No syntax errors detected in includes/env.php
```

- [ ] **Step 4: Commit**

```bash
git add includes/config.php includes/env.php
git commit -m "feat: add rate-limit helpers, generateOTP, APP_ENV"
```

---

### Task 2: Create `login.php`

**Files:**
- Create: `login.php`

---

- [ ] **Step 1: Create `login.php` with the complete implementation**

```php
<?php
require_once 'includes/config.php';

// Already logged in — send to dashboard
if (isAdmin()) redirect('/admin/index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = getClientIp();

    // 1. IP rate limit (5 attempts / 15 min)
    if (checkRateLimit($pdo, $ip, 'ip_login', 5, 15)) {
        $error = 'Too many attempts from your location. Try again in 15 minutes.';
    } else {
        // 2. Look up active user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 3. Per-account lockout check (only when user exists — no email to key on otherwise)
        // NOTE: locked-account uses the same generic error as wrong-password to prevent enumeration.
        // Revealing "account locked" only when the email exists leaks whether the email is registered.
        $accountLocked = $user && checkRateLimit($pdo, $email, 'admin_login', 10, 30);

        if (!$accountLocked && $user && password_verify($password, $user['password'])) {
            // 4a. Success — reset rate limits
            resetRateLimit($pdo, $email, 'admin_login');
            resetRateLimit($pdo, $ip, 'ip_login');

            // 4b. Invalidate any existing unused OTPs
            $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE user_id = ?")
                ->execute([$user['id']]);

            // 4c. Generate and store new OTP (10-minute expiry)
            $otp    = generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $pdo->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, ?)")
                ->execute([$user['id'], $otp, $expiry]);

            // 4d. Stub: write OTP to error log (replaced by PHPMailer in Step 7)
            error_log("GREENCASH DEV OTP for {$user['email']}: $otp");

            // 4e. Store user ID in session (not fully logged in yet — waiting for OTP)
            $_SESSION['otp_user_id'] = $user['id'];
            redirect('/verify-otp.php');
        } else {
            // 4f. Fail — increment rate limits
            // ip_login: always; admin_login: only when user exists AND not already locked
            if ($user && !$accountLocked) {
                incrementRateLimit($pdo, $email, 'admin_login', 10, 30);
            }
            incrementRateLimit($pdo, $ip, 'ip_login', 5, 15);
            // Same generic message for all failure states (wrong password, user not found, account locked)
            // — prevents enumeration by not revealing which condition triggered the failure.
            $error = 'Invalid email or password.';
        }
    }
}

// Show flash (e.g. from OTP lockout redirect)
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .auth-wrap { max-width: 420px; margin: 80px auto; padding: 0 1rem; }
        .brand { color: #2E7D32; font-weight: 700; font-size: 1.35rem; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="text-center mb-4">
        <div class="brand">
            <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="text-muted small mt-1">Admin Portal</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="card-title mb-4">Sign In</h5>

            <?php if ($flash): ?>
                <?php
                $allowedTypes = ['success', 'danger', 'warning', 'info'];
                $flashType = in_array($flash['type'], $allowedTypes, true) ? $flash['type'] : 'info';
                if ($flash['type'] === 'error') $flashType = 'danger';
                ?>
                <div class="alert alert-<?= $flashType ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email" class="form-control" required autofocus
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2">Sign In</button>
            </form>

            <div class="text-center mt-3">
                <a href="forgot-password.php" class="text-muted small text-decoration-none">
                    Forgot your password?
                </a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l login.php
```

Expected: `No syntax errors detected in login.php`

- [ ] **Step 3: Load in browser and verify the page renders**

Open `http://localhost/greencash/login.php`

Expected: Green Cash brand mark, "Sign In" card with email + password fields and a "Forgot your password?" link. No PHP errors.

- [ ] **Step 4: Commit**

```bash
git add login.php
git commit -m "feat: add admin login page with rate limiting and OTP stub"
```

---

### Task 3: Create `verify-otp.php`

**Files:**
- Create: `verify-otp.php`

---

- [ ] **Step 1: Create `verify-otp.php` with the complete implementation**

```php
<?php
require_once 'includes/config.php';

// Guard: must have arrived via the login page
if (empty($_SESSION['otp_user_id'])) {
    redirect('/login.php');
}

$error  = null;
$userId = (int) $_SESSION['otp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // OTP rate limit (5 attempts / 15 min, keyed to user ID)
    if (checkRateLimit($pdo, (string) $userId, 'otp_verify', 5, 15)) {
        unset($_SESSION['otp_user_id']);
        setFlash('danger', 'Too many failed OTP attempts. Please log in again.');
        redirect('/login.php');
    }

    $otpInput = trim($_POST['otp_code'] ?? '');

    $stmt = $pdo->prepare(
        "SELECT * FROM otp_verifications
         WHERE user_id = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->execute([$userId, $otpInput]);
    $otp = $stmt->fetch();

    if ($otp) {
        // Success
        resetRateLimit($pdo, (string) $userId, 'otp_verify');
        $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE id = ?")
            ->execute([$otp['id']]);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        session_regenerate_id(true);
        $_SESSION['admin_id']    = $user['id'];
        $_SESSION['admin_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['role'];
        unset($_SESSION['otp_user_id']);

        redirect('/admin/index.php');
    } else {
        // Fail
        incrementRateLimit($pdo, (string) $userId, 'otp_verify', 5, 15);
        $error = 'Invalid or expired OTP. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .auth-wrap { max-width: 420px; margin: 80px auto; padding: 0 1rem; }
        .brand { color: #2E7D32; font-weight: 700; font-size: 1.35rem; }
        .otp-input { letter-spacing: 0.4rem; font-size: 1.4rem; text-align: center; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="text-center mb-4">
        <div class="brand">
            <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="text-muted small mt-1">Admin Portal</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="card-title mb-1">Two-Factor Verification</h5>
            <p class="text-muted small mb-4">Enter the 6-digit code sent to your email address.</p>

            <?php if (defined('APP_ENV') && APP_ENV === 'local'): ?>
                <div class="alert alert-warning small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>DEV MODE</strong> — OTP was written to the PHP error log (not shown here).
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= sanitize($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="verify-otp.php">
                <?= csrfField() ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold">OTP Code</label>
                    <input type="text" name="otp_code" class="form-control otp-input"
                           maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                           placeholder="000000" required autofocus autocomplete="one-time-code">
                </div>

                <button type="submit" class="btn btn-success w-100 py-2">Verify</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-muted small text-decoration-none">← Back to login</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l verify-otp.php
```

Expected: `No syntax errors detected in verify-otp.php`

- [ ] **Step 3: Test the full login → OTP flow**

1. Open `http://localhost/greencash/login.php`
2. Enter `admin@greencash.co.za` / `changeme123`
3. Should redirect to `http://localhost/greencash/verify-otp.php`
4. Open XAMPP error log: `C:\xampp\php\logs\php_error_log` (or `C:\xampp\apache\logs\error.log`)
5. Find the line: `GREENCASH DEV OTP for admin@greencash.co.za: XXXXXX`
6. Enter the 6-digit code on the OTP page
7. Should redirect to `http://localhost/greencash/admin/index.php` (404 is fine — stub page comes in Task 4)

- [ ] **Step 4: Test OTP guard**

Navigate directly to `http://localhost/greencash/verify-otp.php` without going through login.

Expected: redirect to `login.php`.

- [ ] **Step 5: Test OTP rate-limit lockout**

1. Log in with correct credentials to land on `verify-otp.php`
2. Submit a wrong OTP code 5 times
3. Expected on the 5th attempt: session cleared, redirected to `login.php` with red flash "Too many failed OTP attempts. Please log in again."
4. To reset the OTP rate limit for re-testing: open phpMyAdmin → run `DELETE FROM rate_limits WHERE attempt_type = 'otp_verify'`

- [ ] **Step 6: Commit**

```bash
git add verify-otp.php
git commit -m "feat: add OTP verification page with rate limiting"
```

---

### Task 4: Create `admin/logout.php` and stub `admin/index.php`

**Files:**
- Create: `admin/logout.php`
- Create: `admin/index.php` (stub — replaced in Step 5)

---

- [ ] **Step 1: Create `admin/logout.php`**

```php
<?php
require_once '../includes/config.php';
// No requireAdmin() guard — destroying a non-existent session is harmless.
// config.php handles session_start(), so the session is active before destroy.
session_destroy();
redirect('/login.php');
```

- [ ] **Step 2: Create `admin/index.php` (stub)**

This is a temporary placeholder. Step 5 replaces it with the real dashboard.

```php
<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Dashboard';
include '../includes/admin-header.php';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="text-center text-muted">
        <i class="bi bi-speedometer2" style="font-size: 3rem; color: #2E7D32;"></i>
        <h4 class="mt-3">Admin Dashboard</h4>
        <p class="mb-0">Full dashboard coming in Step 5.</p>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
```

- [ ] **Step 3: Verify PHP syntax**

```bash
php -l admin/logout.php
php -l admin/index.php
```

Expected: no syntax errors on both.

- [ ] **Step 4: Test the complete login → OTP → dashboard → logout flow**

1. `http://localhost/greencash/login.php` → enter credentials
2. `http://localhost/greencash/verify-otp.php` → enter OTP from error log
3. Expected: land on `admin/index.php` — dark green sidebar, "Admin Dashboard" message
4. Click **Logout** in the sidebar or top bar
5. Expected: redirect to `login.php`
6. Confirm navigating to `admin/index.php` directly now redirects to `login.php`

- [ ] **Step 5: Commit**

```bash
git add admin/logout.php admin/index.php
git commit -m "feat: add admin logout and stub dashboard"
```

---

## Chunk 2: Password Reset and Final Verification

---

### Task 5: Create `forgot-password.php`

**Files:**
- Create: `forgot-password.php`

---

- [ ] **Step 1: Create `forgot-password.php` with the complete implementation**

```php
<?php
require_once 'includes/config.php';

if (isAdmin()) redirect('/admin/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        // Look up user — but ALWAYS show the same flash regardless of result (prevents enumeration)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token  = bin2hex(random_bytes(32)); // raw token — goes in the URL
            $hash   = hash('sha256', $token);    // hashed — stored in DB
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store hash in DB using fetched user ID
            $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
                ->execute([$hash, $expiry, $user['id']]);

            // Stub: log the reset link (replaced by PHPMailer in Step 7)
            error_log('GREENCASH DEV RESET LINK: ' . APP_URL . '/reset-password.php?token=' . $token);
        }

        // Always the same response — do not reveal whether email is registered
        setFlash('info', 'If that email is registered, a password reset link has been sent.');
        redirect('forgot-password.php');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .auth-wrap { max-width: 420px; margin: 80px auto; padding: 0 1rem; }
        .brand { color: #2E7D32; font-weight: 700; font-size: 1.35rem; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="text-center mb-4">
        <div class="brand">
            <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="text-muted small mt-1">Admin Portal</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h5 class="card-title mb-1">Reset Password</h5>
            <p class="text-muted small mb-4">Enter your admin email address and we'll send a reset link.</p>

            <?php if ($flash): ?>
                <?php
                $allowedTypes = ['success', 'danger', 'warning', 'info'];
                $flashType = in_array($flash['type'], $allowedTypes, true) ? $flash['type'] : 'info';
                if ($flash['type'] === 'error') $flashType = 'danger';
                ?>
                <div class="alert alert-<?= $flashType ?>">
                    <?= sanitize($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php">
                <?= csrfField() ?>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Email Address</label>
                    <input type="email" name="email"
                           class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= sanitize($_POST['email'] ?? '') ?>"
                           required autofocus>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2">Send Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-muted small text-decoration-none">← Back to login</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l forgot-password.php
```

Expected: `No syntax errors detected in forgot-password.php`

- [ ] **Step 3: Test the form**

1. Open `http://localhost/greencash/forgot-password.php`
2. Submit with a **non-existent** email → same info flash shown ("If that email is registered...")
3. Submit with `admin@greencash.co.za` → same flash shown
4. Check XAMPP error log for: `GREENCASH DEV RESET LINK: http://localhost/greencash/reset-password.php?token=...`

- [ ] **Step 4: Commit**

```bash
git add forgot-password.php
git commit -m "feat: add forgot-password page with anti-enumeration stub"
```

---

### Task 6: Create `reset-password.php`

**Files:**
- Create: `reset-password.php`

---

- [ ] **Step 1: Create `reset-password.php` with the complete implementation**

```php
<?php
require_once 'includes/config.php';

if (isAdmin()) redirect('/admin/index.php');

$errors  = [];
$tokenRaw = $_GET['token'] ?? $_POST['token'] ?? '';
$user    = null;

// Validate token on both GET and POST — guards against token swap
if (!empty($tokenRaw)) {
    $hash = hash('sha256', $tokenRaw);
    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1"
    );
    $stmt->execute([$hash]);
    $user = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (!$user) {
        // Token invalid or expired — show error below
        $errors['token'] = 'This reset link is invalid or has expired.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (empty($password)) {
            $errors['password'] = 'New password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $pdo->prepare(
                "UPDATE users
                 SET password = ?, reset_token = NULL, reset_token_expires = NULL
                 WHERE id = ?"
            )->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);

            setFlash('success', 'Password updated successfully. Please log in.');
            redirect('login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .auth-wrap { max-width: 420px; margin: 80px auto; padding: 0 1rem; }
        .brand { color: #2E7D32; font-weight: 700; font-size: 1.35rem; }
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="text-center mb-4">
        <div class="brand">
            <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <p class="text-muted small mt-1">Admin Portal</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <?php if (!$user || isset($errors['token'])): ?>
                <!-- Invalid / expired token state -->
                <div class="text-center py-2">
                    <i class="bi bi-x-circle text-danger" style="font-size: 2.5rem;"></i>
                    <h5 class="mt-3">Link Invalid or Expired</h5>
                    <p class="text-muted small">This password reset link is invalid or has expired. Reset links are valid for 1 hour.</p>
                    <a href="forgot-password.php" class="btn btn-success mt-2">Request a New Link</a>
                </div>

            <?php else: ?>
                <!-- Valid token — show password form -->
                <h5 class="card-title mb-1">Set New Password</h5>
                <p class="text-muted small mb-4">Choose a strong password of at least 8 characters.</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">Please fix the errors below.</div>
                <?php endif; ?>

                <form method="POST" action="reset-password.php?token=<?= htmlspecialchars(urlencode($tokenRaw), ENT_QUOTES, 'UTF-8') ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($tokenRaw, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" minlength="8"
                               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               required autofocus>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirm" minlength="8"
                               class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
                               required>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <div class="invalid-feedback"><?= sanitize($errors['password_confirm']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2">Update Password</button>
                </form>

            <?php endif; ?>

            <div class="text-center mt-3">
                <a href="login.php" class="text-muted small text-decoration-none">← Back to login</a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

- [ ] **Step 2: Verify PHP syntax**

```bash
php -l reset-password.php
```

Expected: `No syntax errors detected in reset-password.php`

- [ ] **Step 3: Test the full password-reset flow**

1. Go to `http://localhost/greencash/forgot-password.php`
2. Submit `admin@greencash.co.za`
3. Copy the reset URL from the XAMPP error log
4. Open the URL in browser — should show "Set New Password" form
5. Enter a new password (e.g. `NewPass123`) and confirm
6. Expected: redirect to `login.php` with green "Password updated" flash
7. Log in with the new password → OTP → dashboard ✓

- [ ] **Step 4: Test invalid/expired token**

Open `http://localhost/greencash/reset-password.php?token=invalidtoken`

Expected: "Link Invalid or Expired" error card with "Request a New Link" button.

- [ ] **Step 5: Commit**

```bash
git add reset-password.php
git commit -m "feat: add password reset flow with token hashing"
```

---

### Task 7: End-to-End Verification and Push

**Files:** None (verification only)

---

- [ ] **Step 1: Syntax check all 7 files**

```bash
php -l includes/config.php
php -l includes/env.php
php -l login.php
php -l verify-otp.php
php -l admin/logout.php
php -l admin/index.php
php -l forgot-password.php
php -l reset-password.php
```

Expected: `No syntax errors detected` on every file.

- [ ] **Step 2: Full auth flow walkthrough**

Perform this sequence without stopping:

1. `http://localhost/greencash/login.php` — page loads, no errors
2. Enter wrong password 5× from same IP → "Too many attempts from your location" appears
3. Reset to continue: open phpMyAdmin → `DELETE FROM rate_limits WHERE attempt_type = 'ip_login'`
4. Enter correct credentials → redirect to `verify-otp.php`
5. Yellow DEV MODE notice visible
6. OTP in error log → enter correct code → redirect to `admin/index.php`
7. Dashboard stub visible with sidebar and logout button
8. Click Logout → redirect to `login.php`
9. Navigate to `admin/index.php` directly → redirect to `login.php` ✓

- [ ] **Step 3: Verify account-level lockout (admin_login, 10×)**

1. On `login.php`, enter the correct email with a wrong password 10 times in a row
2. On the 10th attempt, expected: "Invalid email or password." (same generic message — no enumeration)
3. On the 11th attempt with the **correct** password, same "Invalid email or password." (account locked)
4. Reset: `DELETE FROM rate_limits WHERE attempt_type = 'admin_login'`

- [ ] **Step 4: Verify OTP lockout (5×)**

1. Log in with correct credentials → land on `verify-otp.php`
2. Enter a wrong OTP code 5 times
3. Expected on 5th attempt: session cleared, redirect to `login.php` with red "Too many failed OTP attempts" flash
4. Reset: `DELETE FROM rate_limits WHERE attempt_type = 'otp_verify'`

- [ ] **Step 5: Verify OTP expiry**

1. Log in with credentials → OTP stored (10-minute expiry)
2. Expire it manually: phpMyAdmin → `UPDATE otp_verifications SET expires_at = NOW() - INTERVAL 1 MINUTE WHERE used = 0`
3. Enter the OTP on `verify-otp.php`
4. Expected: "Invalid or expired OTP" error ✓

- [ ] **Step 6: Verify password reset end-to-end**

1. `forgot-password.php` → submit a **non-existent** email → same info flash shown ✓
2. `forgot-password.php` → submit `admin@greencash.co.za` → same info flash shown ✓ (anti-enumeration)
3. Copy reset link from XAMPP error log → open in browser → "Set New Password" form renders
4. Submit mismatched passwords → validation error shown
5. Submit valid new password → redirect to `login.php` with green "Password updated" flash
6. Log in with new password → full OTP flow succeeds ✓

- [ ] **Step 7: Verify expired/invalid reset token**

1. Open `http://localhost/greencash/reset-password.php?token=invalidtoken`
2. Expected: "Link Invalid or Expired" error card with "Request a New Link" button ✓
3. Test expired token: phpMyAdmin → `UPDATE users SET reset_token_expires = NOW() - INTERVAL 1 MINUTE WHERE reset_token IS NOT NULL`
4. Copy a recently-generated (now expired) token from the error log and open the reset URL
5. Expected: same "Link Invalid or Expired" card ✓

- [ ] **Step 8: Push to staging**

```bash
git push origin staging
```

---

## Done Condition

1. `login.php` loads with no PHP errors
2. Wrong password 5× from same IP → locked; wrong password 10× for same account → locked
3. Correct credentials → `verify-otp.php`; OTP in XAMPP error log; DEV MODE notice visible
4. Wrong OTP 5× → session cleared, redirect to `login.php` with lockout flash
5. Correct OTP → `admin/index.php` stub (sidebar, dashboard message, logout button)
6. Logout → `login.php`; direct access to `admin/index.php` → redirects to `login.php`
7. `forgot-password.php` — same response for registered and unregistered email
8. Reset link in error log → `reset-password.php?token=...` → password updated → login succeeds
9. Invalid/expired reset token → "Link Invalid or Expired" error card
10. All 7 files pass `php -l` with no syntax errors
11. All commits pushed to `origin/staging`
