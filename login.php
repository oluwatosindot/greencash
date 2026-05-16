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
