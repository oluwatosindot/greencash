<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . APP_NAME : APP_NAME ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- App styles -->
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        :root {
            --gc-primary: #2E7D32;
            --gc-primary-light: #66BB6A;
            --gc-primary-dark: #1B5E20;
            --gc-white: #ffffff;
            --gc-text: #212529;
            --gc-muted: #6c757d;
            --gc-border: #dee2e6;
            --gc-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        body { font-family: 'Inter', sans-serif; color: var(--gc-text); }
        .navbar-brand { font-weight: 700; font-size: 1.4rem; color: var(--gc-white) !important; }
        .navbar { background-color: var(--gc-primary) !important; }
        .navbar .nav-link { color: rgba(255,255,255,0.85) !important; font-weight: 500; }
        .navbar .nav-link:hover { color: var(--gc-white) !important; }
        .btn-gc { background-color: var(--gc-primary); color: #fff; border: none; }
        .btn-gc:hover { background-color: var(--gc-primary-dark); color: #fff; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="<?= APP_URL ?>">
            <i class="bi bi-cash-stack me-1"></i><?= APP_NAME ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon" style="filter:invert(1)"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/apply.php">Apply Now</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/track-application.php">Track Application</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/contact.php">Contact</a></li>
                <li class="nav-item ms-2">
                    <a class="btn btn-light btn-sm fw-semibold" href="<?= APP_URL ?>/broker-portal-login.php">Broker Login</a>
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
