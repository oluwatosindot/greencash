<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <title><?= isset($page_title) ? sanitize($page_title) . ' - Admin' : 'Admin' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gc-green: #2E7D32;
        }
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #1b5e20;
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar h6 {
            padding: 0 20px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .sidebar a {
            display: block;
            padding: 10px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar a:hover,
        .sidebar a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .content-wrapper {
            margin-left: 250px;
            flex: 1;
        }
        .top-bar {
            background-color: white;
            border-bottom: 1px solid #ddd;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar h5 {
            margin: 0;
            color: var(--gc-green);
        }
        .top-bar a {
            color: #666;
            text-decoration: none;
        }
        .top-bar a:hover {
            color: var(--gc-green);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h6><?= APP_NAME ?></h6>
        <a href="<?= APP_URL ?>/admin/dashboard.php">Dashboard</a>
        <a href="<?= APP_URL ?>/admin/applications.php">Applications</a>
        <a href="<?= APP_URL ?>/admin/brokers.php">Brokers</a>
        <a href="<?= APP_URL ?>/admin/users.php">Users</a>
        <a href="<?= APP_URL ?>/admin/reports.php">Reports</a>
        <a href="<?= APP_URL ?>/admin/audit-log.php">Audit Log</a>
        <a href="<?= APP_URL ?>/admin/settings.php">Settings</a>
    </div>

    <div class="content-wrapper">
        <div class="top-bar">
            <h5><?= isset($page_title) ? sanitize($page_title) : 'Dashboard' ?></h5>
            <div>
                <span class="me-3">Admin <?= htmlspecialchars($_SESSION['admin_name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                <a href="<?= APP_URL ?>/admin/logout.php">Logout</a>
            </div>
        </div>

        <?php if ($flash = getFlash()): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show m-3" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="p-4">
