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
