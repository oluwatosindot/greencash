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

// Look up the target email for display (read-only, masked is not required since user already authenticated step 1)
$targetEmail = '';
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
if ($row = $stmt->fetch()) {
    $targetEmail = $row['email'];
}

$page_title = 'Verify code';
include 'includes/header.php';
?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:480px">
        <div class="sec-head">
            <h2>Verify your code</h2>
            <p>We sent a 6-digit code to <b><?= htmlspecialchars($targetEmail !== '' ? $targetEmail : 'your account', ENT_QUOTES, 'UTF-8') ?></b>. Enter it below.</p>
        </div>
        <?php if (defined('APP_ENV') && APP_ENV === 'local'): ?>
            <div class="flash flash--warning" style="margin-bottom:20px">
                <span><strong>DEV MODE</strong> — OTP was written to the PHP error log (not shown here).</span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="flash flash--error" style="margin-bottom:20px">
                <span><?= sanitize($error) ?></span>
            </div>
        <?php endif; ?>
        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/verify-otp.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>6-digit code <span class="req">*</span></label>
                    <input name="otp_code" inputmode="numeric" maxlength="6" pattern="\d{6}" required autofocus autocomplete="one-time-code" style="text-align:center;letter-spacing:.5em;font-size:22px;font-family:'Sora',sans-serif">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Verify code</button>
            </form>
        </div>
        <p style="text-align:center;margin-top:18px">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" style="color:var(--green-deep)">← Back to login</a>
        </p>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
