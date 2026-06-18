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

$page_title = 'Sign in';
include 'includes/header.php';
?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:480px">
        <div class="sec-head">
            <h2>Sign in to your account</h2>
            <p>Welcome back.</p>
        </div>
        <?php if ($error): ?>
            <div class="flash flash--error" style="margin-bottom:20px">
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>
        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email" required autofocus value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div class="field">
                    <label>Password <span class="req">*</span></label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:18px">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/forgot-password.php" style="color:var(--green-deep)">Forgot password?</a>
        </p>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
