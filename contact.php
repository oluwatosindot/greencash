<?php
require_once 'includes/config.php';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Collect raw trimmed values
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Preserve values for re-render on error
    $old = compact('name', 'email', 'phone', 'subject', 'message');

    // Validate
    if (empty($name))                                       $errors['name']    = 'Name is required.';
    elseif (mb_strlen($name) > 100)                        $errors['name']    = 'Name must be 100 characters or less.';

    if (empty($email))                                      $errors['email']   = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors['email']   = 'Please enter a valid email address.';
    elseif (mb_strlen($email) > 255)                       $errors['email']   = 'Email must be 255 characters or less.';

    if (!empty($phone) && mb_strlen($phone) > 20)          $errors['phone']   = 'Phone must be 20 characters or less.';

    if (empty($subject))                                    $errors['subject'] = 'Subject is required.';
    elseif (mb_strlen($subject) > 255)                     $errors['subject'] = 'Subject must be 255 characters or less.';

    if (empty($message))                                    $errors['message'] = 'Message is required.';
    elseif (mb_strlen($message) > 5000)                    $errors['message'] = 'Message must be 5000 characters or less.';

    if (empty($errors)) {
        // Store — NOTE: email stored raw (not sanitize()) to preserve the address
        $stmt = $pdo->prepare(
            "INSERT INTO contact_messages (name, email, phone, subject, message, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            sanitize($name),
            $email,
            !empty($phone) ? sanitize($phone) : null,
            sanitize($subject),
            sanitize($message),
            getClientIp(),
        ]);

        setFlash('success', 'Thank you! We\'ll be in touch shortly.');
        redirect('contact.php');
    }
}

$page_title = 'Contact Us';
include 'includes/header.php';
?>

<div class="row g-5">
    <!-- Contact Form -->
    <div class="col-md-7">
        <h1 class="section-title mb-1">Contact Us</h1>
        <p class="text-muted mb-4">Have a question? Fill in the form and we'll get back to you.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">Please fix the errors below and try again.</div>
        <?php endif; ?>

        <form method="POST" action="contact.php" novalidate>
            <?= csrfField() ?>

            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" maxlength="100"
                       class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['name'] ?? '') ?>">
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" maxlength="255"
                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['email'] ?? '') ?>">
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Phone <span class="text-muted fw-normal">(optional)</span></label>
                <input type="tel" name="phone" maxlength="20"
                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['phone'] ?? '') ?>">
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['phone']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
                <input type="text" name="subject" maxlength="255"
                       class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                       value="<?= sanitize($old['subject'] ?? '') ?>">
                <?php if (isset($errors['subject'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['subject']) ?></div>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                <textarea name="message" rows="5" maxlength="5000"
                          class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"><?= sanitize($old['message'] ?? '') ?></textarea>
                <?php if (isset($errors['message'])): ?>
                    <div class="invalid-feedback"><?= sanitize($errors['message']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-gc px-4 py-2">
                <i class="bi bi-send me-2"></i>Send Message
            </button>
        </form>
    </div>

    <!-- Contact Details -->
    <div class="col-md-4 offset-md-1">
        <h5 class="fw-semibold mb-3">Get In Touch</h5>
        <ul class="list-unstyled">
            <li class="mb-3 d-flex gap-3">
                <i class="bi bi-telephone-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Phone</div>
                    <div class="text-muted">+27 (0) 10 000 0000</div>
                </div>
            </li>
            <li class="mb-3 d-flex gap-3">
                <i class="bi bi-envelope-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Email</div>
                    <div class="text-muted">admin@greencash.co.za</div>
                </div>
            </li>
            <li class="d-flex gap-3">
                <i class="bi bi-clock-fill text-success mt-1"></i>
                <div>
                    <div class="fw-semibold">Business Hours</div>
                    <div class="text-muted">Mon–Fri, 8am–5pm</div>
                </div>
            </li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
