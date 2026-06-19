<?php
require_once 'includes/config.php';

if (isAdmin()) redirect('/admin/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Per-IP rate limit — prevents enumeration probing and reset-link spamming.
    $ip = getClientIp();
    if (checkRateLimit($pdo, $ip, 'forgot_pw_ip', 5, 15)) {
        setFlash('error', 'Too many password reset requests. Please try again in 15 minutes.');
        redirect('/forgot-password.php');
    }
    incrementRateLimit($pdo, $ip, 'forgot_pw_ip', 5, 15);

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

$page_title = 'Forgot password';
include 'includes/header.php';
?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:480px">
        <div class="sec-head">
            <h2>Forgot your password?</h2>
            <p>Enter the email on your account and we'll send a reset link.</p>
        </div>
        <?php if (!empty($errors['email'])): ?>
            <div class="flash flash--error" style="margin-bottom:20px">
                <span><?= sanitize($errors['email']) ?></span>
            </div>
        <?php endif; ?>
        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/forgot-password.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>Email address <span class="req">*</span></label>
                    <input type="email" name="email" required autofocus value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:18px">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" style="color:var(--green-deep)">← Back to sign in</a>
        </p>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
