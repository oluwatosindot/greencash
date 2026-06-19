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
        } elseif (strlen($password) > 72) {
            // bcrypt silently truncates at 72 bytes — reject longer so users don't
            // get a false sense of security from an unused suffix.
            $errors['password'] = 'Password must be 72 characters or fewer.';
        } elseif (!preg_match('/\d/', $password)) {
            $errors['password'] = 'Password must contain at least one digit.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $pdo->prepare(
                "UPDATE users
                 SET password = ?, reset_token = NULL, reset_token_expires = NULL
                 WHERE id = ?"
            )->execute([password_hash($password, PASSWORD_BCRYPT), $user['id']]);

            // Regenerate session ID to prevent pre-auth session fixation.
            session_regenerate_id(true);

            setFlash('success', 'Password updated successfully. Please log in.');
            redirect('login.php');
        }
    }
}

$page_title = 'Reset password';
include 'includes/header.php';
?>
<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:480px">
        <?php if (!$user || isset($errors['token'])): ?>
            <div class="sec-head">
                <h2>Link invalid or expired</h2>
                <p>This password reset link is invalid or has expired. Reset links are valid for 1 hour.</p>
            </div>
            <div class="form-shell">
                <div class="form-body" style="text-align:center">
                    <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/forgot-password.php" class="btn btn-primary btn-block">Request a new link</a>
                </div>
            </div>
        <?php else: ?>
            <div class="sec-head">
                <h2>Choose a new password</h2>
                <p>Make it 8+ characters with at least one number.</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="flash flash--error" style="margin-bottom:20px">
                    <span><?= sanitize($errors['password'] ?? ($errors['password_confirm'] ?? 'Please fix the errors below.')) ?></span>
                </div>
            <?php endif; ?>
            <div class="form-shell">
                <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/reset-password.php?token=<?= htmlspecialchars(urlencode($tokenRaw), ENT_QUOTES, 'UTF-8') ?>" novalidate>
                    <?= csrfField() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($tokenRaw, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="field">
                        <label>New password <span class="req">*</span></label>
                        <input type="password" name="password" minlength="8" required autofocus>
                    </div>
                    <div class="field">
                        <label>Confirm new password <span class="req">*</span></label>
                        <input type="password" name="password_confirm" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Update password</button>
                </form>
            </div>
        <?php endif; ?>
        <p style="text-align:center;margin-top:18px">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" style="color:var(--green-deep)">← Back to sign in</a>
        </p>
    </div>
</section>
<?php include 'includes/footer.php'; ?>
