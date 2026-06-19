<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Title — default tagline used when no page-specific title is set
    $effectiveTitle = isset($page_title) ? $page_title : 'Your Salary Boost';
    $effectiveDesc  = isset($page_description) ? $page_description : "Fast, fair salary-advance loans for salaried South Africans. Apply online in minutes — 24 hours, 100% online.";
    $assetBase = htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8');
    $canonical = $assetBase . ($_SERVER['SCRIPT_NAME'] ?? '/');
    $ogImage   = $assetBase . '/assets/img/favicon-512.png';
    ?>
    <title><?= sanitize($effectiveTitle) ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($effectiveDesc, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="theme-color" content="#121C14">

    <!-- Favicon set (cropped from the GreenCash shield) -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $assetBase ?>/assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= $assetBase ?>/assets/img/favicon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= $assetBase ?>/assets/img/favicon-512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $assetBase ?>/assets/img/apple-touch-icon.png">
    <link rel="manifest" href="<?= $assetBase ?>/manifest.json">

    <!-- Open Graph (Facebook, WhatsApp link previews) -->
    <meta property="og:site_name" content="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($effectiveTitle, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($effectiveDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:locale" content="en_ZA">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($effectiveTitle, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($effectiveDesc, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assetBase ?>/assets/css/style.css">
    <style>
        /* Critical preloader CSS — inlined so the loader works before style.css finishes loading */
        .preloader{position:fixed;inset:0;z-index:9999;background:#121C14;display:flex;align-items:center;justify-content:center;transition:opacity .4s ease}
        .preloader.fade-out{opacity:0;pointer-events:none}
        .preloader img{max-width:340px;width:62%;height:auto;animation:gc-preloader-pulse 1.6s ease-in-out infinite;filter:drop-shadow(0 0 32px rgba(26,166,54,.45))}
        @keyframes gc-preloader-pulse{0%,100%{transform:scale(.94);opacity:.78}50%{transform:scale(1.06);opacity:1}}
        @media (prefers-reduced-motion: reduce){.preloader img{animation:none}}
    </style>
</head>
<body>

<div class="preloader" id="gc-preloader" aria-hidden="true">
    <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/GreenCash_Logo_Dark.png" alt="">
</div>
<script>
// Hide preloader when the page is fully loaded. Minimum visible time so super-fast pages
// don't flash the loader for a single frame. Respects prefers-reduced-motion (no fade delay).
(function(){
    var p = document.getElementById('gc-preloader');
    if (!p) return;
    var t0 = Date.now();
    var minMs = 1500;
    function hide(){
        var wait = Math.max(0, minMs - (Date.now() - t0));
        setTimeout(function(){
            p.classList.add('fade-out');
            setTimeout(function(){ if (p.parentNode) p.parentNode.removeChild(p); }, 500);
        }, wait);
    }
    if (document.readyState === 'complete') hide();
    else window.addEventListener('load', hide);
})();
</script>

<header>
    <div class="wrap nav">
        <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>" class="brand">
            <img src="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/assets/img/GreenCash_Logo_Dark.png" alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
        </a>
        <nav class="navlinks" id="navlinks">
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#how">How it works</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#products">Loans</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#employers">For employers</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/#faq">FAQ</a>
            <a href="<?= htmlspecialchars(APP_URL, ENT_QUOTES, 'UTF-8') ?>/track-application.php">Track</a>
        </nav>
        <div class="nav-cta">
            <?php /* Sign in hidden until admin/customer portal is wired up */ ?>
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
