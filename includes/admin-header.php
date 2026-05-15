<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); } echo $_SESSION['csrf_token']; ?>">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . APP_NAME . ' Admin' : APP_NAME . ' Admin' ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gc-primary: #2E7D32;
            --gc-primary-light: #66BB6A;
            --gc-primary-dark: #1B5E20;
            --gc-sidebar-bg: #1B5E20;
            --gc-sidebar-width: 240px;
        }
        body { font-family: 'Inter', sans-serif; background: #f4f6f8; }
        /* Sidebar */
        #sidebar {
            width: var(--gc-sidebar-width);
            min-height: 100vh;
            background: var(--gc-sidebar-bg);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            overflow-y: auto;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.15rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: block;
            text-decoration: none;
        }
        #sidebar .nav-link {
            color: rgba(255,255,255,0.75);
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0;
            transition: background 0.15s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
        #sidebar .nav-section {
            padding: 0.75rem 1rem 0.25rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.4);
        }
        /* Main content */
        #main-content {
            margin-left: var(--gc-sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        /* Top bar */
        #topbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        #topbar .page-heading { font-weight: 600; font-size: 1.05rem; margin: 0; }
        .content-area { padding: 1.5rem; flex: 1; }
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
