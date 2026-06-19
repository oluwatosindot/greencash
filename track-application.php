<?php
require_once 'includes/config.php';

$page_title = 'Track Your Application';
$app        = null;
$notFound   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // Per-IP rate limit — stops enumeration of reference numbers and ID pairs.
    $ip = getClientIp();
    if (checkRateLimit($pdo, $ip, 'track_app_ip', 10, 15)) {
        setFlash('error', 'Too many lookup attempts. Please try again in 15 minutes.');
        redirect('/track-application.php');
    }
    incrementRateLimit($pdo, $ip, 'track_app_ip', 10, 15);

    $ref      = trim($_POST['reference_number'] ?? '');
    $idNumber = trim($_POST['id_number'] ?? '');

    // Reject malformed inputs early — SA ID is 13 digits, reference is GC-XXXXXX format.
    if (!preg_match('/^\d{13}$/', $idNumber)) {
        $notFound = true;
    } elseif (!empty($ref) && !empty($idNumber)) {
        $stmt = $pdo->prepare(
            "SELECT * FROM salary_advance_applications WHERE reference_number = ? AND id_number = ? LIMIT 1"
        );
        $stmt->execute([$ref, $idNumber]);
        $app = $stmt->fetch();
        if (!$app) $notFound = true;
    }
}

include 'includes/header.php';
?>

<section class="block" style="background:var(--paper);min-height:calc(100vh - 280px)">
    <div class="wrap" style="max-width:600px">
        <div class="sec-head">
            <h1>Track your application</h1>
            <p>Enter your reference number and SA ID number to check the latest status.</p>
        </div>

        <div class="form-shell">
            <form class="form-body" method="post" action="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php" novalidate>
                <?= csrfField() ?>
                <div class="field">
                    <label>Reference number <span class="req">*</span></label>
                    <input name="reference_number" placeholder="e.g. GC-20260516-A3F2K1" required value="<?= isset($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <div class="field">
                    <label>SA ID number <span class="req">*</span></label>
                    <input name="id_number" placeholder="13 digits" maxlength="13" inputmode="numeric" required value="<?= isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number'], ENT_QUOTES, 'UTF-8') : '' ?>">
                </div>
                <button type="submit" name="track_submit" class="btn btn-primary btn-block">Check status</button>
            </form>
        </div>

        <?php if ($notFound): ?>
        <div class="flash flash--error" style="margin-top:24px">
            No application found with those details. Please check your reference number and ID number and try again.
        </div>
        <?php endif; ?>

        <?php if (!empty($app)): ?>
        <div class="form-shell" style="margin-top:24px">
            <div class="form-body">
                <div class="success">
                    <?php
                    $status = $app['status'] ?? 'pending';
                    $badge = match ($status) {
                        'approved', 'disbursed' => ['linear-gradient(135deg,var(--green),var(--green-deep))', '#fff', '&#10003;'],
                        'rejected'              => ['linear-gradient(135deg,var(--muted),#424a44)', '#fff', '&#10007;'],
                        default                 => ['linear-gradient(135deg,var(--yellow),#e0a800)', 'var(--ink)', '&#8987;'],
                    };
                    ?>
                    <div class="badge" style="background:<?= $badge[0] ?>;color:<?= $badge[1] ?>"><?= $badge[2] ?></div>
                    <h3>Status: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8') ?></h3>
                    <?php if ($status === 'rejected'): ?>
                    <p>Unfortunately, your application was not approved at this time. If you have questions, please <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php">contact us</a>.</p>
                    <?php elseif ($status === 'approved'): ?>
                    <p>Great news &mdash; your application has been approved. Our team will be in touch shortly to finalise disbursement.</p>
                    <?php elseif ($status === 'disbursed'): ?>
                    <p>Your funds have been disbursed. Please allow up to 24 hours for the deposit to reflect in your account.</p>
                    <?php elseif ($status === 'under_review'): ?>
                    <p>Your application is currently being reviewed by our team. We&rsquo;ll notify you as soon as a decision is made.</p>
                    <?php else: ?>
                    <p>We&rsquo;ve received your application and it&rsquo;s in the queue. Please check back soon for an update.</p>
                    <?php endif; ?>
                    <p>
                        <strong><?= htmlspecialchars(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        &middot; Loan amount: <strong><?= htmlspecialchars(formatCurrency((float) ($app['loan_amount'] ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if (!empty($app['submitted_at'])): ?>
                        <br><span style="font-size:13px">Submitted <?= htmlspecialchars(date('d M Y', strtotime($app['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </p>
                    <div class="ref-no"><?= htmlspecialchars($app['reference_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
