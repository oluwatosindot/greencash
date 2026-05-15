# Pattern: Admin Panel Pages

## Standard page structure

```php
<?php
require_once '../includes/config.php';
requireAdmin();

$page_title = 'Applications';

// --- Handle POST actions (status updates, etc.) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    // ... action logic ...
}

// --- Fetch data ---
$status = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$sql    = "SELECT * FROM salary_advance_applications WHERE 1=1";
$params = [];

if ($status) {
    $sql    .= " AND status = ?";
    $params[] = $status;
}
if ($search) {
    $sql    .= " AND (first_name LIKE ? OR last_name LIKE ? OR reference_number LIKE ? OR id_number LIKE ?)";
    $s       = '%' . $search . '%';
    $params  = array_merge($params, [$s, $s, $s, $s]);
}

$sql .= " ORDER BY submitted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

include '../includes/admin-header.php';
?>

<!-- Page content with Bootstrap 5 -->
<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between align-items-center py-3 mb-3 border-bottom">
                <h1 class="h2">Applications</h1>
                <a href="ajax/export-applications.php" class="btn btn-outline-success btn-sm">
                    Export CSV
                </a>
            </div>

            <!-- Flash message -->
            <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Filters -->
            <form method="GET" class="row g-2 mb-3">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="under_review" <?= $status === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="disbursed" <?= $status === 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name, ref, ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="applications.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Reference</th>
                            <th>Name</th>
                            <th>Amount</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($app['reference_number']) ?></code></td>
                            <td><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></td>
                            <td><?= formatCurrency($app['loan_amount']) ?></td>
                            <td>
                                <span class="badge <?= $app['source'] === 'broker' ? 'bg-info' : 'bg-secondary' ?>">
                                    <?= ucfirst($app['source']) ?>
                                </span>
                            </td>
                            <td><?= statusBadge($app['status']) ?></td>
                            <td><?= date('d M Y', strtotime($app['submitted_at'])) ?></td>
                            <td>
                                <a href="application-details.php?id=<?= $app['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/admin-footer.php'; ?>
```

---

## statusBadge() helper (add to config.php)

```php
function statusBadge(string $status): string {
    $badges = [
        'pending'      => 'bg-warning text-dark',
        'under_review' => 'bg-info text-dark',
        'approved'     => 'bg-success',
        'rejected'     => 'bg-danger',
        'disbursed'    => 'bg-primary',
    ];
    $class = $badges[$status] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}
```

---

## admin/includes/admin-header.php structure

```html
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> — Green Cash Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-gc-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/admin/index.php">
            <span style="color: #66BB6A;">Green</span> Cash Admin
        </a>
        <div class="d-flex align-items-center gap-3">
            <!-- Notifications bell -->
            <a href="/admin/notifications.php" class="text-white position-relative">
                <i class="bi bi-bell fs-5"></i>
            </a>
            <!-- Dark mode toggle -->
            <button class="btn btn-sm btn-outline-light" id="themeToggle">
                <i class="bi bi-moon"></i>
            </button>
            <!-- User -->
            <span class="text-white"><?= htmlspecialchars($_SESSION['admin_name'] ?? '') ?></span>
            <a href="/admin/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid" style="padding-top: 70px;">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
                           href="/admin/index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/applications.php">
                            <i class="bi bi-file-earmark-text"></i> Applications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/brokers.php">
                            <i class="bi bi-people"></i> Brokers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/reports.php">
                            <i class="bi bi-bar-chart"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/audit-log.php">
                            <i class="bi bi-shield-check"></i> Audit Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/users.php">
                            <i class="bi bi-person-gear"></i> Admin Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/notifications.php">
                            <i class="bi bi-bell"></i> Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/settings.php">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Main content starts here (see page template above) -->
```

---

## admin/ajax/update-status.php — AJAX status update

```php
<?php
require_once '../../includes/config.php';
requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

verifyCsrf();

$appId  = (int)($_POST['application_id'] ?? 0);
$status = sanitize($_POST['status'] ?? '');
$notes  = sanitize($_POST['notes'] ?? '');

$allowed = ['pending','under_review','approved','rejected','disbursed'];
if (!in_array($status, $allowed)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid status']));
}

// Fetch application
$stmt = $pdo->prepare("SELECT * FROM salary_advance_applications WHERE id = ? LIMIT 1");
$stmt->execute([$appId]);
$app = $stmt->fetch();

if (!$app) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'message' => 'Application not found']));
}

// Update status
$pdo->prepare(
    "UPDATE salary_advance_applications SET status = ?, notes = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?"
)->execute([$status, $notes, $_SESSION['admin_id'], $appId]);

// Send email notification
sendStatusChangeEmail($app, $status);

// Log action
logAdminAction($pdo, $_SESSION['admin_id'], 'update_application_status',
    "Application #{$appId} ({$app['reference_number']}) status changed to {$status}");

// If broker-submitted, create broker notification
if ($app['broker_id']) {
    $pdo->prepare(
        "INSERT INTO broker_notifications (broker_id, title, message, application_id)
         VALUES (?, ?, ?, ?)"
    )->execute([
        $app['broker_id'],
        'Application Status Update',
        "Application {$app['reference_number']} for {$app['first_name']} {$app['last_name']} is now: " . ucfirst(str_replace('_', ' ', $status)),
        $appId
    ]);
}

echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
```
