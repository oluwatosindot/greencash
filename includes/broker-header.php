<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . APP_NAME . ' Broker Portal' : APP_NAME . ' Broker Portal' ?></title>
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
        }
        body { font-family: 'Inter', sans-serif; }
        .navbar { background-color: var(--gc-primary) !important; }
        .navbar-brand { font-weight: 700; color: #fff !important; }
        .navbar .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 500; }
        .navbar .nav-link:hover, .navbar .nav-link.active { color: #fff !important; }
        .badge-notify {
            position: absolute;
            top: 4px; right: 4px;
            background: #f44336;
            color: #fff;
            border-radius: 50%;
            font-size: 0.65rem;
            width: 16px; height: 16px;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>/broker-portal/index.php">
            <i class="bi bi-cash-stack me-1"></i><?= APP_NAME ?> <span class="fw-normal opacity-75">Broker Portal</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#brokerNav">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="brokerNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/broker-portal/index.php">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'apply.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/broker-portal/apply.php">
                        <i class="bi bi-plus-circle me-1"></i>Submit Application
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'applications.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/broker-portal/applications.php">
                        <i class="bi bi-file-earmark-text me-1"></i>My Applications
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/broker-portal/profile.php">
                        <i class="bi bi-person me-1"></i>Profile
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- Notifications bell (placeholder) -->
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="#" id="notif-bell" title="Notifications">
                        <i class="bi bi-bell fs-5"></i>
                        <span class="badge-notify d-none" id="notif-count">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= APP_URL ?>/broker-portal/logout.php">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : sanitize($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<main class="container py-4">
