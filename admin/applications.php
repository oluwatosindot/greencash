<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = 'Applications';

// Filters
$statusFilter = trim($_GET['status'] ?? '');
$qFilter      = trim($_GET['q']      ?? '');

$validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'disbursed'];
$where  = [];
$params = [];

if (in_array($statusFilter, $validStatuses, true)) {
    $where[]  = 'status = ?';
    $params[] = $statusFilter;
}
if ($qFilter !== '') {
    $where[]  = '(reference_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR id_number LIKE ? OR email LIKE ?)';
    $q = '%' . $qFilter . '%';
    array_push($params, $q, $q, $q, $q, $q);
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Counts per status (for summary cards)
$counts = $pdo->query(
    "SELECT status, COUNT(*) AS n FROM salary_advance_applications GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$total = $pdo->query("SELECT COUNT(*) FROM salary_advance_applications")->fetchColumn();

// Applications list
$stmt = $pdo->prepare("
    SELECT id, reference_number, first_name, last_name, email, phone, id_number,
           loan_amount, status, submitted_at
      FROM salary_advance_applications
    {$whereSql}
    ORDER BY submitted_at DESC
    LIMIT 200
");
$stmt->execute($params);
$apps = $stmt->fetchAll();

include '../includes/admin-header.php';
?>

<!-- Status summary cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total',       $total,                            '#0c1410',  'bi-collection'],
        ['Pending',     $counts['pending']      ?? 0,      '#f4c020', 'bi-clock'],
        ['Under review',$counts['under_review'] ?? 0,      '#6c757d', 'bi-eye'],
        ['Approved',    $counts['approved']     ?? 0,      '#1aa636', 'bi-check2-circle'],
        ['Disbursed',   $counts['disbursed']    ?? 0,      '#0f7a26', 'bi-cash'],
        ['Rejected',    $counts['rejected']     ?? 0,      '#d33',    'bi-x-circle'],
    ];
    foreach ($cards as [$label, $count, $color, $icon]):
    ?>
    <div class="col-md-4 col-xl-2">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:<?= $color ?>22;color:<?= $color ?>;display:grid;place-items:center;font-size:20px;">
                    <i class="bi <?= $icon ?>"></i>
                </div>
                <div>
                    <div class="text-muted small text-uppercase" style="letter-spacing:.04em;font-size:.7rem;font-weight:600"><?= $label ?></div>
                    <div style="font-weight:700;font-size:1.4rem;line-height:1"><?= (int) $count ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Search ref / name / SA ID / email..." value="<?= sanitize($qFilter) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    <?php foreach ($validStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $s)) ?> (<?= (int) ($counts[$s] ?? 0) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= APP_URL ?>/admin/applications.php" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Applications table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>
            <?= count($apps) ?>
            <?= count($apps) === 1 ? 'application' : 'applications' ?>
            <?php if ($qFilter !== '' || $statusFilter !== ''): ?>
            <span class="text-muted small">(filtered)</span>
            <?php endif; ?>
        </span>
        <small class="text-muted">Showing newest first, max 200</small>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr style="background:#f6f8f4">
                    <th style="padding-left:1.25rem">Reference</th>
                    <th>Applicant</th>
                    <th>SA ID</th>
                    <th>Contact</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th style="padding-right:1.25rem"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($apps)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.4"></i>
                        No applications match those filters.
                    </td>
                </tr>
            <?php else: foreach ($apps as $a): ?>
                <tr>
                    <td style="padding-left:1.25rem">
                        <code style="color:var(--gc-green-deep);font-weight:600"><?= sanitize($a['reference_number']) ?></code>
                    </td>
                    <td>
                        <div style="font-weight:600"><?= sanitize($a['first_name'] . ' ' . $a['last_name']) ?></div>
                        <div class="text-muted small"><?= sanitize($a['email']) ?></div>
                    </td>
                    <td><code><?= sanitize($a['id_number']) ?></code></td>
                    <td>
                        <a href="tel:<?= sanitize($a['phone']) ?>" class="text-decoration-none">
                            <?= sanitize($a['phone']) ?>
                        </a>
                    </td>
                    <td class="text-end" style="font-weight:600"><?= formatCurrency((float) $a['loan_amount']) ?></td>
                    <td>
                        <span class="badge bg-<?= sanitize($a['status']) ?>">
                            <?= ucfirst(str_replace('_', ' ', sanitize($a['status']))) ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= date('j M Y', strtotime($a['submitted_at'])) ?><br>
                        <?= date('H:i', strtotime($a['submitted_at'])) ?>
                    </td>
                    <td style="padding-right:1.25rem">
                        <a href="<?= APP_URL ?>/admin/application.php?id=<?= (int) $a['id'] ?>"
                           class="btn btn-sm btn-outline-secondary">
                            View <i class="bi bi-arrow-right"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
