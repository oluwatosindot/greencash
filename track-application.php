<?php
require_once 'includes/config.php';

$page_title = 'Track Your Application';
$app        = null;
$notFound   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $ref      = sanitize(trim($_POST['reference_number'] ?? ''));
    $idNumber = sanitize(trim($_POST['id_number'] ?? ''));

    if (!empty($ref) && !empty($idNumber)) {
        $stmt = $pdo->prepare(
            "SELECT * FROM salary_advance_applications WHERE reference_number = ? AND id_number = ? LIMIT 1"
        );
        $stmt->execute([$ref, $idNumber]);
        $app = $stmt->fetch();
        if (!$app) $notFound = true;
    }
}

// Status timeline (ordered)
$timeline = [
    'pending'      => ['label' => 'Application Received', 'icon' => 'bi-envelope-check'],
    'under_review' => ['label' => 'Under Review',         'icon' => 'bi-search'],
    'approved'     => ['label' => 'Approved',             'icon' => 'bi-hand-thumbs-up'],
    'disbursed'    => ['label' => 'Funds Disbursed',      'icon' => 'bi-bank'],
];
$statusOrder = array_keys($timeline);

include 'includes/header.php';
?>

<section class="py-5">
<div class="container">
<div class="row justify-content-center">
<div class="col-lg-7">

    <div class="text-center mb-4">
        <h2 class="fw-bold">Track Your Application</h2>
        <p class="text-muted">Enter your reference number and SA ID number to check your application status.</p>
    </div>

    <!-- Lookup form -->
    <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <form method="POST" action="track-application.php">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. GC-20260516-A3F2K1"
                           value="<?= sanitize($_POST['reference_number'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">SA ID Number</label>
                    <input type="text" name="id_number" class="form-control" placeholder="13 digits" maxlength="13" inputmode="numeric"
                           value="<?= sanitize($_POST['id_number'] ?? '') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3 px-4">
                <i class="bi bi-search me-1"></i>Check Status
            </button>
        </form>
    </div>
    </div>

    <?php if ($notFound): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        No application found with those details. Please check your reference number and ID number and try again.
    </div>
    <?php endif; ?>

    <?php if ($app): ?>
    <?php
    $statusBadge = [
        'pending'      => 'secondary',
        'under_review' => 'warning',
        'approved'     => 'success',
        'rejected'     => 'danger',
        'disbursed'    => 'primary',
    ];
    $st    = $app['status'];
    $badge = $statusBadge[$st] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $st));
    ?>
    <div class="card border-0 shadow-sm">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="fw-bold mb-0"><?= sanitize($app['first_name']) ?> <?= sanitize($app['last_name']) ?></h5>
                <p class="text-muted small mb-0">
                    Ref: <strong><?= sanitize($app['reference_number']) ?></strong>
                    &nbsp;&middot;&nbsp;
                    Submitted: <?= date('d M Y', strtotime($app['submitted_at'])) ?>
                </p>
            </div>
            <span class="badge bg-<?= $badge ?> fs-6 px-3 py-2"><?= sanitize($label) ?></span>
        </div>

        <div class="mb-3">
            <span class="text-muted small">Loan Amount:</span>
            <strong class="ms-1"><?= formatCurrency((float) $app['loan_amount']) ?></strong>
        </div>

        <?php if ($st === 'rejected'): ?>
        <div class="alert alert-danger mb-0">
            <i class="bi bi-x-circle me-2"></i>
            Unfortunately, your application was not approved at this time.
            If you have questions, please <a href="contact.php" class="alert-link">contact us</a>.
        </div>

        <?php else: ?>
        <!-- Status timeline -->
        <div class="mt-3">
            <?php
            $currentIdx = array_search($st, $statusOrder);
            if ($currentIdx === false) $currentIdx = 0;
            $lastKey = array_key_last($timeline);
            foreach ($timeline as $key => $step):
                $stepIdx = array_search($key, $statusOrder);
                $isDone  = $stepIdx <= $currentIdx;
                $isNow   = $key === $st;
            ?>
            <div class="d-flex align-items-center gap-3 mb-1">
                <div class="timeline-dot <?= $isDone ? 'done' : '' ?> <?= $isNow ? 'current' : '' ?>">
                    <i class="bi <?= $step['icon'] ?>"></i>
                </div>
                <div>
                    <p class="mb-0 fw-semibold <?= $isNow ? 'text-success' : ($isDone ? '' : 'text-muted') ?>">
                        <?= sanitize($step['label']) ?>
                        <?php if ($isNow): ?>
                            <span class="badge bg-success ms-2" style="font-size:.75rem;">Current</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php if ($key !== $lastKey): ?>
            <div class="timeline-line <?= $isDone ? 'done' : '' ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
    </div>
    <?php endif; ?>

</div>
</div>
</div>
</section>

<style>
.timeline-dot {
    width: 44px; height: 44px; border-radius: 50%; background: #dee2e6;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem; color: #6c757d; flex-shrink: 0;
    transition: background .2s;
}
.timeline-dot.done    { background: #2E7D32; color: #fff; }
.timeline-dot.current { background: #2E7D32; color: #fff; box-shadow: 0 0 0 5px #c8e6c9; }
.timeline-line        { width: 3px; height: 28px; background: #dee2e6; margin-left: 20px; margin-bottom: 4px; }
.timeline-line.done   { background: #2E7D32; }
</style>

<?php include 'includes/footer.php'; ?>
