<?php
require_once 'includes/config.php';

// Guard — must arrive via successful apply.php submission
if (empty($_SESSION['app_reference'])) {
    redirect('/apply.php');
}

$app_reference = $_SESSION['app_reference'];
$app_name      = $_SESSION['app_name'] ?? 'Applicant';
unset($_SESSION['app_reference'], $_SESSION['app_name']);

$page_title = 'Application Submitted';
include 'includes/header.php';
?>

<section class="apply" style="min-height:calc(100vh - 200px);display:grid;place-items:center">
    <div class="wrap" style="max-width:640px">
        <div class="form-shell">
            <div class="form-body">
                <div class="success">
                    <div class="badge">&#10003;</div>
                    <h3>Application received!</h3>
                    <p>Thank you<?= !empty($app_name) ? ', ' . htmlspecialchars($app_name, ENT_QUOTES, 'UTF-8') : '' ?>. We've received your application and our team is reviewing it now. You'll get a decision and next steps by SMS and email shortly.</p>
                    <?php if (!empty($app_reference)): ?>
                    <div class="ref-no"><?= htmlspecialchars($app_reference, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <p style="margin-top:18px;font-size:13px">Questions? Call us on <b>+27 (0)78 517 7961</b> &mdash; open 24 hours.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
