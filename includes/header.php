<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- App styles — defines all CSS custom properties and component styles -->
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
    <style>
        /* Navbar-specific styles not in style.css (layout-shell concerns) */
        .navbar-brand { font-weight: 700; font-size: 1.4rem; color: #fff !important; }
        .navbar { background-color: #2E7D32 !important; }
        .navbar .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 500; }
        .navbar .nav-link:hover { color: #fff !important; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">
            <i class="bi bi-cash-stack me-1"></i><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/apply.php">Apply Now</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php">Track Application</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/contact.php">Contact</a></li>
                <li class="nav-item ms-2">
                    <a class="btn btn-light btn-sm fw-semibold" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/broker-portal-login.php">Broker Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
    <?php
    $allowedAlertTypes = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
    $alertType = in_array($flash['type'], $allowedAlertTypes, true) ? $flash['type'] : 'info';
    if ($flash['type'] === 'error') $alertType = 'danger';
    ?>
    <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<main class="container py-4">
