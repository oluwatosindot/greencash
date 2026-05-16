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
