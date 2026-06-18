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

<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap">
        <div class="sec-head">
            <h2>Contact us</h2>
            <p>Questions? We're open 24 hours.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="flash flash--error" style="max-width:980px;margin:0 auto 22px">Please fix the errors below and try again.</div>
        <?php endif; ?>

        <div class="grid-3" style="grid-template-columns:1fr 2fr;align-items:start">
            <div class="card">
                <h3 style="margin-bottom:18px">Reach us</h3>
                <p><b>Address</b><br>29 Kariba Crescent, Ballito<br>KwaZulu-Natal</p>
                <p style="margin-top:14px"><b>Phone / WhatsApp</b><br>+27 (0)78 517 7961</p>
                <p style="margin-top:14px"><b>Email</b><br>info@greencash.co.za</p>
                <p style="margin-top:14px"><b>Hours</b><br>24 hours / 7 days</p>
            </div>

            <div class="form-shell">
                <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php" novalidate>
                    <?= csrfField() ?>
                    <h3 style="margin-bottom:6px">Send us a message</h3>
                    <p class="desc">We typically reply within one business day.</p>
                    <div class="fgrid">
                        <div class="field <?= isset($errors['name']) ? 'invalid' : '' ?>">
                            <label>Your name <span class="req">*</span></label>
                            <input name="name" maxlength="100" required value="<?= sanitize($old['name'] ?? '') ?>">
                            <?php if (isset($errors['name'])): ?><div class="err"><?= sanitize($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="field <?= isset($errors['email']) ? 'invalid' : '' ?>">
                            <label>Email <span class="req">*</span></label>
                            <input type="email" name="email" maxlength="255" required value="<?= sanitize($old['email'] ?? '') ?>">
                            <?php if (isset($errors['email'])): ?><div class="err"><?= sanitize($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="field <?= isset($errors['phone']) ? 'invalid' : '' ?>">
                            <label>Phone <span class="hint" style="font-weight:normal">(optional)</span></label>
                            <input type="tel" name="phone" maxlength="20" value="<?= sanitize($old['phone'] ?? '') ?>">
                            <?php if (isset($errors['phone'])): ?><div class="err"><?= sanitize($errors['phone']) ?></div><?php endif; ?>
                        </div>
                        <div class="field <?= isset($errors['subject']) ? 'invalid' : '' ?>">
                            <label>Subject <span class="req">*</span></label>
                            <input name="subject" maxlength="255" required value="<?= sanitize($old['subject'] ?? '') ?>">
                            <?php if (isset($errors['subject'])): ?><div class="err"><?= sanitize($errors['subject']) ?></div><?php endif; ?>
                        </div>
                        <div class="field full <?= isset($errors['message']) ? 'invalid' : '' ?>">
                            <label>Message <span class="req">*</span></label>
                            <textarea name="message" rows="5" maxlength="5000" required><?= sanitize($old['message'] ?? '') ?></textarea>
                            <?php if (isset($errors['message'])): ?><div class="err"><?= sanitize($errors['message']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Send message</button>
                </form>
            </div>
        </div>

        <div style="margin-top:60px;max-width:780px">
            <h2 id="complaints">Complaints procedure</h2>
            <p style="color:var(--muted);margin-top:10px">If you have a complaint about our service or a loan decision, please email <b>complaints@greencash.co.za</b> with your reference number and a description. We acknowledge within 48 hours and aim to resolve within 14 business days. If you're not satisfied with our response, you can escalate to the National Credit Regulator (NCR) at <a href="https://www.ncr.org.za" style="color:var(--green-deep)">ncr.org.za</a>.</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
