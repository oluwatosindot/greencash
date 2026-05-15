# Pattern: Admin Authentication (2FA with OTP)

## login.php — Step 1: Email + Password

```php
<?php
require_once 'includes/config.php';

// Already logged in?
if (isAdmin()) redirect('/admin/index.php');

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip       = getClientIp();

    // Check IP rate limit (5 attempts / 15 min)
    $ipLock = checkRateLimit($pdo, $ip, 'ip_login', 5, 15);
    if ($ipLock) {
        $error = 'Too many attempts from your location. Try again in 15 minutes.';
    } else {
        // Find admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check per-account lockout (10 attempts / 30 min) — mirrors broker lockout
        if ($user && checkRateLimit($pdo, $email, 'admin_login', 10, 30)) {
            $error = 'Account temporarily locked due to too many failed attempts. Try again in 30 minutes.';
        } elseif ($user && password_verify($password, $user['password'])) {
            // Reset rate limits on success
            resetRateLimit($pdo, $email, 'admin_login');
            resetRateLimit($pdo, $ip, 'ip_login');

            // Generate OTP
            $otp = generateOTP();
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Invalidate old OTPs
            $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE user_id = ?")->execute([$user['id']]);

            // Save new OTP
            $pdo->prepare("INSERT INTO otp_verifications (user_id, otp_code, expires_at) VALUES (?, ?, ?)")
                ->execute([$user['id'], $otp, $expiry]);

            // Send OTP email
            sendAdminOTP($user, $otp);

            // Store user ID in session temporarily (not logged in yet)
            $_SESSION['otp_user_id'] = $user['id'];

            redirect('/verify-otp.php');
        } else {
            // Increment rate limit
            incrementRateLimit($pdo, $email, 'admin_login', 10, 30);
            incrementRateLimit($pdo, $ip, 'ip_login', 5, 15);
            $error = 'Invalid email or password.';
        }
    }
}
?>
```

## verify-otp.php — Step 2: OTP Verification

```php
<?php
require_once 'includes/config.php';

if (empty($_SESSION['otp_user_id'])) redirect('/login.php');

$error = null;
$userId = $_SESSION['otp_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $otpInput = trim($_POST['otp_code'] ?? '');

    $stmt = $pdo->prepare(
        "SELECT * FROM otp_verifications
         WHERE user_id = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1"
    );
    $stmt->execute([$userId, $otpInput]);
    $otp = $stmt->fetch();

    if ($otp) {
        // Mark OTP as used
        $pdo->prepare("UPDATE otp_verifications SET used = 1 WHERE id = ?")->execute([$otp['id']]);

        // Fetch user
        $user = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $user->execute([$userId]);
        $user = $user->fetch();

        // Establish session
        session_regenerate_id(true);
        $_SESSION['admin_id']    = $user['id'];
        $_SESSION['admin_name']  = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_role']  = $user['role'];
        unset($_SESSION['otp_user_id']);

        redirect('/admin/index.php');
    } else {
        $error = 'Invalid or expired OTP. Please try again.';
    }
}
?>
```

## Rate Limit Helper Functions (add to config.php)

```php
/**
 * Check if identifier is rate limited / locked
 * Returns true if locked, false if OK to proceed
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
 * Increment rate limit counter, lock if threshold reached
 */
function incrementRateLimit(PDO $pdo, string $identifier, string $type, int $maxAttempts, int $lockMinutes): void {
    // Note: MySQL evaluates the IF() against the OLD column value before the SET clause runs.
    // We use (attempts + 1 >= ?) which means: "after this increment, have we hit the limit?"
    // Because `attempts` in the IF refers to the pre-increment value, the condition triggers correctly
    // on the Nth attempt (e.g. old=9, 9+1=10 >= 10 → lock on the 10th attempt as intended).
    $pdo->prepare(
        "INSERT INTO rate_limits (identifier, attempt_type, attempts, last_attempt_at)
         VALUES (?, ?, 1, NOW())
         ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            last_attempt_at = NOW(),
            locked_until = IF(attempts + 1 >= ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), locked_until)"
    )->execute([$identifier, $type, $maxAttempts, $lockMinutes]);
}

/**
 * Reset rate limit on successful login
 */
function resetRateLimit(PDO $pdo, string $identifier, string $type): void {
    $pdo->prepare(
        "UPDATE rate_limits SET attempts = 0, locked_until = NULL WHERE identifier = ? AND attempt_type = ?"
    )->execute([$identifier, $type]);
}

/**
 * Generate a 6-digit OTP
 */
function generateOTP(): string {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
```

## forgot-password.php / reset-password.php Pattern

```php
// forgot-password.php: generate token, store hash, send email
$token = bin2hex(random_bytes(32));
$hash  = hash('sha256', $token);
$expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

$pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?")
    ->execute([$hash, $expiry, $email]);

// Email the raw $token in the reset link

// reset-password.php: verify token
$hash = hash('sha256', $_GET['token'] ?? '');
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW() LIMIT 1");
$stmt->execute([$hash]);
$user = $stmt->fetch();
// If found: update password, clear token
```

Note: Add `reset_token VARCHAR(64)` and `reset_token_expires TIMESTAMP NULL` columns to `users` table.
