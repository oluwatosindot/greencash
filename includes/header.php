<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? sanitize($page_title) . ' — ' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') : htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
</head>
<body>

<header>
    <div class="wrap nav">
        <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="brand">
            <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/logo01.jpeg" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>" style="height:42px">
        </a>
        <nav class="navlinks" id="navlinks">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Loans</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php">Track</a>
        </nav>
        <div class="nav-cta">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/login.php" class="btn btn-ghost btn-ghost--on-dark">Sign in</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#apply" class="btn btn-yellow">Apply now</a>
            <button class="burger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
        </div>
    </div>
</header>

<?php $flash = getFlash(); if ($flash):
    $type = in_array($flash['type'], ['success','error','warning','info'], true) ? $flash['type'] : 'info';
?>
<div class="wrap" style="padding-top:18px">
    <div class="flash flash--<?= $type ?>">
        <span><?= sanitize($flash['message']) ?></span>
        <button class="x" type="button" aria-label="Dismiss">×</button>
    </div>
</div>
<?php endif; ?>

<main>
