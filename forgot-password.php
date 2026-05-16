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
