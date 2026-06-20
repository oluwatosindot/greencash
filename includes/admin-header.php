<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } echo $_SESSION['csrf_token']; ?>">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . APP_NAME . ' Admin' : APP_NAME . ' Admin' ?></title>
    <!-- Bootstrap 5 (SRI-pinned) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <!-- Bootstrap Icons (SRI-pinned) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+"
          crossorigin="anonymous">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gc-dark:        #121C14;
            --gc-dark-soft:   #1a241d;
            --gc-green:       #1aa636;
            --gc-green-deep:  #0f7a26;
            --gc-yellow:      #f4c020;
            --gc-paper:       #f6f8f4;
            --gc-line:        #e2e8de;
            --gc-muted:       #5e6b62;
            --gc-sidebar-width: 240px;
        }
        body { font-family: 'Inter', sans-serif; background: var(--gc-paper); color: #0c1410; }
        /* Sidebar — dark, matching the public nav */
        #sidebar {
            width: var(--gc-sidebar-width);
            min-height: 100vh;
            background: var(--gc-dark);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid rgba(255,255,255,0.07);
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.15rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: block;
            text-decoration: none;
        }
        #sidebar .sidebar-brand .accent { color: var(--gc-yellow); }
        #sidebar .nav-link {
            color: rgba(255,255,255,0.72);
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-radius: 0;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        #sidebar .nav-link:hover {
            background: rgba(244,192,32,0.08);
            color: #fff;
        }
        #sidebar .nav-link.active {
            background: rgba(244,192,32,0.14);
            color: var(--gc-yellow);
            border-left-color: var(--gc-yellow);
        }
        #sidebar .nav-link i { width: 18px; text-align: center; }
        #sidebar .nav-section {
            padding: 0.85rem 1rem 0.3rem;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.35);
            font-weight: 600;
        }
        /* Main content */
        #main-content {
            margin-left: var(--gc-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Top bar — dark to unify with the sidebar */
        #topbar {
            background: var(--gc-dark);
            border-bottom: 1px solid rgba(255,255,255,0.07);
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        #topbar .page-heading { font-weight: 600; font-size: 1.1rem; margin: 0; color: #fff; }
        #topbar .who { color: rgba(255,255,255,0.7); font-size: 0.85rem; }
        #topbar .btn-outline-danger {
            color: #ffb3b3;
            border-color: rgba(255,255,255,0.18);
            background: transparent;
        }
        #topbar .btn-outline-danger:hover {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border-color: rgba(255,255,255,0.35);
        }
        .content-area { padding: 1.75rem 1.5rem; flex: 1; }
        /* Bootstrap-overrides that align with the brand */
        .btn-primary, .btn-success {
            background: var(--gc-green-deep);
            border-color: var(--gc-green-deep);
        }
        .btn-primary:hover, .btn-success:hover {
            background: var(--gc-green);
            border-color: var(--gc-green);
        }
        a { color: var(--gc-green-deep); }
        a:hover { color: var(--gc-green); }
        .card { border: 1px solid var(--gc-line); }
        .card-header { background: #fff; border-bottom-color: var(--gc-line); font-weight: 600; }
        .table-hover tbody tr:hover { background: rgba(244,192,32,0.06); }
        .badge.bg-pending     { background: #f4c020 !important; color: #0c1410; }
        .badge.bg-approved    { background: var(--gc-green) !important; color: #fff; }
        .badge.bg-rejected    { background: #d33 !important; color: #fff; }
        .badge.bg-under_review{ background: #6c757d !important; color: #fff; }
        .badge.bg-disbursed   { background: var(--gc-green-deep) !important; color: #fff; }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav id="sidebar">
    <a class="sidebar-brand" href="<?= APP_URL ?>/admin/index.php">
        <i class="bi bi-cash-stack me-2"></i><?= APP_NAME ?>
    </a>

    <div class="nav-section">Main</div>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/index.php">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section">Loans</div>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'applications.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/applications.php">
        <i class="bi bi-file-earmark-text"></i> Applications
    </a>

    <div class="nav-section">People</div>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'brokers.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/brokers.php">
        <i class="bi bi-people"></i> Brokers
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/users.php">
        <i class="bi bi-person-gear"></i> Admin Users
    </a>

    <div class="nav-section">Reports</div>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/reports.php">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'audit-log.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/audit-log.php">
        <i class="bi bi-clock-history"></i> Audit Log
    </a>

    <div class="nav-section">System</div>
    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/admin/settings.php">
        <i class="bi bi-gear"></i> Settings
    </a>
    <a class="nav-link" href="<?= APP_URL ?>/admin/logout.php">
        <i class="bi bi-box-arrow-left"></i> Logout
    </a>
</nav>

<!-- Main content wrapper -->
<div id="main-content">
    <!-- Top bar -->
    <div id="topbar">
        <h1 class="page-heading"><?= isset($page_title) ? sanitize($page_title) : '' ?></h1>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">
                <i class="bi bi-person-circle me-1"></i>
                <?= isset($_SESSION['admin_name']) ? sanitize($_SESSION['admin_name']) : 'Admin' ?>
            </span>
            <a href="<?= APP_URL ?>/admin/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
        </div>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="px-4 pt-3">
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= sanitize($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-area">
