<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' - ' . APP_NAME : APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <style>
        :root {
            --gc-green: #2E7D32;
            --gc-light-green: #4CAF50;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .navbar {
            background-color: var(--gc-green);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: color 0.3s;
        }
        .nav-link:hover {
            color: white !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= APP_URL ?>"><?= APP_NAME ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/apply.php">Apply Now</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/track.php">Track Application</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show m-3" role="alert">
            <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <main class="container py-4">
