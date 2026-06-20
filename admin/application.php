<?php
require_once '../includes/config.php';
requireAdmin();

$appId = (int) ($_GET['id'] ?? 0);
if ($appId <= 0) {
    setFlash('error', 'Invalid application ID.');
    redirect('/admin/applications.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'disbursed'];
    if (in_array($newStatus, $validStatuses, true)) {
        $pdo->prepare("UPDATE salary_advance_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $_SESSION['admin_id'] ?? null, $appId]);
        setFlash('success', 'Status updated to ' . str_replace('_', ' ', $newStatus) . '.');
    } else {
        setFlash('error', 'Invalid status value.');
    }
    redirect('/admin/application.php?id=' . $appId);
}

// Load application
$stmt = $pdo->prepare("SELECT * FROM salary_advance_applications WHERE id = ? LIMIT 1");
$stmt->execute([$appId]);
$app = $stmt->fetch();
if (!$app) {
    setFlash('error', 'Application not found.');
    redirect('/admin/applications.php');
}

// Load documents
$docs = $pdo->prepare("SELECT * FROM application_documents WHERE application_id = ? ORDER BY id");
$docs->execute([$appId]);
$documents = $docs->fetchAll();

$page_title = 'Application ' . $app['reference_number'];

$totalExpenses = (float) ($app['rent'] + $app['food'] + $app['transport'] + $app['other_expenses']);
$disposable    = (float) $app['salary_amount'] - $totalExpenses;
$loanAffordRatio = $disposable > 0 ? ((float) $app['loan_amount'] / $disposable) : INF;

$durationLabels = [
    'less_3m' => 'Less than 3 months',
    '3_6m'    => '3 – 6 months',
    '6_12m'   => '6 – 12 months',
    '1_2y'    => '1 – 2 years',
    '2_5y'    => '2 – 5 years',
    '5y_plus' => '5+ years',
];
$statusLabels = [
    'employed'      => 'Permanent / full-time',
    'contract'      => 'Contract',
    'self_employed' => 'Self-employed',
];

include '../includes/admin-header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= APP_URL ?>/admin/applications.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to list
    </a>
    <h2 class="mb-0" style="font-size:1.4rem;font-weight:700">
        <code style="color:var(--gc-green-deep)"><?= sanitize($app['reference_number']) ?></code>
        <span class="badge bg-<?= sanitize($app['status']) ?> ms-2">
            <?= ucfirst(str_replace('_', ' ', sanitize($app['status']))) ?>
        </span>
    </h2>
    <div class="ms-auto small text-muted">
        Submitted <?= date('j M Y, H:i', strtotime($app['submitted_at'])) ?>
    </div>
</div>

<div class="row g-3">
    <!-- Left column -->
    <div class="col-lg-8">
        <!-- Applicant card -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person"></i> Applicant</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted small">Full name</dt>
                    <dd class="col-sm-8" style="font-weight:600"><?= sanitize(trim($app['first_name'] . ' ' . ($app['other_names'] ?? '') . ' ' . $app['last_name'])) ?></dd>

                    <dt class="col-sm-4 text-muted small">SA ID number</dt>
                    <dd class="col-sm-8"><code><?= sanitize($app['id_number']) ?></code></dd>

                    <dt class="col-sm-4 text-muted small">Email</dt>
                    <dd class="col-sm-8"><a href="mailto:<?= sanitize($app['email']) ?>"><?= sanitize($app['email']) ?></a></dd>

                    <dt class="col-sm-4 text-muted small">Mobile</dt>
                    <dd class="col-sm-8"><a href="tel:<?= sanitize($app['phone']) ?>"><?= sanitize($app['phone']) ?></a></dd>

                    <dt class="col-sm-4 text-muted small">Address</dt>
                    <dd class="col-sm-8">
                        <?= sanitize($app['address']) ?><br>
                        <?= sanitize(trim($app['city'] . ', ' . $app['province'] . ' ' . $app['zip_code'])) ?>
                    </dd>
                </dl>
            </div>
        </div>

        <!-- Employment card -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-briefcase"></i> Employment</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted small">Status</dt>
                    <dd class="col-sm-8"><?= sanitize($statusLabels[$app['employment_status']] ?? $app['employment_status']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Employer</dt>
                    <dd class="col-sm-8" style="font-weight:600"><?= sanitize($app['employer_name']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Job title</dt>
                    <dd class="col-sm-8"><?= sanitize($app['job_title']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Tenure</dt>
                    <dd class="col-sm-8"><?= sanitize($durationLabels[$app['employment_duration']] ?? $app['employment_duration']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Employer contact</dt>
                    <dd class="col-sm-8"><?= sanitize($app['employer_contact']) ?></dd>
                </dl>
            </div>
        </div>

        <!-- Affordability card -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-calculator"></i> Affordability</div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Loan requested</div>
                        <div style="font-size:1.6rem;font-weight:700;color:var(--gc-green-deep)"><?= formatCurrency((float) $app['loan_amount']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Net salary</div>
                        <div style="font-size:1.6rem;font-weight:700"><?= formatCurrency((float) $app['salary_amount']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small text-uppercase" style="font-size:.7rem;letter-spacing:.05em">Disposable</div>
                        <div style="font-size:1.6rem;font-weight:700;color:<?= $disposable >= (float) $app['loan_amount'] * 1.15 ? 'var(--gc-green)' : '#d33' ?>">
                            <?= formatCurrency($disposable) ?>
                        </div>
                    </div>
                </div>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted small">Next payday</dt>
                    <dd class="col-sm-8"><?= date('j F Y', strtotime($app['next_payday_date'])) ?></dd>

                    <dt class="col-sm-4 text-muted small">Repayment date</dt>
                    <dd class="col-sm-8"><?= $app['repayment_date'] ? date('j F Y', strtotime($app['repayment_date'])) : '—' ?></dd>

                    <dt class="col-sm-4 text-muted small">Rent</dt>
                    <dd class="col-sm-8"><?= formatCurrency((float) $app['rent']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Food</dt>
                    <dd class="col-sm-8"><?= formatCurrency((float) $app['food']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Transport</dt>
                    <dd class="col-sm-8"><?= formatCurrency((float) $app['transport']) ?></dd>

                    <dt class="col-sm-4 text-muted small">Other expenses</dt>
                    <dd class="col-sm-8"><?= formatCurrency((float) $app['other_expenses']) ?></dd>

                    <dt class="col-sm-4 text-muted small" style="border-top:1px solid var(--gc-line);padding-top:.5rem"><strong>Total expenses</strong></dt>
                    <dd class="col-sm-8" style="border-top:1px solid var(--gc-line);padding-top:.5rem"><strong><?= formatCurrency($totalExpenses) ?></strong></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4">
        <!-- Status update -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-gear"></i> Manage status</div>
            <div class="card-body">
                <form method="post">
                    <?= csrfField() ?>
                    <select name="status" class="form-select mb-2">
                        <option value="pending"      <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="under_review" <?= $app['status'] === 'under_review' ? 'selected' : '' ?>>Under review</option>
                        <option value="approved"     <?= $app['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="disbursed"    <?= $app['status'] === 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                        <option value="rejected"     <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-primary w-100">Update status</button>
                </form>
            </div>
        </div>

        <!-- Documents -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-paperclip"></i> Uploaded documents</div>
            <div class="list-group list-group-flush">
            <?php if (empty($documents)): ?>
                <div class="list-group-item text-muted small">No documents on file.</div>
            <?php else: foreach ($documents as $d): ?>
                <a href="<?= APP_URL ?>/serve-document.php?file=<?= urlencode(basename($d['file_path'])) ?>"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                   target="_blank">
                    <i class="bi bi-file-earmark-pdf" style="color:var(--gc-green-deep);font-size:1.1rem"></i>
                    <div class="flex-grow-1 small">
                        <div style="font-weight:600"><?= sanitize(ucwords(str_replace('_', ' ', $d['document_type']))) ?></div>
                        <div class="text-muted" style="font-size:.78rem"><?= sanitize($d['file_name']) ?></div>
                    </div>
                    <i class="bi bi-download text-muted"></i>
                </a>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning"></i> Quick contact</div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                   href="mailto:<?= sanitize($app['email']) ?>?subject=Your%20GreenCash%20application%20<?= urlencode($app['reference_number']) ?>">
                    <i class="bi bi-envelope" style="color:var(--gc-green-deep)"></i>
                    Email applicant
                </a>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                   href="tel:<?= sanitize($app['phone']) ?>">
                    <i class="bi bi-telephone" style="color:var(--gc-green-deep)"></i>
                    Call <?= sanitize($app['phone']) ?>
                </a>
                <a class="list-group-item list-group-item-action d-flex align-items-center gap-2"
                   href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $app['phone']) ?>?text=Hi%20<?= urlencode($app['first_name']) ?>%2C%20regarding%20your%20application%20<?= urlencode($app['reference_number']) ?>"
                   target="_blank">
                    <i class="bi bi-whatsapp" style="color:#25D366"></i>
                    WhatsApp applicant
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
