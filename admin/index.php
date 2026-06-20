<?php
require_once '../includes/config.php';
requireAdmin();
$page_title = 'Dashboard';

// Headline stats
$totalApps     = (int) $pdo->query("SELECT COUNT(*) FROM salary_advance_applications")->fetchColumn();
$pendingApps   = (int) $pdo->query("SELECT COUNT(*) FROM salary_advance_applications WHERE status IN ('pending','under_review')")->fetchColumn();
$approvedApps  = (int) $pdo->query("SELECT COUNT(*) FROM salary_advance_applications WHERE status IN ('approved','disbursed')")->fetchColumn();
$totalLoanValue= (float) $pdo->query("SELECT COALESCE(SUM(loan_amount), 0) FROM salary_advance_applications WHERE status IN ('approved','disbursed')")->fetchColumn();
$apps7d        = (int) $pdo->query("SELECT COUNT(*) FROM salary_advance_applications WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$contactMsgs   = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Recent applications
$recent = $pdo->query(
    "SELECT id, reference_number, first_name, last_name, loan_amount, status, submitted_at
       FROM salary_advance_applications
      ORDER BY submitted_at DESC
      LIMIT 5"
)->fetchAll();

include '../includes/admin-header.php';
?>

<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['Total applications', $totalApps,                    'bi-collection',     'var(--gc-green-deep)'],
        ['Awaiting review',    $pendingApps,                  'bi-hourglass-split','#f4c020'],
        ['Approved/disbursed', $approvedApps,                 'bi-check2-circle',  'var(--gc-green)'],
        ['Approved value',     formatCurrency($totalLoanValue), 'bi-cash-stack',   'var(--gc-green-deep)'],
        ['This week',          $apps7d,                       'bi-calendar-week',  '#5e6b62'],
        ['Contact messages 7d', $contactMsgs,                 'bi-envelope',       '#5e6b62'],
    ];
    foreach ($stats as [$label, $value, $icon, $color]):
    ?>
    <div class="col-md-6 col-xl-2">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:36px;height:36px;border-radius:8px;background:<?= $color ?>22;color:<?= $color ?>;display:grid;place-items:center">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div class="text-muted small text-uppercase" style="font-size:.65rem;letter-spacing:.06em;font-weight:600"><?= $label ?></div>
                </div>
                <div style="font-size:1.4rem;font-weight:700;line-height:1.1"><?= is_string($value) ? sanitize($value) : (int) $value ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history"></i> Recent applications</span>
        <a href="<?= APP_URL ?>/admin/applications.php" class="btn btn-sm btn-outline-secondary">View all →</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr style="background:#f6f8f4">
                    <th style="padding-left:1.25rem">Reference</th>
                    <th>Applicant</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="padding-right:1.25rem"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recent)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
                        No applications yet.
                    </td>
                </tr>
            <?php else: foreach ($recent as $a): ?>
                <tr>
                    <td style="padding-left:1.25rem">
                        <code style="color:var(--gc-green-deep);font-weight:600"><?= sanitize($a['reference_number']) ?></code>
                    </td>
                    <td><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?></td>
                    <td class="text-end" style="font-weight:600"><?= formatCurrency((float) $a['loan_amount']) ?></td>
                    <td>
                        <span class="badge bg-<?= sanitize($a['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', sanitize($a['status']))) ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= date('j M, H:i', strtotime($a['submitted_at'])) ?>
                    </td>
                    <td style="padding-right:1.25rem">
                        <a href="<?= APP_URL ?>/admin/application.php?id=<?= (int) $a['id'] ?>"
                           class="btn btn-sm btn-outline-secondary">View</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
